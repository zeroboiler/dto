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
 *   #[NestedArray(OrderItemDTO::class)]
 *   public readonly array $items;
 *
 * The DTO class must have a constructor that accepts an array (via fromArray).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class NestedArray implements ValidationAttribute
{
    /**
     * @param  class-string<DataTransferObject>  $dtoClass
     */
    public function __construct(
        public readonly string $dtoClass,
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('array') */
    public function ruleKey(): string
    {
        return 'array';
    }
}
