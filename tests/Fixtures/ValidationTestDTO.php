<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

final class ValidationTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Integer, Between(0, 120)]
        public readonly int $age,
    ) {}
}
