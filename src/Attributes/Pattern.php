<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property against a regex pattern.
 *
 *   #[Pattern('/^[A-Z]{3}-\d{4}$/')]
 *   public string $code;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Pattern
{
    public function __construct(
        public string $regex,
        public ?string $message = null,
    ) {}
}
