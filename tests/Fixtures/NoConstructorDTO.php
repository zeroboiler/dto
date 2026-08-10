<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use ZeroBoiler\DTO\DataTransferObject;

/**
 * Truly empty DTO — no constructor parameters.
 *
 * Tests the edge case where a DTO has no properties at all.
 * fromArray() should return an instance, toArray() should return [].
 * Used by OpenApiSchemaGenerator (generates empty object schema),
 * DtoMetadataResolver (empty properties/rules/messages), and
 * serialization edge case tests.
 */
final class NoConstructorDTO extends DataTransferObject
{
    // Intentionally no constructor — tests empty DTO behavior.
    // PHP requires at least a no-arg constructor on abstract base classes,
    // but since DataTransferObject is abstract and this subclass doesn't
    // define one, PHP's implicit constructor takes over.
}
