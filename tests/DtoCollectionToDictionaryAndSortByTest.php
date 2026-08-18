<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

// --- Fixtures ---

class ItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly int $price,
    ) {}
}

class NullableKeyDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly string $label = '',
    ) {}
}

// --- Tests ---

describe('DtoCollection toDictionary', function () {
    it('maps one property to another as key-value pairs', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-A', 'Apple', 100),
            new ItemDTO('SKU-B', 'Banana', 50),
        ]);

        $dict = $items->toDictionary('sku', 'name');

        expect($dict)->toBe([
            'SKU-A' => 'Apple',
            'SKU-B' => 'Banana',
        ]);
    });

    it('skips items with null key property', function () {
        $items = new DtoCollection([
            new NullableKeyDTO(null, 'No Code'),
            new NullableKeyDTO('X1', 'Has Code'),
        ]);

        $dict = $items->toDictionary('code', 'label');

        expect($dict)->toBe([
            'X1' => 'Has Code',
        ]);
    });

    it('returns empty array for empty collection', function () {
        $items = new DtoCollection([]);

        expect($items->toDictionary('sku', 'name'))->toBe([]);
    });

    it('uses full DTO array when valueField is null', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-A', 'Apple', 100),
        ]);

        $dict = $items->toDictionary('sku');

        expect($dict)->toHaveKey('SKU-A');
        expect($dict['SKU-A'])->toBe(['sku' => 'SKU-A', 'name' => 'Apple', 'price' => 100]);
    });

    it('delegates to pluckKey internally', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-A', 'Apple', 100),
            new ItemDTO('SKU-B', 'Banana', 50),
        ]);

        // toDictionary and pluckKey should produce the same result
        expect($items->toDictionary('sku', 'name'))->toBe($items->pluckKey('sku', 'name'));
    });
});

describe('DtoCollection sortBy with property name', function () {
    it('sorts by string property ascending', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-C', 'Cherry', 30),
            new ItemDTO('SKU-A', 'Apple', 100),
            new ItemDTO('SKU-B', 'Banana', 50),
        ]);

        $sorted = $items->sortBy('name');

        expect($sorted->map(fn (ItemDTO $d) => $d->name))->toBe(['Apple', 'Banana', 'Cherry']);
    });

    it('sorts by int property ascending', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-C', 'Cherry', 30),
            new ItemDTO('SKU-A', 'Apple', 100),
            new ItemDTO('SKU-B', 'Banana', 50),
        ]);

        $sorted = $items->sortBy('price');

        expect($sorted->map(fn (ItemDTO $d) => $d->price))->toBe([30, 50, 100]);
    });

    it('returns new collection without mutating original', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-B', 'Banana', 50),
            new ItemDTO('SKU-A', 'Apple', 100),
        ]);

        $sorted = $items->sortBy('name');

        // Original order preserved
        expect($items->map(fn (ItemDTO $d) => $d->sku))->toBe(['SKU-B', 'SKU-A']);
        // Sorted order
        expect($sorted->map(fn (ItemDTO $d) => $d->sku))->toBe(['SKU-A', 'SKU-B']);
    });

    it('returns empty collection for empty input', function () {
        $items = new DtoCollection([]);

        $sorted = $items->sortBy('name');

        expect($sorted->isEmpty())->toBeTrue();
        expect($sorted)->not->toBe($items); // new instance
    });
});

describe('DtoCollection sortBy with callback', function () {
    it('sorts by callback return value', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-C', 'Cherry', 30),
            new ItemDTO('SKU-A', 'Apple', 100),
            new ItemDTO('SKU-B', 'Banana', 50),
        ]);

        // Sort by price descending
        $sorted = $items->sortBy(fn (ItemDTO $d) => -$d->price);

        expect($sorted->map(fn (ItemDTO $d) => $d->price))->toBe([100, 50, 30]);
    });

    it('callback receives correct DTO type', function () {
        $items = new DtoCollection([
            new ItemDTO('SKU-A', 'Apple', 100),
        ]);

        $sorted = $items->sortBy(function (DataTransferObject $dto) use (&$receivedType): int {
            $receivedType = get_class($dto);
            return 0;
        });

        expect($receivedType)->toBe(ItemDTO::class);
    });
});
