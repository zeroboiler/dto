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
 *   public readonly string $password;
 *
 * Validates that `password_confirmation` matches `password`.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Confirmed implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute confirmation does not match.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('confirmed')
     */
    public function ruleKey(): string
    {
        return 'confirmed';
    }
}
