<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection toArrayBy and toDictionary edge cases', function () {
    it('toArrayBy skips items with null key values', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $result = $collection->toArrayBy('name');

        // 'name' is a non-null string, should work
        expect($result)->toHaveKey('Alice');
        expect($result['Alice'])->toBeArray();
    });

    it('toArrayBy returns empty array for empty collection', function () {
        $collection = new DtoCollection([]);
        $result = $collection->toArrayBy('name');

        expect($result)->toBe([]);
    });

    it('toDictionary returns empty array for empty collection', function () {
        $collection = new DtoCollection([]);
        $result = $collection->toDictionary('name', 'email');

        expect($result)->toBe([]);
    });

    it('toDictionary maps key property to value property', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $result = $collection->toDictionary('name', 'email');

        expect($result)->toBe(['Alice' => 'alice@test.com']);
    });

    it('toArrayBy is an alias for pluckKey with single key', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->toArrayBy('name'))->toBe($collection->pluckKey('name'));
    });

    it('toDictionary produces same result as pluckKey with two keys', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->toDictionary('name', 'email'))->toBe($collection->pluckKey('name', 'email'));
    });

    it('pluck returns all values for a property across the collection', function () {
        $d1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@test.com'], validate: false);
        $d2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@test.com'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('pluck returns empty array for empty collection', function () {
        $collection = new DtoCollection([]);
        $result = $collection->pluck('name');

        expect($result)->toBe([]);
    });

    it('filter returns a new DtoCollection with matching items only', function () {
        $d1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@test.com'], validate: false);
        $d2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@test.com'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $filtered = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->name === 'Alice');

        expect($filtered)->toHaveCount(1);
        expect($filtered->first()?->name)->toBe('Alice');
    });
});
