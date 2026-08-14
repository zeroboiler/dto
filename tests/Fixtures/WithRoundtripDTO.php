<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * With-roundtrip fixture — tests immutable `with()` override behavior.
 *
 * Verifies:
 * - with() returns a new instance (immutability)
 * - with() always validates (deprecated $validate param has no effect)
 * - with() merges correctly with allValues()
 * - Serialization roundtrip: fromArray → toArray → fromArray produces equal DTOs
 * - Hidden fields are excluded from toArray() but included in allValues()
 * - Cast attributes are applied during with() roundtrip
 * - Nested null handling in with()
 */
final class WithRoundtripDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public readonly int $quantity = 1,

        #[Required]
        public readonly string $sku = '',

        #[Cast('boolean')]
        public readonly bool $isActive = false,

        #[Nullable]
        public readonly ?string $note = null,

        #[Hidden]
        public readonly string $internalCode = '',
    ) {}
}
