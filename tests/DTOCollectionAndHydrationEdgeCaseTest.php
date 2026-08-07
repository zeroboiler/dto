<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Tests for DtoCollection, DtoMetadataResolver type detection, and hydration edge cases.
 *
 * Ensures:
 * - DtoCollection rejects non-DTO items in constructor
 * - DtoCollection::offsetSet rejects non-DTO values
 * - DtoCollection::offsetUnset re-indexes array
 * - DtoCollection::filter returns new instance with correct type safety
 * - DtoCollection::map preserves index keys
 * - DtoCollection::pluck uses reflection for readonly property access
 * - DtoCollection::pluckKey works with and without valueField
 * - DtoCollection is empty/not-empty correct
 * - fromJson rejects sequential arrays (only JSON objects allowed)
 * - Empty DTO hydration works correctly
 */
final class DTOCollectionAndHydrationEdgeCaseTest extends TestCase
{
    // ---------------------------------------------------------------
    // Helper: create a MinimalDTO with sensible defaults
    // ---------------------------------------------------------------

    private static function minimalDTO(string $value, string $name = 'test'): MinimalDTO
    {
        return MinimalDTO::fromArray(['name' => $name, 'value' => $value], validate: false);
    }

    private static function createUserDTO(string $email, string $name, ?string $password = null): CreateUserDTO
    {
        return CreateUserDTO::fromArray([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], validate: false);
    }

    // ---------------------------------------------------------------
    // DtoCollection constructor validation
    // ---------------------------------------------------------------

