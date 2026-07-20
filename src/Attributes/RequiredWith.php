<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field when any of the given fields are present.
 *
 *   #[RequiredWith(['email', 'phone'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWith
{
    /**
     * @param  array<int, string>  $fields  Field names; required if ANY is present.
     */
    public function __construct(
        public array $fields,
        public ?string $message = null,
    ) {}
}
