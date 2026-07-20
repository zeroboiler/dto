<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field when any of the given fields are absent.
 *
 *   #[RequiredWithout(['email'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithout
{
    /**
     * @param  array<int, string>  $fields  Field names; required if ANY is absent.
     */
    public function __construct(
        public array $fields,
        public ?string $message = null,
    ) {}
}
