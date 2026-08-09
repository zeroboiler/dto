<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require array elements to be distinct (no duplicates).
 *
 *   #[Distinct]
 *   public readonly array $tags;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Distinct implements ValidationAttribute
{
    /**
     * @param  string|null  $message  Custom validation message (optional).
     *                              Format: 'The :attribute field has duplicate values.'
     */
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('distinct')
     */
    public function ruleKey(): string
    {
        return 'distinct';
    }
}
