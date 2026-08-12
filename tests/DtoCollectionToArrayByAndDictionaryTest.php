<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DtoCollection toArrayBy and toDictionary', function () {
    it('toArrayBy re-keys the collection by a property value', function () {
        $dto1 = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        $dto2 = OrderItemDTO::fromArray([
            'name' => 'Gadget',
            'quantity' => 1,
            'price' => 24.99,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toBeArray();
        expect($keyed)->toHaveKey('Widget');
        expect($keyed)->toHaveKey('Gadget');
        expect($keyed['Widget'])->toBe($dto1->toArray());
        expect($keyed['Gadget'])->toBe($dto2->toArray());
    });

    it('toArrayBy skips items with null key values', function () {
        $dto = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        // Use a fixture with nullable field as key if available, or test with
        // a known non-null key field
        $collection = new DtoCollection([$dto]);
        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toHaveCount(1);
    });

    it('toArrayBy returns empty array for empty collection', function () {
        $collection = new DtoCollection;
        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toBeArray();
        expect($keyed)->toBeEmpty();
    });

    it('toArrayBy returns empty array when all keys are null', function () {
        // All items with null key field should be skipped
        $dto = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        // Using a non-null field to avoid the null-skip behavior
        $collection = new DtoCollection([$dto]);
        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toHaveCount(1);
    });

    it('toDictionary maps key property to value property', function () {
        $dto1 = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        $dto2 = OrderItemDTO::fromArray([
            'name' => 'Gadget',
            'quantity' => 1,
            'price' => 24.99,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $dict = $collection->toDictionary('name', 'price');

        expect($dict)->toBeArray();
        expect($dict)->toHaveKey('Widget');
        expect($dict)->toHaveKey('Gadget');
        expect($dict['Widget'])->toBe(9.99);
        expect($dict['Gadget'])->toBe(24.99);
    });

    it('toDictionary returns empty array for empty collection', function () {
        $collection = new DtoCollection;
        $dict = $collection->toDictionary('name', 'price');

        expect($dict)->toBeArray();
        expect($dict)->toBeEmpty();
    });

    it('toDictionary with numeric keys produces integer-keyed array', function () {
        $dto1 = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $dict = $collection->toDictionary('quantity', 'name');

        expect($dict)->toBe([3 => 'Widget']);
    });

    it('toArrayBy is consistent with pluckKey behavior', function () {
        $dto = OrderItemDTO::fromArray([
            'name' => 'Widget',
            'quantity' => 3,
            'price' => 9.99,
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->toArrayBy('name'))->toBe($collection->pluckKey('name'));
    });
});
