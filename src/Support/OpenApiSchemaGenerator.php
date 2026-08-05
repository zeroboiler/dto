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
 * Generate OpenAPI schema from a DTO class.
 */
final class OpenApiSchemaGenerator
{
    /**
     * Generate an OpenAPI schema array for a DTO class.
     *
     * @param  class-string  $dtoClass
     * @return array<string, mixed>
     */
    public static function generate(string $dtoClass): array
    {
        $components = [];
        $schema = self::generateInternal($dtoClass, $components);

        // If nested DTOs were detected, generate() produces schemas with
        // dangling $ref pointers — the component definitions are discarded.
        // Direct users to generateWithComponents() instead. (BUG-2 R38)
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
            return ['type' => 'object', 'properties' => new \stdClass];
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
            $key = json_encode($schema);
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
     * @param  class-string  $className
     */
    private static function componentName(string $className): string
    {
        // Convert PascalCase to PascalCase (keep as-is for OpenAPI component names)
        $reflection = new ReflectionClass($className);

        return $reflection->getShortName();
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

            match (true) {
                $instance instanceof Email => $propSchema['format'] = 'email',
                $instance instanceof Url => $propSchema['format'] = 'uri',
                $instance instanceof Uuid => $propSchema['format'] = 'uuid',
                $instance instanceof Pattern => $propSchema['pattern'] = self::stripRegexDelimiters($instance->regex),
                $instance instanceof Integer => $propSchema['type'] = 'integer',
                $instance instanceof Numeric => $propSchema['type'] = 'number',
                $instance instanceof Boolean => $propSchema['type'] = 'boolean',
                $instance instanceof Date => $propSchema['format'] = 'date',
                $instance instanceof In => $propSchema['enum'] = $instance->values,
                $instance instanceof EnumAttribute => $propSchema = self::applyEnumSchema($instance, $propSchema),
                $instance instanceof Max => $propSchema = self::applyMaxConstraint($instance->value, $propSchema),
                $instance instanceof Min => $propSchema = self::applyMinConstraint($instance->value, $propSchema),
                $instance instanceof Between => $propSchema = self::applyBetweenConstraint($instance, $propSchema),
                $instance instanceof StartsWith => $propSchema = self::applyStartsWithPattern($instance, $propSchema),
                $instance instanceof EndsWith => $propSchema = self::applyEndsWithPattern($instance, $propSchema),
                default => null,
            };
        }

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
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyMinConstraint(int $value, array $propSchema): array
    {
        if (self::isNumericType($propSchema)) {
            $propSchema['minimum'] = $value;
        } else {
            $propSchema['minLength'] = $value;
        }

        return $propSchema;
    }

    /**
     * Apply a Max constraint as either maximum (numeric) or maxLength (string).
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyMaxConstraint(int $value, array $propSchema): array
    {
        if (self::isNumericType($propSchema)) {
            $propSchema['maximum'] = $value;
        } else {
            $propSchema['maxLength'] = $value;
        }

        return $propSchema;
    }

    /**
     * Apply a Between constraint (both min and max) with type-aware mapping.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyBetweenConstraint(Between $between, array $propSchema): array
    {
        $propSchema = self::applyMinConstraint((int) $between->min, $propSchema);

        return self::applyMaxConstraint((int) $between->max, $propSchema);
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

        if ($existing !== null) {
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
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyEnumSchema(EnumAttribute $enum, array $propSchema): array
    {
        $enumClass = $enum->enumClass;

        if (! enum_exists($enumClass)) {
            return $propSchema;
        }

        $values = array_map(
            fn (\BackedEnum $case): mixed => $case->value,
            $enumClass::cases()
        );

        $propSchema['enum'] = $values;

        // Infer type from first enum value
        if ($values !== []) {
            $propSchema['type'] = is_int($values[0]) ? 'integer' : 'string';
        }

        return $propSchema;
    }

    /**
     * Check if a reflection property has a given attribute.
     */
    private static function hasAttribute(?ReflectionProperty $prop, string $attributeClass): bool
    {
        if (! $prop instanceof ReflectionProperty) {
            return false;
        }

        return array_any($prop->getAttributes(), fn (\ReflectionAttribute $attr): bool => $attr->getName() === $attributeClass);
    }

    private static function inferType(?\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            // Check if it's a ValueObject — infer from its columnType()
            $isVo = class_exists($typeName) && in_array(ValueObjectContract::class, class_implements($typeName) ?: [], true);
            if ($isVo) {
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
     * @param  class-string<ValueObjectContract>  $voClass
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
