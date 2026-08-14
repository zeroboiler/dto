<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Tests for DtoCollection push() mutability semantics and append() immutability.
 *
 * Verifies that push() mutates in-place while append() returns a new collection,
 * and documents the behavioral difference for consumers.
 *
 * @covers \ZeroBoiler\DTO\DtoCollection
 */
final class DtoCollectionMutabilityTest extends TestCase
{
    // -----------------------------------------------------------------------
    // push() — mutates in-place, returns self for chaining
    // -----------------------------------------------------------------------

    public function test_push_mutates_original_collection(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $returned = $collection->push($dto2);

        // push() returns self (same instance)
        self::assertSame($collection, $returned);
        // Original collection is mutated
        self::assertCount(2, $collection);
    }

    public function test_push_chain_multiple_items(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'name' => 'Carol',
            'email' => 'carol@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection->push($dto2)->push($dto3);

        self::assertCount(3, $collection);
    }

    // -----------------------------------------------------------------------
    // append() — returns new collection, original unchanged
    // -----------------------------------------------------------------------

    public function test_append_returns_new_collection_without_mutating_original(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $original = new DtoCollection([$dto1]);
        $newCollection = $original->append($dto2);

        // append() returns a NEW instance
        self::assertNotSame($original, $newCollection);
        // Original is unchanged
        self::assertCount(1, $original);
        // New has both
        self::assertCount(2, $newCollection);
    }

    // -----------------------------------------------------------------------
    // merge() — returns new collection, neither original mutated
    // -----------------------------------------------------------------------

    public function test_merge_returns_new_collection_without_mutating_originals(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collectionA = new DtoCollection([$dto1]);
        $collectionB = new DtoCollection([$dto2]);

        $merged = $collectionA->merge($collectionB);

        self::assertNotSame($collectionA, $merged);
        self::assertNotSame($collectionB, $merged);
        self::assertCount(1, $collectionA);
        self::assertCount(1, $collectionB);
        self::assertCount(2, $merged);
    }

    // -----------------------------------------------------------------------
    // offsetSet() — direct mutation
    // -----------------------------------------------------------------------

    public function test_offset_set_appends_when_offset_null(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[] = $dto2;

        self::assertCount(2, $collection);
    }

    public function test_offset_set_replaces_at_offset(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        self::assertCount(1, $collection);
        self::assertSame('Bob', $collection[0]->name);
    }

    // -----------------------------------------------------------------------
    // offsetUnset() — removes and re-indexes
    // -----------------------------------------------------------------------

    public function test_offset_unset_removes_and_reindexes(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'name' => 'Carol',
            'email' => 'carol@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[1]); // Remove Bob

        self::assertCount(2, $collection);
        // After re-index: [0] => Alice, [1] => Carol
        self::assertSame('Alice', $collection[0]->name);
        self::assertSame('Carol', $collection[1]->name);
    }

    // -----------------------------------------------------------------------
    // Type guard — non-DTO rejection
    // -----------------------------------------------------------------------

    public function test_constructor_rejects_non_dto(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        // @phpstan-ignore-next-line Intentional wrong type for test
        new DtoCollection(['not a dto']);
    }

    public function test_offset_set_rejects_non_dto(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $collection = new DtoCollection([$dto]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        // @phpstan-ignore-next-line Intentional wrong type for test
        $collection[] = 'not a dto';
    }

    // -----------------------------------------------------------------------
    // isEmpty / isNotEmpty
    // -----------------------------------------------------------------------

    public function test_is_empty_on_new_collection(): void
    {
        $collection = new DtoCollection;

        self::assertTrue($collection->isEmpty());
        self::assertFalse($collection->isNotEmpty());
    }

    public function test_is_not_empty_after_push(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);

        $collection = new DtoCollection;
        $collection->push($dto);

        self::assertFalse($collection->isEmpty());
        self::assertTrue($collection->isNotEmpty());
    }

    // -----------------------------------------------------------------------
    // make() static factory
    // -----------------------------------------------------------------------

    public function test_make_creates_collection(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);

        $collection = DtoCollection::make([$dto]);

        self::assertCount(1, $collection);
        self::assertInstanceOf(DtoCollection::class, $collection);
    }

    public function test_make_creates_empty_collection(): void
    {
        $collection = DtoCollection::make();

        self::assertCount(0, $collection);
        self::assertTrue($collection->isEmpty());
    }

    // -----------------------------------------------------------------------
    // first / last
    // -----------------------------------------------------------------------

    public function test_first_returns_first_item(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        self::assertSame('Alice', $collection->first()?->name);
    }

    public function test_first_returns_null_on_empty(): void
    {
        $collection = new DtoCollection;

        self::assertNull($collection->first());
    }

    public function test_last_returns_last_item(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        self::assertSame('Bob', $collection->last()?->name);
    }

    public function test_last_returns_null_on_empty(): void
    {
        $collection = new DtoCollection;

        self::assertNull($collection->last());
    }

    // -----------------------------------------------------------------------
    // map / filter
    // -----------------------------------------------------------------------

    public function test_map_returns_plain_array(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        self::assertSame(['Alice', 'Bob'], $names);
    }

    public function test_filter_returns_new_collection(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => $dto->name === 'Alice'
        );

        self::assertNotSame($collection, $filtered);
        self::assertCount(1, $filtered);
        self::assertSame('Alice', $filtered[0]->name);
    }

    // -----------------------------------------------------------------------
    // Serialization
    // -----------------------------------------------------------------------

    public function test_to_array_serializes_each_dto(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $array = $collection->toArray();

        self::assertSame([['name' => 'Alice', 'email' => 'alice@example.com']], $array);
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        self::assertSame($collection->toArray(), $collection->jsonSerialize());
    }
}
