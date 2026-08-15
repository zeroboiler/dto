<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for comprehensive V13 edge-case testing.
 *
 * Properties: email (required, email), name (required, string),
 * age (optional, int with cast), password (hidden, nullable).
 * Used to test hidden field behavior, equals, isEmpty, fromJson,
 * serialization, and DtoCollection operations.
 */
final class ComprehensiveDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(2), Max(100)]
        public readonly string $name,

        #[Max(200)]
        public readonly int $age = 0,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}
