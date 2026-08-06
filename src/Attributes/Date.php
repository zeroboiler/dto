<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as a date or date format.
 *
 *   #[Date]
 *   public string $birthDate;
 *
 *   #[Date(format: 'd-m-Y')]
 *   public string $birthDate;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Date implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $format = null,
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'date';
    }
}
