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
 *   public string $code;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Pattern implements ValidationAttribute
{
    public function __construct(
        /** @param string $regex The regular expression pattern (with delimiters) */
        public readonly string $regex,
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('regex') */
    public function ruleKey(): string
    {
        return 'regex';
    }
}
