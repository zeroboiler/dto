<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Explicitly allow the field to be null.
 *
 * When applied, the Laravel `nullable` rule is added so that null values
 * pass validation. This is useful when you want to make the nullability
 * explicit in the validation layer, even if the PHP type already uses `?`
 * (e.g., `?string $bio`).
 *
 * Note: Properties with `?` type hints are already nullable at the PHP level.
 * This attribute is primarily for controlling validation behavior — ensuring
 * that `null` passes Laravel's validator without triggering `required` errors.
 *
 *   #[Nullable]
 *   public readonly ?string $bio;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Sometimes For conditional validation (only validate when field is present)
 * @see \ZeroBoiler\DTO\Attributes\Present For requiring field presence (even if null)
 * @see \ZeroBoiler\DTO\Attributes\Required For requiring a non-null value
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nullable implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute may be null.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('nullable')
     */
    public function ruleKey(): string
    {
        return 'nullable';
    }
}
