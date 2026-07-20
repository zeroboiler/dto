<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Explicitly allow the field to be null.
 *
 *   #[Nullable]
 *   public ?string $bio;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nullable implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'nullable';
    }
}
