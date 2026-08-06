<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * DTO fixture combining multiple constraint types for comprehensive edge-case coverage.
 *
 * Tests interaction between: Required, Nullable, DefaultValue, Hidden, MapFrom,
 * Pattern, In, Enum, Min, Max, Sometimes.
 */
final class MultiConstraintDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(50), Pattern('/^[a-zA-Z\s]+$/')]
        public readonly string $username,

        #[Nullable, Max(255)]
        public readonly ?string $bio = null,

        #[Required, Enum(UserStatus::class)]
        public readonly UserStatus $status,

        #[In(['admin', 'editor', 'viewer'])]
        #[DefaultValue('viewer')]
        public readonly string $role = 'viewer',

        #[MapFrom('pref_lang')]
        #[DefaultValue('en')]
        public readonly string $language = 'en',

        #[Hidden]
        public readonly ?string $token = null,

        #[Sometimes]
        public readonly ?string $optional_note = null,
    ) {}
}
