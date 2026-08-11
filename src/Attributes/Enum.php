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
 * Uses Laravel's `Rule::enum()` validation rule internally, ensuring
 * the property value matches a valid backed value of the target enum.
 *
 * When combined with the enums package (zeroboiler/enums), the enum
 * value is also auto-cast during hydration via
 * {@see \ZeroBoiler\DTO\DataTransferObject::castValueToEnum()}.
 * This means you can pass the backed value (e.g., 'active') and the
 * DTO property will hold the actual enum case instance.
 *
 *   #[Enum(UserStatus::class)]
 *   public readonly UserStatus $status;
 *
 * Requires zeroboiler/enums for full integration (hydration casting).
 * For validation-only usage, any backed enum class works.
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
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

    /**
     * @return string The Laravel validation rule key ('enum')
     */
    public function ruleKey(): string
    {
        return 'enum';
    }
}
