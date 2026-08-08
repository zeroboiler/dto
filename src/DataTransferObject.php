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
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\ValueObjects\Contracts\ValueObject;

/**
 * Base Data Transfer Object — zero boilerplate hydration, validation and serialization.
 *
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 * @phpstan-type DtoPropertyMeta array{
 *     map_from: string|null,
 *     default: mixed,
 *     has_default: bool,
 *     cast: string|null,
 *     hidden: bool,
 *     nullable: bool,
 *     value_object_class: class-string<ValueObject>|null,
 *     dto_class: class-string<static>|null,
 *     enum_class: class-string<\BackedEnum>|null,
 *     nested_array_class: class-string<static>|null,
 *     collection_class: class-string<static>|null
 * }
 * @phpstan-type DtoResolvedMetadata array{
 *     properties: array<string, DtoPropertyMeta>,
 *     rules: array<string, array<int, mixed>>,
 *     messages: array<string, string>
 * }
 */
abstract class DataTransferObject implements Arrayable, FromRequestDTO, JsonSerializable, ValidatableDTO
{
    /** @var array<string, array<string, mixed>> Cache for reflection metadata per class */
    private static array $_zbMetadataCache = [];

    /** @var array<string, float> Cache creation timestamps per class */
    private static array $_zbMetadataCacheTimestamps = [];

    /**
     * TTL in seconds for metadata cache in local/testing environments.
     *
     * When the app runs in dev mode, cached metadata older than
     * this TTL is automatically invalidated on next access.
     * Set to 0 to disable TTL-based invalidation.
     */
    private static float $_zbMetadataCacheTtl = 0.0;

    /**
     * Set the TTL for metadata cache entries.
     *
     * Called by the service provider when APP_ENV is local or testing.
     * Pass 0 to disable TTL-based invalidation (production default).
     *
     * @param  float  $seconds  Cache TTL in seconds (0 = disabled)
     */
    public static function setMetadataCacheTtl(float $seconds): void
    {
        self::$_zbMetadataCacheTtl = $seconds;
    }

