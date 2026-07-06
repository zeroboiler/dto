<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\ValueObjects\Contracts\ValueObject;

/**
 * Base Data Transfer Object — zero boilerplate hydration, validation and serialization.
 *
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
abstract class DataTransferObject implements Arrayable, JsonSerializable
{
    /** @var array<string, array<string, mixed>> Cache for reflection metadata per class */
    private static array $_metadataCache = [];

    public static function flushMetadataCache(?string $class = null): void
    {
        if ($class !== null) {
            unset(self::$_metadataCache[$class]);
        } else {
            self::$_metadataCache = [];
        }
    }

    /**
     * Create DTO instance from an associative array.
     *
     * @param  array<string, mixed>  $data
     * @param  bool  $validate  Run validation before hydration
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data, bool $validate = true): static
    {
        if ($validate) {
            static::validateArray($data);
        }

        $metadata = self::resolveMetadata();
        $args = [];

        foreach ($metadata['properties'] as $name => $prop) {
            $sourceKey = $prop['map_from'] ?? $name;
            $hasKey = array_key_exists((string) $sourceKey, $data) || Arr::has($data, $sourceKey);
            $value = Arr::get($data, $sourceKey);

            // Only apply default when the key is entirely absent from the data.
            // Explicit null or empty string should be respected as intentional values (#678).
            if (! $hasKey && $prop['has_default']) {
                $value = $prop['default'];
            }

            // ValueObject auto-instantiation (#689)
            // If the property is typed as a ValueObject and the raw value is not already an instance,
            // construct the VO from the raw value. The VO constructor handles validation.
            if ($value !== null && $prop['value_object_class'] !== null && ! $value instanceof $prop['value_object_class']) {
                $value = self::castValueToValueObject($value, $prop['value_object_class']);
            } elseif ($prop['cast'] !== null && $value !== null) {
                $value = self::castValue($value, $prop['cast']);
            }

            $args[$name] = $value;
        }

        return new static(...$args);
    }

    /**
     * Create DTO instance from a partial associative array (PATCH semantics).
     *
     * Only fields present in $data are validated and hydrated.
     * Missing fields fall back to their default values, or type-appropriate
     * empty values when no default exists (string → '', int → 0, etc.).
     *
     * This is ideal for PATCH endpoints where only changed fields are submitted.
     * For merging onto an existing DTO, use {@see with()} instead.
     *
     * @param  array<string, mixed>  $data
     * @param  bool  $validatePresent  Run validation on fields present in $data
     *
     * @throws ValidationException
     */
    public static function fromPartialArray(array $data, bool $validatePresent = true): static
    {
        if ($validatePresent && $data !== []) {
            static::validatePartialArray($data);
        }

        $metadata = self::resolveMetadata();
        $args = [];

        foreach ($metadata['properties'] as $name => $prop) {
            $sourceKey = $prop['map_from'] ?? $name;
            $hasKey = array_key_exists((string) $sourceKey, $data) || Arr::has($data, $sourceKey);

            if ($hasKey) {
                $value = Arr::get($data, $sourceKey);
                // Apply the same casting/VO logic as fromArray
                if ($value !== null && $prop['value_object_class'] !== null && ! $value instanceof $prop['value_object_class']) {
                    $value = self::castValueToValueObject($value, $prop['value_object_class']);
                } elseif ($prop['cast'] !== null && $value !== null) {
                    $value = self::castValue($value, $prop['cast']);
                }
                $args[$name] = $value;
            } elseif ($prop['has_default']) {
                // Missing field: use default if available, otherwise type-appropriate empty value
                $args[$name] = $prop['default'];
            } else {
                $args[$name] = self::emptyValueForType($name, $prop['nullable'], $prop);
            }
        }

        return new static(...$args);
    }

    /**
     * Get a type-appropriate empty value for a missing field.
     *
     * Uses reflection on the constructor parameter to determine the type.
     *
     * @param  array<string, mixed>  $propMeta
     */
    private static function emptyValueForType(string $paramName, bool $nullable, array $propMeta): mixed
    {
        // If the property is nullable, null is the natural default
        if ($nullable) {
            return null;
        }

        // Try to infer from cast type
        $cast = $propMeta['cast'] ?? null;
        if ($cast !== null) {
            return match ($cast) {
                'int', 'integer' => 0,
                'float', 'double' => 0.0,
                'string' => '',
                'bool', 'boolean' => false,
                'array' => [],
                default => null,
            };
        }

        // Use reflection to get the type from the constructor parameter
        $reflection = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getName() === $paramName) {
                    $type = $param->getType();

                    if ($type instanceof \ReflectionNamedType) {
                        $typeName = $type->getName();

                        return match ($typeName) {
                            'int' => 0,
                            'float' => 0.0,
                            'string' => '',
                            'bool' => false,
                            'array' => [],
                            default => null,
                        };
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate only the fields present in the partial data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validatePartialArray(array $data): array
    {
        $metadata = self::resolveMetadata();
        $partialRules = [];

        foreach ($metadata['rules'] as $field => $rules) {
            $sourceKey = $metadata['properties'][$field]['map_from'] ?? $field;
            $hasKey = array_key_exists((string) $sourceKey, $data) || Arr::has($data, $sourceKey);

            if (! $hasKey) {
                continue;
            }

            // Convert 'required' to 'sometimes' for partial updates
            $adjustedRules = array_map(
                fn (mixed $rule): mixed => $rule === 'required' ? 'sometimes' : $rule,
                $rules
            );

            $partialRules[$field] = $adjustedRules;
        }

        if ($partialRules === []) {
            return $data;
        }

        $validator = Validator::make($data, $partialRules, $metadata['messages']);

        return $validator->validate();
    }

    /**
     * Create DTO from a partial request (PATCH/PUT with sparse data).
     *
     * @throws ValidationException
     */
    public static function fromPartialRequest(Request $request, bool $validate = true): static
    {
        return static::fromPartialArray($request->all(), $validate);
    }

    /**
     * @throws ValidationException
     */
    public static function fromRequest(Request $request, bool $validate = true): static
    {
        return static::fromArray($request->all(), $validate);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validateArray(array $data): array
    {
        $metadata = self::resolveMetadata();

        /** @var ValidatorContract $validator */
        $validator = Validator::make($data, $metadata['rules'], $metadata['messages']);

        return $validator->validate();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::resolveMetadata()['rules'];
    }

    /**
     * @return array<string, mixed>
     */
    public function allValues(): array
    {
        return $this->convertProperties(includeHidden: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->convertProperties(includeHidden: false);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function with(array $overrides, bool $validate = true): static
    {
        $data = array_merge($this->allValues(), $overrides);

        return static::fromArray($data, validate: $validate);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * @return array{
     *     properties: array<string, array<string, mixed>>,
     *     rules: array<string, array<int, mixed>>,
     *     messages: array<string, string>
     * }
     */
    private static function resolveMetadata(): array
    {
        $class = static::class;

        return self::$_metadataCache[$class] ?? self::$_metadataCache[$class] = DtoMetadataResolver::resolve($class);
    }

    /**
     * @return array<string, mixed>
     */
    private function convertProperties(bool $includeHidden): array
    {
        $metadata = self::resolveMetadata();
        $result = [];

        foreach ($metadata['properties'] as $name => $prop) {
            if (! $includeHidden && $prop['hidden'] === true) {
                continue;
            }

            $result[$name] = $this->normalizeValue($this->{$name}, $includeHidden);
        }

        return $result;
    }

    private function normalizeValue(mixed $value, bool $includeHidden): mixed
    {
        if ($value instanceof DataTransferObject) {
            return $includeHidden ? $value->allValues() : $value->toArray();
        }

        if ($value instanceof ValueObject) {
            // Use columnType() to determine serialization strategy.
            // Single-value VOs (string/integer columns) serialize to their primitive.
            // Composite VOs (json columns) serialize to their array representation.
            $columnType = $value::columnType();

            if ($columnType === 'json') {
                return $value->toArray();
            }

            $primitive = $value->toPrimitive();

            return is_scalar($primitive) ? $primitive : $value->toArray();
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return $value;
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'array' => is_array($value) ? $value : self::decodeJsonArray((string) $value),
            'date', 'datetime' => $value instanceof \DateTimeInterface ? $value : new Carbon($value),
            default => $value,
        };
    }

    /**
     * Cast a raw value to a ValueObject instance.
     *
     * Handles both single-value VOs (constructed from a scalar)
     * and composite VOs (constructed from an array or JSON string).
     *
     * @param  mixed  $value  Raw value from input data
     * @param  class-string<ValueObject>  $voClass
     *
     * @throws \InvalidArgumentException If the value cannot be cast to the VO
     * @throws ValidationException If the VO validation fails
     */
    private static function castValueToValueObject(mixed $value, string $voClass): ValueObject
    {
        // Already a VO instance — return as-is (shouldn't happen due to caller check, but be safe)
        if ($value instanceof $voClass) {
            return $value;
        }

        // Try fromPrimitive first — it handles both scalar and JSON/array inputs
        if (method_exists($voClass, 'fromPrimitive')) {
            try {
                return $voClass::fromPrimitive($value);
            } catch (\InvalidArgumentException) {
                // fromPrimitive failed, try direct construction below
            }
        }

        // Composite VO from array — try named constructor
        if (is_array($value)) {
            try {
                return new $voClass(...$value);
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException(
                    "Cannot cast array to {$voClass}: ".$e->getMessage(),
                    0,
                    $e
                );
            }
        }

        // Single-value VO from scalar — try direct construction
        if (is_string($value) || is_int($value) || is_float($value)) {
            try {
                return new $voClass($value);
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException(
                    'Cannot cast '.get_debug_type($value)." to {$voClass}: ".$e->getMessage(),
                    0,
                    $e
                );
            }
        }

        throw new \InvalidArgumentException(
            'Cannot cast '.get_debug_type($value)." to ValueObject {$voClass}"
        );
    }

    /**
     * Safely decode a JSON string to an array.
     * Returns [] for empty strings or invalid JSON instead of silently returning null.
     *
     * @return array<string, mixed>
     */
    private static function decodeJsonArray(string $value): array
    {
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
