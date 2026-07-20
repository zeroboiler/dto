<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Cast a property from one type to another during hydration.
 *
 *   #[Cast('integer')]
 *   public int $count;
 *
 *   #[Cast('float')]
 *   public float $price;
 *
 *   #[Cast('boolean')]
 *   public bool $active;
 *
 *   #[Cast(Carbon::class)]
 *   public Carbon $createdAt;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Cast
{
    public function __construct(
        public string $type,
    ) {}
}
