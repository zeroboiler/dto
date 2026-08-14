<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Edge-case fixture: Nested DTO with hidden fields for testing recursive normalization.
 *
 * Tests that:
 * - Nested DTOs in toArray() exclude hidden fields
 * - Nested DTOs in allValues() include hidden fields
 * - Empty nested DTOs serialize correctly
 */
final class NestedWithHiddenDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $publicName = '',

        #[Hidden]
        public readonly string $internalId = '',
    ) {}
}

/**
 * Parent DTO containing a nested DTO with hidden fields.
 */
final class ParentWithNestedHiddenDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title = '',

        #[Hidden]
        public readonly string $apiKey = '',

        public readonly ?NestedWithHiddenDTO $nested = null,
    ) {}
}
