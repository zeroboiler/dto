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
 *   public int $quantity;
 *
 *   #[Between(10, 255)]
 *   public string $description;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Between implements ValidationAttribute
{
    public function __construct(
        public int|float $min,
        public int|float $max,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'between';
    }
}
