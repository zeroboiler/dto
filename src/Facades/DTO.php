<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array validate(string $dtoClass, array $data)
 * @method static \ZeroBoiler\DTO\DataTransferObject make(string $dtoClass, array $data)
 * @method static array schema(string $dtoClass)
 */
final class DTO extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.dto';
    }
}
