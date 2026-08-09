<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field to be present in the input data (even if null).
 *
 *   #[Present]
 *   public readonly ?string $apiKey;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Present implements ValidationAttribute
{
    public function __construct(
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('present')
     */
    public function ruleKey(): string
    {
        return 'present';
    }
}
