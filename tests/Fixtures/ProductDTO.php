<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with diverse attribute combinations for edge case testing.
 */
final class ProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(200)]
        public readonly string $name,

        #[Required, Pattern('/^[A-Z]{2}\d{4}$/')]
        public readonly string $sku,

        #[Required, Min(0)]
        public readonly int $priceCents,

        #[Boolean]
        public readonly bool $isActive = true,

        #[DefaultValue('general')]
        public readonly string $category = 'general',

        #[Nullable]
        public readonly ?string $description = null,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[MapFrom('vendor_code')]
        public readonly ?string $vendorCode = null,

        #[Hidden]
        public readonly ?string $internalNotes = null,

        #[Integer]
        public readonly int $stockCount = 0,
    ) {}
}
