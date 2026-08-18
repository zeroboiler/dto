<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Maximum length/value constraint.
 *
 * For strings, validates maximum length. For numeric values, validates maximum value.
 *
 *   #[Max(255)]
 *   public readonly string $name;
 *
 *   #[Max(100)]
 *   public readonly int $score;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Min For the minimum constraint
 * @see \ZeroBoiler\DTO\Attributes\Between For combined min/max constraint
 * @see \ZeroBoiler\DTO\Attributes\Size For exact length/value constraint
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max implements ValidationAttribute
{
    /**
     * @param  int|float  $value  Maximum value (numeric) or length (string)
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must not be greater than :max characters.'
     */
    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('max')
     */
    public function ruleKey(): string
    {
        return 'max';
    }
}
