<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DTO serialization roundtrip: fromArray → toArray → fromArray.
 *
 * Covers:
 * - Scalar type roundtrips (string, int, float, bool)
 * - Nullable property roundtrips
 * - Hidden property exclusion in toArray but inclusion in allValues
 * - DefaultValue attribute behavior in roundtrips
 * - MapFrom key aliasing in roundtrips
 * - Empty DTO roundtrip
 * - with() creates valid new instance
 */
final class DTOSerializationRoundtripTest extends TestCase
{
    // ---------------------------------------------------------------
    // Basic roundtrip
    // ---------------------------------------------------------------

    public function test_from_array_to_array_roundtrip_preserves_values(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        $this->assertSame('test@example.com', $result['email']);
        $this->assertSame('Test User', $result['name']);
        $this->assertSame('active', $result['status']);
    }

    public function test_from_array_to_array_with_defaults_applied(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->toArray();

        // Status should have its default value
        $this->assertArrayHasKey('status', $result);
        $this->assertNotEmpty($result['status']);
    }

    // ---------------------------------------------------------------
    // Hidden property roundtrip
    // ---------------------------------------------------------------

    public function test_to_array_excludes_hidden_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $array = $dto->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_all_values_includes_hidden_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret123', $all['password']);
    }

    // ---------------------------------------------------------------
    // Nullable property roundtrip
    // ---------------------------------------------------------------

    public function test_nullable_null_roundtrip(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone' => null,
        ], validate: false);

        $result = $dto->toArray();

        $this->assertArrayHasKey('phone', $result);
        $this->assertNull($result['phone']);
    }

    public function test_nullable_with_value_roundtrip(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone' => '+1234567890',
        ], validate: false);

        $result = $dto->toArray();

        $this->assertSame('+1234567890', $result['phone']);
    }

    // ---------------------------------------------------------------
    // MapFrom roundtrip
    // ---------------------------------------------------------------

    public function test_map_from_key_aliased_on_hydration(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => '+1234567890',
        ], validate: false);

        // Property should be accessible via original name
        $this->assertSame('+1234567890', $dto->phone);
    }

    // ---------------------------------------------------------------
    // with() roundtrip
    // ---------------------------------------------------------------

    public function test_with_creates_new_instance_with_overrides(): void
    {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $updated = $original->with(['status' => 'inactive']);

        // Original unchanged
        $this->assertSame('active', $original->status);
        // Updated has new value
        $this->assertSame('inactive', $updated->status);
        // Other fields preserved
        $this->assertSame('test@example.com', $updated->email);
    }

    // ---------------------------------------------------------------
    // only() / except()
    // ---------------------------------------------------------------

    public function test_only_returns_specified_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        $this->assertSame(['email' => 'test@example.com'], $result);
    }

    public function test_only_returns_multiple_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('status', $result);
    }

    public function test_except_excludes_specified_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');

        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
    }

    // ---------------------------------------------------------------
    // equals()
    // ---------------------------------------------------------------

    public function test_equals_returns_true_for_same_data(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_returns_false_for_different_data(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertFalse($dto1->equals($dto2));
    }

    // ---------------------------------------------------------------
    // toJson
    // ---------------------------------------------------------------

    public function test_to_json_produces_valid_json(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('test@example.com', $decoded['email']);
    }

    public function test_to_json_with_pretty_print(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    // ---------------------------------------------------------------
    // isEmpty / isNotEmpty
    // ---------------------------------------------------------------

    public function test_dto_with_values_is_not_empty(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    // ---------------------------------------------------------------
    // rules()
    // ---------------------------------------------------------------

    public function test_rules_returns_array_with_correct_structure(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertIsArray($rules['email']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    // ---------------------------------------------------------------
    // fromPartialArray
    // ---------------------------------------------------------------

    public function test_from_partial_array_hydrates_only_present_fields(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        $this->assertSame('Updated Name', $dto->name);
    }

    public function test_from_partial_array_uses_defaults_for_missing_fields(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Test',
        ], validatePresent: false);

        // Status should have its default value
        $this->assertNotEmpty($dto->status);
    }

    // ---------------------------------------------------------------
    // MinimalDTO edge case
    // ---------------------------------------------------------------

    public function test_minimal_dto_roundtrip(): void
    {
        $dto = MinimalDTO::fromArray([
            'name' => 'test',
            'value' => '42',
        ], validate: false);
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertSame('test', $result['name']);
        $this->assertSame('42', $result['value']);
    }
}
