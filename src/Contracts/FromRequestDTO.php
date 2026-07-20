<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Contracts;

use Illuminate\Http\Request;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Contract for DTOs that can be hydrated from an HTTP request.
 *
 * ResourceController calls `$dtoClass::fromRequest($request)`
 * to hydrate DTOs. This contract makes that requirement explicit.
 *
 * @see DataTransferObject
 */
interface FromRequestDTO
{
    /**
     * Create a DTO instance from an HTTP request.
     *
     * @param  bool  $validate  Run validation before hydration
     */
    public static function fromRequest(Request $request, bool $validate = true): static;
}
