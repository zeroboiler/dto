<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with nested DTO and array of DTOs for testing OpenAPI schema generation (#76).
 * Also uses union types for testing (#75).
 */
class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderNumber,

        public readonly AddressDTO $shippingAddress,

        /** @var OrderItemDTO[] */
        public readonly array $items = [],

        public readonly int|float|string $rawTotal = 0,
    ) {}
}
