<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent cast for DTO classes.
 *
 *   protected $casts = [
 *       'payload' => CreateUserDTO::class,
 *   ];
 *
 * @template T of \ZeroBoiler\DTO\DataTransferObject
 */
class DTOCast implements CastsAttributes
{
    /** @var class-string<T> */
    private string $dtoClass;

    /**
     * @param  class-string<T>  $dtoClass
     */
    public function __construct(string $dtoClass)
    {
        $this->dtoClass = $dtoClass;
    }

    /**
     * @return T|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($data)) {
            return null;
        }

        /** @var T $dtoClass */
        $dtoClass = $this->dtoClass;

        return $dtoClass::fromArray($data, validate: false);
    }

    /**
     * @param  T|array<string, mixed>|null  $value
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \ZeroBoiler\DTO\DataTransferObject) {
            return json_encode($value->toArray());
        }

        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * @param  T|null  $value
     * @return array<string, mixed>|null
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return $value?->toArray();
    }
}
