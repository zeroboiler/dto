<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Comprehensive DTO fixture — tests all 37+ validation attributes and metadata attributes.
 *
 * Used to verify:
 * - All validation attribute types generate correct Laravel rules
 * - Cast types work correctly (integer, boolean, array, date)
 * - MapFrom key remapping
 * - Hidden field exclusion
 * - Default values
 * - Nullable/optional field handling
 * - Complex rule combinations (Min+Max+Pattern, etc.)
 */
final class ComprehensiveDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Nullable, Url]
        public readonly ?string $website = null,

        #[Required, Uuid]
        public readonly string $uuid,

        #[Required, Min(1), Max(100)]
        #[Integer]
        public readonly int $age,

        #[Required, Boolean]
        public readonly bool $isActive,

        #[DefaultValue(false)]
        public readonly bool $isVerified = false,

        #[Required, In(['admin', 'editor', 'viewer'])]
        public readonly string $role,

        #[Required, Size(2)]
        public readonly string $countryCode,

        #[Required, Pattern('/^[A-Z][a-z]+$/')]
        public readonly string $firstName,

        #[Required, StartsWith('+'), EndsWith('0')]
        public readonly string $phone,

        #[Accepted]
        public readonly bool $termsAccepted = false,

        #[Prohibited]
        public readonly ?string $secretField = null,

        #[Cast('array')]
        public readonly array $metadata = [],

        #[Cast('date')]
        public readonly ?\Carbon\Carbon $createdAt = null,

        #[DefaultValue('user')]
        public readonly string $type = 'user',

        #[Hidden]
        public readonly ?string $internalNote = null,

        #[MapFrom('display_name')]
        public readonly ?string $displayName = null,
    ) {}
}
