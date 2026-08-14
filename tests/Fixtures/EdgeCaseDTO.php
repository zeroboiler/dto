<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Edge-case DTO fixture — covers all common attribute types.
 *
 * Tests:
 * - #[Required] + #[Email] validation
 * - #[MapFrom] key aliasing with dot notation
 * - #[Cast] type casting (integer, boolean, array)
 * - #[Hidden] exclusion from toArray/toJson
 * - #[DefaultValue] fallback for absent keys
 * - #[Nullable] without #[Required]
 * - #[Min] / #[Max] / #[Size] / #[Pattern] / #[In] / #[Uuid] / #[Url]
 * - fromArray, fromPartialArray, fromJson hydration
 * - toArray, allValues, only, except, equals, isEmpty, isNotEmpty
 * - with() immutable update
 */
class EdgeCaseDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(100)]
        public readonly string $name,

        #[MapFrom('user_handle')]
        public readonly ?string $handle = null,

        #[MapFrom('meta.avatar')]
        public readonly ?string $avatar = null,

        #[Cast('integer')]
        public readonly int $score = 0,

        #[Cast('boolean')]
        public readonly bool $isActive = true,

        #[Cast('array')]
        public readonly array $tags = [],

        #[DefaultValue('viewer')]
        public readonly string $role,

        #[Hidden]
        public readonly ?string $token = null,

        #[Nullable]
        public readonly ?string $bio = null,

        #[In(['admin', 'editor', 'viewer', 'guest'])]
        public readonly string $permission = 'guest',

        #[Size(36)]
        #[Uuid]
        public readonly ?string $uuid = null,

        #[Url]
        public readonly ?string $website = null,

        #[Pattern('/^[A-Z]{2}-\d{4}$/')]
        public readonly ?string $code = null,
    ) {}
}
