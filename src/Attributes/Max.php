<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Maximum length/value constraint.
 *
 *   #[Max(255)]
 *   public string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max implements ValidationAttribute
{
    public function __construct(
        public int $value,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'max';
    }
}
