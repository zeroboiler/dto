<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing DefaultValue + Prohibited + Hidden interactions.
 *
 * Exercises edge cases where:
 * - A hidden property has a default value (should be in allValues but not toArray)
 * - A prohibited field is explicitly banned even if present in input
 * - A property with StartsWith constraint validates prefix matching
 */
final class InteractionEdgeCaseDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(50)]
        public readonly string $username,

        #[Prohibited]
        public readonly ?string $adminOverride = null,

        #[Hidden]
        #[DefaultValue('internal')]
        public readonly string $source = 'internal',

        #[StartsWith('@')]
        #[Required]
        public readonly string $handle,

        #[DefaultValue(100)]
        public readonly int $limit = 100,
    ) {}
}
