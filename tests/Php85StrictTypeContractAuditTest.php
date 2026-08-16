<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\TaskListDTO;

/**
 * Comprehensive PHP 8.5 strict type contract audit for PHPStan Level 9.
 *
 * Tests that every public API method returns strictly typed values
 * with no `mixed` leaks, all comparisons use strict operators,
 * and all return types match their declared signatures.
 *
 * Covers edge cases not fully tested elsewhere:
 * - fromArray/toArray perfect roundtrip for all scalar types
 * - Hidden field exclusion contract
 * - MapFrom dot notation and simple mapping
 * - Cast type coercion (integer, boolean, array)
 * - DefaultValue application on absent keys
 * - Nullable property behavior
 * - isEmpty()/isNotEmpty() boundary conditions
 * - with() immutable update contract
 * - equals() strict array comparison
 * - fromPartialArray PATCH semantics
 * - fromJson strict validation (rejects sequential arrays)
 * - only()/except() selective output
 * - allValues() includes hidden fields
 * - DtoCollection type safety
 */
final class Php85StrictTypeContractAuditTest extends TestCase
{
    // -----------------------------------------------------------------
    // fromArray / toArray roundtrip
    // -----------------------------------------------------------------

    public function test_from_array_to_array_roundtrip_preserves_all_types(): void
    {
        $data = [
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'score' => 9.5,
            'tags' => ['php', 'laravel'],
            'source_bio' => 'Developer',
            'role' => 'admin',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        self::assertSame('Alice', $result['name']);
        self::assertSame(30, $result['age']);
        self::assertTrue($result['active']);
        self::assertSame(9.5, $result['score']);
        self::assertSame(['php', 'laravel'], $result['tags']);
        self::assertSame('Developer', $result['bio']);
        self::assertSame('admin', $result['role']);
    }

    public function test_from_array_to_array_excludes_hidden_fields(): void
    {
        $data = [
            'name' => 'Bob',
            'age' => 25,
            'active' => false,
            'tags' => [],
            'secret' => 'password123',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        self::assertArrayNotHasKey('secret', $result);
    }

    public function test_all_values_includes_hidden_fields(): void
    {
        $data = [
            'name' => 'Charlie',
            'age' => 40,
            'active' => true,
            'tags' => [],
            'secret' => 's3cret',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $all = $dto->allValues();

        self::assertArrayHasKey('secret', $all);
        self::assertSame('s3cret', $all['secret']);
    }

    // -----------------------------------------------------------------
    // MapFrom — source key mapping
    // -----------------------------------------------------------------

    public function test_map_from_maps_source_key_to_property(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
            'source_bio' => 'Mapped bio',
        ], validate: false);

        self::assertSame('Mapped bio', $dto->bio);
    }

    public function test_map_from_missing_source_key_uses_default(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        self::assertNull($dto->bio);
    }

    // -----------------------------------------------------------------
    // Cast type coercion
    // -----------------------------------------------------------------

    public function test_cast_integer_coerces_string_to_int(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => '25', // string
            'active' => true,
            'tags' => [],
        ], validate: false);

