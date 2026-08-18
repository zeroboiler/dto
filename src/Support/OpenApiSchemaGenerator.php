<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\ValueObjects\Contracts\ValueObject as ValueObjectContract;

/**
 * Generate OpenAPI 3.0 schemas from DTO class definitions.
 *
 * @internal Not part of the public API for most consumers. Use the
 *           {@see \ZeroBoiler\DTO\Facades\DTO::schema()} facade method or
 *           the `zeroboiler:dto-schema` artisan command instead.
 *
 * Reads constructor parameters and validation attributes via reflection
 * to produce accurate property schemas with types, constraints, and
 * required-field lists. Supports nested DTOs (via `$ref` to component
 * schemas) and union types (via `oneOf`).
 *
 * @see \ZeroBoiler\DTO\DataTransferObject For the base DTO class
 * @see \ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand For the CLI command
 */
final class OpenApiSchemaGenerator
{
    /**
     * Generate an OpenAPI schema array for a DTO class.
     *
     * Use this for DTOs without nested DTO references. For DTOs with
     * nested DTOs, use {@see generateWithComponents()} instead.
     *
     * @param  class-string  $dtoClass
     * @return array<string, mixed>
     *
     * @throws \LogicException If the DTO contains nested DTO references
     * @throws \ReflectionException If the class does not exist
     */
    public static function generate(string $dtoClass): array
    {
        $components = [];
        $schema = self::generateInternal($dtoClass, $components);

        // If nested DTOs were detected, generate() produces schemas with
        // dangling $ref pointers — the component definitions are discarded.
        // Direct users to generateWithComponents() instead.
        if ($components !== []) {
            $componentNames = implode(', ', array_keys($components));

            throw new \LogicException(
                'DTO contains nested DTO references ('.$componentNames.'). '
                .'Use OpenApiSchemaGenerator::generateWithComponents() instead, '
                .'which returns both the schema and component definitions.'
            );
        }

        return $schema;
    }

    /**
     * Generate an OpenAPI schema with component schemas for nested DTOs.
     *
     * Returns ['schema' => [...], 'components' => ['schemas' => [...]]]
     * where components.schemas contains one entry per nested DTO.
     *
     * @param  class-string  $dtoClass
     * @return array{schema: array<string, mixed>, components: array{schemas: array<string, array<string, mixed>>}}
     *
     * @throws \ReflectionException If the class does not exist
     */
    public static function generateWithComponents(string $dtoClass): array
    {
        $components = [];
        $schema = self::generateInternal($dtoClass, $components);

        return ['schema' => $schema, 'components' => ['schemas' => $components]];
    }

    /**
     * Internal recursive schema generator.
     *
     * @param  class-string  $dtoClass
     * @param  array<string, array<string, mixed>>  $components  Collected component schemas (modified by reference)
     * @return array<string, mixed>
     */
    private static function generateInternal(string $dtoClass, array &$components): array
    {
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return ['type' => 'object', 'properties' => []];
        }

        $properties = [];
        $required = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            $propReflection = $reflection->hasProperty($name)
                ? new ReflectionProperty($dtoClass, $name)
                : null;

            // Skip hidden properties entirely
            if (self::hasAttribute($propReflection, Hidden::class)) {
                continue;
            }

            // Check for DefaultValue attribute early to determine if field is required
            $hasDefaultValueAttr = false;
            $defaultValue = null;

            if ($propReflection instanceof ReflectionProperty) {
                foreach ($propReflection->getAttributes() as $attr) {
                    if ($attr->getName() === DefaultValue::class) {
                        $instance = $attr->newInstance();
                        assert($instance instanceof DefaultValue);
                        $hasDefaultValueAttr = true;
                        $defaultValue = $instance->value;

                        break;
                    }
                }
            }

            $isRequired = ! $param->isDefaultValueAvailable() && ! $hasDefaultValueAttr && ! $type?->allowsNull();

            // Explicit #[Required] attribute forces the field into required list
            // even when the type allows null.
            if (! $isRequired && self::hasAttribute($propReflection, Required::class)) {
                $isRequired = true;
            }

            if ($isRequired) {
                $required[] = $name;
            }

            $propSchema = self::inferPropertySchema($type, $components);

            // Only mark as nullable if the type actually allows null.
            $typeAllowsNull = $type?->allowsNull() ?? true;
            if ($typeAllowsNull) {
                $propSchema['nullable'] = true;
            }

            if ($hasDefaultValueAttr) {
                $propSchema['default'] = $defaultValue;
            }

