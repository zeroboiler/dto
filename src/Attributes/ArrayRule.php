<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a field is an array, optionally with min/max count.
 *
 *   #[ArrayRule]
 *   public array $tags;
 *
 *   #[ArrayRule(min: 1, max: 10)]
 *   public array $items;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayRule implements ValidationAttribute
{
    public function __construct(
        /** @param int|null $min Minimum item count */
        public readonly ?int $min = null,
        /** @param int|null $max Maximum item count */
        public readonly ?int $max = null,
        /** @param string|null $message Custom validation message */
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('array') */
    public function ruleKey(): string
    {
        return 'array';
    }
}
