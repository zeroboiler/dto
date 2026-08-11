<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Map a source key (from request/array) to a differently-named DTO property.
 *
 * A metadata-only attribute (does not implement
 * {@see \ZeroBoiler\DTO\Contracts\ValidationAttribute}).
 *
 * When hydrating from an array or request, the resolver looks for the mapped
 * key instead of the property name. Supports dot notation for nested keys.
 *
 *   #[MapFrom('user_name')]
 *   public readonly ?string $name;
 *
 *   #[MapFrom('meta.phone')]
 *   public readonly ?string $phone;
 *
 * The mapped key is also used for partial updates
 * ({@see \ZeroBoiler\DTO\DataTransferObject::fromPartialArray()})
 * and validation rule field matching.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapFrom
{
    /**
     * @param  string  $key  The source key name (supports dot notation for nested keys, e.g. 'meta.phone')
     */
    public function __construct(
        public readonly string $key,
    ) {}
}
