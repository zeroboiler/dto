<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a field is an array, optionally with min/max count.
 *
 *   #[ArrayRule]
 *   public readonly array $tags;
 *
 *   #[ArrayRule(min: 1, max: 10)]
 *   public readonly array $items;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\NestedArray For arrays of nested DTO instances
 * @see \ZeroBoiler\DTO\Attributes\Collection For DtoCollection-wrapped nested DTOs
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayRule implements ValidationAttribute
{
    /**
     * @param  int|null  $min  Minimum number of elements (null = no minimum)
     * @param  int|null  $max  Maximum number of elements (null = no maximum)
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must have between :min and :max items.'
     */
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
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
