<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Comprehensive roundtrip DTO fixture for testing hydration → serialization cycles.
 *
 * Exercises every property type the DTO system supports:
 * - Scalar types (string, int, float, bool)
 * - Nullable types (?string, ?int)
 * - Array type with Cast('array')
 * - Hidden field
 * - MapFrom with different source key
 * - DefaultValue
 * - Cast('integer') on a property
 *
 * Used to verify that:
 * 1. fromArray() → toArray() is a perfect roundtrip for all types
 * 2. with() → toArray() preserves defaults and hidden exclusions
 * 3. equals() works correctly across all property types
 * 4. isEmpty() / isNotEmpty() boundary conditions
 */
final class RoundtripDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public readonly string $name,

        #[Required]
        #[Cast('integer')]
        public readonly int $age,

        #[Required]
        public readonly bool $active,

        #[DefaultValue(0.0)]
        public readonly float $score,

        #[DefaultValue([])]
        #[Cast('array')]
        public readonly array $tags,

        #[MapFrom('source_bio')]
        #[Max(500)]
        public readonly ?string $bio = null,

        #[Hidden]
        public readonly ?string $secret = null,

        #[DefaultValue('user')]
        public readonly string $role = 'user',
    ) {}
}
