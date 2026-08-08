<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as numeric.
 *
 *   #[Numeric]
 *   public readonly string|float|int $price;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Numeric implements ValidationAttribute
{
    public function __construct(
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('numeric') */
    public function ruleKey(): string
    {
        return 'numeric';
    }
}
