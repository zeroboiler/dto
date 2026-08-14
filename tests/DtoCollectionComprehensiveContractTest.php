<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection — comprehensive edge cases', function (): void {
    it('creates empty collection via make()', function (): void {
        $collection = DtoCollection::make();

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    it('creates collection from DTO instances', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
        expect($collection->isNotEmpty())->toBeTrue();
    });

    it('rejects non-DTO instances in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
    });

    it('first() and last() return correct items', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        expect($collection->first()->email)->toBe('a@b.com');
        expect($collection->last()->email)->toBe('b@c.com');
    });

    it('first() and last() return null on empty collection', function (): void {
        $collection = DtoCollection::make();

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('map() returns plain array with correct types', function (): void {
        $dto1 = ProductDTO::fromArray([
            'name' => 'Widget', 'price' => '10.00', 'stock' => 5,
        ], validate: false);
        $dto2 = ProductDTO::fromArray([
            'name' => 'Gadget', 'price' => '25.00', 'stock' => 3,
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->map(fn (ProductDTO $dto, int $i): string => $dto->name);

        expect($names)->toBe(['Widget', 'Gadget']);
    });

    it('filter() returns new collection with matching items', function (): void {
        $dto1 = ProductDTO::fromArray([
            'name' => 'Widget', 'price' => '10.00', 'stock' => 5,
        ], validate: false);
        $dto2 = ProductDTO::fromArray([
            'name' => 'Gadget', 'price' => '25.00', 'stock' => 0,
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $inStock = $collection->filter(fn (ProductDTO $dto): bool => $dto->stock > 0);

        expect($inStock->count())->toBe(1);
        expect($inStock->first()->name)->toBe('Widget');
    });

    it('pluck() extracts single property from each DTO', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@b.com', 'b@c.com']);
    });

    it('pluckKey() creates associative array keyed by property', function (): void {
        $dto1 = OrderItemDTO::fromArray([
            'productName' => 'Widget', 'price' => 10.0,
        ], validate: false);
        $dto2 = OrderItemDTO::fromArray([
            'productName' => 'Gadget', 'price' => 25.0,
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $byName = $collection->pluckKey('productName');

        expect($byName)->toHaveKey('Widget');
        expect($byName['Widget']['price'])->toBe(10.0);
    });

    it('toDictionary() extracts key-value pairs', function (): void {
        $dto1 = OrderItemDTO::fromArray([
            'productName' => 'Widget', 'price' => 10.0,
        ], validate: false);
        $dto2 = OrderItemDTO::fromArray([
            'productName' => 'Gadget', 'price' => 25.0,
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $map = $collection->toDictionary('productName', 'price');

        expect($map)->toBe(['Widget' => 10.0, 'Gadget' => 25.0]);
    });

    it('toArrayBy() is an alias for pluckKey()', function (): void {
        $dto = OrderItemDTO::fromArray([
            'productName' => 'Widget', 'price' => 10.0,
        ], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect($collection->toArrayBy('productName'))->toBe($collection->pluckKey('productName'));
    });

    it('append() creates new collection with added item', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $newCollection = $collection->append($dto2);

        expect($collection->count())->toBe(1); // Original unchanged
        expect($newCollection->count())->toBe(2);
    });

    it('merge() creates new collection with combined items', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection1 = DtoCollection::make([$dto1]);
        $collection2 = DtoCollection::make([$dto2]);
        $merged = $collection1->merge($collection2);

        expect($merged->count())->toBe(2);
        expect($collection1->count())->toBe(1); // Original unchanged
    });

    it('push() mutates in-place and returns self', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2); // Mutated
        expect($result)->toBe($collection); // Same instance
    });

    it('offsetExists/offsetGet/offsetSet/offsetUnset work correctly', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1]);

        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeFalse();
        expect($collection[0]->email)->toBe('a@b.com');

        // Set at offset
        $collection[1] = $dto2;
        expect($collection[1]->email)->toBe('b@c.com');

        // Unset re-indexes
        unset($collection[0]);
        expect(isset($collection[0]))->toBeTrue(); // Re-indexed
        expect($collection[0]->email)->toBe('b@c.com');
    });

    it('clone() throws RuntimeException', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect(fn (): mixed => clone $collection)
            ->toThrow(\RuntimeException::class, 'DtoCollection is immutable');
    });

    it('jsonSerialize produces correct JSON structure', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $collection = DtoCollection::make([$dto]);

        $json = json_encode($collection);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0]['email'])->toBe('a@b.com');
        expect($decoded[0])->not->toHaveKey('password'); // Hidden
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret',
        ], validate: false);
        $collection = DtoCollection::make([$dto]);

        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });

    it('foreach iteration works via IteratorAggregate', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = [];

        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('items() returns raw DTO instances', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $collection = DtoCollection::make([$dto]);

        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });
});