        self::assertIsInt($dto->age);
        self::assertSame(25, $dto->age);
    }

    public function test_cast_integer_coerces_float_string_to_int(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => '25.9',
            'active' => true,
            'tags' => [],
        ], validate: false);

        self::assertIsInt($dto->age);
        self::assertSame(25, $dto->age);
    }

    public function test_cast_array_passes_through_arrays(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
            'tags' => ['a', 'b'],
        ], validate: false);

        self::assertSame(['a', 'b'], $dto->tags);
    }

    public function test_cast_array_decodes_json_strings(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
            'tags' => '{"key": "value"}',
        ], validate: false);

        self::assertIsArray($dto->tags);
        self::assertSame(['key' => 'value'], $dto->tags);
    }

    // -----------------------------------------------------------------
    // DefaultValue
    // -----------------------------------------------------------------

    public function test_default_value_applied_when_key_absent(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        self::assertSame(0.0, $dto->score);
        self::assertSame([], $dto->tags);
        self::assertSame('user', $dto->role);
    }

    public function test_default_value_overridden_by_explicit_null(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
            'bio' => null,
        ], validate: false);

        self::assertNull($dto->bio);
    }

    // -----------------------------------------------------------------
    // Nullable properties
    // -----------------------------------------------------------------

    public function test_nullable_property_accepts_null(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
            'bio' => null,
        ], validate: false);

        self::assertNull($dto->bio);
    }

    public function test_nullable_property_accepts_value(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
            'bio' => 'Hello',
        ], validate: false);

        self::assertSame('Hello', $dto->bio);
    }

    // -----------------------------------------------------------------
    // isEmpty / isNotEmpty
    // -----------------------------------------------------------------

    public function test_is_empty_returns_true_for_all_defaults(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        self::assertTrue($dto->isEmpty());
        self::assertFalse($dto->isNotEmpty());
    }

    public function test_is_empty_returns_false_when_field_has_value(): void
    {
        $dto = AllDefaultsDTO::fromArray(['name' => 'custom'], validate: false);

        self::assertFalse($dto->isEmpty());
        self::assertTrue($dto->isNotEmpty());
    }

    public function test_is_empty_treats_zero_as_non_empty(): void
    {
        $dto = AllDefaultsDTO::fromArray(['count' => 0], validate: false);

        // count=0 is a non-nullable int, so 0 is NOT empty
        self::assertFalse($dto->isEmpty());
    }

    public function test_is_empty_treats_false_as_empty_for_bool(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        // active=false is the default, and false is considered empty
        self::assertTrue($dto->isEmpty());
    }

    // -----------------------------------------------------------------
    // equals — strict array comparison
    // -----------------------------------------------------------------

    public function test_equals_returns_true_for_same_data(): void
    {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => ['a'],
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => ['a'],
        ], validate: false);

        self::assertTrue($dto1->equals($dto2));
    }

    public function test_equals_ignores_hidden_fields(): void
    {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
            'secret' => 'pass1',
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
            'secret' => 'pass2',
        ], validate: false);

        // Hidden fields excluded from comparison
        self::assertTrue($dto1->equals($dto2));
    }

    public function test_equals_returns_false_for_different_data(): void
    {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        self::assertFalse($dto1->equals($dto2));
    }

    // -----------------------------------------------------------------
    // with — immutable update
    // -----------------------------------------------------------------

    public function test_with_returns_new_instance(): void
    {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        $dto2 = $dto1->with(['name' => 'Bob'], validate: false);

        self::assertNotSame($dto1, $dto2);
        self::assertSame('Alice', $dto1->name);
        self::assertSame('Bob', $dto2->name);
    }

    public function test_with_preserves_unchanged_fields(): void
    {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => ['x'],
        ], validate: false);

        $dto2 = $dto1->with(['name' => 'Bob'], validate: false);

        self::assertSame(30, $dto2->age);
        self::assertTrue($dto2->active);
        self::assertSame(['x'], $dto2->tags);
    }

    // -----------------------------------------------------------------
    // fromPartialArray — PATCH semantics
    // -----------------------------------------------------------------

    public function test_from_partial_array_only_updates_provided_fields(): void
    {
        $dto = RoundtripDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        self::assertSame('Updated Name', $dto->name);
        self::assertSame(0, $dto->age);       // default for int
        self::assertFalse($dto->active);       // default for bool
        self::assertSame(0.0, $dto->score);    // DefaultValue attribute
        self::assertSame([], $dto->tags);      // DefaultValue attribute
        self::assertNull($dto->bio);           // nullable, no default
        self::assertSame('user', $dto->role);  // DefaultValue attribute
    }

    // -----------------------------------------------------------------
    // fromJson — strict validation
    // -----------------------------------------------------------------

    public function test_from_json_parses_object(): void
    {
        $json = '{"name": "Alice", "age": 25, "active": true}';

        $dto = MinimalDTO::fromJson($json, validate: false);

        self::assertSame('Alice', $dto->name);
        self::assertSame('25', $dto->value);
    }

    public function test_from_json_rejects_sequential_array(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('["not", "an", "object"]');
    }

    public function test_from_json_rejects_invalid_json(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('{invalid json');
    }

    // -----------------------------------------------------------------
    // only / except
    // -----------------------------------------------------------------

    public function test_only_returns_specified_fields(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
            'bio' => 'Hello',
            'role' => 'admin',
        ], validate: false);

        $result = $dto->only('name', 'age');

        self::assertArrayHasKey('name', $result);
        self::assertArrayHasKey('age', $result);
        self::assertArrayNotHasKey('active', $result);
        self::assertArrayNotHasKey('bio', $result);
        self::assertArrayNotHasKey('role', $result);
        self::assertArrayNotHasKey('secret', $result);
    }

    public function test_except_excludes_specified_fields(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
            'bio' => 'Hello',
            'role' => 'admin',
        ], validate: false);

        $result = $dto->except('age', 'bio');

        self::assertArrayHasKey('name', $result);
        self::assertArrayNotHasKey('age', $result);
        self::assertArrayNotHasKey('bio', $result);
        self::assertArrayNotHasKey('secret', $result); // hidden, always excluded
    }

    // -----------------------------------------------------------------
    // toJson / jsonSerialize
    // -----------------------------------------------------------------

    public function test_to_json_returns_valid_json_string(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        $json = $dto->toJson();

        self::assertIsString($json);
        self::assertNotEmpty($json);

        $decoded = json_decode($json, true);

        self::assertIsArray($decoded);
        self::assertSame('Alice', $decoded['name']);
        self::assertArrayNotHasKey('secret', $decoded);
    }

    public function test_json_serialize_returns_array(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'tags' => [],
        ], validate: false);

        $result = $dto->jsonSerialize();

        self::assertIsArray($result);
        self::assertSame('Alice', $result['name']);
    }

    // -----------------------------------------------------------------
    // rules / rulesFor
    // -----------------------------------------------------------------

    public function test_rules_returns_array_of_arrays(): void
    {
        $rules = RoundtripDTO::rules();

        self::assertIsArray($rules);
        self::assertArrayHasKey('name', $rules);
        self::assertIsArray($rules['name']);

        self::assertContains('required', $rules['name']);
        self::assertContains('min:1', $rules['name']);
        self::assertContains('max:100', $rules['name']);
    }

    public function test_rules_for_returns_rules_by_default(): void
    {
        $rules = RoundtripDTO::rulesFor('update');

        self::assertSame(RoundtripDTO::rules(), $rules);
    }

    public function test_validate_array_returns_validated_data(): void
    {
        $result = MinimalDTO::validateArray([
            'name' => 'Test',
            'value' => '123',
        ]);

        self::assertIsArray($result);
        self::assertSame('Test', $result['name']);
    }

    // -----------------------------------------------------------------
    // Nested DTO
    // -----------------------------------------------------------------

    public function test_nested_dto_hydration(): void
    {
        $data = [
            'street' => '123 Main St',
            'city' => 'Springfield',
        ];

        $address = AddressDTO::fromArray($data, validate: false);

        self::assertSame('123 Main St', $address->street);
        self::assertSame('Springfield', $address->city);
        self::assertNull($address->zipCode);
    }

    // -----------------------------------------------------------------
    // DtoCollection — type safety
    // -----------------------------------------------------------------

    public function test_dto_collection_type_guards(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        self::assertCount(2, $collection);
        self::assertFalse($collection->isEmpty());
        self::assertTrue($collection->isNotEmpty());

        $first = $collection->first();
        self::assertNotNull($first);
        self::assertSame('A', $first->name);

        $last = $collection->last();
        self::assertNotNull($last);
        self::assertSame('B', $last->name);
    }

    public function test_dto_collection_pluck_extracts_property(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        self::assertSame(['Alice', 'Bob'], $collection->pluck('name'));
    }

    public function test_dto_collection_to_array_serializes(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $result = $collection->toArray();

        self::assertSame([
            ['name' => 'A', 'value' => '1'],
            ['name' => 'B', 'value' => '2'],
        ], $result);
    }

    public function test_dto_collection_push_mutates_and_returns(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $returned = $collection->push($dto2);

        self::assertSame($collection, $returned); // same instance
        self::assertCount(2, $collection);
    }

    public function test_dto_collection_append_returns_new_instance(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $original = new DtoCollection([$dto1]);
        $new = $original->append($dto2);

        self::assertNotSame($original, $new);
        self::assertCount(1, $original);
        self::assertCount(2, $new);
    }

    public function test_dto_collection_filter_returns_new_instance(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (DataTransferObject $dto): bool => $dto->name === 'Alice'
        );

        self::assertCount(1, $filtered);
        self::assertSame('Alice', $filtered->first()?->name);
    }

    public function test_dto_collection_map_returns_plain_array(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (DataTransferObject $dto): string => $dto->name);

        self::assertSame(['Alice', 'Bob'], $names);
    }

    public function test_dto_collection_json_serialize(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);

        $collection = new DtoCollection([$dto]);
        $result = $collection->jsonSerialize();

        self::assertSame([['name' => 'A', 'value' => '1']], $result);
    }

    // -----------------------------------------------------------------
    // ArrayAccess contract
    // -----------------------------------------------------------------

    public function test_dto_collection_array_access(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        self::assertTrue(isset($collection[0]));
        self::assertFalse(isset($collection[2]));
        self::assertSame('A', $collection[0]->name);
        self::assertSame('B', $collection[1]->name);
    }

    // -----------------------------------------------------------------
    // DtoException
    // -----------------------------------------------------------------

    public function test_dto_exception_invalid_json_message(): void
    {
        $exception = DTOException::invalidJson('data', 'Syntax error');

        self::assertStringContainsString('data', $exception->getMessage());
        self::assertStringContainsString('Syntax error', $exception->getMessage());
    }

    public function test_dto_exception_invalid_cast_message(): void
    {
        $exception = DTOException::invalidCast('age', 'integer', 'hello');

        self::assertStringContainsString('age', $exception->getMessage());
        self::assertStringContainsString('integer', $exception->getMessage());
    }

    public function test_dto_exception_to_string(): void
    {
        $exception = DTOException::invalidJson('field', 'err');

        $string = (string) $exception;

        self::assertIsString($string);
        self::assertStringContainsString('DTOException', $string);
    }

    // -----------------------------------------------------------------
    // Multiple fixture DTOs — contract consistency
    // -----------------------------------------------------------------

    /**
     * @dataProvider allFixtureDtosProvider
     */
    public function test_all_fixture_dtos_can_create_from_array(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray($data, validate: false);
        $result = $dto->toArray();

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }

    /**
     * @return array<string, array{dtoClass: class-string<DataTransferObject>, data: array<string, mixed>}>
     */
    public static function allFixtureDtosProvider(): array
    {
        return [
            'MinimalDTO' => [
                'dtoClass' => MinimalDTO::class,
                'data' => ['name' => 'Test', 'value' => '123'],
            ],
            'AddressDTO' => [
                'dtoClass' => AddressDTO::class,
                'data' => ['street' => '123 Main', 'city' => 'Springfield'],
            ],
            'AllDefaultsDTO' => [
                'dtoClass' => AllDefaultsDTO::class,
                'data' => [],
            ],
            'RoundtripDTO' => [
                'dtoClass' => RoundtripDTO::class,
                'data' => ['name' => 'Alice', 'age' => 30, 'active' => true, 'tags' => []],
            ],
            'CreateUserDTO' => [
                'dtoClass' => CreateUserDTO::class,
                'data' => ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'],
            ],
        ];
    }
}