    /**
     * Flush metadata cache entries.
     *
     * When $class is provided, only that class's cache is cleared.
     * When null (default), all cached metadata is cleared.
     *
     * Used by the service provider for Octane/Swoole cache flush.
     *
     * @param  string|null  $class  Specific class to flush, or null for all
     */
    public static function flushMetadataCache(?string $class = null): void
    {
        if ($class !== null) {
            unset(self::$_zbMetadataCache[$class]);
            unset(self::$_zbMetadataCacheTimestamps[$class]);
        } else {
            self::$_zbMetadataCache = [];
            self::$_zbMetadataCacheTimestamps = [];
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
            $sourceKey = is_string($prop['map_from'] ?? null) ? $prop['map_from'] : $name;
            $hasKey = array_key_exists($sourceKey, $data) || Arr::has($data, $sourceKey);
            $value = Arr::get($data, $sourceKey);

            // Only apply default when the key is entirely absent from the data.
            // Explicit null or empty string should be respected as intentional values (#678).
            if (! $hasKey && $prop['has_default']) {
                $value = $prop['default'];
            }

            $args[$name] = self::castAndHydrateValue($value, $prop, $name);
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
            $sourceKey = is_string($prop['map_from'] ?? null) ? $prop['map_from'] : $name;
            $hasKey = array_key_exists($sourceKey, $data) || Arr::has($data, $sourceKey);

            if ($hasKey) {
                $value = Arr::get($data, $sourceKey);
                $args[$name] = self::castAndHydrateValue($value, $prop, $name);
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
     * Uses the cached property metadata from resolveMetadata() to avoid
     * repeated reflection on every partial update. Falls back to
     * cast type inference or reflection only as a last resort.
     *
     * @param  string  $paramName  Property name (for error messages)
     * @param  bool  $nullable  Whether the property accepts null
     * @param  array<string, mixed>  $propMeta  Cached property metadata
     */
    private static function emptyValueForType(string $paramName, bool $nullable, array $propMeta): mixed
    {
        // If the property is nullable, null is the natural default
        if ($nullable) {
            return null;
        }

        // Try to infer from cast type (most common path)
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

        // For known complex types (DTO, ValueObject, Enum, NestedArray, Collection),
        // skip reflection — partial updates can't construct these without source data.
        if ($propMeta['value_object_class'] !== null
            || $propMeta['dto_class'] !== null
            || $propMeta['enum_class'] !== null
            || $propMeta['nested_array_class'] !== null
            || $propMeta['collection_class'] !== null
        ) {
            return null;
        }

        // Use reflection to get the type from the constructor parameter (last resort)
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
                            default => throw new \InvalidArgumentException(
                                "Cannot provide empty value for '{$paramName}' of type '{$typeName}' in partial update. "
                                .'Provide this field explicitly or make it nullable.'
                            ),
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
            $sourceKey = is_string($metadata['properties'][$field]['map_from'] ?? null) ? $metadata['properties'][$field]['map_from'] : $field;
            $hasKey = array_key_exists($sourceKey, $data) || Arr::has($data, $sourceKey);

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
     * Create DTO from a partial HTTP request (PATCH semantics).
     *
     * Only fields present in the request are validated and hydrated.
     * Missing fields fall back to their default values or type-appropriate
     * empty values when no default exists.
     *
     * @throws ValidationException If validation fails and $validate is true
     */
    public static function fromPartialRequest(Request $request, bool $validate = true): static
    {
        return static::fromPartialArray($request->all(), $validate);
    }

    /**
     * Create DTO instance from an HTTP request.
     *
     * Delegates to {@see fromArray()} with the request's input data.
     *
     * @param  Request  $request  The HTTP request
     * @param  bool  $validate  Run validation before hydration
     *
     * @throws ValidationException If validation fails and $validate is true
     */
    #[\Override]
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
    #[\Override]
    public static function rules(): array
    {
        return self::resolveMetadata()['rules'];
    }

    /**
     * Return validation rules scoped to a specific action.
     *
     * By default, returns the same rules as {@see rules()} for all actions.
     * Override in subclasses to provide action-specific rules (e.g.
     * different rules for 'create' vs 'update').
     *
     * Common actions: 'create', 'update', 'patch', 'delete'.
     *
     * @param  string  $action  The action context
     * @return array<string, array<int, mixed>>
     */
    #[\Override]
    public static function rulesFor(string $action): array
    {
        return static::rules();
    }

    /**
     * Convert all properties to array, including hidden fields.
     *
     * Unlike {@see toArray()}, this includes properties marked with #[Hidden].
     * Useful for internal operations, debugging, and Eloquent cast serialization.
     *
     * @return array<string, mixed>
     */
    public function allValues(): array
    {
        return $this->convertProperties(includeHidden: true);
    }

    /**
     * Convert all public (non-hidden) properties to an associative array.
     *
     * Properties marked with #[Hidden] are excluded from output.
     * Nested DTOs, DtoCollections, ValueObjects, BackedEnums, and
     * DateTimeInterface instances are recursively normalized to primitives.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(): array
    {
        return $this->convertProperties(includeHidden: false);
    }

    /**
     * Serialize the DTO to a JSON string.
     *
     * Uses the same output as {@see toArray()} (hidden fields excluded).
     *
     * @param  int  $options  JSON encoding options (e.g. JSON_PRETTY_PRINT)
     * @return string JSON string, empty string on encoding failure
     */
    public function toJson(int $options = 0): string
    {
        try {
            return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }
    }

    /**
     * Serialize the DTO for json_encode().
     *
     * @return array<string, mixed> The toArray() output
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Check if two DTOs have the same serialized output.
     *
     * Compares toArray() results — hidden fields are excluded from comparison.
     * Two DTOs are "equal" if they produce the same public-facing array.
     *
     *   if ($dto1->equals($dto2)) { ... }
     */
    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Check if all properties have their default or empty values.
     *
     * Useful for detecting DTOs that carry no meaningful data
     * (e.g., in PATCH requests where nothing was provided).
     *
     * A property is considered "empty" if it is:
     * - `null` (for nullable properties or optional properties without a value)
     * - An empty string `''`
     * - An empty array `[]`
     * - `false` (for boolean properties)
     *
     * Note: `0` and `0.0` are considered **non-empty** because they are
     * valid, meaningful values (e.g., `$quantity = 0`, `$price = 0.0`).
     * Only nullable properties treat `null` as empty; non-nullable int/float
     * properties with value `0` are not considered empty.
     *
     *   if ($dto->isEmpty()) { return; }
     */
    public function isEmpty(): bool
    {
        foreach (self::resolveMetadata()['properties'] as $name => $prop) {
            /** @var mixed $value */
            $value = $this->{$name};

            // Non-nullable int/float with value 0 or 0.0 is NOT empty (it's a valid value)
            if (! $prop['nullable'] && $value !== null && $value !== '' && $value !== false && $value !== []) {
                return false;
            }

            // Nullable properties: null is empty, non-null scalars are not
            if ($prop['nullable'] && $value !== null && $value !== '' && $value !== false && $value !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the DTO has at least one non-empty property.
     *
     * Negation of {@see isEmpty()}.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Create DTO instance from a JSON string.
     *
     * Convenience wrapper around {@see fromArray()} that decodes JSON first.
     *
     *   $dto = CreateUserDTO::fromJson($request->getContent());
     *
     * @param  string  $json  JSON string to decode
     * @param  bool  $validate  Run validation before hydration
     *
     * @throws DTOException If the JSON cannot be decoded
     * @throws ValidationException If validation fails and $validate is true
     */
    public static function fromJson(string $json, bool $validate = true): static
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw DTOException::invalidJson('(root)', $e->getMessage());
        }

        if (! is_array($data)) {
            throw DTOException::invalidJson('(root)', 'Expected a JSON object, got '.get_debug_type($data));
        }

        // Reject sequential arrays — only associative arrays (JSON objects) are valid
        if (array_is_list($data)) {
            throw DTOException::invalidJson('(root)', 'Expected a JSON object (associative array), got a sequential array');
        }

        /** @var array<string, mixed> $data */
        return static::fromArray($data, $validate);
    }

    /**
     * Return an array containing only the specified fields.
     *
     * Uses toArray() (excludes hidden fields). Non-existent keys are
     * silently ignored to match Laravel's Arr::only() behavior.
     *
     * @param  array<int, string>|string  $keys
     * @return array<string, mixed>
     */
    public function only(array|string $keys): array
    {
        $resolvedKeys = is_array($keys) ? $keys : [$keys];

        return Arr::only($this->toArray(), $resolvedKeys);
    }

    /**
     * Return an array with the specified fields excluded.
     *
     * Uses toArray() (excludes hidden fields). Non-existent keys are
     * silently ignored to match Laravel's Arr::except() behavior.
     *
     * @param  array<int, string>|string  $keys
     * @return array<string, mixed>
     */
    public function except(array|string $keys): array
    {
        $resolvedKeys = is_array($keys) ? $keys : [$keys];

        return Arr::except($this->toArray(), $resolvedKeys);
    }

    /**
     * Create an immutable copy with the given overrides.
     *
     * Always validates the merged data to prevent invalid state (#2).
     * The original DTO instance remains unchanged — a new instance is returned.
     *
     * @param  array<string, mixed>  $overrides  Properties to merge into the DTO
     * @param  bool  $validate  @deprecated Has no effect. Validation always runs to prevent invalid state.
     *
     * @throws ValidationException If the merged data fails validation
     */
    public function with(array $overrides, bool $validate = true): static
    {
        $data = array_merge($this->allValues(), $overrides);

        // Validation always runs in with() to prevent invalid state (#2).
        // The $validate parameter is kept for backward compatibility but has no effect.
        return static::fromArray($data, validate: true);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Apply casting, ValueObject instantiation, enum casting, and nested DTO
     * hydration to a raw property value.
     *
     * Centralizes the transformation pipeline shared by fromArray() and
     * fromPartialArray() to eliminate duplicate casting logic.
     *
     * @param  mixed  $value  Raw value from input data
     * @param  array<string, mixed>  $prop  Property metadata from resolveMetadata()
     * @param  string  $name  Property name (for error context)
     */
    private static function castAndHydrateValue(mixed $value, array $prop, string $name): mixed
    {
        /** @var class-string<ValueObject>|null $voClass */
        $voClass = $prop['value_object_class'] ?? null;

        // ValueObject auto-instantiation (#689)
        if ($value !== null && $voClass !== null && ! $value instanceof $voClass) {
            $value = self::castValueToValueObject($value, $voClass);
        } elseif ($prop['cast'] !== null && $value !== null) {
            /** @var string $castType */
            $castType = $prop['cast'];
            $value = self::castValue($value, $castType, $name);
        }

        /** @var class-string<\BackedEnum>|null $enumClass */
        $enumClass = $prop['enum_class'] ?? null;

        // BackedEnum auto-cast (#with-roundtrip)
        if ($value !== null && $enumClass !== null && ! $value instanceof $enumClass) {
            $value = self::castValueToEnum($value, $enumClass);
        }

        /** @var class-string<self>|null $dtoClass */
        $dtoClass = $prop['dto_class'] ?? null;

        // Nested DTO hydration (#117)
        if ($value !== null && $dtoClass !== null && ! $value instanceof $dtoClass) {
            $value = self::hydrateNestedDto($value, $dtoClass);
        }

        /** @var class-string<self>|null $nestedArrayClass */
        $nestedArrayClass = $prop['nested_array_class'] ?? null;

        // Array of nested DTOs (#117)
        if ($value !== null && $nestedArrayClass !== null && is_array($value)) {
            $arrayValue = $value;
            /** @var array<int, mixed> $arrayValue */
            $value = self::hydrateNestedArray($arrayValue, $nestedArrayClass);
        }

        /** @var class-string<self>|null $collectionClass */
        $collectionClass = $prop['collection_class'] ?? null;

        // Collection hydration (#28)
        if ($value !== null && $collectionClass !== null && is_array($value)) {
            /** @var array<int, mixed> $value */
            return self::hydrateCollection($value, $collectionClass);
        }

        return $value;
    }

    /**
     * Resolve metadata for this DTO class.
     *
     * Uses static caching with optional TTL-based invalidation.
     * Results are stored in per-class static properties, not in the
     * singleton EnumCache (each DTO class manages its own cache).
     *
     * @return DtoResolvedMetadata
     * @phpstan-return DtoResolvedMetadata
     *
     * @throws \ReflectionException If the class cannot be reflected
     */
    private static function resolveMetadata(): array
    {
        $class = static::class;

        // TTL-based invalidation for dev/testing environments (#3)
        if (self::$_zbMetadataCacheTtl > 0.0 && isset(self::$_zbMetadataCacheTimestamps[$class])) {
            $age = microtime(true) - self::$_zbMetadataCacheTimestamps[$class];
            if ($age >= self::$_zbMetadataCacheTtl) {
                unset(self::$_zbMetadataCache[$class]);
                unset(self::$_zbMetadataCacheTimestamps[$class]);
            }
        }

        if (! isset(self::$_zbMetadataCache[$class])) {
            self::$_zbMetadataCache[$class] = DtoMetadataResolver::resolve($class);
            self::$_zbMetadataCacheTimestamps[$class] = microtime(true);
        }

        /** @var DtoResolvedMetadata $metadata */
        $metadata = self::$_zbMetadataCache[$class];

        return $metadata;
    }

    /**
     * Convert DTO properties to an associative array.
     *
     * Iterates over all resolved properties and normalizes each value
     * (hydrating nested DTOs, serializing ValueObjects, etc.).
     *
     * @param  bool  $includeHidden  Whether to include #[Hidden] properties
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

            /** @var mixed $value */
            $value = $this->{$name};
            $result[$name] = $this->normalizeValue($value, $includeHidden);
        }

        return $result;
    }

    /**
     * Normalize a single property value for serialization.
     *
     * Handles DtoCollection, nested DataTransferObject, arrays (recursively),
     * and delegates scalar normalization to {@see normalizeScalar()}.
     *
     * @param  mixed  $value  The raw property value
     * @param  bool  $includeHidden  Whether nested DTOs should include hidden fields
     */
    private function normalizeValue(mixed $value, bool $includeHidden): mixed
    {
        if ($value instanceof DtoCollection) {
            return $value->toArray();
        }

        if ($value instanceof DataTransferObject) {
            return $includeHidden ? $value->allValues() : $value->toArray();
        }

        // Handle arrays that may contain DTO instances or nested arrays
        if (is_array($value)) {
            return array_map(
                function (mixed $item) use ($includeHidden): mixed {
                    if ($item instanceof DataTransferObject) {
                        return $includeHidden ? $item->allValues() : $item->toArray();
                    }

                    // Recursively normalize nested arrays (#27)
                    if (is_array($item)) {
                        return $this->normalizeValue($item, $includeHidden);
                    }

                    return $this->normalizeScalar($item);
                },
                $value
            );
        }

        return $this->normalizeScalar($value);
    }

    /**
     * Normalize a scalar or object value to a JSON-serializable primitive.
     *
     * Handles ValueObject (toPrimitive/toArray), BackedEnum (value),
     * UnitEnum (name), and DateTimeInterface (ATOM format).
     * All other values pass through unchanged.
     *
     * @param  mixed  $value  The value to normalize
     * @return mixed The normalized primitive value
     */
    private function normalizeScalar(mixed $value): mixed
    {
        if ($value instanceof ValueObject) {
            // Use columnType() to determine serialization strategy.
            // Single-value VOs (string/integer columns) serialize to their primitive.
            // Composite VOs (json columns) serialize to their array representation.
            $columnType = $value::columnType();

            if ($columnType === 'json') {
                return $value->toArray();
            }

            $primitive = $value->toPrimitive();
            $result = is_scalar($primitive) ? $primitive : $value->toArray();

            return $result;
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

    /**
     * Cast a raw value to a BackedEnum instance.
     *
     * Handles both backed values (string/int) and enum names.
     * This ensures that with() roundtrips work correctly: toArray()
     * serializes enums to their backed value, and this method
     * reconstructs them when hydrating via fromArray().
     *
     * @param  mixed  $value  Raw value (backed value or enum name)
     * @param  class-string<\BackedEnum>  $enumClass
     *
     * @throws \InvalidArgumentException If the value cannot be cast to the enum
     */
    private static function castValueToEnum(mixed $value, string $enumClass): \BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        // Try from() with the raw value (works for string/int backed enums)
        if (is_int($value) || is_string($value)) {
            try {
                return $enumClass::from($value);
            } catch (\ValueError) {
                // from() failed, try tryFrom() for a graceful null check,
                // then fall back to name-based lookup
            }
        }

        // Try matching by name (for cases where the enum name is passed)
        $stringValue = is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);
        if ($stringValue !== null) {
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $stringValue) {
                    return $case;
                }
            }
        }

        throw new \InvalidArgumentException(
            'Cannot cast '.get_debug_type($value).' to enum '.$enumClass
        );
    }

    /**
     * Cast a raw value to a PHP scalar or object type.
     *
     * @param  mixed  $value  Raw value from input data
     * @param  string  $type  Target cast type (e.g. 'int', 'string', 'date')
     * @param  string  $propertyName  Property name for error messages
     *
     * @throws \InvalidArgumentException When the value cannot be cast to date/datetime
     */
    private static function castValue(mixed $value, string $type, string $propertyName): mixed
    {
        return match ($type) {
            'int', 'integer' => is_numeric($value) ? (int) $value : 0,
            'float', 'double' => is_numeric($value) ? (float) $value : 0.0,
            'string' => is_scalar($value) ? (string) $value : (is_object($value) && method_exists($value, '__toString') ? (string) $value : ''),
            'bool', 'boolean' => self::castToBool($value),
            'array' => is_array($value) ? $value : self::decodeJsonArray(is_string($value) ? $value : '', $propertyName),
            'date', 'datetime' => self::castToDate($value, $propertyName),
            default => $value,
        };
    }

    /**
     * Cast a raw value to a boolean.
     *
     * Follows PHP's `filter_var(FILTER_VALIDATE_BOOLEAN)` semantics for
     * string values ("1", "true", "on", "yes" → true; "0", "false", "off",
     * "no", "" → false), with explicit handling for int, float, and null.
     *
     * @param  mixed  $value  Raw value from input data
     * @return bool Always returns a strict boolean (never null)
     */
    private static function castToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0 && $value !== 0.0;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return false;
    }

    /**
     * Cast a raw value to a Carbon instance.
     *
     * Passes through existing DateTimeInterface instances, parses
     * strings/ints/floats via Carbon, returns null for null, and
     * throws for unsupported types.
     *
     * @param  mixed  $value  Raw value from input data
     * @param  string  $propertyName  Property name for error messages
     *
     * @throws \InvalidArgumentException When the value cannot be cast to date/datetime
     */
    private static function castToDate(mixed $value, string $propertyName): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return $value instanceof Carbon ? $value : Carbon::instance($value);
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            return new Carbon($value);
        }

