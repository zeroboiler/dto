<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with both NestedArray and Collection properties for testing
 * the distinction between plain array hydration and DtoCollection wrapping.
 *
 * - $items returns `array<AddressDTO>` (plain array via NestedArray)
 * - $orders returns `DtoCollection<OrderItemDTO>` (typed collection via Collection)
 */
final class MixedCollectionDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderId,

        #[NestedArray(AddressDTO::class)]
        public readonly array $items = [],

        #[Collection(OrderItemDTO::class)]
        public readonly DtoCollection $orders,
    ) {}
}