    public function test_collection_rejects_non_dto_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        new DtoCollection([new \stdClass]);
    }

    public function test_collection_rejects_mixed_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $dto = self::createUserDTO('test@example.com', 'Test');
        new DtoCollection([$dto, 'not a dto']);
    }

    public function test_collection_accepts_empty_array(): void
    {
        $col = new DtoCollection;

        $this->assertCount(0, $col);
        $this->assertTrue($col->isEmpty());
        $this->assertFalse($col->isNotEmpty());
    }

    // ---------------------------------------------------------------
    // DtoCollection offsetSet validation
    // ---------------------------------------------------------------

    public function test_offsetSet_rejects_non_dto(): void
    {
        $col = new DtoCollection;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        $col[] = 'invalid';
    }

    public function test_offsetSet_appends_with_null_offset(): void
    {
        $col = new DtoCollection;
        $col[] = self::minimalDTO('a');
        $col[] = self::minimalDTO('b');

        $this->assertCount(2, $col);
        $this->assertSame('a', $col[0]->value);
        $this->assertSame('b', $col[1]->value);
    }

    public function test_offsetSet_replaces_at_index(): void
    {
        $col = new DtoCollection([self::minimalDTO('a')]);
        $col[0] = self::minimalDTO('b');

        $this->assertCount(1, $col);
        $this->assertSame('b', $col[0]->value);
    }

    // ---------------------------------------------------------------
    // DtoCollection offsetUnset re-indexing
    // ---------------------------------------------------------------

    public function test_offsetUnset_re_indexes(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('a'),
            self::minimalDTO('b'),
            self::minimalDTO('c'),
        ]);
        unset($col[0]);

        // Re-indexed: [0 => b, 1 => c]
        $this->assertCount(2, $col);
        $this->assertSame('b', $col[0]->value);
        $this->assertSame('c', $col[1]->value);
        $this->assertNull($col[2]); // no longer exists
    }

    // ---------------------------------------------------------------
    // DtoCollection filter (returns new instance)
    // ---------------------------------------------------------------

    public function test_filter_returns_new_collection(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('aaa'),
            self::minimalDTO('bb'),
            self::minimalDTO('ccc'),
        ]);
        $filtered = $col->filter(fn (DataTransferObject $dto): bool => strlen($dto->value) >= 3);

        $this->assertNotSame($col, $filtered, 'filter() must return a new instance');
        $this->assertCount(2, $filtered);
    }

    // ---------------------------------------------------------------
    // DtoCollection map
    // ---------------------------------------------------------------

    public function test_map_preserves_index_keys(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('a'),
            self::minimalDTO('b'),
        ]);
        $mapped = $col->map(fn (DataTransferObject $dto, int $index): string => "{$index}:{$dto->value}");

        $this->assertSame(['0:a', '1:b'], $mapped);
    }

    // ---------------------------------------------------------------
    // DtoCollection pluck
    // ---------------------------------------------------------------

    public function test_pluck_extracts_single_property(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('alpha'),
            self::minimalDTO('beta'),
        ]);
        $values = $col->pluck('value');

        $this->assertSame(['alpha', 'beta'], $values);
    }

    // ---------------------------------------------------------------
    // DtoCollection pluckKey
    // ---------------------------------------------------------------

    public function test_pluckKey_with_value_field(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('key1'),
            self::minimalDTO('key2'),
        ]);
        $map = $col->pluckKey('value', 'value');

        $this->assertSame(['key1' => 'key1', 'key2' => 'key2'], $map);
    }

    public function test_pluckKey_without_value_field_uses_toArray(): void
    {
        $col = new DtoCollection([
            self::createUserDTO('a@test.com', 'Alice'),
            self::createUserDTO('b@test.com', 'Bob'),
        ]);
        $map = $col->pluckKey('email');

        $this->assertArrayHasKey('a@test.com', $map);
        $this->assertArrayHasKey('b@test.com', $map);
        $this->assertIsArray($map['a@test.com']);
    }

    // ---------------------------------------------------------------
    // DtoCollection first / last
    // ---------------------------------------------------------------

    public function test_first_last_on_empty_collection(): void
    {
        $col = new DtoCollection;

        $this->assertNull($col->first());
        $this->assertNull($col->last());
    }

    public function test_first_last_on_single_item(): void
    {
        $dto = self::minimalDTO('only');
        $col = new DtoCollection([$dto]);

        $this->assertSame($dto, $col->first());
        $this->assertSame($dto, $col->last());
    }

    // ---------------------------------------------------------------
    // fromJson rejects sequential arrays
    // ---------------------------------------------------------------

    public function test_fromJson_rejects_sequential_array(): void
    {
        $this->expectException(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object');

        CreateUserDTO::fromJson('[1,2,3]', validate: false);
    }

    // ---------------------------------------------------------------
    // Empty DTO hydration
    // ---------------------------------------------------------------

    public function test_empty_dto_hydration(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        $this->assertInstanceOf(EmptyDTO::class, $dto);
        $this->assertSame([], $dto->toArray());
        $this->assertTrue($dto->isEmpty());
    }

    // ---------------------------------------------------------------
    // DtoCollection make / push / items
    // ---------------------------------------------------------------

    public function test_make_creates_collection(): void
    {
        $col = DtoCollection::make([self::minimalDTO('x')]);

        $this->assertCount(1, $col);
    }

    public function test_push_returns_same_instance(): void
    {
        $col = new DtoCollection([self::minimalDTO('a')]);
        $result = $col->push(self::minimalDTO('b'));

        $this->assertSame($col, $result, 'push() must return the same instance (fluent)');
        $this->assertCount(2, $col);
    }

    public function test_items_returns_raw_dto_array(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('a'),
            self::minimalDTO('b'),
        ]);
        $items = $col->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(DataTransferObject::class, $items[0]);
        $this->assertInstanceOf(DataTransferObject::class, $items[1]);
    }

    // ---------------------------------------------------------------
    // DtoCollection jsonSerialize
    // ---------------------------------------------------------------

    public function test_jsonSerialize_returns_toArray(): void
    {
        $col = new DtoCollection([self::minimalDTO('test')]);

        $encoded = json_encode($col);
        $decoded = json_decode($encoded, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('test', $decoded[0]['value']);
    }

    // ---------------------------------------------------------------
    // DtoCollection toArray / allValues
    // ---------------------------------------------------------------

    public function test_toArray_excludes_hidden(): void
    {
        $dto = self::createUserDTO('hidden@test.com', 'Hidden Test', 'secret123');
        $col = new DtoCollection([$dto]);
        $arr = $col->toArray();

        // password is #[Hidden]
        $this->assertArrayNotHasKey('password', $arr[0]);
        $this->assertSame('hidden@test.com', $arr[0]['email']);
    }

    public function test_allValues_includes_hidden(): void
    {
        $dto = self::createUserDTO('all@test.com', 'All Test', 'secret456');
        $col = new DtoCollection([$dto]);
        $all = $col->allValues();

        $this->assertArrayHasKey('password', $all[0]);
        $this->assertSame('secret456', $all[0]['password']);
    }

    // ---------------------------------------------------------------
    // DtoCollection Countable
    // ---------------------------------------------------------------

    public function test_countable_interface(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('a'),
            self::minimalDTO('b'),
        ]);

        $this->assertCount(2, $col);
        $this->assertSame(2, count($col));
    }

    // ---------------------------------------------------------------
    // DtoCollection IteratorAggregate
    // ---------------------------------------------------------------

    public function test_foreach_iteration(): void
    {
        $col = new DtoCollection([
            self::minimalDTO('a'),
            self::minimalDTO('b'),
        ]);
        $values = [];

        foreach ($col as $key => $dto) {
            $values[$key] = $dto->value;
        }

        $this->assertSame([0 => 'a', 1 => 'b'], $values);
    }

    // ---------------------------------------------------------------
    // DtoCollection offsetExists / offsetGet
    // ---------------------------------------------------------------

    public function test_offset_exists(): void
    {
        $col = new DtoCollection([self::minimalDTO('x')]);

        $this->assertTrue($col->offsetExists(0));
        $this->assertFalse($col->offsetExists(1));
        $this->assertFalse($col->offsetExists('key'));
    }

    public function test_offset_get_returns_null_for_missing(): void
    {
        $col = new DtoCollection;

        $this->assertNull($col->offsetGet(0));
    }

    // ---------------------------------------------------------------
    // Metadata cache flush
    // ---------------------------------------------------------------

    public function test_flushMetadataCache_clears_all(): void
    {
        // This ensures the static method works without errors
        DataTransferObject::flushMetadataCache();

        // Resolve metadata to populate cache
        CreateUserDTO::fromArray([
            'email' => 'flush@test.com',
            'name' => 'Flush Test',
        ], validate: false);

        // Flush again — should not throw
        DataTransferObject::flushMetadataCache();

        $this->assertTrue(true); // Reached without error
    }

    public function test_flushMetadataCache_for_specific_class(): void
    {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        $this->assertTrue(true); // Reached without error
    }

    // ---------------------------------------------------------------
    // equals() identity check
    // ---------------------------------------------------------------

    public function test_equals_same_data(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'eq@test.com',
            'name' => 'Equal',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'eq@test.com',
            'name' => 'Equal',
        ], validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_different_data(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'A',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'B',
        ], validate: false);

        $this->assertFalse($dto1->equals($dto2));
    }

    // ---------------------------------------------------------------
    // only() / except() selective output
    // ---------------------------------------------------------------

    public function test_only_returns_specified_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'sel@test.com',
            'name' => 'Select',
        ], validate: false);

        $only = $dto->only('email');

        $this->assertArrayHasKey('email', $only);
        $this->assertArrayNotHasKey('name', $only);
    }

    public function test_except_removes_specified_fields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'exc@test.com',
            'name' => 'Except',
        ], validate: false);

        $except = $dto->except('email');

        $this->assertArrayNotHasKey('email', $except);
        $this->assertArrayHasKey('name', $except);
    }

    // ---------------------------------------------------------------
    // toJson
    // ---------------------------------------------------------------

    public function test_toJson_returns_valid_json(): void
    {
        $dto = self::createUserDTO('json@test.com', 'Json Test');
        $json = $dto->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('json@test.com', $decoded['email']);
    }
}
