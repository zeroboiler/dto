<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property as a valid UUID.
 *
 * Accepts UUID v1, v3, v4, and v5 formats.
 *
 *   #[Uuid]
 *   public readonly string $id;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Email For email format validation
 * @see \ZeroBoiler\DTO\Attributes\Url For URL format validation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Uuid implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute must be a valid UUID.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('uuid')
     */
    public function ruleKey(): string
    {
        return 'uuid';
    }
}
