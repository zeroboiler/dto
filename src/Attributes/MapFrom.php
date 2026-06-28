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
 *   #[MapFrom('user_name')]
 *   public string $name;
 *
 *   #[MapFrom('meta.phone')]
 *   public ?string $phone;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class MapFrom
{
    public function __construct(
        public string $key,
    ) {}
}
