<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;

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
            return ['type' => 'object', 'properties' => new \stdClass()];
        }

        $properties = [];
        $required = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();
            $isRequired = !$param->isDefaultValueAvailable() && !$type?->allowsNull();

            if ($isRequired) {
                $required[] = $name;
            }

            $propSchema = ['type' => self::inferType($type)];

            if (!$isRequired) {
                $propSchema['nullable'] = true;
            }

            // Check for Hidden attribute
            $propReflection = $reflection->hasProperty($name)
                ? new \ReflectionProperty($dtoClass, $name)
                : null;

            if ($propReflection) {
                foreach ($propReflection->getAttributes() as $attr) {
                    if ($attr->getName() === \ZeroBoiler\DTO\Attributes\Hidden::class) {
                        continue 2;
                    }
                }

                // Add description from Description attribute if present
                foreach ($propReflection->getAttributes() as $attr) {
                    if ($attr->getName() === \ZeroBoiler\DTO\Attributes\Default::class) {
                        $instance = $attr->newInstance();
                        $propSchema['default'] = $instance->value;
                    }
                }
            }

            $properties[$name] = $propSchema;
        }

        $schema = [
            'type' => 'object',
            'properties' => (object) $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
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
