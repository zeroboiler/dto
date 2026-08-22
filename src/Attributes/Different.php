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
 *   public readonly string $secondaryEmail;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Same For requiring values to match
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Different implements ValidationAttribute
{
    /**
     * @param  string  $field  The name of the other field that this field must differ from
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute and :other must be different.'
     */
    public function __construct(
        public readonly string $field,
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('different')
     */
    public function ruleKey(): string
    {
        return 'different';
    }
}
