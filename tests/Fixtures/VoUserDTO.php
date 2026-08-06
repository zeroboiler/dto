<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\ValueObjects\Email;
use ZeroBoiler\ValueObjects\Money;
use ZeroBoiler\ValueObjects\Url;

/**
 * Test fixture: DTO with ValueObject properties.
 *
 * Demonstrates VO auto-instantiation from raw values.
 */
final class VoUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly Email $email,

        public readonly ?Url $website = null,

        public readonly ?Money $balance = null,
    ) {}
}
