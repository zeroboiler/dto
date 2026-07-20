<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Only run validation rules when the field is present in the input data.
 *
 *   #[Sometimes]
 *   public ?string $nickname;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sometimes
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
