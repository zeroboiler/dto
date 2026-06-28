<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property as boolean.
 *
 *   #[Boolean]
 *   public mixed $acceptsTerms;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
