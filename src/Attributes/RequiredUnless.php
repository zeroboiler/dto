<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field unless another field equals a specific value.
 *
 *   #[RequiredUnless('type', 'company')]
 *   public ?string $firstName;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredUnless implements ValidationAttribute
{
    public function __construct(
        public string $field,
        public mixed $value = null,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'required_unless';
    }
}
