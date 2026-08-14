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
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * Edge-case coverage tests for DtoCollection mutations, partial updates,
 * fromJson edge cases, and DataTransferObject serialization contracts.
 */
final class DtoEdgeCaseCoverageV2Test extends TestCase
{
    private function makeMinimal(string $name = 'Alice', string $value = 'test'): MinimalDTO
    {
        return MinimalDTO::fromArray(['name' => $name, 'value' => $value], validate: false);
    }

    private function makeRoundtrip(array $overrides = []): RoundtripDTO
    {
        return RoundtripDTO::fromArray(array_merge([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], $overrides), validate: false);
    }

    // ── DtoCollection Mutation & Immutability ──────────────────────

    public function test_collection_push_mutates_in_place(): void
    {
        $collection = new DtoCollection([$this->makeMinimal()]);

        $result = $collection->push($this->makeMinimal('Bob'));

        $this->assertSame($collection, $result); // push returns self
        $this->assertCount(2, $collection);
    }

    public function test_collection_append_returns_new_instance(): void
    {
        $collection = new DtoCollection([$this->makeMinimal()]);

        $newCollection = $collection->append($this->makeMinimal('Bob'));

        $this->assertNotSame($collection, $newCollection);
        $this->assertCount(1, $collection);
        $this->assertCount(2, $newCollection);
    }

    public function test_collection_merge_two_collections(): void
    {
        $col1 = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);
        $col2 = new DtoCollection([$this->makeMinimal('Charlie')]);

        $merged = $col1->merge($col2);

