<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Maximum length/value constraint.
 *
 *   #[Max(255)]
 *   public string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max
{
    public function __construct(
        public int $value,
        public ?string $message = null,
    ) {}
}
