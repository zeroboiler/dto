<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Provide a default value when source key is missing.
 *
 *   #[DefaultValue('active')]
 *   public string $status;
 *
 *   #[DefaultValue([])]
 *   public array $tags;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class DefaultValue
{
    public function __construct(
        public mixed $value = null,
    ) {}
}
