<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Provide a default value when source key is missing.
 *
 *   #[Default('active')]
 *   public string $status;
 *
 *   #[Default([])]
 *   public array $tags;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Default
{
    public function __construct(
        public mixed $value = null,
    ) {}
}
