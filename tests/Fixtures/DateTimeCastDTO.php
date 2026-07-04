<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\DataTransferObject;

class DateTimeCastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('datetime')]
        public readonly mixed $created_at = null,
    ) {}
}
