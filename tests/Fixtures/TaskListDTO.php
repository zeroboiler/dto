<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO fixture for testing #[Collection] attribute with DtoCollection hydration.
 *
 * Models a task list with a collection of OrderItemDTO tasks.
 */
final class TaskListDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $projectName,

        #[Required]
        public readonly string $owner,

        #[Collection(OrderItemDTO::class)]
        public readonly array $tasks = [],

        #[DefaultValue(false)]
        public readonly bool $completed = false,
    ) {}
}
