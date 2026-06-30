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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;

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

            if (! $hasKey && $prop['has_default']) {
                $value = $prop['default'];
            }

            if ($prop['cast'] !== null && $value !== null) {
                $value = self::castValue($value, $prop['cast']);
            }

            $args[$name] = $value;
        }

        return new static(...$args);
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
            default => $value,
        };
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
