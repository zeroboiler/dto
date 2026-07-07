<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate that a string ends with the given suffix(es).
 *
 *   #[EndsWith('@company.com')]
 *   public string $workEmail;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EndsWith
{
    /**
     * @param  string|array<int, string>  $suffix
     */
    public function __construct(
        public string|array $suffix,
        public ?string $message = null,
    ) {}
}
