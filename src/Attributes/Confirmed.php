<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require a matching `{field}_confirmation` field (e.g., password confirmation).
 *
 *   #[Confirmed]
 *   public string $password;
 *
 * Validates that `password_confirmation` matches `password`.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Confirmed implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'confirmed';
    }
}
