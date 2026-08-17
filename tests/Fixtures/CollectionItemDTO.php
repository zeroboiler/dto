<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Fixture: simple DTO with nullable property for collection edge case testing.
 */
final class CollectionItemDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1)]
        public readonly int $id,

        #[Required, Max(100)]
        public readonly string $name,

        #[Required]
        public readonly int $score,

        public readonly ?string $email = null,
    ) {}
}
