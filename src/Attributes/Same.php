<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field's value to match another field's value.
 *
 *   #[Same('password')]
 *   public readonly string $passwordRepeat;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Same implements ValidationAttribute
{
    public function __construct(
        /** @param string $field The field name to match */
        public readonly string $field,
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('same') */
    public function ruleKey(): string
    {
        return 'same';
    }
}
