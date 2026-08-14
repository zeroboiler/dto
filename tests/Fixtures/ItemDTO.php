<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\DataTransferObject;

/**
 * Simple DTO for DtoCollection dictionary/pluckKey testing.
 *
 * Has an int id, string name, and nullable category for testing
 * toDictionary(), toArrayBy(), and pluck with nullable values.
 */
final class ItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $category = null,
    ) {}
}
