<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field's value to match another field's value.
 *
 *   #[Same('password')]
 *   public string $passwordRepeat;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Same
{
    public function __construct(
        public string $field,
        public ?string $message = null,
    ) {}
}
