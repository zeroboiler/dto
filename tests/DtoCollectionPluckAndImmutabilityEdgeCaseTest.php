<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DtoCollection pluck() and pluckKey() edge cases', function () {
    it('pluck() returns empty array for empty collection', function () {
        $collection = DtoCollection::make([]);

        // pluck on a non-existent property on empty collection should throw
        // but empty collection short-circuits
        $result = $collection->toArray();

        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    });

    it('pluck() with single item collection returns array with one element', function () {
        $data = ['street' => 'Main St', 'city' => 'Springfield', 'zipCode' => '62701'];
        $dto = AddressDTO::fromArray($data, validate: false);
        $collection = new DtoCollection([$dto]);

        $streets = $collection->pluck('street');

        expect($streets)->toBe(['Main St']);
    });

    it('pluckKey() with valid key returns correct key-value mapping', function () {
        $data = ['productName' => 'Test', 'price' => 10.0, 'quantity' => 1];
        $dto = OrderItemDTO::fromArray($data, validate: false);
        $collection = new DtoCollection([$dto]);

        $result = $collection->pluckKey('quantity');

        expect($result)->toBeArray();
        expect($result)->toHaveKey(1);
        expect($result[1])->toBeArray(); // no value field → returns toArray()
    });

    it('pluckKey() skips items where key value is null', function () {
        // This tests that null keys don't become empty-string keys
        $data = ['productName' => 'Test', 'price' => 10.0, 'quantity' => 1];
        $dto = OrderItemDTO::fromArray($data, validate: false);
        $collection = new DtoCollection([$dto]);

        $result = $collection->pluckKey('quantity', 'productName');

        expect($result)->toBeArray();
        expect($result)->toHaveKey(1);
        expect($result[1])->toBe('Test');
    });

    it('toArrayBy() returns associative array keyed by property', function () {
        $data1 = ['productName' => 'Item A', 'price' => 10.0, 'quantity' => 1];
        $data2 = ['productName' => 'Item B', 'price' => 20.0, 'quantity' => 2];
        $dto1 = OrderItemDTO::fromArray($data1, validate: false);
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('quantity');

        expect($keyed)->toBeArray();
        expect($keyed)->toHaveKey(1);
        expect($keyed)->toHaveKey(2);
        expect($keyed[1])->toBeArray();
        expect($keyed[1])->toHaveKey('productName');
        expect($keyed[1]['productName'])->toBe('Item A');
    });

    it('toDictionary() returns key-value pairs', function () {
        $data1 = ['productName' => 'Item A', 'price' => 10.0, 'quantity' => 1];
        $data2 = ['productName' => 'Item B', 'price' => 20.0, 'quantity' => 2];
        $dto1 = OrderItemDTO::fromArray($data1, validate: false);
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $result = $collection->toDictionary('quantity', 'productName');

        expect($result)->toBe([1 => 'Item A', 2 => 'Item B']);
    });

    it('map() with index parameter works correctly', function () {
        $data1 = ['productName' => 'First', 'price' => 10.0, 'quantity' => 1];
        $data2 = ['productName' => 'Second', 'price' => 20.0, 'quantity' => 2];
        $dto1 = OrderItemDTO::fromArray($data1, validate: false);
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $result = $collection->map(fn (OrderItemDTO $dto, int $index): string => $dto->toArray()['productName'].'-'.$index);

        expect($result)->toBe(['First-0', 'Second-1']);
    });
});

describe('DtoCollection push vs append immutability', function () {
    it('push() mutates in-place and returns same instance', function () {
        $data = ['productName' => 'Item', 'price' => 10.0, 'quantity' => 1];
        $dto = OrderItemDTO::fromArray($data, validate: false);
        $collection = new DtoCollection([$dto]);

        $data2 = ['productName' => 'Item 2', 'price' => 20.0, 'quantity' => 2];
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);
        $result = $collection->push($dto2);

        expect($result)->toBe($collection); // Same instance
        expect($collection->count())->toBe(2);
    });

    it('append() returns new instance without mutating original', function () {
        $data = ['productName' => 'Item', 'price' => 10.0, 'quantity' => 1];
        $dto = OrderItemDTO::fromArray($data, validate: false);
        $collection = new DtoCollection([$dto]);

        $data2 = ['productName' => 'Item 2', 'price' => 20.0, 'quantity' => 2];
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);
        $newCollection = $collection->append($dto2);

        expect($newCollection)->not->toBe($collection); // Different instance
        expect($collection->count())->toBe(1); // Original unchanged
        expect($newCollection->count())->toBe(2); // New has both
    });
});

describe('DtoCollection clone restriction', function () {
    it('__clone() always throws RuntimeException', function () {
        $collection = DtoCollection::make([]);

        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });
});

describe('DtoCollection offsetUnset re-indexing', function () {
    it('re-indexes after offsetUnset to prevent key gaps', function () {
        $data1 = ['productName' => 'A', 'price' => 10.0, 'quantity' => 1];
        $data2 = ['productName' => 'B', 'price' => 20.0, 'quantity' => 2];
        $data3 = ['productName' => 'C', 'price' => 30.0, 'quantity' => 3];
        $dto1 = OrderItemDTO::fromArray($data1, validate: false);
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);
        $dto3 = OrderItemDTO::fromArray($data3, validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        expect($collection->count())->toBe(3);

        unset($collection[1]); // Remove middle item

        expect($collection->count())->toBe(2);
        // After re-indexing, keys should be 0 and 1
        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeTrue();
        expect(isset($collection[2]))->toBeFalse();

        // last() should return the third item (now at index 1)
        expect($collection->last()->toArray()['productName'])->toBe('C');
    });
});

describe('DtoCollection merge', function () {
    it('merge returns new collection with all items from both', function () {
        $data1 = ['productName' => 'A', 'price' => 10.0, 'quantity' => 1];
        $data2 = ['productName' => 'B', 'price' => 20.0, 'quantity' => 2];
        $dto1 = OrderItemDTO::fromArray($data1, validate: false);
        $dto2 = OrderItemDTO::fromArray($data2, validate: false);

        $c1 = new DtoCollection([$dto1]);
        $c2 = new DtoCollection([$dto2]);

        $merged = $c1->merge($c2);

        expect($merged)->not->toBe($c1);
        expect($merged)->not->toBe($c2);
        expect($merged->count())->toBe(2);
        expect($c1->count())->toBe(1); // Originals unchanged
        expect($c2->count())->toBe(1);
    });
});
