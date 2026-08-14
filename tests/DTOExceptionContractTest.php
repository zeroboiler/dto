<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Tests DTOException named constructors and __toString behavior.
 *
 * Verifies that DTOException provides consistent factory methods,
 * proper message formatting, and string representation.
 */
final class DTOExceptionContractTest extends TestCase
{
    /**
     * @test
     */
    public function invalid_cast_creates_exception_with_property_and_type(): void
    {
        $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');

        $this->assertInstanceOf(DTOException::class, $exception);
        $message = $exception->getMessage();
        $this->assertStringContainsString('age', $message);
        $this->assertStringContainsString('integer', $message);
        $this->assertStringContainsString('Cannot cast', $message);
    }

    /**
     * @test
     */
    public function invalid_json_creates_exception_with_property_and_error(): void
    {
        $exception = DTOException::invalidJson('payload', 'Syntax error');

        $this->assertInstanceOf(DTOException::class, $exception);
        $message = $exception->getMessage();
        $this->assertStringContainsString('payload', $message);
        $this->assertStringContainsString('Syntax error', $message);
        $this->assertStringContainsString('Cannot decode JSON', $message);
    }

    /**
     * @test
     */
    public function to_string_contains_class_name(): void
    {
        $exception = DTOException::invalidCast('field', 'string', 123);

        $string = (string) $exception;

        $this->assertStringContainsString('DTOException', $string);
        $this->assertStringContainsString('Cannot cast', $string);
    }

    /**
     * @test
     */
    public function invalid_cast_with_null_value_formats_correctly(): void
    {
        $exception = DTOException::invalidCast('status', 'integer', null);

        $message = $exception->getMessage();
        $this->assertStringContainsString('null', $message);
    }

    /**
     * @test
     */
    public function invalid_cast_with_array_value_uses_debug_type(): void
    {
        $exception = DTOException::invalidCast('tags', 'string', ['a', 'b']);

        $message = $exception->getMessage();
        $this->assertStringContainsString('array', $message);
    }

    /**
     * @test
     */
    public function invalid_json_with_empty_error_message(): void
    {
        $exception = DTOException::invalidJson('root', '');

        $this->assertInstanceOf(DTOException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}
