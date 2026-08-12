<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a value has an exact size (length for strings, count for arrays).
 *
 *   #[Size(5)]
 *   public readonly string $code;
 *
 *   #[Size(3)]
 *   public readonly array $items;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Min For minimum size constraint
 * @see \ZeroBoiler\DTO\Attributes\Max For maximum size constraint
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Size implements ValidationAttribute
{
    /**
     * @param  int  $value  The exact size: length for strings, count for arrays, or value for numbers
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be :size characters.'
     */
    public function __construct(
        public readonly int $value,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('size')
     */
    public function ruleKey(): string
    {
        return 'size';
    }
}
