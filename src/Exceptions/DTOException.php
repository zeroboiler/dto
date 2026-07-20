<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Exceptions;

use Exception;

final class DTOException extends Exception
{
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
}
