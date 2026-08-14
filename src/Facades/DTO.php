<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * DTO facade — runtime access to DTO operations via the DTOManager singleton.
 *
 * Provides a clean interface for DTO creation, validation, JSON hydration,
 * rules retrieval, and OpenAPI schema generation without directly calling
 * static methods on DTO classes.
 *
 *   DTO::make(CreateUserDTO::class, $data);
 *   DTO::validate(CreateUserDTO::class, $data);
 *   DTO::makeFromJson(CreateUserDTO::class, $json);
 *   DTO::rules(CreateUserDTO::class);
 *   DTO::rulesFor(CreateUserDTO::class, 'update');
 *   DTO::schema(CreateUserDTO::class);
 *
 * @see \ZeroBoiler\DTO\DTOManager For the underlying singleton implementation
 *
 * @mixin \ZeroBoiler\DTO\DTOManager
 *
 * @method static array validate(string $dtoClass, array $data) Validate data against a DTO class.
 * @method static \ZeroBoiler\DTO\DataTransferObject make(string $dtoClass, array $data) Create a DTO instance from data.
 * @method static \ZeroBoiler\DTO\DataTransferObject makeFromJson(string $dtoClass, string $json) Create a DTO instance from a JSON string.
 * @method static \ZeroBoiler\DTO\DataTransferObject fromJson(string $dtoClass, string $json) Create a DTO instance from a JSON string.
 * @method static \ZeroBoiler\DTO\DataTransferObject fromPartialArray(string $dtoClass, array $data) Create a DTO instance from partial data (PATCH).
 * @method static \ZeroBoiler\DTO\DataTransferObject fromPartialRequest(string $dtoClass, \Illuminate\Http\Request $request) Create a DTO instance from a partial request (PATCH).
 * @method static array rules(string $dtoClass) Get validation rules for a DTO class.
 * @method static array rulesFor(string $dtoClass, string $action) Get action-scoped validation rules.
 * @method static array schema(string $dtoClass) Generate OpenAPI schema for a DTO class.
 */
final class DTO extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'zeroboiler.dto';
    }
}
