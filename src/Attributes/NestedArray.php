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
 * Mark an array property as containing nested DTO instances.
 * During hydration, each element of the array will be converted to the DTO.
 *
 * Produces a plain PHP array of DTO instances. For a type-safe collection
 * wrapper with additional methods (first, last, map, filter, pluck, etc.),
 * use {@see \ZeroBoiler\DTO\Attributes\Collection} instead.
 *
 *   #[NestedArray(OrderItemDTO::class)]
 *   public readonly array $items;
 *
 * The DTO class must have a constructor that accepts an array (via fromArray).
 *
 * @see \ZeroBoiler\DTO\Attributes\Collection For DtoCollection-wrapped nested DTOs
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class NestedArray implements ValidationAttribute
{
    /**
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class for each array element
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly string $dtoClass,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('array')
     */
    public function ruleKey(): string
    {
        return 'array';
    }
}
