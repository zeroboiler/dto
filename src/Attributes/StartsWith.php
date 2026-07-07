<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate that a string starts with the given prefix(es).
 *
 *   #[StartsWith('https://')]
 *   public string $url;
 *
 *   #[StartsWith(['+90', '+1'])]
 *   public string $phone;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class StartsWith
{
    /**
     * @param  string|array<int, string>  $prefix
     */
    public function __construct(
        public string|array $prefix,
        public ?string $message = null,
    ) {}
}
