<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DtoCollection edge cases', function () {
    it('constructs from empty array', function () {
        $collection = new DtoCollection;
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
        expect($collection->isNotEmpty())->toBeFalse();
        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('rejects non-DTO items', function () {
        new DtoCollection([new \stdClass]);
    })->throws(\InvalidArgumentException::class);

    it('make creates from array of DTOs', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        expect($collection->count())->toBe(2);
    });

    it('push appends and returns self (fluent)', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection;
        $result = $collection->push($dto);
        expect($result)->toBe($collection); // same instance (fluent)
        expect($collection->count())->toBe(1);
    });

    it('map returns plain array', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $d): string => $d->name);
        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('filter returns new collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
            'status' => 'inactive',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $active = $collection->filter(fn (CreateUserDTO $d): bool => $d->status === 'active');
        expect($active)->not->toBe($collection); // new instance
        expect($active->count())->toBe(1);
    });

    it('pluck extracts a single field', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $emails = $collection->pluck('email');
        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('pluckKey builds key-value map', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');
        expect($map)->toBe([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });

    it('pluckKey without value field returns full arrays', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $map = $collection->pluckKey('email');
        expect($map)->toHaveKey('a@test.com');
        expect($map['a@test.com'])->toBeArray();
    });

    it('ArrayAccess works for iteration', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        // offsetExists
        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeTrue();
        expect(isset($collection[2]))->toBeFalse();

        // offsetGet
        expect($collection[0]->name)->toBe('Alice');
        expect($collection[1]->name)->toBe('Bob');
        expect($collection[99])->toBeNull();

        // offsetSet
        $collection[2] = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ], validate: false);
        expect($collection->count())->toBe(3);
    });

    it('offsetUnset re-indexes', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);
        expect($collection->count())->toBe(2);
        // After re-index, first item should now be Bob (index 0)
        expect($collection->first()->name)->toBe('Bob');
        expect($collection[0]->name)->toBe('Bob');
        expect($collection->last()->name)->toBe('Charlie');
    });

    it('jsonSerialize returns array of arrays', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $serialized = $collection->jsonSerialize();
        expect($serialized)->toBeArray();
        expect($serialized[0])->toBeArray();
        expect($serialized[0])->toHaveKey('email');
    });

    it('toArray returns serialized DTOs', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $arr = $collection->toArray();
        expect($arr[0])->not->toHaveKey('password'); // Hidden
        expect($arr[0])->toHaveKey('email');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $arr = $collection->allValues();
        expect($arr[0])->toHaveKey('password');
        expect($arr[0]['password'])->toBe('secret');
    });

    it('items returns raw DTO instances', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $items = $collection->items();
        expect($items[0])->toBe($dto); // same instance
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });

    it('IteratorAggregate works with foreach', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = [];
        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }
        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('offsetSet rejects non-DTO', function () {
        $collection = new DtoCollection;
        $collection[] = 'not a dto';
    })->throws(\InvalidArgumentException::class);
});
