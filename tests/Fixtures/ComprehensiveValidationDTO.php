<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Comprehensive DTO fixture exercising ALL validation attributes.
 *
 * Not intended for real-world use — covers every attribute for
 * PHPStan level 9 type-safety and validation correctness testing.
 */
class ComprehensiveValidationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Max(100), Min(3)]
        public readonly string $username,

        #[Nullable, Pattern('/^[A-Z]{3}-\d{4}$/')]
        public readonly ?string $code = null,

        #[Boolean]
        public readonly bool $isActive = true,

        #[Integer, Min(0), Max(100)]
        #[Cast('integer')]
        public readonly int $score = 0,

        #[Numeric]
        #[Cast('float')]
        public readonly float $rating = 0.0,

        #[In(['admin', 'editor', 'viewer'])]
        public readonly string $role = 'viewer',

        #[Url]
        public readonly ?string $website = null,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[Json]
        public readonly ?string $metadata = null,

        #[MapFrom('source_field')]
        public readonly ?string $mappedField = null,

        #[Hidden]
        public readonly ?string $secret = null,

        #[DefaultValue('draft')]
        public readonly string $status = 'draft',

        #[Size(5)]
        public readonly array $tags = [],

        #[StartsWith('+'), Max(15)]
        public readonly ?string $phone = null,

        #[EndsWith('.com')]
        public readonly ?string $domain = null,

        #[Prohibited]
        public readonly ?string $bannedField = null,

        #[Present]
        public readonly ?string $optionalPresent = null,

        #[Same('password')]
        public readonly ?string $passwordConfirmation = null,

        #[Different('email')]
        public readonly ?string $notEmail = null,

        #[Accepted]
        public readonly bool $termsAccepted = false,

        #[Declined]
        public readonly bool $spamDeclined = false,

        #[Confirmed]
        public readonly ?string $password = null,

        #[RequiredWith('email')]
        public readonly ?string $emailToken = null,

        #[RequiredWithAll(['email', 'username'])]
        public readonly ?string $emailAndNameToken = null,

        #[RequiredWithout('website')]
        public readonly ?string $fallbackContact = null,

        #[RequiredWithoutAll(['website', 'phone'])]
        public readonly ?string $primaryContact = null,

        #[RequiredIf('role', 'admin')]
        public readonly ?string $adminSecret = null,

        #[Integer]
        public readonly ?int $age = null,
    ) {}
}
