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
 *   #[Boolean]
 *   public readonly bool $acceptsTerms;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('boolean') */
    public function ruleKey(): string
    {
        return 'boolean';
    }
}
