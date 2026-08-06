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
 *   public ?string $internalField;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Prohibited implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'prohibited';
    }
}
