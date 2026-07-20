<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be present in the input data (even if null).
 *
 *   #[Present]
 *   public ?string $apiKey;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Present implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'present';
    }
}
