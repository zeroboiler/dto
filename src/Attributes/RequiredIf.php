<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field only when another field equals a specific value.
 *
 *   #[RequiredIf('type', 'individual')]
 *   public ?string $firstName;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredIf
{
    public function __construct(
        public string $field,
        public mixed $value = null,
        public ?string $message = null,
    ) {}
}
