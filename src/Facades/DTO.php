<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Facades;

use Illuminate\Support\Facades\Facade;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * @method static array<string, mixed> validate(string $dtoClass, array<string, mixed> $data)
 * @method static DataTransferObject make(string $dtoClass, array<string, mixed> $data)
 * @method static array<string, mixed> schema(string $dtoClass)
 */
final class DTO extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.dto';
    }
}
