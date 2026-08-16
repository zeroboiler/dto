<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection serialization contract', function (): void {
    it('serializes to JSON via jsonSerialize', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveCount(2);
        expect($decoded[0]['email'])->toBe('a@test.com');
        expect($decoded[1]['email'])->toBe('b@test.com');
    });

    it('toArray returns serialized DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr[0])->toHaveKey('email');
        expect($arr[0])->toHaveKey('name');
    });

    it('allValues includes hidden properties', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });

    it('items returns raw DTO instances', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });

    it('empty collection serializes to empty JSON array', function (): void {
        $collection = new DtoCollection;
        $json = json_encode($collection);

        expect($json)->toBe('[]');
    });

    it('make creates collection from DTO instances', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = DtoCollection::make([$dto1]);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBeInstanceOf(CreateUserDTO::class);
    });

    it('filter returns new collection without mutating original', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (ZeroBoiler\DTO\DataTransferObject $d) => $d->name === 'Alice'
        );

        expect($filtered->count())->toBe(1);
        expect($collection->count())->toBe(2); // Original unchanged
    });

    it('append returns new collection without mutating original', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $appended = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('merge combines two collections without mutating originals', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('push mutates in place and returns same instance', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection); // Same instance
    });

    it('pluck extracts a single property from all DTOs', function (): void {
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

    it('pluckKey builds key-value dictionary', function (): void {
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

    it('toArrayBy is alias for pluckKey with single key', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveKey('a@test.com');
    });

    it('toDictionary extracts two properties as key-value', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict['a@test.com'])->toBe('Alice');
    });

    it('offsetGet returns null for out-of-bounds', function (): void {
        $collection = new DtoCollection;

        expect($collection->offsetGet(0))->toBeNull();
        expect($collection->offsetGet(99))->toBeNull();
    });

    it('offsetSet and offsetUnset work with ArrayAccess', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection;
        $collection->offsetSet(null, $dto1);
        $collection->offsetSet(0, $dto2); // Replace

        expect($collection->count())->toBe(1);
        expect($collection->offsetGet(0)->name)->toBe('Bob');

        $collection->offsetUnset(0);
        expect($collection->count())->toBe(0);
    });

    it('rejects non-DTO in constructor', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO in offsetSet', function (): void {
        $collection = new DtoCollection;

        expect(fn () => $collection->offsetSet(null, 'string'))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('clone throws RuntimeException', function (): void {
        $collection = new DtoCollection;

        expect(fn () => clone $collection)
            ->toThrow(\RuntimeException::class);
    });

    it('map returns plain array of results', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(
            fn (ZeroBoiler\DTO\DataTransferObject $d, int $i): string => $d->name
        );

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('first and last return correct items', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first()->name)->toBe('Alice');
        expect($collection->last()->name)->toBe('Bob');
    });

    it('first and last return null for empty collection', function (): void {
        $collection = new DtoCollection;

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('isEmpty and isNotEmpty work correctly', function (): void {
        $collection = new DtoCollection;
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();

        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        expect($collection->isEmpty())->toBeFalse();
        expect($collection->isNotEmpty())->toBeTrue();
    });
});
