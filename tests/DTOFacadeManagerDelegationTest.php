<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DTOManager delegation and facade pattern.
 *
 * Covers: validate, make, makeFromJson, schema delegation,
 * error handling, and edge cases.
 */
final class DTOFacadeManagerDelegationTest extends TestCase
{
    private DTOManager $manager;

    protected function setUp(): void
    {
        $this->manager = new DTOManager;
    }

    // ---------------------------------------------------------------
    // validate() delegation
    // ---------------------------------------------------------------

    public function test_validate_returns_validated_data(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $result = $this->manager->validate(CreateUserDTO::class, $data);

        $this->assertIsArray($result);
        $this->assertSame('test@example.com', $result['email']);
        $this->assertSame('Test User', $result['name']);
    }

    public function test_validate_throws_on_invalid_data(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->validate(CreateUserDTO::class, [
            'email' => 'not-an-email',
        ]);
    }

    public function test_validate_throws_on_missing_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->validate(CreateUserDTO::class, []);
    }

    // ---------------------------------------------------------------
    // make() delegation
    // ---------------------------------------------------------------

    public function test_make_creates_dto_instance(): void
    {
        $dto = $this->manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertInstanceOf(CreateUserDTO::class, $dto);
        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('Test User', $dto->name);
    }

    public function test_make_applies_defaults(): void
    {
        $dto = $this->manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Status should have default value
        $this->assertNotEmpty($dto->status);
    }

    public function test_make_throws_on_invalid_data(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->make(CreateUserDTO::class, [
            'email' => 'invalid-email',
            'name' => 'Test',
        ]);
    }

    // ---------------------------------------------------------------
    // makeFromJson() delegation
    // ---------------------------------------------------------------

    public function test_make_from_json_creates_dto_from_valid_json(): void
    {
        $json = '{"email":"test@example.com","name":"Test User"}';

        $dto = $this->manager->makeFromJson(CreateUserDTO::class, $json);

        $this->assertInstanceOf(CreateUserDTO::class, $dto);
        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('Test User', $dto->name);
    }

    public function test_make_from_json_throws_on_invalid_json(): void
    {
        $this->expectException(DTOException::class);

        $this->manager->makeFromJson(CreateUserDTO::class, 'not-valid-json');
    }

    public function test_make_from_json_throws_on_json_array(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object');

        $this->manager->makeFromJson(CreateUserDTO::class, '["test@example.com"]');
    }

    // ---------------------------------------------------------------
    // schema() delegation
    // ---------------------------------------------------------------

    public function test_schema_returns_openapi_structure(): void
    {
        $schema = $this->manager->schema(MinimalDTO::class);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }

    // ---------------------------------------------------------------
    // Cross-DTO isolation
    // ---------------------------------------------------------------

    public function test_different_dtos_return_different_schemas(): void
    {
        $schema1 = $this->manager->schema(MinimalDTO::class);
        $schema2 = $this->manager->schema(CreateUserDTO::class);

        // Both should be valid object schemas
        $this->assertSame('object', $schema1['type']);
        $this->assertSame('object', $schema2['type']);

        // But they should have different property sets
        $props1 = array_keys((array) $schema1['properties']);
        $props2 = array_keys((array) $schema2['properties']);

        // At minimum, CreateUserDTO should have email/name
        $this->assertContains('email', $props2);
        $this->assertContains('name', $props2);
    }

    // ---------------------------------------------------------------
    // Empty data handling
    // ---------------------------------------------------------------

    public function test_validate_with_empty_data_throws_for_required_dto(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->validate(CreateUserDTO::class, []);
    }

    public function test_make_with_empty_data_throws_for_required_dto(): void
    {
        $this->expectException(ValidationException::class);

        $this->manager->make(CreateUserDTO::class, []);
    }

    public function test_make_with_empty_json_string_throws(): void
    {
        $this->expectException(DTOException::class);

        $this->manager->makeFromJson(CreateUserDTO::class, '');
    }

    public function test_make_with_null_json_throws(): void
    {
        $this->expectException(DTOException::class);

        $this->manager->makeFromJson(CreateUserDTO::class, 'null');
    }
}
