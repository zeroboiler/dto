<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Explicitly allow the field to be null.
 *
 *   #[Nullable]
 *   public ?string $bio;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nullable
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
