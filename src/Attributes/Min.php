<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Minimum length/value constraint.
 *
 *   #[Min(3)]
 *   public string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Min
{
    public function __construct(
        public int $value,
        public ?string $message = null,
    ) {}
}
