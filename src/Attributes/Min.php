<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Minimum length/value constraint.
 *
 *   #[Min(3)]
 *   public string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Min implements ValidationAttribute
{
    public function __construct(
        public readonly int $value,
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('min') */
    public function ruleKey(): string
    {
        return 'min';
    }
}
