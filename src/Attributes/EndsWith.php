<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a string ends with the given suffix(es).
 *
 *   #[EndsWith('@company.com')]
 *   public string $workEmail;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EndsWith implements ValidationAttribute
{
    /**
     * @param  string|array<int, string>  $suffix
     */
    public function __construct(
        public readonly string|array $suffix,
        public readonly ?string $message = null,
    ) {}

    /** @return string The Laravel validation rule key ('ends_with') */
    public function ruleKey(): string
    {
        return 'ends_with';
    }
}
