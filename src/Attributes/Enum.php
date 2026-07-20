<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property against a backed enum class.
 *
 *   #[Enum(UserStatus::class)]
 *   public string $status;
 *
 * Requires zeroboiler/enums for full integration.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Enum
{
    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    public function __construct(
        public string $enumClass,
        public ?string $message = null,
    ) {}
}
