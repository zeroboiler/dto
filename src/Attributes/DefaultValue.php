<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Provide a default value when the source key is entirely absent from input data.
 *
 * A metadata-only attribute (does not implement
 * {@see \ZeroBoiler\DTO\Contracts\ValidationAttribute}).
 *
 * Applied during hydration: if the mapped source key (see {@see \ZeroBoiler\DTO\Attributes\MapFrom})
 * is not present in the input array, this default is used. Explicit `null`
 * or empty string values are NOT overridden — only truly absent keys trigger
 * the default.
 *
 * Takes priority over PHP constructor default values when both are present.
 *
 *   #[DefaultValue('active')]
 *   public readonly string $status;
 *
 *   #[DefaultValue([])]
 *   public readonly array $tags;
 *
 *   #[DefaultValue(42)]
 *   public readonly int $perPage;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class DefaultValue
{
    /**
     * @param  mixed  $value  The default value to use when the source key is absent.
     *                       Can be any type: string, int, float, bool, array, or null.
     */
    public function __construct(
        public readonly mixed $value = null,
    ) {}
}
