<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Cast a property value to a specific type during hydration.
 *
 * Applied before validation, so the cast value is what gets validated.
 * Supported cast types are handled by
 * {@see \ZeroBoiler\DTO\DataTransferObject::castValue()}.
 *
 * Supported types:
 * - `'integer'`, `'int'` — Cast to int via `(int) $value`
 * - `'float'`, `'double'` — Cast to float via `(float) $value`
 * - `'string'` — Cast to string via `(string) $value`
 * - `'boolean'`, `'bool'` — Cast to bool via `filter_var($value, FILTER_VALIDATE_BOOLEAN)`
 * - `'array'` — Decode JSON string to array (or pass through arrays as-is)
 * - `'date'`, `'datetime'` — Parse to {@see \Illuminate\Support\Carbon} instance
 *
 *   #[Cast('integer')]
 *   public readonly int $count;
 *
 *   #[Cast('date')]
 *   public readonly \Carbon\Carbon $createdAt;
 *
 *   #[Cast('array')]
 *   public readonly array $metadata;
 *
 * @see DataTransferObject::castValue() For the cast implementation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Cast
{
    /**
     * @param  string  $type  The target cast type (e.g., 'integer', 'string', 'date', 'array')
     */
    public function __construct(
        public readonly string $type,
    ) {}
}
