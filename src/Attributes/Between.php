<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a numeric value or string length is between two bounds.
 *
 *   #[Between(1, 100)]
 *   public readonly int $quantity;
 *
 *   #[Between(10, 255)]
 *   public readonly string $description;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Min For minimum bound only
 * @see \ZeroBoiler\DTO\Attributes\Max For maximum bound only
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Between implements ValidationAttribute
{
    /**
     * @param  int|float  $min  Minimum bound (inclusive)
     * @param  int|float  $max  Maximum bound (inclusive)
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be between :min and :max.'
     */
    public function __construct(
        public readonly int|float $min,
        public readonly int|float $max,
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('between')
     */
    public function ruleKey(): string
    {
        return 'between';
    }
}
