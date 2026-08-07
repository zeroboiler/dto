<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with deeply nested DTO types for testing recursive hydration and serialization.
 *
 * Tests that nested DTO -> nested DTO -> scalar values are correctly
 * hydrated from arrays and serialized back to arrays.
 */
final class DeepNestedDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $id,

        #[Required]
        public readonly AddressDTO $address,

        #[Required]
        public readonly string $label,
    ) {}
}
