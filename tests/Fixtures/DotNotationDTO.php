<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing MapFrom with dot-notation keys.
 */
final class DotNotationDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user.profile.firstName')]
        #[Required]
        public readonly string $firstName,

        #[MapFrom('user.profile.lastName')]
        #[Required]
        public readonly string $lastName,

        #[MapFrom('contact_email')]
        public readonly ?string $email = null,
    ) {}
}
