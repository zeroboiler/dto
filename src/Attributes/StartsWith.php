<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Validate that a string starts with the given prefix(es).
 *
 *   #[StartsWith('https://')]
 *   public readonly string $url;
 *
 *   #[StartsWith(['+90', '+1'])]
 *   public readonly string $phone;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\EndsWith For suffix validation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class StartsWith implements ValidationAttribute
{
    /**
     * @param  string|array<int, string>  $prefix  Required prefix or list of prefixes
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        public readonly string|array $prefix,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return string The Laravel validation rule key ('starts_with')
     */
    public function ruleKey(): string
    {
        return 'starts_with';
    }
}
