<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Mark an array property as a type-safe collection of DTO instances.
 *
 * During hydration, each element will be converted to the specified DTO class.
 * During serialization, the value is returned as an array of normalized DTOs.
 *
 *   #[Collection(UserDTO::class)]
 *   public readonly array $users;
 *
 * Unlike {@see NestedArray}, Collection also wraps the result in a
 * {@see \ZeroBoiler\DTO\DtoCollection} instance providing type-safe access.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Collection implements ValidationAttribute
{
    /**
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class for each collection element
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly string $dtoClass,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('array')
     */
    public function ruleKey(): string
    {
        return 'array';
    }
}
