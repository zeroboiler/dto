<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate that a value has an exact size (length for strings, count for arrays).
 *
 *   #[Size(5)]
 *   public string $code;
 *
 *   #[Size(3)]
 *   public array $items;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Size
{
    public function __construct(
        public int $value,
        public ?string $message = null,
    ) {}
}
