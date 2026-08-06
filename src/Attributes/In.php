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
 *   public string $status;
 *
 * Or with enum:
 *   #[Enum(UserStatus::class)]
 *   public string $status;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class In implements ValidationAttribute
{
    /**
     * @param  array<int, string|int>  $values
     */
    public function __construct(
        public readonly array $values,
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'in';
    }
}
