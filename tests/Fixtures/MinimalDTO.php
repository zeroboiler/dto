<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Minimal DTO with only required string fields for edge case testing.
 *
 * Used to test fromJson, fromPartialArray, and validation edge cases
 * with the simplest possible DTO.
 */
final class MinimalDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        public readonly string $value,
    ) {}
}
