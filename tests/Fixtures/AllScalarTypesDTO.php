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
 * Edge-case fixture: DTO with all scalar type combinations for serialization testing.
 *
 * Tests normalization of:
 * - int, float, string, bool types
 * - nullable properties
 * - Cast attributes (integer, boolean, string)
 * - DefaultValue attributes
 * - Hidden properties
 * - MapFrom attributes
 * - Empty string / zero / false values (testing isEmpty semantics)
 */
final class AllScalarTypesDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public readonly int $count = 1,

        #[Required]
        public readonly string $name = '',

        #[Cast('boolean')]
        public readonly bool $active = false,

        #[DefaultValue(0.0)]
        public readonly float $score = 0.0,

        #[Nullable]
        public readonly ?string $optional = null,

        #[DefaultValue('default-tag')]
        public readonly string $tag = 'default-tag',

        #[MapFrom('source_field')]
        public readonly ?string $mapped = null,

        #[Hidden]
        public readonly string $secret = '',

        #[DefaultValue([])]
        public readonly array $items = [],

        #[Cast('integer')]
        public readonly int $castedInt = 0,
    ) {}
}
