<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * DTO hydration and serialization edge-case tests.
 *
 * Tests edge cases in fromArray, fromJson, fromPartialArray, with(),
 * toArray, equals, isEmpty, only/except, and JSON roundtrips.
 *
 * @covers \ZeroBoiler\DTO\DataTransferObject
 */
final class DTOHydrationEdgeCaseTest extends TestCase
{
    // -------------------------------------------------------------------
    // fromJson edge cases
    // -------------------------------------------------------------------

    public function test_fromJson_accepts_empty_object(): void
    {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto->toArray())->toHaveKey('foo');
        expect($dto->toArray())->toHaveKey('bar');
    }

    public function test_fromJson_rejects_sequential_array(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('["value1", "value2"]', validate: false);
    }

    public function test_fromJson_rejects_invalid_json(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('{"invalid json"', validate: false);
    }

    public function test_fromJson_rejects_non_object_top_level_number(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('42', validate: false);
    }

    public function test_fromJson_rejects_string_top_level(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('"just a string"', validate: false);
    }

    public function test_fromJson_rejects_boolean_top_level(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('true', validate: false);
    }

    public function test_fromJson_rejects_null_top_level(): void
    {
        $this->expectException(DTOException::class);

        MinimalDTO::fromJson('null', validate: false);
    }

    public function test_fromJson_hydrates_with_valid_data(): void
    {
        $dto = MinimalDTO::fromJson('{"name": "Alice", "value": "test"}', validate: false);

        expect($dto->toArray()['name'])->toBe('Alice');
        expect($dto->toArray()['value'])->toBe('test');
    }

    // -------------------------------------------------------------------
    // toArray / allValues — Hidden fields
    // -------------------------------------------------------------------

    public function test_toArray_excludes_hidden_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'password' => 'secret123',
        ], validate: false);

        $result = $dto->toArray();

        expect($result)->not->toHaveKey('password');
    }

    public function test_allValues_includes_hidden_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'password' => 'secret123',
        ], validate: false);

        $result = $dto->allValues();

        expect($result)->toHaveKey('password');
        expect($result['password'])->toBe('secret123');
    }

    // -------------------------------------------------------------------
    // MapFrom key aliasing
    // -------------------------------------------------------------------

    public function test_fromArray_maps_source_key_to_property(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->toArray()['phone'])->toBe('+1234567890');
    }

    // -------------------------------------------------------------------
    // Cast
    // -------------------------------------------------------------------

    public function test_cast_integer_transforms_string_to_int(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => '42',
            'active' => true,
        ], validate: false);

        expect($dto->toArray()['age'])->toBe(42);
    }

    public function test_cast_array_transforms_json_string(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'tags' => '["php","laravel"]',
        ], validate: false);

        expect($dto->toArray()['tags'])->toBe(['php', 'laravel']);
    }

    // -------------------------------------------------------------------
    // DefaultValue
    // -------------------------------------------------------------------

    public function test_default_value_applied_when_key_absent(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->toArray()['status'])->toBe('active');
    }

    public function test_default_value_not_overriding_explicit_value(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'inactive',
        ], validate: false);

        expect($dto->toArray()['status'])->toBe('inactive');
    }

    // -------------------------------------------------------------------
    // only / except
    // -------------------------------------------------------------------

    public function test_only_single_key_as_string(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->only('name');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('name');
    }

    public function test_only_multiple_keys(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->only('name', 'age');

        expect($result)->toHaveCount(2);
    }

    public function test_only_with_array_of_keys(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->only(['name', 'age']);

        expect($result)->toHaveCount(2);
    }

    public function test_except_single_key_as_string(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->except('name');

        expect($result)->not->toHaveKey('name');
    }

    public function test_except_with_array_of_keys(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->except(['name', 'age']);

        expect($result)->not->toHaveKey('name');
        expect($result)->not->toHaveKey('age');
        expect($result)->toHaveKey('active');
    }

    public function test_only_ignores_non_existent_keys(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->only('non_existent_key');

        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    }

    // -------------------------------------------------------------------
    // equals / isEmpty / isNotEmpty
    // -------------------------------------------------------------------

    public function test_equals_same_data_returns_true(): void
    {
        $a = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $b = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    }

    public function test_equals_different_data_returns_false(): void
    {
        $a = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $b = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 30,
            'active' => true,
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    }

    public function test_equals_ignores_hidden_fields(): void
    {
        $a = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'secret' => 'one',
        ], validate: false);

        $b = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'secret' => 'different',
        ], validate: false);

        // equals() compares toArray() which excludes hidden
        expect($a->equals($b))->toBeTrue();
    }

    public function test_isEmpty_with_all_defaults(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    }

    public function test_zero_int_is_not_empty(): void
    {
        // RoundtripDTO has score with default 0.0 — float 0.0 is empty
        // but age is required int — if set to 0 it's NOT empty
        $dto = RoundtripDTO::fromArray([
            'name' => '',
            'age' => 0,
            'active' => false,
        ], validate: false);

        // age=0 is non-nullable int, so 0 is a valid value → not empty
        expect($dto->isEmpty())->toBeFalse();
    }

    // -------------------------------------------------------------------
    // with() immutable update
    // -------------------------------------------------------------------

    public function test_with_creates_new_instance(): void
    {
        $original = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $modified = $original->with(['name' => 'Bob']);

        expect($modified)->not->toBe($original);
        expect($modified->toArray()['name'])->toBe('Bob');
    }

    public function test_with_does_not_mutate_original(): void
    {
        $original = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $original->with(['name' => 'Bob']);

        expect($original->toArray()['name'])->toBe('Alice');
    }

    // -------------------------------------------------------------------
    // toJson
    // -------------------------------------------------------------------

    public function test_toJson_produces_valid_json(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('Alice');
    }

    public function test_toJson_handles_pretty_print(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("\n");
    }

    public function test_jsonSerialize_matches_toArray(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    }

    // -------------------------------------------------------------------
    // fromPartialArray
    // -------------------------------------------------------------------

    public function test_fromPartialArray_uses_defaults_for_missing_fields(): void
    {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->toArray()['name'])->toBe('Alice');
        // score has DefaultValue(0.0)
        expect($dto->toArray()['score'])->toBe(0.0);
    }

    public function test_fromPartialArray_with_empty_data_uses_all_defaults(): void
    {
        $dto = EmptyDTO::fromPartialArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    }

    public function test_fromPartialArray_preserves_explicit_values(): void
    {
        $dto = RoundtripDTO::fromPartialArray([
            'name' => 'Bob',
            'age' => 25,
        ], validate: false);

        expect($dto->toArray()['name'])->toBe('Bob');
        expect($dto->toArray()['age'])->toBe(25);
    }

    // -------------------------------------------------------------------
    // MapFrom dot notation
    // -------------------------------------------------------------------

    public function test_fromArray_handles_dot_notation_map_from(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'source_bio' => 'Hello world',
        ], validate: false);

        expect($dto->toArray()['bio'])->toBe('Hello world');
    }

    // -------------------------------------------------------------------
    // rules() and rulesFor()
    // -------------------------------------------------------------------

    public function test_rules_returns_array(): void
    {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    }

    public function test_rulesFor_defaults_to_rules(): void
    {
        $rules = RoundtripDTO::rules();
        $rulesForCreate = RoundtripDTO::rulesFor('create');

        expect($rulesForCreate)->toBe($rules);
    }
}
