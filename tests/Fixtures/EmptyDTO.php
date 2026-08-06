<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\DataTransferObject;

final class EmptyDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?string $bar = null,
    ) {}
}
