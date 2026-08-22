<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as a date or date format.
 *
 *   #[Date]
 *   public readonly string $birthDate;
 *
 *   #[Date(format: 'd-m-Y')]
 *   public readonly string $birthDate;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Cast For type casting to Carbon (use #[Cast('date')] for hydration)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Date implements ValidationAttribute
{
    /**
     * @param  string|null  $format  Optional date format string (e.g. 'Y-m-d', 'd/m/Y').
     *                              When null, validates as a generic date.
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute is not a valid date.'
     */
    public function __construct(
        public ?string $format = null,
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('date')
     */
    public function ruleKey(): string
    {
        return 'date';
    }
}
