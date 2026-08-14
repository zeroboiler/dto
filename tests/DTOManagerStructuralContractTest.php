<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\DTO\DTOManager;

/**
 * Verifies DTOManager structural and type safety contract.
 *
 * Ensures DTOManager is:
 * - final and readonly
 * - All public methods have return types
 * - No mixed return types
 * - Constructor is generated (readonly class)
 */
final class DTOManagerStructuralContractTest extends TestCase
{
    /**
     * @test
     */
    public function dto_manager_is_final_readonly(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $this->assertTrue($ref->isFinal(), 'DTOManager must be final');
        $this->assertTrue($ref->isReadOnly(), 'DTOManager must be readonly');
    }

    /**
     * @test
     */
    public function dto_manager_has_class_docblock(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $doc = $ref->getDocComment();

        $this->assertNotFalse($doc, 'DTOManager must have a class-level docblock');
        $this->assertStringContainsString('/**', $doc);
    }

    /**
     * @test
     */
    public function all_public_methods_have_return_types(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            $this->assertNotNull(
                $returnType,
                "DTOManager::{$method->getName()}() must have an explicit return type"
            );
        }
    }

    /**
     * @test
     */
    public function no_public_method_returns_mixed(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            $this->assertNotNull($returnType);

            if ($returnType instanceof \ReflectionNamedType) {
                $this->assertNotSame(
                    'mixed',
                    $returnType->getName(),
                    "DTOManager::{$method->getName()}() must not return mixed"
                );
            }
        }
    }

    /**
     * @test
     */
    public function all_public_methods_have_docblocks(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            $this->assertNotFalse(
                $doc,
                "DTOManager::{$method->getName()}() must have a docblock"
            );
            $this->assertStringContainsString('/**', $doc);
        }
    }

    /**
     * @test
     */
    public function dto_manager_has_expected_methods(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        $expectedMethods = [
            'validate', 'make', 'makeFromJson', 'rules', 'rulesFor',
            'schema', 'fromPartialArray', 'fromPartialRequest', 'fromJson',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "DTOManager must have {$method}() method"
            );
        }
    }

    /**
     * @test
     */
    public function dto_manager_validate_returns_array(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $method = $ref->getMethod('validate');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * @test
     */
    public function dto_manager_make_returns_data_transfer_object(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $method = $ref->getMethod('make');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(
            \ZeroBoiler\DTO\DataTransferObject::class,
            $returnType->getName()
        );
    }

    /**
     * @test
     */
    public function dto_manager_schema_returns_array(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $method = $ref->getMethod('schema');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }
}
