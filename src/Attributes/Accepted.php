<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be accepted — must be one of: yes, on, 1, true.
 *
 *   #[Accepted]
 *   public mixed $terms;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Accepted implements ValidationAttribute
{
    public function __construct(
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'accepted';
    }
}
