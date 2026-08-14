<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DtoCollection immutability contract and edge-case tests.
 *
 * Tests that DtoCollection properly enforces immutability through clone prevention,
 * append/merge create new instances, and push mutates in-place.
 *
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\DtoCollection::append()
 * @see \ZeroBoiler\DTO\DtoCollection::merge()
 * @see \ZeroBoiler\DTO\DtoCollection::push()
 */

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

// ── Tests ─────────────────────────────────────────────────────

describe('DtoCollection — Immutability Contract', function (): void {
    it('append returns new instance without modifying original', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($original)->not->toBe($appended);
    });

    it('merge returns new instance without modifying originals', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter returns new instance without modifying original', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $original = new DtoCollection([$dto1, $dto2]);
        $filtered = $original->filter(
            fn (CreateUserDTO $d): bool => $d->name === 'Alice'
        );

        expect($original->count())->toBe(2);
        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('push mutates in-place and returns same instance', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection); // same instance
    });
});

describe('DtoCollection — Clone Prevention', function (): void {
    it('clone throws RuntimeException', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });

    it('RuntimeException message mentions append and merge', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        try {
            clone $collection;
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            expect($e->getMessage())->toContain('append');
            expect($e->getMessage())->toContain('merge');
        }
    });
});

describe('DtoCollection — Constructor Validation', function (): void {
    it('accepts empty array', function (): void {
        $collection = new DtoCollection([]);
        expect($collection->count())->toBe(0);
        expect($collection->isEmpty())->toBeTrue();
    });

    it('rejects non-DTO items', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects null items', function (): void {
        expect(fn () => new DtoCollection([null]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects int items', function (): void {
        expect(fn () => new DtoCollection([42]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects string items', function (): void {
        expect(fn () => new DtoCollection(['string']))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('DtoCollection — ArrayAccess Edge Cases', function (): void {
    it('offsetGet returns null for non-existent index', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        expect($collection[99])->toBeNull();
    });

    it('offsetExists returns false for non-existent index', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        expect($collection->offsetExists(5))->toBeFalse();
    });

    it('offsetSet with null offset appends to end', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[null] = $dto2;

        expect($collection->count())->toBe(2);
        expect($collection[1]->email)->toBe('b@test.com');
    });

    it('offsetSet with index replaces existing item', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect($collection[0]->email)->toBe('b@test.com');
    });

    it('offsetSet rejects non-DTO value', function (): void {
        $collection = new DtoCollection([]);

        expect(fn () => $collection[0] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes after removal', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        // After re-indexing, indices should be 0 and 1
        expect($collection[0]->email)->toBe('b@test.com');
        expect($collection[1]->email)->toBe('c@test.com');
        expect($collection->count())->toBe(2);
    });
});

describe('DtoCollection — Iteration and Counting', function (): void {
    it('is iterable with foreach', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = [];

        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('count() works with PHP count()', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        expect(count($collection))->toBe(3);
    });

    it('make factory creates instance', function (): void {
        $collection = DtoCollection::make();

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->isEmpty())->toBeTrue();
    });

    it('last() returns last item without side effects', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        // Call last() twice — should not affect internal pointer
        expect($collection->last()->name)->toBe('Bob');
        expect($collection->last()->name)->toBe('Bob');
    });
});

describe('DtoCollection — Serialization', function (): void {
    it('jsonSerialize produces array of arrays', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded[0]['email'])->toBe('a@test.com');
        expect($decoded[1]['name'])->toBe('Bob');
    });

    it('toArray excludes hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $arr = $collection->toArray();

        // password is hidden
        expect($arr[0])->not->toHaveKey('password');
        expect($arr[0])->toHaveKey('email');
    });

    it('allValues includes hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $arr = $collection->allValues();

        expect($arr[0])->toHaveKey('password');
        expect($arr[0]['password'])->toBe('secret123');
    });
});

describe('DtoCollection — Structural Contract', function (): void {
    it('is a final class', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('has declare(strict_types=1)', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        $contents = file_get_contents((string) $ref->getFileName());
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('implements ArrayAccess', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
    });

    it('implements Countable', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
    });

    it('implements IteratorAggregate', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
    });

    it('implements JsonSerializable', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });
});
