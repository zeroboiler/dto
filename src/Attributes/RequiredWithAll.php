<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field when ALL of the given fields are present.
 *
 *   #[RequiredWithAll(['email', 'phone'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithAll
{
    /**
     * @param  array<int, string>  $fields  Field names; required if ALL are present.
     */
    public function __construct(
        public array $fields,
        public ?string $message = null,
    ) {}
}
