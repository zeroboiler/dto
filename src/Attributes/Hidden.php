<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Exclude a property from toArray()/toJson() output.
 *
 *   #[Hidden]
 *   public string $password;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Hidden {}
