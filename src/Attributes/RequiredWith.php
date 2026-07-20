<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field when any of the given fields are present.
 *
 *   #[RequiredWith(['email', 'phone'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWith implements ValidationAttribute
{
    /**
     * @param  array<int, string>  $fields  Field names; required if ANY is present.
     */
    public function __construct(
        public array $fields,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'required_with';
    }
}
