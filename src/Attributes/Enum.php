<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate property against a backed enum class.
 *
 *   #[Enum(UserStatus::class)]
 *   public string $status;
 *
 * Requires zeroboiler/enums for full integration.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Enum implements ValidationAttribute
{
    /**
     * @param  class-string<\BackedEnum>  $enumClass  The enum class to validate against
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The selected :attribute is invalid.'
     */
    public function __construct(
        public readonly string $enumClass,
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('enum') */
    public function ruleKey(): string
    {
        return 'enum';
    }
}
