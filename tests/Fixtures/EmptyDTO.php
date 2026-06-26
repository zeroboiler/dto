<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\DataTransferObject;

class EmptyDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $foo = null,
        public readonly ?string $bar = null,
    ) {}
}
