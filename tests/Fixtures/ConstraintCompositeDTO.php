<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Fixture DTO for testing Between, StartsWith, EndsWith constraints
 * in both validation rules and OpenAPI schema generation.
 */
final class ConstraintCompositeDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(100)]
        public readonly string $username,

        #[Required, Between(1, 255)]
        public readonly int $score,

        #[Required, StartsWith('https://')]
        public readonly string $website,

        #[Required, EndsWith('@zeroboiler.dev')]
        public readonly string $email,

        #[Hidden]
        public readonly string $secret = '',
    ) {}
}
