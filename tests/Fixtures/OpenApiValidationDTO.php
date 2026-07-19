<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Fixture DTO that exercises all validation attributes for OpenAPI schema generation.
 */
class OpenApiValidationDTO extends DataTransferObject
{
    public function __construct(
        #[Email]
        public readonly string $email,

        #[Url]
        public readonly string $website,

        #[Uuid]
        public readonly string $externalId,

        #[Pattern('/^[A-Z]{3}$/')]
        public readonly string $code,

        #[Integer, Min(1), Max(100)]
        public readonly int $quantity,

        #[Numeric, Between(0, 99.99)]
        public readonly float $price,

        #[Min(2), Max(50)]
        public readonly string $name,

        #[Date]
        public readonly string $birthDate,

        #[Required]
        public readonly ?string $optionalButRequired,

        #[StartsWith('https://')]
        public readonly string $apiUrl,

        #[EndsWith('@company.com')]
        public readonly string $workEmail,
    ) {}
}
