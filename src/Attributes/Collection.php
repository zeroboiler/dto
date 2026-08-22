<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Mark an array property as a type-safe collection of DTO instances.
 *
 * During hydration, each element will be converted to the specified DTO class
 * and wrapped in a {@see \ZeroBoiler\DTO\DtoCollection} instance.
 * During serialization, the value is returned as an array of normalized DTOs.
 *
 *   #[Collection(OrderItemDTO::class)]
 *   public readonly DtoCollection $items;
 *
 * Unlike {@see \ZeroBoiler\DTO\Attributes\NestedArray} (which produces a plain PHP array),
 * Collection wraps the result in a {@see \ZeroBoiler\DTO\DtoCollection} providing
 * type-safe access methods (first, last, map, filter, pluck, etc.).
 *
 * @see \ZeroBoiler\DTO\DtoCollection For the collection wrapper class
 * @see \ZeroBoiler\DTO\Attributes\NestedArray For plain array of nested DTOs (without DtoCollection wrapper)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Collection implements ValidationAttribute
{
    /**
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class for each collection element
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly string $dtoClass,
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('array')
     */
    public function ruleKey(): string
    {
        return 'array';
    }
}
