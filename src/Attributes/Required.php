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
 * In partial update mode ({@see \ZeroBoiler\DTO\DataTransferObject::fromPartialArray()}),
 * `required` is automatically relaxed to `sometimes` for present fields only.
 *
 *   #[Required]
 *   public readonly string $email;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Nullable For allowing null values
 * @see \ZeroBoiler\DTO\Attributes\Present For requiring field presence without requiring a value
 * @see \ZeroBoiler\DTO\Attributes\Prohibited For preventing the field entirely
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute field is required.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('required')
     */
    public function ruleKey(): string
    {
        return 'required';
    }
}
