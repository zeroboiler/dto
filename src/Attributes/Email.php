<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as email address.
 *
 *   #[Email]
 *   public readonly string $email;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Url For URL validation
 * @see \ZeroBoiler\DTO\Attributes\Uuid For UUID format validation
 * @see \ZeroBoiler\DTO\Attributes\Max For constraining email length
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Email implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be a valid email address.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('email')
     */
    public function ruleKey(): string
    {
        return 'email';
    }
}
