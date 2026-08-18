<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as boolean.
 *
 * Accepts `true`, `false`, `1`, `0`, `"1"`, and `"0"`.
 * For type casting of arbitrary truthy/falsy values,
 * use {@see \ZeroBoiler\DTO\Attributes\Cast} with `'boolean'`.
 *
 *   #[Boolean]
 *   public readonly bool $acceptsTerms;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Cast For type casting (handles "yes"/"no", "on"/"off", etc.)
 * @see \ZeroBoiler\DTO\Attributes\Accepted For validating "yes"/"on"/"1"/"true" specifically
 * @see \ZeroBoiler\DTO\Attributes\Declined For validating "no"/"off"/"0"/"false" specifically
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute field must be true or false.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('boolean')
     */
    public function ruleKey(): string
    {
        return 'boolean';
    }
}
