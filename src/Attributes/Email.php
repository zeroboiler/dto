<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Validate property as email.
 *
 *   #[Email]
 *   public string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Email
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
