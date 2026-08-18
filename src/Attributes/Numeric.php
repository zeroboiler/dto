<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as numeric (integer or float).
 *
 * Accepts integers and floating-point numbers, including numeric strings.
 * For integer-only validation, use {@see Integer}.
 *
 *   #[Numeric]
 *   public readonly string|float|int $price;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Integer For integer-only validation
 * @see \ZeroBoiler\DTO\Attributes\Min For minimum value constraint
 * @see \ZeroBoiler\DTO\Attributes\Max For maximum value constraint
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Numeric implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be a number.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('numeric')
     */
    public function ruleKey(): string
    {
        return 'numeric';
    }
}
