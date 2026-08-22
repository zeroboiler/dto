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
 * Validates that the value is a whole number (no decimals).
 * For float values, use {@see Numeric} instead.
 *
 *   #[Integer]
 *   public readonly int $age;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Numeric For numeric validation (allows floats)
 * @see \ZeroBoiler\DTO\Attributes\Min For minimum value constraint
 * @see \ZeroBoiler\DTO\Attributes\Max For maximum value constraint
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Integer implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be an integer.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('integer')
     */
    public function ruleKey(): string
    {
        return 'integer';
    }
}
