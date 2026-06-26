<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Mark a property as required.
 *
 *   #[Required]
 *   public string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
