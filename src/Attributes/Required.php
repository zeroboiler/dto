<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Mark a property as required.
 *
 *   #[Required]
 *   public string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'required';
    }
}
