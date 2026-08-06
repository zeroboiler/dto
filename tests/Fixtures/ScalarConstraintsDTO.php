<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing scalar type constraints:
 * Boolean, Integer, Numeric, Uuid, Accepted, Prohibited, Size, Sometimes.
 */
final class ScalarConstraintsDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Boolean]
        public readonly bool $is_admin = false,

        #[Integer, Min(0), Max(100)]
        public readonly int $score = 0,

        #[Numeric]
        public readonly float $rating = 0.0,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[Accepted]
        public readonly bool $terms = false,

        #[Prohibited]
        public readonly ?string $secret = null,

        #[Sometimes, Size(3)]
        public readonly ?string $code = null,

        #[DefaultValue('pending')]
        public readonly string $status = 'pending',
    ) {}
}
