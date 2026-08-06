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
 *   public readonly string $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Integer implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'integer';
    }
}
