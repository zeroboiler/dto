<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field only when another field equals a specific value.
 *
 *   #[RequiredIf('type', 'individual')]
 *   public ?string $firstName;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredIf implements ValidationAttribute
{
    /**
     * @param  string  $field  The name of the other field to check
     * @param  mixed  $value  The value that triggers the requirement (string, int, bool, or array)
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly string $field,
        public readonly mixed $value = null,
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('required_if') */
    public function ruleKey(): string
    {
        return 'required_if';
    }
}
