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
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Comprehensive DTO fixture with mixed attribute types for integration testing.
 *
 * Exercises: Required, Min, Max, Pattern, Email, Cast, MapFrom, Hidden,
 * DefaultValue, nullable, array, and boolean properties.
 */
final class MixedAttributesDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(100)]
        public readonly string $username,

        #[Required, Pattern('/^[a-f0-9]{6}$/')]
        public readonly string $hexCode,

        #[MapFrom('user_email')]
        public readonly ?string $email = null,

        #[Cast('integer')]
        public readonly int $age = 0,

        #[DefaultValue('user')]
        public readonly string $role,

        #[Hidden]
        public readonly ?string $token = null,

        public readonly bool $isActive = false,

        public readonly array $tags = [],
    ) {}
}
