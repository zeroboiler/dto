<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO for testing fromPartialArray interaction with DefaultValue attribute.
 *
 * Tests the edge case where:
 * - Some fields have DefaultValue attributes
 * - Some fields have PHP constructor defaults
 * - Some fields are nullable without defaults
 * - Partial update only provides a subset of fields
 */
final class PartialDefaultValueDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(50)]
        public readonly string $name,

        #[Email]
        #[DefaultValue('default@example.com')]
        public readonly string $email,

        #[MapFrom('user_role')]
        #[DefaultValue('viewer')]
        public readonly string $role,

        #[DefaultValue(true)]
        public readonly bool $isActive,

        #[Min(0)]
        #[DefaultValue(100)]
        public readonly int $score,

        public readonly ?string $optionalNote = null,
    ) {}
}
