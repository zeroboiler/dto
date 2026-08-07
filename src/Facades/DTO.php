<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Facades;

use Illuminate\Support\Facades\Facade;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO facade — runtime access to DTO operations via the DTOManager singleton.
 *
 * Provides a clean interface for DTO creation, validation, JSON hydration,
 * and OpenAPI schema generation without directly calling static methods.
 *
 *   DTO::make(CreateUserDTO::class, $data);
 *   DTO::validate(CreateUserDTO::class, $data);
 *   DTO::makeFromJson(CreateUserDTO::class, $json);
 *   DTO::schema(CreateUserDTO::class);
 *
 * @see \ZeroBoiler\DTO\DTOManager For the underlying singleton implementation
 *
 * @mixin \ZeroBoiler\DTO\DTOManager
 *
 * @method static array<string, mixed> validate(string $dtoClass, array<string, mixed> $data) Validate data against a DTO class.
 * @method static \ZeroBoiler\DTO\DataTransferObject make(string $dtoClass, array<string, mixed> $data) Create a DTO instance from data.
 * @method static \ZeroBoiler\DTO\DataTransferObject makeFromJson(string $dtoClass, string $json) Create a DTO instance from a JSON string.
 * @method static array<string, mixed> schema(string $dtoClass) Generate OpenAPI schema for a DTO class.
 */
final class DTO extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.dto';
    }
}
