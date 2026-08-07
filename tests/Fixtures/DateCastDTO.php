<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\DataTransferObject;

final class DateCastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('date')]
        public readonly ?\Carbon\Carbon $event_date = null,
    ) {}
}
