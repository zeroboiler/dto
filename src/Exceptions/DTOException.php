<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Exceptions;

use Exception;

/**
 * Exception thrown for DTO-related errors during casting, hydration, and serialization.
 *
 * Provides named constructors for common failure modes:
 * - {@see invalidCast()} — when a property value cannot be cast to the target type
 * - {@see invalidJson()} — when a JSON string cannot be decoded into an array
 *
 * @see \ZeroBoiler\DTO\DataTransferObject For the base DTO class that throws this exception
 * @see \ZeroBoiler\DTO\Casts\DTOCast For the Eloquent cast that may throw this exception
 */
final class DTOException extends Exception
{
    /**
     * Thrown when a property value cannot be cast to the target type.
     *
     * @param  string  $property  The property being cast
     * @param  string  $type  The target cast type (e.g. 'integer', 'date')
     * @param  mixed  $value  The value that failed casting
     */
    public static function invalidCast(string $property, string $type, mixed $value): self
    {
        $typeStr = get_debug_type($value);

        return new self("Cannot cast property [{$property}] value [{$typeStr}] to [{$type}].");
    }

    /**
     * Thrown when a JSON string cannot be decoded into an array.
     *
     * @param  string  $property  The property being cast
     * @param  string  $jsonError  The json_last_error_msg() output
     */
    public static function invalidJson(string $property, string $jsonError): self
    {
        return new self("Cannot decode JSON for property [{$property}]: {$jsonError}");
    }

    /**
     * Get a human-readable string representation of the exception.
     *
     * Useful for logging and display contexts where catching and
     * re-throwing as a string is needed (e.g., custom error pages).
     */
    public function __toString(): string
    {
        return self::class.': '.$this->getMessage();
    }
}
