<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing conditional and comparison validation attributes:
 * Confirmed, Declined, Same, Different, RequiredWith, RequiredWithout.
 *
 * Models a user registration form with password confirmation and
 * optional referral fields.
 */
final class RegistrationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(8), Max(128), Confirmed]
        public readonly string $password,

        #[Required]
        public readonly bool $termsAccepted,

        #[Declined]
        public readonly bool $marketingOptOut = false,

        #[Same('password')]
        public readonly string $passwordRepeat = '',

        #[Different('email')]
        #[Nullable, Max(255)]
        public readonly ?string $username = null,

        #[RequiredWith('referralCode')]
        #[Max(100)]
        public readonly ?string $referrerName = null,

        #[RequiredWithout('email')]
        #[Nullable, Max(255)]
        public readonly ?string $phone = null,

        #[Nullable, Max(50)]
        public readonly ?string $referralCode = null,

        #[Hidden]
        public readonly ?string $ipAddress = null,
    ) {}
}
