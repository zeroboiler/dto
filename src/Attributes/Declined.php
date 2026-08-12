<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be declined — must be one of: no, off, 0, false.
 *
 *   #[Declined]
 *   public readonly bool $optOut;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Accepted For the inverse (require accept)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Declined implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be declined.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('declined')
     */
    public function ruleKey(): string
    {
        return 'declined';
    }
}
