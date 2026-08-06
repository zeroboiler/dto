<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require array elements to be distinct (no duplicates).
 *
 *   #[Distinct]
 *   public array $tags;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Distinct implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'distinct';
    }
}
