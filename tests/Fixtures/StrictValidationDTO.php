<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO for testing with() validation rejection.
 *
 * Used to verify that with() always validates and rejects
 * invalid merged data, preventing DTO state corruption.
 */
final class StrictValidationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Required, Min(0), Max(200)]
        public readonly int $age,

        #[Min(1), Max(100)]
        public readonly int $score = 50,
    ) {}
}
