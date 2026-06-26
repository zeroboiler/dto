<?php

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
}
