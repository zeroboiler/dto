<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing nullable property roundtrip behavior.
 *
 * Exercises:
 * - Explicit null values preserved through fromArray → toArray
 * - Nullable fields appear in toArray() output
 * - Hidden nullable fields excluded from toArray() but included in allValues()
 * - with() roundtrip preserves nullable state
 */
final class NullableRoundtripDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Nullable]
        public readonly ?string $nickname = null,

        #[Nullable]
        public readonly ?string $email = null,

        #[Hidden]
        public readonly ?string $secret = null,
    ) {}
}
