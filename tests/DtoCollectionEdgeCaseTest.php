<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection immutability and edge cases', function () {
    it('push returns self for fluent chaining', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        // push() mutates in-place AND returns self (fluent)
        expect($result)->toBe($collection);
        expect($collection->count())->toBe(2);
        expect($collection->first()->email)->toBe('a@example.com');
        expect($collection->last()->email)->toBe('b@example.com');
    });

    it('rejects non-DTO via offsetSet', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect(fn () => $collection[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetSet at specific index replaces existing item', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect($collection[0]->email)->toBe('b@example.com');
    });

    it('offsetUnset re-indexes the array', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@example.com',
            'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        expect($collection->count())->toBe(2);
        // Re-indexed: old [1] is now [0], old [2] is now [1]
        expect($collection[0]->email)->toBe('b@example.com');
        expect($collection[1]->email)->toBe('c@example.com');
    });

    it('offsetExists returns false for non-existent keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->offsetExists(0))->toBeTrue();
        expect($collection->offsetExists(1))->toBeFalse();
        expect($collection->offsetExists('email'))->toBeFalse();
    });

    it('offsetGet returns null for non-existent keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->offsetGet(99))->toBeNull();
    });

    it('filter returns new DtoCollection with matching items', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bob@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => str_starts_with($dto->email, 'alice')
        );

        expect($filtered)->not->toBe($collection);
        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('map returns plain array with callback results', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('map receives correct index as second parameter', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $indices = $collection->map(fn (CreateUserDTO $dto, int $index): int => $index);

        expect($indices)->toBe([0, 1]);
    });

    it('jsonSerialize returns array of toArray results', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->toHaveKey('name');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret123');
    });

    it('isEmpty and isNotEmpty work correctly', function () {
        $empty = new DtoCollection;
        $nonEmpty = new DtoCollection([
            CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
            ], validate: false),
        ]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('make factory creates collection from array', function () {
        $dtos = [
            CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
            ], validate: false),
        ];

        $collection = DtoCollection::make($dtos);

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(1);
    });

    it('pluckKey builds key-value map from DTO properties', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bob@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'alice@example.com' => 'Alice',
            'bob@example.com' => 'Bob',
        ]);
    });

    it('pluckKey without valueField uses toArray', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@example.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $map = $collection->pluckKey('email');

        expect($map['alice@example.com'])->toBeArray();
        expect($map['alice@example.com'])->toHaveKey('name');
    });

    it('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not a dto', 42, null]))
            ->toThrow(\InvalidArgumentException::class);
    });
});
