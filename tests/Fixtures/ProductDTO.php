<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

class ProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(255)]
        public readonly string $name,

        #[Required, Numeric]
        public readonly string $price,

        #[Integer, Min(0)]
        public readonly int $stock = 0,
    ) {}
}
