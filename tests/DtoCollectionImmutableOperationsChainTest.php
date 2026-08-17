<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

/**
 * DtoCollection immutable operation chaining and edge case tests.
 *
 * Covers fluent method chaining, method immutability guarantees,
 * boundary conditions, and type safety in collection operations.
 */
describe('DtoCollection Immutable Operations Chain', function (): void {
    function makeDto(string $email, string $name): CreateUserDTO
    {
        return CreateUserDTO::fromArray([
            'email' => $email,
            'name' => $name,
        ], validate: false);
    }

    it('append returns a new collection with the added item', function (): void {
        $col = DtoCollection::make([makeDto('a@b.com', 'A')]);
        $new = $col->append(makeDto('c@d.com', 'C'));

        expect($col->count())->toBe(1);
        expect($new->count())->toBe(2);
        expect($new->last()?->name)->toBe('C');
    });

    it('merge combines two collections without mutating originals', function (): void {
        $col1 = DtoCollection::make([makeDto('a@b.com', 'A')]);
        $col2 = DtoCollection::make([makeDto('c@d.com', 'C'), makeDto('e@f.com', 'E')]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter returns new collection without matching items', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
            makeDto('e@f.com', 'Eve'),
        ]);

        $filtered = $col->filter(fn (CreateUserDTO $dto) => strlen($dto->name) > 4);

        expect($filtered->count())->toBe(2);
        expect($filtered->first()?->name)->toBe('Alice');
        expect($col->count())->toBe(3); // Original unchanged
    });

    it('sortBy returns new sorted collection', function (): void {
        $col = DtoCollection::make([
            makeDto('c@d.com', 'Charlie'),
            makeDto('a@b.com', 'Alice'),
            makeDto('e@f.com', 'Eve'),
        ]);

        $sorted = $col->sortBy('name');

        expect($sorted->first()?->name)->toBe('Alice');
        expect($sorted->last()?->name)->toBe('Eve');
        expect($col->first()?->name)->toBe('Charlie'); // Original unchanged
    });

    it('sortBy callback sorts by derived value', function (): void {
        $col = DtoCollection::make([
            makeDto('short@b.com', 'Bob'),
            makeDto('long@b.com', 'Alexander'),
            makeDto('mid@b.com', 'Charlie'),
        ]);

        $sorted = $col->sortBy(fn (CreateUserDTO $dto) => strlen($dto->name));

        expect($sorted->first()?->name)->toBe('Bob');
        expect($sorted->last()?->name)->toBe('Alexander');
    });

    it('sortBy pushes null values to the end', function (): void {
        // Use toArray which may have null property values
        $col = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
        ]);

        $sorted = $col->sortBy('nonexistent');

        // Should not throw, null values sorted to end
        expect($sorted->count())->toBe(1);
    });

    it('take returns first N items', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'A'),
            makeDto('c@d.com', 'C'),
            makeDto('e@f.com', 'E'),
        ]);

        $taken = $col->take(2);

        expect($taken->count())->toBe(2);
        expect($col->count())->toBe(3);
    });

    it('skip returns remaining items after N', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'A'),
            makeDto('c@d.com', 'C'),
            makeDto('e@f.com', 'E'),
        ]);

        $skipped = $col->skip(1);

        expect($skipped->count())->toBe(2);
        expect($skipped->first()?->name)->toBe('C');
    });

    it('chunk splits collection correctly', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'A'),
            makeDto('c@d.com', 'C'),
            makeDto('e@f.com', 'E'),
            makeDto('g@h.com', 'G'),
            makeDto('i@j.com', 'I'),
        ]);

        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(3);
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    it('unique removes duplicates by toArray() comparison', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('a@b.com', 'Alice'), // Duplicate
            makeDto('c@d.com', 'Charlie'),
        ]);

        $unique = $col->unique();

        expect($unique->count())->toBe(2);
    });

    it('push mutates in-place and returns self', function (): void {
        $col = DtoCollection::make([makeDto('a@b.com', 'A')]);
        $result = $col->push(makeDto('c@d.com', 'C'));

        expect($col->count())->toBe(2); // Mutated
        expect($result)->toBe($col); // Same instance
    });

    it('contains checks predicate', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        expect($col->contains(fn (CreateUserDTO $d) => $d->name === 'Alice'))->toBeTrue();
        expect($col->contains(fn (CreateUserDTO $d) => $d->name === 'Bob'))->toBeFalse();
    });

    it('search returns first matching item or null', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $found = $col->search(fn (CreateUserDTO $d) => $d->email === 'c@d.com');
        $notFound = $col->search(fn (CreateUserDTO $d) => $d->email === 'x@y.com');

        expect($found?->name)->toBe('Charlie');
        expect($notFound)->toBeNull();
    });

    it('toArray serializes all items', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $arr = $col->toArray();

        expect($arr)->toHaveCount(2);
        expect($arr[0])->toHaveKey('email');
        expect($arr[0])->toHaveKey('name');
        expect($arr[0]['email'])->toBe('a@b.com');
    });

    it('pluck extracts single property from all items', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    it('pluckKey creates associative array', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('map returns plain array of callback results', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $names = $col->map(fn (CreateUserDTO $d) => $d->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('toArrayBy re-keys by property value', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $keyed = $col->toArrayBy('email');

        expect($keyed)->toHaveKey('a@b.com');
        expect($keyed['a@b.com'])->toHaveKey('name');
    });

    it('toDictionary extracts key-value pairs', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $dict = $col->toDictionary('email', 'name');

        expect($dict)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('offsetUnset re-indexes collection', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'A'),
            makeDto('c@d.com', 'C'),
            makeDto('e@f.com', 'E'),
        ]);

        unset($col[1]); // Remove middle item

        expect($col->count())->toBe(2);
        expect($col[0]?->name)->toBe('A');
        expect($col[1]?->name)->toBe('E');
    });

    it('rejects non-DTO items in constructor', function (): void {
        expect(fn () => new DtoCollection(['not_a_dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in offsetSet', function (): void {
        $col = DtoCollection::make([]);
        expect(fn () => $col[] = 'not_a_dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('make creates empty collection from no arguments', function (): void {
        $col = DtoCollection::make();

        expect($col->isEmpty())->toBeTrue();
        expect($col->count())->toBe(0);
    });

    it('jsonSerialize returns toArray output', function (): void {
        $col = DtoCollection::make([makeDto('a@b.com', 'Alice')]);

        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('implements IteratorAggregate for foreach', function (): void {
        $col = DtoCollection::make([
            makeDto('a@b.com', 'Alice'),
            makeDto('c@d.com', 'Charlie'),
        ]);

        $names = [];
        foreach ($col as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('implements ArrayAccess for index access', function (): void {
        $col = DtoCollection::make([makeDto('a@b.com', 'Alice')]);

        expect(isset($col[0]))->toBeTrue();
        expect($col[0]?->name)->toBe('Alice');
        expect($col[99])->toBeNull();
    });

    it('isEmpty and isNotEmpty are consistent', function (): void {
        $empty = DtoCollection::make();
        $nonEmpty = DtoCollection::make([makeDto('a@b.com', 'A')]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('allValues includes hidden properties of nested DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $col = DtoCollection::make([$dto1]);
        $all = $col->allValues();

        // allValues delegates to each DTO's allValues()
        expect($all[0])->toHaveKey('password');
    });
});
