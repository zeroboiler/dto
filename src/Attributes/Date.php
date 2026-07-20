<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

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
final class Date
{
    public function __construct(
        public ?string $format = null,
        public ?string $message = null,
    ) {}
}
