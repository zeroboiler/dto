<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\DataTransferObject;

class DateCastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('date')]
        public readonly mixed $event_date = null,
    ) {}
}
