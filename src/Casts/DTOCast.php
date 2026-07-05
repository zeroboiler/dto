<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Eloquent cast for DTO classes.
 *
 *   protected $casts = [
 *       'payload' => CreateUserDTO::class,
 *   ];
 *
 *   // With validation on set (ensures data integrity on save):
 *   protected function casts(): array
 *   {
 *       return ['payload' => (new DTOCast(CreateUserDTO::class, validate: true))];
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
     * @param  bool  $validate  Whether to validate arrays passed to set()
     */
    public function __construct(
        private readonly string $dtoClass,
        private readonly bool $validate = false,
    ) {}

    /**
     * @param  Model  $model
     * @return T|null
     */
    public function get($model, string $key, $value, array $attributes)
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
     * @param  Model  $model
     * @param  T|array<string, mixed>|null  $value
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DataTransferObject) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            // Validate the array data if validation is enabled
            if ($this->validate) {
                /** @var class-string<T> $dtoClass */
                $dtoClass = $this->dtoClass;
                $dtoClass::fromArray($value, validate: true);
            }

            return json_encode($value);
        }

        return $value;
    }

    /**
     * @param  Model  $model
     * @param  T|null  $value
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return $value?->toArray();
    }
}
