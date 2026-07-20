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
        public string $regex,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'regex';
    }
}
