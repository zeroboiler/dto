<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with array cast for testing edge cases (#64).
 */
class ArrayCastDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Cast('array')]
        public readonly array $tags = [],

        #[Cast('array')]
        public readonly array $metadata = [],
    ) {}
}
