<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field's value to be different from another field's value.
 *
 *   #[Different('email')]
 *   public string $secondaryEmail;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Different implements ValidationAttribute
{
    public function __construct(
        /** @param string $field The field name to compare against */
        public readonly string $field,
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('different') */
    public function ruleKey(): string
    {
        return 'different';
    }
}
