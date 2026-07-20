<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require array elements to be distinct (no duplicates).
 *
 *   #[Distinct]
 *   public array $tags;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Distinct
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
