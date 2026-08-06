<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with nested DTO property for testing OpenAPI schema generation (#76).
 */
final class OrderItemDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $productName,

        #[Required]
        public readonly float $price,

        public readonly int $quantity = 1,
    ) {}
}
