<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with all-default values — no required fields.
 *
 * Tests:
 * - fromArray with empty data
 * - isEmpty() returns true
 * - Default values applied correctly
 * - fromPartialArray merges with defaults
 */
final class AllDefaultsDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('default-name')]
        public readonly string $name = 'default-name',

        #[DefaultValue(0)]
        public readonly int $count = 0,

        #[DefaultValue(false)]
        public readonly bool $active = false,

        #[DefaultValue([])]
        public readonly array $items = [],

        #[Hidden]
        #[DefaultValue('hidden-secret')]
        public readonly string $token = 'hidden-secret',
    ) {}
}
