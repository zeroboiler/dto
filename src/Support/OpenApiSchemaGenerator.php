<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\ValueObjects\Contracts\ValueObject as ValueObjectContract;

/**
 * Generate OpenAPI schema from a DTO class.
 */
class OpenApiSchemaGenerator
{
    /**
     * Generate an OpenAPI schema array for a DTO class.
     *
     * @param  class-string  $dtoClass
     * @return array<string, mixed>
     */
    public static function generate(string $dtoClass): array
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
                ? new \ReflectionProperty($dtoClass, $name)
                : null;

            // Skip hidden properties entirely
            if (self::hasAttribute($propReflection, Hidden::class)) {
                continue;
            }

            // Check for DefaultValue attribute early to determine if field is required
            $hasDefaultValueAttr = false;
            $defaultValue = null;

            if ($propReflection instanceof \ReflectionProperty) {
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

            if ($isRequired) {
                $required[] = $name;
            }

            $propSchema = ['type' => self::inferType($type)];

            // Only mark as nullable if the type actually allows null.
            // Optional fields (not in `required`) mean the field can be absent,
            // but nullable means the field can be explicitly null — these are different concepts.
            $typeAllowsNull = $type?->allowsNull() ?? true;
            if ($typeAllowsNull) {
                $propSchema['nullable'] = true;
            }

            if ($hasDefaultValueAttr) {
                $propSchema['default'] = $defaultValue;
            }

            // Enrich schema with validation attribute constraints
            if ($propReflection instanceof \ReflectionProperty) {
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
     * Enrich the OpenAPI property schema with constraints from validation attributes.
     *
     * @param  array<string, mixed>  $propSchema
     * @return array<string, mixed>
     */
    private static function applyValidationAttributes(\ReflectionProperty $prop, array $propSchema): array
    {
        foreach ($prop->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            match (true) {
                $instance instanceof Email => $propSchema['format'] = 'email',
                $instance instanceof Url => $propSchema['format'] = 'uri',
                $instance instanceof Uuid => $propSchema['format'] = 'uuid',
                $instance instanceof Max => $propSchema['maxLength'] = $instance->value,
                $instance instanceof Min => $propSchema['minLength'] = $instance->value,
                $instance instanceof Pattern => $propSchema['pattern'] = ltrim($instance->regex, '/'),
                $instance instanceof Integer => $propSchema['type'] = 'integer',
                $instance instanceof Numeric => $propSchema['type'] = 'number',
                $instance instanceof Boolean => $propSchema['type'] = 'boolean',
                $instance instanceof Date => $propSchema['format'] = 'date',
                $instance instanceof In => $propSchema['enum'] = $instance->values,
                $instance instanceof EnumAttribute => $propSchema = self::applyEnumSchema($instance, $propSchema),
                default => null,
            };
        }

        return $propSchema;
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
    private static function hasAttribute(?\ReflectionProperty $prop, string $attributeClass): bool
    {
        if (! $prop instanceof \ReflectionProperty) {
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
