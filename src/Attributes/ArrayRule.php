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
 *   public readonly array $tags;
 *
 *   #[ArrayRule(min: 1, max: 10)]
 *   public readonly array $items;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayRule implements ValidationAttribute
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('array')
     */
    public function ruleKey(): string
    {
        return 'array';
    }
}