            // Enrich schema with validation attribute constraints
            if ($propReflection instanceof ReflectionProperty) {
                $propSchema = self::applyValidationAttributes($propReflection, $propSchema);
            }

            $properties[$name] = $propSchema;
        }

        $schema = [
            'type' => 'object',
            'properties' => (object) $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Infer a full property schema from a reflection type, handling nested DTOs and unions.
     *
     * @param  array<string, array<string, mixed>>  $components
     * @return array<string, mixed>
     */
    private static function inferPropertySchema(?\ReflectionType $type, array &$components): array
    {
        if (! $type instanceof \ReflectionType) {
            return ['type' => 'string'];
        }
        // Union types — produce oneOf schema (#75)
        if ($type instanceof \ReflectionUnionType) {
            return self::inferUnionSchema($type, $components);
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return ['type' => 'object'];
        }

        if (! $type instanceof \ReflectionNamedType) {
            return ['type' => 'string'];
        }

        $typeName = $type->getName();

        // Nested DTO support (#76) — detect DataTransferObject subclasses
        if (class_exists($typeName) && is_subclass_of($typeName, DataTransferObject::class)) {
            $componentName = self::componentName($typeName);

            // Avoid infinite recursion on self-references
            if (! isset($components[$componentName])) {
                $components[$componentName] = ['type' => 'object']; // placeholder
                $components[$componentName] = self::generateInternal($typeName, $components);
            }

            return ['$ref' => '#/components/schemas/'.$componentName];
        }

        // For other types (including arrays), delegate to inferType
        $inferredType = self::inferType($type);

        return ['type' => $inferredType];
    }

    /**
     * Infer schema for a union type, producing oneOf for multiple distinct types.
     *
     * @param  array<string, array<string, mixed>>  $components
     * @return array<string, mixed>
     */
    private static function inferUnionSchema(\ReflectionUnionType $type, array &$components): array
    {
        $schemas = [];

        foreach ($type->getTypes() as $subType) {
            if ($subType instanceof \ReflectionNamedType) {
                if ($subType->getName() === 'null') {
                    continue; // null is handled via 'nullable' property
                }

                $schemas[] = self::inferPropertySchema($subType, $components);
            } elseif ($subType instanceof \ReflectionUnionType) {
                $schemas[] = self::inferUnionSchema($subType, $components);
            }
        }

        // Deduplicate by comparing serialized form
        $unique = [];
        foreach ($schemas as $schema) {
            $key = json_encode($schema) ?: '';
            if (! array_key_exists($key, $unique)) {
                $unique[$key] = $schema;
            }
        }
        $schemas = array_values($unique);

        if (count($schemas) === 0) {
            return ['type' => 'string'];
        }

        if (count($schemas) === 1) {
            return $schemas[0];
        }

        // Multiple distinct types — use oneOf
        return ['oneOf' => $schemas];
    }

    /**
     * Generate a component schema name from a DTO class name.
     *
     * Extracts the short class name (without namespace) for use as
     * an OpenAPI component schema key. Uses string parsing instead
     * of reflection to avoid unnecessary class loading.
     *
     * @param  class-string  $className  The fully-qualified DTO class name
     * @return string The short class name (e.g. 'CreateUserDTO' from 'App\DTO\CreateUserDTO')
     */
    private static function componentName(string $className): string
    {
        $separator = strrpos($className, '\');

        return $separator === false ? $className : substr($className, $separator + 1);
    }

    /**
     * Enrich the OpenAPI property schema with constraints from validation attributes.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyValidationAttributes(ReflectionProperty $prop, array $propSchema): array
    {
        foreach ($prop->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            $propSchema = match (true) {
                $instance instanceof Email => self::applyFormat($propSchema, 'email'),
                $instance instanceof Url => self::applyFormat($propSchema, 'uri'),
                $instance instanceof Uuid => self::applyFormat($propSchema, 'uuid'),
                $instance instanceof Pattern => self::applyPatternAttribute($propSchema, $instance->regex),
                $instance instanceof Integer => self::applyTypeOverride($propSchema, 'integer'),
                $instance instanceof Numeric => self::applyTypeOverride($propSchema, 'number'),
                $instance instanceof Boolean => self::applyTypeOverride($propSchema, 'boolean'),
                $instance instanceof Date => self::applyDateFormat($propSchema, $instance->format),
                $instance instanceof In => self::applyInConstraint($propSchema, $instance->values),
                $instance instanceof EnumAttribute => self::applyEnumSchema($instance, $propSchema),
                $instance instanceof Max => self::applyMaxConstraint($instance->value, $propSchema),
                $instance instanceof Min => self::applyMinConstraint($instance->value, $propSchema),
                $instance instanceof Between => self::applyBetweenConstraint($instance, $propSchema),
                $instance instanceof StartsWith => self::applyStartsWithPattern($instance, $propSchema),
                $instance instanceof EndsWith => self::applyEndsWithPattern($instance, $propSchema),
                default => $propSchema,
            };
        }

        return $propSchema;
    }

    /**
     * Apply a format constraint to the property schema.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyFormat(array $propSchema, string $format): array
    {
        $propSchema['format'] = $format;

        return $propSchema;
    }

    /**
     * Apply a type override to the property schema.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyTypeOverride(array $propSchema, string $type): array
    {
        $propSchema['type'] = $type;

        return $propSchema;
    }

    /**
     * Apply a date format constraint to the property schema.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyDateFormat(array $propSchema, ?string $format): array
    {
        $propSchema['format'] = 'date';
        if ($format !== null) {
            $propSchema['pattern'] = $format;
        }

        return $propSchema;
    }

    /**
     * Apply a regex pattern constraint to the property schema.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyPatternAttribute(array $propSchema, string $regex): array
    {
        $propSchema['pattern'] = self::stripRegexDelimiters($regex);

        return $propSchema;
    }

    /**
     * Apply an enum constraint to the property schema.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyInConstraint(array $propSchema, array $values): array
    {
        $propSchema['enum'] = $values;

        return $propSchema;
    }

    /**
     * Determine whether the current schema type represents a numeric value.
     *
     * @param  array<string, mixed>  $propSchema
     */
    private static function isNumericType(array $propSchema): bool
    {
        return ($propSchema['type'] ?? null) === 'integer'
            || ($propSchema['type'] ?? null) === 'number';
    }

    /**
     * Strip leading and trailing regex delimiters from a pattern string.
     *
     * Handles common delimiters: /, #, ~, and |.
     *
     * @param  string  $regex  The regex pattern with delimiters
     * @return string The pattern without delimiters
     */
    private static function stripRegexDelimiters(string $regex): string
    {
        $delimiters = ['/', '#', '~', '|'];

        foreach ($delimiters as $delim) {
            if (str_starts_with($regex, $delim) && str_ends_with($regex, $delim)) {
                return substr($regex, 1, -1);
            }
        }

        // Fall back to stripping leading delimiters only (backward compat)
        return ltrim($regex, '/#~|');
    }

    /**
     * Apply a Min constraint as either minimum (numeric) or minLength (string).
     *
     * @param  int|float  $value  Constraint value (kept as-is for numeric types, cast to int for string lengths)
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyMinConstraint(int|float $value, array $propSchema): array
    {
        if (self::isNumericType($propSchema)) {
            $propSchema['minimum'] = $value;
        } else {
            $propSchema['minLength'] = (int) $value;
        }

        return $propSchema;
    }

    /**
     * Apply a Max constraint as either maximum (numeric) or maxLength (string).
     *
     * @param  int|float  $value  Constraint value (kept as-is for numeric types, cast to int for string lengths)
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyMaxConstraint(int|float $value, array $propSchema): array
    {
        if (self::isNumericType($propSchema)) {
            $propSchema['maximum'] = $value;
        } else {
            $propSchema['maxLength'] = (int) $value;
        }

        return $propSchema;
    }

    /**
     * Apply a Between constraint (both min and max) with type-aware mapping.
     *
     * Preserves float values for numeric types (e.g. between 0.5 and 99.9)
     * and casts to int for string types (e.g. between 1 and 255 characters).
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyBetweenConstraint(Between $between, array $propSchema): array
    {
        $propSchema = self::applyMinConstraint($between->min, $propSchema);

        return self::applyMaxConstraint($between->max, $propSchema);
    }

    /**
     * Merge a pattern fragment into an existing pattern (if any) using a logical AND.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function mergePattern(string $fragment, array $propSchema): array
    {
        $existing = $propSchema['pattern'] ?? null;

        if ($existing !== null && is_string($existing)) {
            // Combine patterns using lookahead to preserve both constraints
            $propSchema['pattern'] = '(?='.ltrim($fragment, '^').')'.$existing;
        } else {
            $propSchema['pattern'] = $fragment;
        }

        return $propSchema;
    }

    /**
     * Apply StartsWith constraint as a pattern prefix.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyStartsWithPattern(StartsWith $startsWith, array $propSchema): array
    {
        $prefixes = (array) $startsWith->prefix;
        if (count($prefixes) === 1) {
            $escaped = preg_quote($prefixes[0], '/');

            return self::mergePattern('^'.$escaped, $propSchema);
        }
        // Multiple prefixes → alternation group
        $escaped = array_map(fn (string $p): string => preg_quote($p, '/'), $prefixes);

        return self::mergePattern('^('.implode('|', $escaped).')', $propSchema);
    }

    /**
     * Apply EndsWith constraint as a pattern suffix.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyEndsWithPattern(EndsWith $endsWith, array $propSchema): array
    {
        $suffixes = (array) $endsWith->suffix;
        if (count($suffixes) === 1) {
            $escaped = preg_quote($suffixes[0], '/');

            return self::mergePattern($escaped.'$', $propSchema);
        }
        $escaped = array_map(fn (string $s): string => preg_quote($s, '/'), $suffixes);

        return self::mergePattern('('.implode('|', $escaped).')$', $propSchema);
    }

    /**
     * Apply enum constraints to the property schema.
     *
     * Uses reflection on the enum's backing type to determine the OpenAPI
     * type instead of inspecting runtime values, ensuring PHPStan L9 safety.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyEnumSchema(EnumAttribute $enum, array $propSchema): array
    {
        $enumClass = $enum->enumClass;

        if (! enum_exists($enumClass)) {
            return $propSchema;
        }

        if (! is_a($enumClass, \BackedEnum::class, true)) {
            return $propSchema;
        }

        /** @var class-string<\BackedEnum> $enumClass */
        $values = array_map(
            static fn (\BackedEnum $case): mixed => $case->value,
            $enumClass::cases()
        );

        $propSchema['enum'] = $values;

        // Infer OpenAPI type from the enum's backing type (not runtime values)
        $backingType = (new \ReflectionEnum($enumClass))->getBackingType();

        if ($backingType !== null) {
            $propSchema['type'] = $backingType->getName() === 'int' ? 'integer' : 'string';
        }

        return $propSchema;
    }

    /**
     * Check if a reflection property has a given attribute.
     *
     * @param  \ReflectionProperty|null  $prop  The property to check, or null if the property doesn't exist
     * @param  string  $attributeClass  The fully-qualified attribute class name
     * @return bool True if the property has the attribute
     */
    private static function hasAttribute(?ReflectionProperty $prop, string $attributeClass): bool
    {
        if (! $prop instanceof ReflectionProperty) {
            return false;
        }

        return array_any($prop->getAttributes(), fn (\ReflectionAttribute $attr): bool => $attr->getName() === $attributeClass);
    }

    /**
     * Infer the OpenAPI type name from a PHP reflection type.
     *
     * Handles named types (including ValueObjects), union types,
     * intersection types, and falls back to 'string' for unknown types.
     *
     * @return string OpenAPI type identifier ('string', 'integer', 'number', 'boolean', 'array', 'object')
     */
    private static function inferType(?\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            // Check if it's a ValueObject — infer from its columnType()
            $isVo = class_exists($typeName) && is_a($typeName, ValueObjectContract::class, true);
            if ($isVo) {
                /** @var class-string<ValueObjectContract> $typeName */
                return self::inferVoType($typeName);
            }

            return match ($typeName) {
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                'string' => 'string',
                default => 'object',
            };
        }

        if ($type instanceof \ReflectionUnionType) {
            // Collect all named types from the union (including null)
            $types = [];
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof \ReflectionNamedType) {
                    if ($subType->getName() === 'null') {
                        continue;
                    }
                    $types[] = self::inferType($subType);
                }
            }

            // Deduplicate
            $types = array_values(array_unique($types));

            // If only one non-null type remains, return it
            if (count($types) === 1) {
                return $types[0];
            }

            // Multiple distinct types — return 'object' as the safest generic
            return 'object';
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return 'object';
        }

        return 'string';
    }

    /**
     * Infer the OpenAPI type from a ValueObject's columnType() method.
     *
     * @param  class-string<ValueObjectContract>  $voClass  The ValueObject class to infer the type for
     * @return string OpenAPI type identifier ('string', 'integer', 'number', 'boolean', 'object')
     */
    private static function inferVoType(string $voClass): string
    {
        if (! method_exists($voClass, 'columnType')) {
            return 'object';
        }

        return match ($voClass::columnType()) {
            'string' => 'string',
            'integer' => 'integer',
            'decimal', 'float' => 'number',
            'boolean', 'bool' => 'boolean',
            'json', 'array' => 'object',
            default => 'object',
        };
    }
}
