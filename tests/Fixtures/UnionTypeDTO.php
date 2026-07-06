<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with union type property for testing OpenAPI union type support (#75).
 */
class UnionTypeDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $id,

        public readonly int|string $identifier,
    ) {}
}
