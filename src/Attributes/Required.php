<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Mark a property as required.
 *
 * Generates a `required` Laravel validation rule. When used on a property,
 * the field must be present in the input data and must not be empty.
 *
 * In partial update mode ({@see fromPartialArray()}), `required` is
 * automatically relaxed to `sometimes` for present fields only.
 *
 *   #[Required]
 *   public readonly string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute field is required.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('required') */
    public function ruleKey(): string
    {
        return 'required';
    }
}
