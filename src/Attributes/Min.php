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
 * For strings, validates minimum length. For numeric values, validates minimum value.
 *
 *   #[Min(3)]
 *   public readonly string $name;
 *
 *   #[Min(0)]
 *   public readonly int $age;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Max For the maximum constraint
 * @see \ZeroBoiler\DTO\Attributes\Between For combined min/max constraint
 * @see \ZeroBoiler\DTO\Attributes\Size For exact length/value constraint
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Min implements ValidationAttribute
{
    /**
     * @param  int|float  $value  Minimum value (numeric) or length (string)
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be at least :min characters.'
     */
    public function __construct(
        public readonly int|float $value,
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('min')
     */
    public function ruleKey(): string
    {
        return 'min';
    }
}
