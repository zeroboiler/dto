<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require a matching `{field}_confirmation` field (e.g., password confirmation).
 *
 *   #[Confirmed]
 *   public string $password;
 *
 * Validates that `password_confirmation` matches `password`.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Confirmed
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
