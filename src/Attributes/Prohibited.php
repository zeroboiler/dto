<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field to be absent from the input data.
 *
 *   #[Prohibited]
 *   public ?string $internalField;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Prohibited
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
