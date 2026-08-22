<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be absent from the input data.
 *
 *   #[Prohibited]
 *   public readonly ?string $internalField;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Prohibited implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute field is prohibited.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('prohibited')
     */
    public function ruleKey(): string
    {
        return 'prohibited';
    }
}
