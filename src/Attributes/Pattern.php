<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property against a regex pattern.
 *
 *   #[Pattern('/^[A-Z]{3}-\d{4}$/')]
 *   public readonly string $code;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Pattern implements ValidationAttribute
{
    /**
     * @param  string  $regex  The regular expression pattern (with delimiters, e.g. '/^[A-Z]{3}$/')
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute format is invalid.'
     */
    public function __construct(
        public readonly string $regex,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('regex')
     */
    public function ruleKey(): string
    {
        return 'regex';
    }
}
