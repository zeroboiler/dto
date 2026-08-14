<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Validation-only DTO fixture — covers all remaining validation attributes.
 *
 * Tests all 37 validation attributes in a single DTO for structural
 * contract testing. Not all fields are meaningful together, but this
 * ensures every attribute resolves correctly through DtoMetadataResolver.
 */
class ValidationAttributeContractDTO extends DataTransferObject
{
    public function __construct(
        // Basic required
        #[Required]
        public readonly string $username,

        // Type checks
        #[Integer]
        public readonly ?int $age = null,

        #[Numeric]
        public readonly ?float $price = null,

        #[Boolean]
        public readonly bool $agree = false,

        // String validators
        #[Same('field1')]
        public readonly ?string $field1 = null,

        #[Different('field1')]
        public readonly ?string $field2 = null,

        #[Confirmed]
        public readonly ?string $password = null,

        // Conditional required
        #[RequiredIf('type', 'individual')]
        public readonly ?string $firstName = null,

        #[RequiredUnless('type', 'company')]
        public readonly ?string $lastName = null,

        #[RequiredWith('email')]
        public readonly ?string $email = null,

        #[RequiredWithAll(['phone', 'email'])]
        public readonly ?string $address = null,

        #[RequiredWithout('email')]
        public readonly ?string $phone = null,

        #[RequiredWithoutAll(['email', 'phone'])]
        public readonly ?string $fax = null,

        // Date validation
        #[Date]
        public readonly ?string $birthday = null,

        // Array validators
        #[Distinct]
        public readonly array $emails = [],

        // State validators
        #[Accepted]
        public readonly bool $terms = false,

        #[Declined]
        public readonly bool $optOut = false,

        #[Prohibited]
        public readonly ?string $secret = null,

        // General field
        public readonly ?string $type = null,
    ) {}
}
