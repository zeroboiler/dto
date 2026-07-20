<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate that the value is a valid JSON string.
 *
 *   #[Json]
 *   public string $metadata;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Json
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
