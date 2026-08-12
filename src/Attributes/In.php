<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property value is in a list of allowed values.
 *
 *   #[In(['draft', 'published', 'archived'])]
 *   public readonly string $status;
 *
 * Or with enum:
 *   #[Enum(UserStatus::class)]
 *   public readonly string $status;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\Enum For enum-based value validation (preferred for backed enums)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class In implements ValidationAttribute
{
    /**
     * @param  array<int, string|int>  $values  List of allowed values
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly array $values,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('in')
     */
    public function ruleKey(): string
    {
        return 'in';
    }
}
