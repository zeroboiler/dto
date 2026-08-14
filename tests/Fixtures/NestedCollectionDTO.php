<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

/**
 * Nested DTO collection fixture — tests array and DtoCollection hydration.
 *
 * Used to verify:
 * - #[NestedArray] produces plain array of DTOs
 * - #[Collection] produces DtoCollection instance
 * - Nested DTO serialization (recursive toArray)
 * - DtoCollection methods (pluck, map, filter, count, etc.)
 */
final class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderId,

        #[Required, Min(0.01)]
        public readonly float $total,

        #[Required, NestedArray(OrderItemDTO::class)]
        public readonly array $items = [],

        #[Collection(AddressDTO::class)]
        public readonly ?DtoCollection $shippingAddresses = null,

        #[Hidden]
        public readonly ?string $internalMemo = null,
    ) {}
}
