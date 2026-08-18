<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as a valid URL.
 *
 *   #[Url]
 *   public readonly string $website;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Email For email format validation
 * @see \ZeroBoiler\DTO\Attributes\Uuid For UUID format validation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Url implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be a valid URL.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('url')
     */
    public function ruleKey(): string
    {
        return 'url';
    }
}
