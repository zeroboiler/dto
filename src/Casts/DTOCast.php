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
 * @template T of \ZeroBoiler\DTO\DataTransferObject
 *
 * @implements CastsAttributes<T, T|array<string, mixed>|null>
 */
class DTOCast implements CastsAttributes
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
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array<string, mixed>  $attributes
     * @return T|null
     */
    public function get(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($data)) {
            return null;
        }

        /** @var class-string<T> $dtoClass */
        $dtoClass = $this->dtoClass;

        return $dtoClass::fromArray($data, validate: false);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  T|array<string, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     *
     * @throws \InvalidArgumentException When value is not a DTO, array, or null
     * @throws ValidationException When validation is enabled and data is invalid
     * @return array<string, mixed>|null
     */
    public function set(object $model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DataTransferObject) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            /** @var class-string<T> $dtoClass */
            $dtoClass = $this->dtoClass;

            // Hydrate through the DTO to ensure consistent serialization
            // (applies defaults, casts, etc.) and optionally validate (#8).
            $dto = $dtoClass::fromArray($value, validate: $this->validate);

            return json_encode($dto->toArray());
        }

        // Reject unexpected types to prevent silent data corruption (#8)
        throw new \InvalidArgumentException(
            'DTOCast::set() expects a DTO instance, array, or null; got '.get_debug_type($value)
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  T|null  $value
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    public function serialize(object $model, string $key, $value, array $attributes)
    {
        return $value?->toArray();
    }
}
