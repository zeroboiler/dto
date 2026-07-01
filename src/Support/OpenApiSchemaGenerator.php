<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;

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

        foreach ($prop->getAttributes() as $attr) {
            if ($attr->getName() === $attributeClass) {
                return true;
            }
        }

        return false;
    }

    private static function inferType(?\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            return match ($type->getName()) {
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                'string' => 'string',
                default => 'object',
            };
        }

        return 'string';
    }
}
