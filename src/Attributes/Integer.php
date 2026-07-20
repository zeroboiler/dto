<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property as integer.
 *
 *   #[Integer]
 *   public int|string $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Integer
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
