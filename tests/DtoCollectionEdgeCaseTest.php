<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * DtoCollection edge-case tests.
 *
 * Tests immutability of append/merge, ArrayAccess consistency,
 * pluck/pluckKey with reflection, and iterator behavior.
 *
 * @covers \ZeroBoiler\DTO\DtoCollection
 */
final class DtoCollectionEdgeCaseTest extends TestCase
{
    private function createMinimalDto(string $name = 'Test', string $value = 'test'): MinimalDTO
    {
        return MinimalDTO::fromArray(['name' => $name, 'value' => $value], validate: false);
    }

    // -------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------

    public function test_make_creates_empty_collection(): void
    {
        $col = DtoCollection::make();

        expect($col->isEmpty())->toBeTrue();
        expect($col->count())->toBe(0);
    }

    public function test_constructor_rejects_non_dto_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DtoCollection([new \stdClass()]);
    }

    public function test_constructor_accepts_mixed_dto_subclasses(): void
    {
        $dto1 = $this->createMinimalDto('Alice', 'a');
        $dto2 = AddressDTO::fromArray(['street' => '123 Main', 'city' => 'NYC'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        expect($col->count())->toBe(2);
    }

    // -------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------

    public function test_offset_exists_for_valid_index(): void
    {
        $col = new DtoCollection([$this->createMinimalDto()]);

        expect($col->offsetExists(0))->toBeTrue();
        expect($col->offsetExists(1))->toBeFalse();
    }

    public function test_offset_get_returns_dto(): void
    {
        $dto = $this->createMinimalDto('Alice');
        $col = new DtoCollection([$dto]);

        expect($col[0]->toArray()['name'])->toBe('Alice');
    }

    public function test_offset_get_returns_null_for_missing(): void
    {
        $col = new DtoCollection();

        expect($col[0])->toBeNull();
    }

    public function test_offset_set_appends_when_offset_null(): void
    {
        $col = new DtoCollection();
        $dto = $this->createMinimalDto('Alice');

        $col[] = $dto;

        expect($col->count())->toBe(1);
    }

    public function test_offset_set_replaces_at_index(): void
    {
        $col = new DtoCollection([$this->createMinimalDto('Alice')]);
        $col[0] = $this->createMinimalDto('Bob');

        expect($col[0]->toArray()['name'])->toBe('Bob');
    }

    public function test_offset_set_rejects_non_dto(): void
    {
        $col = new DtoCollection();

        $this->expectException(\InvalidArgumentException::class);

        $col[] = 'not a dto';
    }

    public function test_offset_unset_reindexes(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
            $this->createMinimalDto('Charlie'),
        ]);

        unset($col[0]);

        expect($col->count())->toBe(2);
        expect($col[0]->toArray()['name'])->toBe('Bob');
        expect($col[1]->toArray()['name'])->toBe('Charlie');
    }

    // -------------------------------------------------------------------
    // Immutability: append / merge
    // -------------------------------------------------------------------

    public function test_append_creates_new_collection(): void
    {
        $original = new DtoCollection([$this->createMinimalDto('Alice')]);
        $modified = $original->append($this->createMinimalDto('Bob'));

        expect($original->count())->toBe(1);
        expect($modified->count())->toBe(2);
        expect($original)->not->toBe($modified);
    }

    public function test_merge_combines_two_collections(): void
    {
        $col1 = new DtoCollection([$this->createMinimalDto('Alice')]);
        $col2 = new DtoCollection([$this->createMinimalDto('Bob')]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    }

    // -------------------------------------------------------------------
    // Mutability: push
    // -------------------------------------------------------------------

    public function test_push_mutates_in_place_and_returns_self(): void
    {
        $col = new DtoCollection([$this->createMinimalDto('Alice')]);
        $result = $col->push($this->createMinimalDto('Bob'));

        expect($col->count())->toBe(2);
        expect($result)->toBe($col);
    }

    // -------------------------------------------------------------------
    // Iteration
    // -------------------------------------------------------------------

    public function test_foreach_traverses_all_items(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
        ]);

        $names = [];
        foreach ($col as $dto) {
            $names[] = $dto->toArray()['name'];
        }

        expect($names)->toBe(['Alice', 'Bob']);
    }

    // -------------------------------------------------------------------
    // first / last
    // -------------------------------------------------------------------

    public function test_first_returns_first_item(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
        ]);

        expect($col->first()->toArray()['name'])->toBe('Alice');
    }

    public function test_last_returns_last_item(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
        ]);

        expect($col->last()->toArray()['name'])->toBe('Bob');
    }

    public function test_first_returns_null_on_empty(): void
    {
        $col = new DtoCollection();

        expect($col->first())->toBeNull();
    }

    public function test_last_returns_null_on_empty(): void
    {
        $col = new DtoCollection();

        expect($col->last())->toBeNull();
    }

    // -------------------------------------------------------------------
    // pluck / pluckKey
    // -------------------------------------------------------------------

    public function test_pluck_extracts_single_field(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice', 'a'),
            $this->createMinimalDto('Bob', 'b'),
        ]);

        expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
    }

    public function test_pluckKey_creates_key_value_map(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice', 'a'),
            $this->createMinimalDto('Bob', 'b'),
        ]);

        $map = $col->pluckKey('value', 'name');

        expect($map)->toBe([
            'a' => 'Alice',
            'b' => 'Bob',
        ]);
    }

    public function test_pluckKey_without_value_field_uses_full_array(): void
    {
        $col = new DtoCollection([$this->createMinimalDto('Alice')]);

        $map = $col->pluckKey('name');

        expect($map)->toHaveKey('Alice');
        expect($map['Alice'])->toBeArray();
    }

    // -------------------------------------------------------------------
    // map / filter
    // -------------------------------------------------------------------

    public function test_map_returns_plain_array(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
        ]);

        $names = $col->map(fn (DataTransferObject $d): string => $d->toArray()['name']);

        expect($names)->toBe(['Alice', 'Bob']);
    }

    public function test_filter_returns_new_collection(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice'),
            $this->createMinimalDto('Bob'),
        ]);

        $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->toArray()['name'] === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->toArray()['name'])->toBe('Alice');
        // Original unchanged
        expect($col->count())->toBe(2);
    }

    // -------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------

    public function test_toArray_serializes_all_dtos(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto('Alice', 'a'),
            $this->createMinimalDto('Bob', 'b'),
        ]);

        $result = $col->toArray();

        expect($result)->toBe([
            ['name' => 'Alice', 'value' => 'a'],
            ['name' => 'Bob', 'value' => 'b'],
        ]);
    }

    public function test_allValues_includes_all_fields_in_dtos(): void
    {
        $col = new DtoCollection([$this->createMinimalDto('Alice')]);

        $result = $col->allValues();

        expect($result)->toBe([
            ['name' => 'Alice', 'value' => 'test'],
        ]);
    }

    public function test_jsonSerialize_returns_toArray(): void
    {
        $col = new DtoCollection([$this->createMinimalDto('Alice')]);

        expect($col->jsonSerialize())->toBe($col->toArray());
    }

    // -------------------------------------------------------------------
    // items / isEmpty / isNotEmpty
    // -------------------------------------------------------------------

    public function test_items_returns_raw_dto_instances(): void
    {
        $dto = $this->createMinimalDto('Alice');
        $col = new DtoCollection([$dto]);

        expect($col->items())->toBe([$dto]);
    }

    public function test_isEmpty_and_isNotEmpty(): void
    {
        $empty = new DtoCollection();
        $nonEmpty = new DtoCollection([$this->createMinimalDto()]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    }

    // -------------------------------------------------------------------
    // Countable
    // -------------------------------------------------------------------

    public function test_count_matches_count_method(): void
    {
        $col = new DtoCollection([
            $this->createMinimalDto(),
            $this->createMinimalDto(),
            $this->createMinimalDto(),
        ]);

        expect(count($col))->toBe(3);
        expect($col->count())->toBe(3);
    }
}
