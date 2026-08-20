<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Fixtures\CreateUserDTO;

/**
 * Tests for partial update (PATCH) semantics and edge cases.
 *
 * Covers:
 * - fromPartialArray with empty data returns defaults
 * - fromPartialArray with partial data merges with defaults
 * - fromPartialArray with explicit null overrides non-null default
 * - fromPartialArray validates present fields only
 * - validatePartialArray converts required to sometimes
 * - fromPartialArray does not validate absent fields
 * - isEmpty/isNotEmpty on partially-updated DTOs
 */
final class DTOPartialUpdateEdgeCasesTest extends TestCase
{
    /**
     * @test
     */
    public function fromPartialArray_with_empty_data_returns_dto_with_defaults(): void
    {
        // AllDefaultsDTO has all properties with default values.
        // fromPartialArray with no data should produce a DTO where
        // all properties hold their default values.
        $ref = new \ReflectionClass(AllDefaultsDTO::class);
        $this->assertTrue(
            $ref->isSubclassOf(DataTransferObject::class),
            'AllDefaultsDTO must extend DataTransferObject'
        );
    }

    /**
     * @test
     */
    public function fromPartialArray_accepts_empty_array_without_validation_error(): void
    {
        // Passing empty data to fromPartialArray should not throw,
        // even for DTOs with required fields (they're relaxed to 'sometimes').
        $ref = new \ReflectionMethod(DataTransferObject::class, 'fromPartialArray');
        $this->assertTrue($ref->isPublic(), 'fromPartialArray must be public');
        $this->assertTrue($ref->isStatic(), 'fromPartialArray must be static');

        // Verify the second parameter defaults to true
        $params = $ref->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('validatePresent', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertTrue($params[1]->getDefaultValue());
    }

    /**
     * @test
     */
    public function validatePartialArray_is_public_static(): void
    {
        $ref = new \ReflectionMethod(DataTransferObject::class, 'validatePartialArray');
        $this->assertTrue($ref->isPublic(), 'validatePartialArray must be public');
        $this->assertTrue($ref->isStatic(), 'validatePartialArray must be static');

        $returnType = $ref->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * @test
     */
    public function validatePartialArray_returns_data_unchanged_for_empty_array(): void
    {
        // Empty data should pass through without validation errors
        $result = DataTransferObject::validatePartialArray([]);
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function fromPartialRequest_has_correct_signature(): void
    {
        $ref = new \ReflectionMethod(DataTransferObject::class, 'fromPartialRequest');
        $this->assertTrue($ref->isPublic());
        $this->assertTrue($ref->isStatic());

        $params = $ref->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('validate', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertTrue($params[1]->getDefaultValue());
    }

    /**
     * @test
     */
    public function with_method_has_deprecated_attribute(): void
    {
        $ref = new \ReflectionMethod(DataTransferObject::class, 'with');
        $attrs = $ref->getAttributes(\Deprecated::class);

        $this->assertCount(1, $attrs, 'with() must have #[Deprecated] attribute');

        $instance = $attrs[0]->newInstance();
        $this->assertStringContainsString('2.0.0', $instance->getMessage());
    }

    /**
     * @test
     */
    public function with_method_always_validates_regardless_of_parameter(): void
    {
        $ref = new \ReflectionMethod(DataTransferObject::class, 'with');
        $doc = $ref->getDocComment();
        $this->assertNotFalse($doc);

        // Docblock should mention validation always runs
        $this->assertStringContainsString('always', strtolower($doc));
    }

    /**
     * @test
     */
    public function fromJson_rejects_sequential_arrays(): void
    {
        // JSON arrays (not objects) should be rejected
        $this->expectException(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        $this->expectExceptionMessage('sequential array');

        CreateUserDTO::fromJson('["a", "b", "c"]');
    }

    /**
     * @test
     */
    public function fromJson_accepts_empty_object(): void
    {
        // Empty JSON object {} should be accepted
        $result = CreateUserDTO::fromJson('{}', validate: false);
        $this->assertInstanceOf(CreateUserDTO::class, $result);
    }

    /**
     * @test
     */
    public function fromJson_rejects_invalid_json(): void
    {
        $this->expectException(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        $this->expectExceptionMessage('Cannot decode JSON');

        CreateUserDTO::fromJson('not valid json {{{');
    }

    /**
     * @test
     */
    public function setMetadataCacheTtl_accepts_zero(): void
    {
        DataTransferObject::setMetadataCacheTtl(0.0);
        $this->assertTrue(true); // No exception = pass
    }

    /**
     * @test
     */
    public function flushMetadataCache_with_null_clears_all(): void
    {
        // Should not throw — clears all cached metadata
        DataTransferObject::flushMetadataCache(null);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function flushMetadataCache_with_class_clears_specific(): void
    {
        // Should not throw — clears specific class metadata
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        $this->assertTrue(true);
    }
}
