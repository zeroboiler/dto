<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Exclude a property from public serialization output.
 *
 * Hidden properties are still accessible on the DTO instance itself
 * and included in {@see DataTransferObject::allValues()} output.
 * Only the public serialization methods ({@see DataTransferObject::toArray()},
 * {@see DataTransferObject::toJson()}, {@see DataTransferObject::jsonSerialize()})
 * respect this attribute.
 *
 *   #[Hidden]
 *   public readonly string $password;
 *
 * This attribute has no constructor parameters.
 *
 * @see MapFrom For source key aliasing (metadata attribute)
 * @see Cast For type casting (metadata attribute)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Hidden {}
