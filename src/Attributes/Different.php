<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field's value to be different from another field's value.
 *
 *   #[Different('email')]
 *   public string $secondaryEmail;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Different
{
    public function __construct(
        public string $field,
        public ?string $message = null,
    ) {}
}
