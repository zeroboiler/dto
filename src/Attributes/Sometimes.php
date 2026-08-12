<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Only run validation rules when the field is present in the input data.
 *
 *   #[Sometimes]
 *   public readonly ?string $nickname;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Nullable For allowing null values
 * @see \ZeroBoiler\DTO\Attributes\Present For requiring field presence (even if null)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sometimes implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute validation should only run if the field is present.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('sometimes')
     */
    public function ruleKey(): string
    {
        return 'sometimes';
    }
}
