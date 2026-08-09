<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Tests for DTOManager — readonly class verification, delegation, and edge cases.
 */
final class DtoReadonlyManagerTest extends TestCase
{
    private DTOManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new DTOManager;
    }

    // ---------------------------------------------------------------
    // Class structure assertions
    // ---------------------------------------------------------------

    public function test_dto_manager_is_final(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        $this->assertTrue($ref->isFinal());
    }

    public function test_dto_manager_is_readonly(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        $this->assertTrue($ref->isReadOnly());
    }

    public function test_dto_manager_has_no_public_properties(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        $this->assertEmpty($props, 'DTOManager should have no public properties');
    }

    // ---------------------------------------------------------------
    // schema delegation
    // ---------------------------------------------------------------

    public function test_schema_throws_for_nonexistent_class(): void
    {
        $this->expectException(\ReflectionException::class);

        $this->manager->schema('NonExistentDTOClass');
    }

    // ---------------------------------------------------------------
    // makeFromJson delegation
    // ---------------------------------------------------------------

    public function test_make_from_json_throws_for_invalid_json(): void
    {
        $this->expectException(DTOException::class);

        // Use a real DTO class with invalid JSON
        $this->manager->makeFromJson(
            \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class,
            'not-valid-json'
        );
    }

    public function test_make_from_json_throws_for_sequential_array_json(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object (associative array)');

        $this->manager->makeFromJson(
            \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class,
            '["value1", "value2"]'
        );
    }

    // ---------------------------------------------------------------
    // Type consistency checks
    // ---------------------------------------------------------------

    public function test_manager_methods_have_strict_return_types(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        $methods = ['validate', 'make', 'makeFromJson', 'schema'];
        foreach ($methods as $method) {
            $methodRef = $ref->getMethod($method);
            $returnType = $methodRef->getReturnType();

            $this->assertNotNull(
                $returnType,
                "Method {$method}() must have a declared return type"
            );

            $typeName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : 'mixed';
            $this->assertNotSame(
                'mixed',
                $typeName,
                "Method {$method}() should not return mixed"
            );
        }
    }
}