        throw new \InvalidArgumentException(
            "Cannot cast value of type '".get_debug_type($value)."' to date/datetime for property '{$propertyName}'"
        );
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
     * Decode a JSON string to an array.
     *
     * Returns [] for empty strings. Throws DTOException on invalid JSON
     * to prevent silent data loss.
     *
     * @param  string  $value  Raw JSON string
     * @param  string  $propertyName  Property name for error context
     * @return array<string, mixed>
     *
     * @throws DTOException When the JSON is invalid or does not decode to an array
     */
    private static function decodeJsonArray(string $value, string $propertyName = ''): array
    {
        if ($value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw DTOException::invalidJson($propertyName, $e->getMessage());
        }

        if (! is_array($decoded)) {
            throw DTOException::invalidJson($propertyName, 'Expected a JSON array, got '.get_debug_type($decoded));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Hydrate a nested DTO from raw data.
     *
     * If the value is already a DTO instance, returns it as-is.
     * If the value is an array, recursively calls fromArray() on the DTO class.
     *
     * @param  mixed  $value  Raw value from input data
     * @param  class-string<self>  $dtoClass  The nested DTO class to hydrate
     *
     * @throws \InvalidArgumentException If the value cannot be cast to the DTO
     */
    private static function hydrateNestedDto(mixed $value, string $dtoClass): DataTransferObject
    {
        if ($value instanceof $dtoClass) {
            return $value;
        }

        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            return $dtoClass::fromArray($value, validate: false);
        }

        throw new \InvalidArgumentException(
            'Cannot hydrate '.get_debug_type($value)." into nested DTO {$dtoClass}. Expected array or {$dtoClass} instance."
        );
    }

    /**
     * Hydrate a collection of nested DTOs.
     *
     * Each element of the input array is converted to the specified DTO class
     * and the result is wrapped in a {@see DtoCollection}.
     *
     * @param  array<int, mixed>  $values  Raw array of data
     * @param  class-string<self>  $dtoClass  The DTO class for each element
     *
     * @phpstan-return DtoCollection<self>
     */
    private static function hydrateCollection(array $values, string $dtoClass): DtoCollection
    {
        $items = self::hydrateNestedArray($values, $dtoClass);

        return new DtoCollection($items);
    }

    /**
     * Hydrate an array of nested DTOs.
     *
     * Each element of the input array is converted to the specified DTO class.
     * Elements that are already DTO instances are passed through.
     *
     * @param  array<int, mixed>  $values  Raw array of data
     * @param  class-string<self>  $dtoClass  The DTO class for each element
     * @return array<int, DataTransferObject> Array of hydrated DTO instances
     */
    private static function hydrateNestedArray(array $values, string $dtoClass): array
    {
        $result = [];

        foreach ($values as $index => $value) {
            if ($value instanceof $dtoClass) {
                $result[] = $value;
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result[] = $dtoClass::fromArray($value, validate: false);
            } else {
                throw new \InvalidArgumentException(
                    "Cannot hydrate element at index {$index} into {$dtoClass}. Expected array, got ".get_debug_type($value).'.'
                );
            }
        }

        return $result;
    }
}
