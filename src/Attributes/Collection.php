<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Mark an array property as a type-safe collection of DTO instances.
 *
 * During hydration, each element will be converted to the specified DTO class.
 * During serialization, the value is returned as an array of normalized DTOs.
 *
 *   #[Collection(UserDTO::class)]
 *   public readonly array $users;
 *
 * Unlike {@see NestedArray}, Collection also wraps the result in a
 * {@see \ZeroBoiler\DTO\Collection} instance providing type-safe access.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Collection
{
    /**
     * @param  class-string<DataTransferObject>  $dtoClass
     */
    public function __construct(
        public string $dtoClass,
        public ?string $message = null,
    ) {}
}
