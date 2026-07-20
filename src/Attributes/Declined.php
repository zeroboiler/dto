<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be declined — must be one of: no, off, 0, false.
 *
 *   #[Declined]
 *   public mixed $optOut;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Declined implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'declined';
    }
}
