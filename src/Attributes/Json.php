<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that the value is a valid JSON string.
 *
 *   #[Json]
 *   public readonly string $metadata;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Cast For casting JSON strings to arrays (use #[Cast('array')] for hydration)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Json implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be a valid JSON string.'
     */
    public function __construct(
        public ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('json')
     */
    public function ruleKey(): string
    {
        return 'json';
    }
}
