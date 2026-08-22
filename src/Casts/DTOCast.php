<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Eloquent cast for DTO classes.
 *
 *   protected $casts = [
 *       'payload' => CreateUserDTO::class,
 *   ];
 *
 * Validation is enabled by default on set() to prevent storing invalid data.
 * To opt out (e.g. for performance-critical paths with pre-validated data):
 *
 *   protected function casts(): array
 *   {
 *       return ['payload' => new DTOCast(CreateUserDTO::class, validate: false)];
 *   }
 *
 * @see \ZeroBoiler\DTO\DTOSServiceProvider For auto-detection setup
 * @see \ZeroBoiler\DTO\DataTransferObject For the base DTO class being cast
 * @see \ZeroBoiler\DTO\Exceptions\DTOException For errors during JSON decode
 *
 * @template T of \\ZeroBoiler\\DTO\\DataTransferObject
 *
 * @implements CastsAttributes<T|null, T|array<string, mixed>|null>
 */
final class DTOCast implements CastsAttributes
{
    /**
     * @param  class-string<T>  $dtoClass
     * @param  bool  $validate  Whether to validate arrays passed to set() (default: true)
     */
    public function __construct(
        private readonly string $dtoClass,
        private readonly bool $validate = true,
    ) {}

    /**
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being cast
     * @param  mixed  $value  The raw value from the database (JSON string or array)
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return T|null
     */
    public function get(object $model, string $key, mixed $value, array $attributes): ?DataTransferObject
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            try {
                /** @var mixed $data */
                $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
            } catch (\JsonException) {
                return null;
            }
        } else {
            $data = $value;
        }

        if (! is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        /** @var class-string<T> $dtoClass */
        $dtoClass = $this->dtoClass;

        return $dtoClass::fromArray($data, validate: false);
    }

    /**
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being cast
     * @param  DataTransferObject|array<string, mixed>|null  $value  The DTO, array, or null to store
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return array<string, mixed>|string|null
     *
     * @throws \InvalidArgumentException When value is not a DTO, array, or null
     * @throws ValidationException When validation is enabled and data is invalid
     */
    public function set(object $model, string $key, mixed $value, array $attributes): array|string|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DataTransferObject) {
            try {
                return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return '';
            }
        }

        if (is_array($value)) {
            /** @var class-string<T> $dtoClass */
            $dtoClass = $this->dtoClass;

            // Hydrate through the DTO to ensure consistent serialization
            // (applies defaults, casts, etc.) and optionally validate (#8).
            $dto = $dtoClass::fromArray($value, validate: $this->validate);

            try {
                return json_encode($dto->toArray(), JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return '';
            }
        }

        // Reject unexpected types to prevent silent data corruption (#8)
        throw new \InvalidArgumentException(
            'DTOCast::set() expects a DTO instance, array, or null; got '.get_debug_type($value)
        );
    }

    /**
     * Serialize the DTO for JSON casting.
     *
     * Laravel calls this method when serializing model attributes to JSON.
     * Not part of the CastsAttributes interface — Laravel detects it via method_exists().
     *
     * @param  object  $model  The Eloquent model instance
     * @param  string  $key  The attribute name being serialized
     * @param  mixed  $value  The raw attribute value (typically DataTransferObject|null)
     * @param  array<string, mixed>  $attributes  All model attributes
     * @return array<string, mixed>|null The DTO's toArray() output, or null
     */
    public function serialize(object $model, string $key, mixed $value, array $attributes): ?array
    {
        return $value?->toArray();
    }
}
