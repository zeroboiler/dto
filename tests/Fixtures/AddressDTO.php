<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

final class AddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        public readonly ?string $zipCode = null,
    ) {}
}
