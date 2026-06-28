<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property as URL.
 *
 *   #[Url]
 *   public string $website;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Url
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
