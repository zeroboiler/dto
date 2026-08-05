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
 * Supported cast types: 'integer', 'int', 'float', 'double', 'string',
 * 'boolean', 'bool', 'array', 'date', 'datetime'.
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
 *   #[Cast('date')]
 *   public \Carbon\Carbon $createdAt;
 *
 *   #[Cast('array')]
 *   public array $metadata;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Cast
{
    public function __construct(
        public string $type,
    ) {}
}
