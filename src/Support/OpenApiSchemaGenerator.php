<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
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

            if (! $isRequired) {
                $propSchema['nullable'] = true;
            }

            if ($hasDefaultValueAttr) {
                $propSchema['default'] = $defaultValue;
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
