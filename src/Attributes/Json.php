<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that the value is a valid JSON string.
 *
 *   #[Json]
 *   public string $metadata;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Json implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'json';
    }
}
