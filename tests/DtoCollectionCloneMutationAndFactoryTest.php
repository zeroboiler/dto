<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection clone isolation', function (): void {
    it('append returns a new collection without modifying original', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'one@example.com',
            'name' => 'One',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'two@example.com',
            'name' => 'Two',
        ], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended)->not->toBe($original);
    });

    it('clone creates an independent copy', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'clone@example.com',
            'name' => 'Clone',
        ], validate: false);

        $original = new DtoCollection([$dto1]);
        $cloned = clone $original;

        // Both should have the same count
        expect($cloned->count())->toBe($original->count());

        // Mutating clone should not affect original
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'new@example.com',
            'name' => 'New',
        ], validate: false);

        $cloned[] = $dto2;

        expect($original->count())->toBe(1);
        expect($cloned->count())->toBe(2);
    });

    it('merge returns a new collection without modifying originals', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'A',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'B',
        ], validate: false);

        $collection1 = new DtoCollection([$dto1]);
        $collection2 = new DtoCollection([$dto2]);

        $merged = $collection1->merge($collection2);

        expect($collection1->count())->toBe(1);
        expect($collection2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('filter returns a new collection without modifying original', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'keep@example.com',
            'name' => 'Keep',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'remove@example.com',
            'name' => 'Remove',
        ], validate: false);

        $original = new DtoCollection([$dto1, $dto2]);
        $filtered = $original->filter(
            fn (CreateUserDTO $dto): bool => $dto->name === 'Keep'
        );

        expect($original->count())->toBe(2);
        expect($filtered->count())->toBe(1);
    });
});

describe('DtoCollection offsetSet mutation', function (): void {
    it('offsetSet appends when offset is null', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = new DtoCollection;
        $collection[] = $dto;

        expect($collection->count())->toBe(1);
        expect($collection->first())->not->toBeNull();
    });

    it('offsetSet replaces at specific index', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'old@example.com',
            'name' => 'Old',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'new@example.com',
            'name' => 'New',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect($collection->first()->email)->toBe('new@example.com');
    });

    it('offsetSet rejects non-DTO values', function (): void {
        $collection = new DtoCollection;

        expect(fn (): mixed => $collection[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes the array', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'one@example.com',
            'name' => 'One',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'two@example.com',
            'name' => 'Two',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'three@example.com',
            'name' => 'Three',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[1]);

        // After re-index, keys should be 0, 1 (not 0, 2)
        $items = $collection->items();
        expect(array_keys($items))->toBe([0, 1]);
        expect($collection->count())->toBe(2);
    });
});

describe('DtoCollection push mutates in-place', function (): void {
    it('push adds item and returns same instance for chaining', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = new DtoCollection;
        $result = $collection->push($dto);

        expect($collection->count())->toBe(1);
        expect($result)->toBe($collection); // Same instance
    });
});

describe('DtoCollection rejects non-DTO in constructor', function (): void {
    it('throws on string in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws on int in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection([42]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws on null in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection([null]))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('DtoCollection make factory', function (): void {
    it('creates empty collection', function (): void {
        $collection = DtoCollection::make();

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(0);
        expect($collection->isEmpty())->toBeTrue();
    });

    it('creates collection with items', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = DtoCollection::make([$dto]);

        expect($collection->count())->toBe(1);
    });
});

describe('DtoCollection pluckKey', function (): void {
    it('creates key-value pairs from DTO properties', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $mapped = $collection->pluckKey('email', 'name');

        expect($mapped)->toBeArray();
        expect($mapped)->toHaveKey('a@example.com');
        expect($mapped['a@example.com'])->toBe('Alice');
    });
});
