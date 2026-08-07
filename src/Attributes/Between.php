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
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Between implements ValidationAttribute
{
    public function __construct(
        /** @param int|float $min Minimum value */
        public readonly int|float $min,
        /** @param int|float $max Maximum value */
        public readonly int|float $max,
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('between') */
    public function ruleKey(): string
    {
        return 'between';
    }
}
