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
        public ?int $min = null,
        public ?int $max = null,
        public ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'array';
    }
}