        $this->assertCount(3, $merged);
        $this->assertNotSame($col1, $merged);
        $this->assertCount(2, $col1); // original unchanged
    }

    public function test_collection_filter_returns_new_instance(): void
    {
        $collection = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);
        $filtered = $collection->filter(
            fn (DataTransferObject $dto) => $dto->toArray()['name'] === 'Alice'
        );

        $this->assertNotSame($collection, $filtered);
        $this->assertCount(1, $filtered);
        $this->assertCount(2, $collection);
    }

    public function test_collection_filter_reindexes(): void
    {
        $collection = new DtoCollection([
            $this->makeMinimal('Alice'),
            $this->makeMinimal('Bob'),
            $this->makeMinimal('Charlie'),
        ]);
        $filtered = $collection->filter(
            fn (DataTransferObject $dto) => $dto->toArray()['name'] !== 'Bob'
        );

        $this->assertCount(2, $filtered);
        // Keys should be re-indexed [0, 1], not [0, 2]
        $this->assertSame([0, 1], array_keys($filtered->items()));
    }

    public function test_collection_offset_unset_reindexes(): void
    {
        $collection = new DtoCollection([
            $this->makeMinimal('Alice'),
            $this->makeMinimal('Bob'),
            $this->makeMinimal('Charlie'),
        ]);
        unset($collection[1]); // Remove Bob

        $this->assertCount(2, $collection);
        $this->assertSame('Alice', $collection[0]->toArray()['name']);
        $this->assertSame('Charlie', $collection[1]->toArray()['name']);
    }

    public function test_collection_rejects_non_dto_in_constructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        new DtoCollection([new \stdClass()]);
    }

    public function test_collection_rejects_non_dto_in_offset_set(): void
    {
        $collection = new DtoCollection();

        $this->expectException(\InvalidArgumentException::class);
        $collection[] = new \stdClass();
    }

    // ── DtoCollection Iteration ───────────────────────────────────

    public function test_collection_get_iterator_yields_all_items(): void
    {
        $collection = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);

        $collected = [];
        foreach ($collection as $dto) {
            $collected[] = $dto->toArray()['name'];
        }

        $this->assertSame(['Alice', 'Bob'], $collected);
    }

    public function test_collection_count_matches(): void
    {
        $collection = new DtoCollection([
            $this->makeMinimal('Alice'),
            $this->makeMinimal('Bob'),
            $this->makeMinimal('Charlie'),
        ]);

        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->count());
    }

    public function test_collection_first_and_last(): void
    {
        $collection = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);

        $first = $collection->first();
        $last = $collection->last();

        $this->assertNotNull($first);
        $this->assertNotNull($last);
        $this->assertSame('Alice', $first->toArray()['name']);
        $this->assertSame('Bob', $last->toArray()['name']);
    }

    public function test_collection_first_last_null_on_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    // ── DtoCollection Pluck & Dictionary ──────────────────────────

    public function test_collection_pluck_extracts_property(): void
    {
        $collection = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);
        $names = $collection->pluck('name');

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_collection_map_with_index(): void
    {
        $collection = new DtoCollection([$this->makeMinimal('Alice'), $this->makeMinimal('Bob')]);
        $result = $collection->map(fn (DataTransferObject $dto, int $index) => $index . '-' . $dto->toArray()['name']);

        $this->assertSame(['0-Alice', '1-Bob'], $result);
    }

    // ── DtoCollection Serialization ───────────────────────────────

    public function test_collection_json_serialize(): void
    {
        $collection = new DtoCollection([$this->makeMinimal()]);

        $serialized = $collection->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertCount(1, $serialized);
        $this->assertSame(['name' => 'Alice', 'value' => 'test'], $serialized[0]);
    }

    public function test_collection_to_array_vs_all_values(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
            'phone_number' => '555-1234',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        // toArray() excludes hidden properties
        $publicArray = $collection->toArray();
        $this->assertArrayNotHasKey('password', $publicArray[0]);

        // allValues() includes hidden properties
        $allValues = $collection->allValues();
        $this->assertArrayHasKey('password', $allValues[0]);
    }

    public function test_collection_make_factory(): void
    {
        $collection = DtoCollection::make([$this->makeMinimal()]);

        $this->assertCount(1, $collection);
    }

    // ── fromJson Edge Cases ───────────────────────────────────────

    public function test_from_json_throws_for_invalid_json(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Cannot decode JSON');

        MinimalDTO::fromJson('{invalid json}');
    }

    public function test_from_json_throws_for_sequential_array(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object (associative array), got a sequential array');

        MinimalDTO::fromJson('["Alice", "Bob"]');
    }

    public function test_from_json_with_nested_data_roundtrip(): void
    {
        $dto = $this->makeRoundtrip(['tags' => ['dev', 'php']]);
        $json = $dto->toJson();

        $restored = RoundtripDTO::fromJson($json, validate: false);

        $this->assertTrue($dto->equals($restored));
    }

    public function test_from_json_with_map_from_key(): void
    {
        $dto = RoundtripDTO::fromJson(json_encode([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'source_bio' => 'Hello world',
        ]), validate: false);

        $this->assertSame('Hello world', $dto->toArray()['bio']);
    }

    // ── isEmpty / isNotEmpty ────────────────────────────────────────

    public function test_empty_dto_with_all_null_properties_is_empty(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        $this->assertTrue($dto->isEmpty());
        $this->assertFalse($dto->isNotEmpty());
    }

    public function test_empty_dto_with_non_null_property_is_not_empty(): void
    {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    // ── equals() Contract ──────────────────────────────────────────

    public function test_equals_returns_true_for_same_data(): void
    {
        $dto1 = $this->makeMinimal();
        $dto2 = $this->makeMinimal();

        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_returns_false_for_different_data(): void
    {
        $dto1 = $this->makeMinimal('Alice');
        $dto2 = $this->makeMinimal('Bob');

        $this->assertFalse($dto1->equals($dto2));
    }

    // ── only / except ─────────────────────────────────────────────

    public function test_only_returns_specified_fields(): void
    {
        $dto = $this->makeMinimal();
        $result = $dto->only('name');

        $this->assertSame(['name' => 'Alice'], $result);
    }

    public function test_only_with_multiple_keys(): void
    {
        $dto = $this->makeMinimal();
        $result = $dto->only(['name', 'value']);

        $this->assertSame(['name' => 'Alice', 'value' => 'test'], $result);
    }

    public function test_only_with_nonexistent_key(): void
    {
        $dto = $this->makeMinimal();
        $result = $dto->only('nonexistent');

        $this->assertSame([], $result);
    }

    public function test_except_removes_specified_fields(): void
    {
        $dto = $this->makeRoundtrip(['tags' => ['dev']]);
        $result = $dto->except('tags');

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayNotHasKey('tags', $result);
    }

    // ── DTOException Factory Methods ───────────────────────────────

    public function test_dto_exception_invalid_cast(): void
    {
        $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');

        $this->assertStringContainsString('age', $ex->getMessage());
        $this->assertStringContainsString('integer', $ex->getMessage());
    }

    public function test_dto_exception_invalid_json(): void
    {
        $ex = DTOException::invalidJson('payload', 'Syntax error');

        $this->assertStringContainsString('payload', $ex->getMessage());
        $this->assertStringContainsString('Syntax error', $ex->getMessage());
    }

    public function test_dto_exception_to_string(): void
    {
        $ex = DTOException::invalidJson('data', 'error');

        $string = (string) $ex;

        $this->assertStringStartsWith(DTOException::class, $string);
        $this->assertStringContainsString($ex->getMessage(), $string);
    }

    // ── allValues Includes Hidden ─────────────────────────────────

    public function test_all_values_includes_hidden_properties(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
            'phone_number' => '555-1234',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret123', $all['password']);
    }

    // ── toJson Hardening ────────────────────────────────────────────

    public function test_to_json_returns_valid_json_string(): void
    {
        $dto = $this->makeMinimal();
        $json = $dto->toJson();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Alice', $decoded['name']);
    }

    public function test_to_json_with_pretty_print(): void
    {
        $dto = $this->makeMinimal();
        $json = $dto->toJson(JSON_PRETTY_PRINT);

        // Pretty-printed JSON should contain newlines
        $this->assertStringContainsString("\n", $json);
    }

    // ── jsonSerialize Contract ─────────────────────────────────────

    public function test_json_serialize_equals_to_array(): void
    {
        $dto = $this->makeMinimal();

        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    // ── with() Immutable Update ───────────────────────────────────

    public function test_with_returns_new_instance(): void
    {
        $dto = $this->makeMinimal();
        $updated = $dto->with(['name' => 'Bob'], validate: false);

        $this->assertNotSame($dto, $updated);
        $this->assertSame('Alice', $dto->toArray()['name']);
        $this->assertSame('Bob', $updated->toArray()['name']);
    }

    public function test_with_preserves_original(): void
    {
        $dto = $this->makeRoundtrip(['tags' => ['dev']]);
        $updated = $dto->with(['age' => 31], validate: false);

        // Original unchanged
        $this->assertSame(30, $dto->toArray()['age']);
        // Updated has new value
        $this->assertSame(31, $updated->toArray()['age']);
        // Tags preserved
        $this->assertSame(['dev'], $updated->toArray()['tags']);
    }

    public function test_with_hidden_property_excluded_from_merge(): void
    {
        $dto = $this->makeRoundtrip(['secret' => 'hidden-value']);
        $updated = $dto->with(['name' => 'Bob'], validate: false);

        // toArray() should NOT include secret (hidden)
        $this->assertArrayNotHasKey('secret', $updated->toArray());
    }

    // ── fromPartialArray Edge Cases ────────────────────────────────

    public function test_from_partial_array_with_no_data_uses_defaults(): void
    {
        $dto = RoundtripDTO::fromPartialArray([], validatePresent: false);

        // Should have defaults for all optional fields
        $arr = $dto->toArray();
        $this->assertSame(0.0, $arr['score']);
        $this->assertSame([], $arr['tags']);
        $this->assertSame('user', $arr['role']);
    }

    public function test_from_partial_array_only_updates_provided_fields(): void
    {
        $dto = RoundtripDTO::fromPartialArray(['age' => 25], validatePresent: false);

        $arr = $dto->toArray();
        $this->assertSame(25, $arr['age']);
        // Other required fields get type-appropriate empty values
        $this->assertSame('', $arr['name']); // string → ''
        $this->assertSame(false, $arr['active']); // bool → false
    }

    public function test_from_partial_array_respects_map_from(): void
    {
        $dto = RoundtripDTO::fromPartialArray(['source_bio' => 'Updated bio'], validatePresent: false);

        $this->assertSame('Updated bio', $dto->toArray()['bio']);
    }
}
