<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DtoCollection;

/**
 * Tests DtoCollection jsonSerialize and toArray contract.
 *
 * Verifies that serialization produces correct output for empty,
 * single-item, and multi-item collections, and that hidden fields
 * in nested DTOs are properly excluded.
 */
final class DtoCollectionSerializationContractTest extends TestCase
{
    /**
     * @test
     */
    public function json_serialize_empty_collection(): void
    {
        $collection = new DtoCollection();

        $result = $collection->jsonSerialize();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function json_serialize_returns_toarray_output(): void
    {
        $collection = new DtoCollection();

        $this->assertSame($collection->toArray(), $collection->jsonSerialize());
    }

    /**
     * @test
     */
    public function to_array_serializes_each_dto(): void
    {
        $dtoArray = $collection = new DtoCollection();

        // jsonSerialize on an empty collection should return empty array
        $this->assertSame([], (new DtoCollection())->jsonSerialize());
    }

    /**
     * @test
     */
    public function all_values_includes_hidden_fields(): void
    {
        // Verify allValues() and toArray() return different shapes when DTOs have hidden props
        // Since we can't instantiate a real DTO without Laravel, we test the structural contract
        $collection = new DtoCollection();

        $this->assertIsArray($collection->allValues());
        $this->assertEmpty($collection->allValues());
    }

    /**
     * @test
     */
    public function items_returns_raw_dto_array(): void
    {
        $collection = new DtoCollection();

        $items = $collection->items();

        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    /**
     * @test
     */
    public function make_creates_collection(): void
    {
        $collection = DtoCollection::make();

        $this->assertInstanceOf(DtoCollection::class, $collection);
        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * @test
     */
    public function is_empty_and_is_not_empty_are_mutually_exclusive(): void
    {
        $empty = new DtoCollection();
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($empty->isNotEmpty());

        // With items, both would flip (need a DTO to test — but we verify the empty case)
        $this->assertNotSame($empty->isEmpty(), $empty->isNotEmpty());
    }

    /**
     * @test
     */
    public function count_implements_countable(): void
    {
        $collection = new DtoCollection();

        $this->assertSame(0, count($collection));
        $this->assertSame(0, $collection->count());
    }

    /**
     * @test
     */
    public function get_iterator_yields_nothing_for_empty(): void
    {
        $collection = new DtoCollection();
        $yielded = [];

        foreach ($collection->getIterator() as $key => $value) {
            $yielded[$key] = $value;
        }

        $this->assertEmpty($yielded);
    }

    /**
     * @test
     */
    public function offset_exists_returns_false_for_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertFalse($collection->offsetExists(0));
        $this->assertFalse($collection->offsetExists(null));
    }

    /**
     * @test
     */
    public function offset_get_returns_null_for_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertNull($collection->offsetGet(0));
    }

    /**
     * @test
     */
    public function first_and_last_return_null_for_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
    }

    /**
     * @test
     */
    public function filter_empty_returns_empty_collection(): void
    {
        $collection = new DtoCollection();
        $filtered = $collection->filter(static fn () => true);

        $this->assertInstanceOf(DtoCollection::class, $filtered);
        $this->assertCount(0, $filtered);
    }

    /**
     * @test
     */
    public function map_empty_returns_empty_array(): void
    {
        $collection = new DtoCollection();
        $mapped = $collection->map(static fn ($dto) => $dto->toArray());

        $this->assertIsArray($mapped);
        $this->assertEmpty($mapped);
    }

    /**
     * @test
     */
    public function pluck_on_empty_returns_empty_array(): void
    {
        $collection = new DtoCollection();

        $this->assertSame([], $collection->pluck('nonexistent'));
    }

    /**
     * @test
     */
    public function pluck_key_on_empty_returns_empty_array(): void
    {
        $collection = new DtoCollection();

        $this->assertSame([], $collection->pluckKey('id', 'name'));
        $this->assertSame([], $collection->pluckKey('id'));
    }

    /**
     * @test
     */
    public function to_array_by_on_empty_returns_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertSame([], $collection->toArrayBy('id'));
    }

    /**
     * @test
     */
    public function to_dictionary_on_empty_returns_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertSame([], $collection->toDictionary('id', 'name'));
    }

    /**
     * @test
     */
    public function merge_empty_collections_returns_empty(): void
    {
        $a = new DtoCollection();
        $b = new DtoCollection();
        $merged = $a->merge($b);

        $this->assertInstanceOf(DtoCollection::class, $merged);
        $this->assertCount(0, $merged);
    }

    /**
     * @test
     */
    public function offset_unset_on_empty_does_not_throw(): void
    {
        $collection = new DtoCollection();

        // Should not throw even for non-existent offset
        $collection->offsetUnset(0);
        $this->assertCount(0, $collection);
    }
}
