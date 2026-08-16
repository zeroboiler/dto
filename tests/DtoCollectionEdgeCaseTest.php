<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

/**
 * Edge case and boundary tests for DtoCollection.
 *
 * Covers: empty collection, single item, clone immutability,
 * re-indexing after unset, type safety on push, filter/merge edge cases.
 */
describe('DtoCollection Edge Cases', function () {
    it('creates empty collection with no arguments', function () {
        $collection = DtoCollection::make();

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
        expect($collection->allValues())->toBe([]);
        expect($collection->items())->toBe([]);
    });

    it('creates collection with single item', function () {
        $item = ItemDTO::fromArray(['id' => 1, 'name' => 'Widget'], validate: false);
        $collection = DtoCollection::make([$item]);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe($item);
        expect($collection->last())->toBe($item);
    });

    it('filter returns new collection with correct items', function () {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false),
            ItemDTO::fromArray(['id' => 3, 'name' => 'Charlie'], validate: false),
        ];
        $collection = DtoCollection::make($items);
        $filtered = $collection->filter(fn (DataTransferObject $dto): bool => $dto->toArray()['id'] > 1);

        expect($filtered)->toBeInstanceOf(DtoCollection::class);
        expect($filtered->count())->toBe(2);
        expect($filtered)->not->toBe($collection);
    });

    it('filter of empty collection returns empty collection', function () {
        $collection = DtoCollection::make();
        $filtered = $collection->filter(fn (DataTransferObject $dto): bool => true);

        expect($filtered->isEmpty())->toBeTrue();
        expect($filtered)->not->toBe($collection);
    });

    it('pluck extracts single key values', function () {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false),
        ];
        $collection = DtoCollection::make($items);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alpha', 'Bravo']);
    });

    it('pluck of empty collection returns empty array', function () {
        $collection = DtoCollection::make();
        $names = $collection->pluck('name');

        expect($names)->toBe([]);
    });

    it('merge combines two collections', function () {
        $items1 = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
        ];
        $items2 = [
            ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false),
        ];
        $col1 = DtoCollection::make($items1);
        $col2 = DtoCollection::make($items2);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($merged)->not->toBe($col1);
    });

    it('merge with empty collection returns same count', function () {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
        ];
        $col1 = DtoCollection::make($items);
        $empty = DtoCollection::make();
        $merged = $col1->merge($empty);

        expect($merged->count())->toBe(1);
    });

    it('push returns new collection with added item', function () {
        $item1 = ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false);
        $item2 = ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false);
        $collection = DtoCollection::make([$item1]);
        $new = $collection->push($item2);

        expect($new->count())->toBe(2);
        expect($collection->count())->toBe(1); // Original unchanged
        expect($new)->not->toBe($collection);
    });

    it('append is alias for push', function () {
        $item1 = ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false);
        $item2 = ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false);
        $collection = DtoCollection::make([$item1]);
        $new = $collection->append($item2);

        expect($new->count())->toBe(2);
        expect($new->last())->toBe($item2);
    });

    it('first and last on single item return same DTO', function () {
        $item = ItemDTO::fromArray(['id' => 1, 'name' => 'Solo'], validate: false);
        $collection = DtoCollection::make([$item]);

        expect($collection->first())->toBe($item);
        expect($collection->last())->toBe($item);
    });

    it('first on empty collection returns null', function () {
        $collection = DtoCollection::make();

        expect($collection->first())->toBeNull();
    });

    it('last on empty collection returns null', function () {
        $collection = DtoCollection::make();

        expect($collection->last())->toBeNull();
    });

    it('toArray returns array of arrays', function () {
        $item = ItemDTO::fromArray(['id' => 42, 'name' => 'Xray'], validate: false);
        $collection = DtoCollection::make([$item]);
        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr[0])->toBeArray();
        expect($arr[0])->toHaveKey('name');
    });

    it('map returns array of transformed values', function () {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Bravo'], validate: false),
        ];
        $collection = DtoCollection::make($items);
        $mapped = $collection->map(fn (DataTransferObject $dto): string => $dto->toArray()['name']);

        expect($mapped)->toBe(['Alpha', 'Bravo']);
    });

    it('jsonSerialize returns same as toArray', function () {
        $item = ItemDTO::fromArray(['id' => 99, 'name' => 'JSON'], validate: false);
        $collection = DtoCollection::make([$item]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });

    it('isNotEmpty is inverse of isEmpty', function () {
        $empty = DtoCollection::make();
        $nonEmpty = DtoCollection::make([
            ItemDTO::fromArray(['id' => 1, 'name' => 'X'], validate: false),
        ]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('works with different DTO types', function () {
        $orderItem = OrderItemDTO::fromArray(['productName' => 'Widget', 'price' => 9.99], validate: false);
        $collection = DtoCollection::make([$orderItem]);

        expect($collection->count())->toBe(1);
        expect($collection->first()->toArray())->toHaveKey('productName');
        expect($collection->first()->toArray())->toHaveKey('price');
    });

    it('static make is equivalent to constructor', function () {
        $item = ItemDTO::fromArray(['id' => 1, 'name' => 'Test'], validate: false);
        $fromMake = DtoCollection::make([$item]);
        $fromNew = new DtoCollection([$item]);

        expect($fromMake->count())->toBe($fromNew->count());
        expect($fromMake->toArray())->toBe($fromNew->toArray());
    });
});
