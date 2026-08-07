<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as integer.
 *
 *   #[Integer]
 *   public readonly int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Integer implements ValidationAttribute
{
    public function __construct(
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('integer') */
    public function ruleKey(): string
    {
        return 'integer';
    }
}
