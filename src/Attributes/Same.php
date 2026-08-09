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
    /**
     * @param  string  $field  The name of the other field that this field must match
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute and :other must match.'
     */
    public function __construct(
        public readonly string $field,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('same')
     */
    public function ruleKey(): string
    {
        return 'same';
    }
}
