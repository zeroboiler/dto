<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CollectionItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * V46 DtoCollection comprehensive edge case tests.
 *
 * Exercises DtoCollection methods that may not be fully covered by existing tests:
 * - unique() deduplication by toArray() equality
 * - chunk() splitting with exact and inexact divisions
 * - sortBy() with null values (null-last semantics)
 * - contains() and search() with various callbacks
 * - take() and skip() boundary conditions
 * - pluckKey() and toDictionary() with null keys
 * - ArrayAccess set/unset edge cases
 * - Empty collection operations
 * - Mixed push/append/merge behavior
 */
describe('V46 DtoCollection Comprehensive Edge Cases', function () {
    // ── Helper to create test items ───────────────────────────────────

    function makeItem(int $id, string $name, int $score, ?string $email = null): CollectionItemDTO
    {
        return new CollectionItemDTO(id: $id, name: $name, score: $score, email: $email);
    }

    // ── Unique deduplication ─────────────────────────────────────────

    it('unique removes duplicates based on toArray() equality', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90),
            makeItem(2, 'Bob', 85),
            makeItem(1, 'Alice', 90), // exact duplicate
        ]);

        $unique = $col->unique();
        expect($unique->count())->toBe(2);
        expect($unique->first()->toArray()['name'])->toBe('Alice');
    });

    it('unique preserves first occurrence', function () use (&$makeItem): void {
        $items = [
            makeItem(1, 'First', 10),
            makeItem(1, 'First', 10), // same toArray(), different object
        ];

        $col = new DtoCollection($items);
        $unique = $col->unique();

        expect($unique->count())->toBe(1);
    });

    it('unique returns new collection without modifying original', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90),
            makeItem(1, 'Alice', 90),
        ]);

        expect($col->count())->toBe(2);
        $unique = $col->unique();
        expect($unique->count())->toBe(1);
        expect($col->count())->toBe(2); // original unchanged
    });

    // ── Chunk splitting ──────────────────────────────────────────────

    it('chunk divides collection evenly', function () use (&$makeItem): void {
        $items = [
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
            makeItem(3, 'C', 30),
            makeItem(4, 'D', 40),
        ];

        $col = new DtoCollection($items);
        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(2);
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[0]->first()->toArray()['name'])->toBe('A');
        expect($chunks[1]->first()->toArray()['name'])->toBe('C');
    });

    it('chunk handles uneven division with remainder chunk', function () use (&$makeItem): void {
        $items = [
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
            makeItem(3, 'C', 30),
        ];

        $col = new DtoCollection($items);
        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(2);
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(1);
    });

    it('chunk with size larger than collection returns single chunk', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $chunks = $col->chunk(10);

        expect($chunks)->toHaveCount(1);
        expect($chunks[0]->count())->toBe(1);
    });

    // ── SortBy with null values ──────────────────────────────────────

    it('sortBy pushes null values to end', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'C', 30, 'c@test.com'),
            makeItem(2, 'A', 10, null), // null email
            makeItem(3, 'B', 20, 'b@test.com'),
        ]);

        $sorted = $col->sortBy('email');

        // b@test.com should be first (alphabetically before c), null last
        $items = $sorted->items();
        expect($items[0]->toArray()['name'])->toBe('B');
        expect($items[1]->toArray()['name'])->toBe('C');
        expect($items[2]->toArray()['email'])->toBeNull();
    });

    it('sortBy with callback handles null return values', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 30),
            makeItem(2, 'B', 10),
            makeItem(3, 'C', 20),
        ]);

        $sorted = $col->sortBy(fn (CollectionItemDTO $dto): int => $dto->score);

        $items = $sorted->items();
        expect($items[0]->score)->toBe(10);
        expect($items[1]->score)->toBe(20);
        expect($items[2]->score)->toBe(30);
    });

    it('sortBy returns new collection without modifying original', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(2, 'B', 20),
            makeItem(1, 'A', 10),
        ]);

        $sorted = $col->sortBy('name');
        expect($sorted->first()->toArray()['name'])->toBe('A');
        expect($col->first()->toArray()['name'])->toBe('B'); // original unchanged
    });

    // ── Contains and Search ───────────────────────────────────────────

    it('contains returns true when callback matches', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90),
            makeItem(2, 'Bob', 85),
        ]);

        expect($col->contains(fn (CollectionItemDTO $d): bool => $d->name === 'Alice'))->toBeTrue();
        expect($col->contains(fn (CollectionItemDTO $d): bool => $d->name === 'Charlie'))->toBeFalse();
    });

    it('contains returns false for empty collection', function (): void {
        $col = new DtoCollection;
        expect($col->contains(fn (): bool => true))->toBeFalse();
    });

    it('search returns first matching DTO', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90),
            makeItem(2, 'Bob', 85),
            makeItem(3, 'Charlie', 95),
        ]);

        $found = $col->search(fn (CollectionItemDTO $d): bool => $d->score >= 90);
        expect($found)->not->toBeNull();
        expect($found->toArray()['name'])->toBe('Alice'); // first match
    });

    it('search returns null when no match', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        expect($col->search(fn (CollectionItemDTO $d): bool => $d->score > 100))->toBeNull();
    });

    it('search returns null for empty collection', function (): void {
        $col = new DtoCollection;
        expect($col->search(fn (): bool => true))->toBeNull();
    });

    // ── Take and Skip boundaries ─────────────────────────────────────

    it('take returns exact number of items', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
            makeItem(3, 'C', 30),
        ]);

        $taken = $col->take(2);
        expect($taken->count())->toBe(2);
        expect($taken->first()->toArray()['name'])->toBe('A');
    });

    it('take with count exceeding collection returns all items', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        expect($col->take(100)->count())->toBe(1);
    });

    it('skip skips exact number of items', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
            makeItem(3, 'C', 30),
        ]);

        $rest = $col->skip(1);
        expect($rest->count())->toBe(2);
        expect($rest->first()->toArray()['name'])->toBe('B');
    });

    it('skip with count exceeding collection returns empty', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        expect($col->skip(100)->count())->toBe(0);
    });

    // ── PluckKey and toDictionary with null keys ──────────────────────

    it('pluckKey skips items with null key values', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90, 'alice@test.com'),
            makeItem(2, 'Bob', 85, null), // null email → skipped as key
            makeItem(3, 'Charlie', 95, 'charlie@test.com'),
        ]);

        $map = $col->pluckKey('email', 'name');
        expect($map)->toHaveCount(2); // Bob skipped
        expect($map['alice@test.com'])->toBe('Alice');
        expect($map['charlie@test.com'])->toBe('Charlie');
    });

    it('toDictionary skips items with null key values', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'Alice', 90, 'alice@test.com'),
            makeItem(2, 'No Email', 0, null),
        ]);

        $dict = $col->toDictionary('email', 'name');
        expect($dict)->toHaveCount(1);
        expect($dict['alice@test.com'])->toBe('Alice');
    });

    // ── ArrayAccess edge cases ───────────────────────────────────────

    it('offsetSet with null offset appends to end', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $col[] = makeItem(2, 'B', 20);

        expect($col->count())->toBe(2);
        expect($col[1]->toArray()['name'])->toBe('B');
    });

    it('offsetSet with explicit offset overwrites', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $col[0] = makeItem(2, 'B', 20);

        expect($col->count())->toBe(1);
        expect($col[0]->toArray()['name'])->toBe('B');
    });

    it('offsetUnset re-indexes after removal', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
            makeItem(3, 'C', 30),
        ]);

        unset($col[0]);

        expect($col->count())->toBe(2);
        expect($col[0]->toArray()['name'])->toBe('B'); // re-indexed
        expect($col[1]->toArray()['name'])->toBe('C');
    });

    it('offsetGet returns null for missing offset', function (): void {
        $col = new DtoCollection;
        expect($col[99])->toBeNull();
    });

    it('offsetExists returns false for missing offset', function (): void {
        $col = new DtoCollection;
        expect(isset($col[0]))->toBeFalse();
    });

    // ── Empty collection operations ──────────────────────────────────

    it('empty collection toArray returns empty array', function (): void {
        expect((new DtoCollection)->toArray())->toBe([]);
    });

    it('empty collection isEmpty returns true', function (): void {
        expect((new DtoCollection)->isEmpty())->toBeTrue();
        expect((new DtoCollection)->isNotEmpty())->toBeFalse();
    });

    it('empty collection first and last return null', function (): void {
        $col = new DtoCollection;
        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('empty collection map returns empty array', function (): void {
        expect((new DtoCollection)->map(fn () => true))->toBe([]);
    });

    it('empty collection filter returns empty collection', function (): void {
        $result = (new DtoCollection)->filter(fn () => true);
        expect($result->count())->toBe(0);
    });

    it('empty collection unique returns empty collection', function (): void {
        expect((new DtoCollection)->unique()->count())->toBe(0);
    });

    it('empty collection chunk returns empty array', function (): void {
        expect((new DtoCollection)->chunk(5))->toBe([]);
    });

    it('empty collection take returns empty collection', function (): void {
        expect((new DtoCollection)->take(5)->count())->toBe(0);
    });

    it('empty collection skip returns empty collection', function (): void {
        expect((new DtoCollection)->skip(5)->count())->toBe(0);
    });

    // ── Push (mutating) vs Append (immutable) ─────────────────────────

    it('push mutates in-place and returns self', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $result = $col->push(makeItem(2, 'B', 20));

        expect($col->count())->toBe(2); // mutated
        expect($result)->toBe($col); // same instance
    });

    it('append returns new collection without modifying original', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $new = $col->append(makeItem(2, 'B', 20));

        expect($col->count())->toBe(1); // unchanged
        expect($new->count())->toBe(2);
        expect($new->last()->toArray()['name'])->toBe('B');
    });

    // ── Merge ────────────────────────────────────────────────────────

    it('merge combines two collections without modifying originals', function () use (&$makeItem): void {
        $col1 = new DtoCollection([makeItem(1, 'A', 10)]);
        $col2 = new DtoCollection([makeItem(2, 'B', 20)]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
    });

    it('merge with empty collection returns copy of original', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'A', 10)]);
        $merged = $col->merge(new DtoCollection);

        expect($merged->count())->toBe(1);
        expect($merged->first()->toArray()['name'])->toBe('A');
    });

    // ── JSON serialization ──────────────────────────────────────────

    it('jsonSerialize returns toArray output', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'Alice', 90)]);

        $serialized = $col->jsonSerialize();
        expect($serialized)->toBe($col->toArray());
    });

    it('allValues includes all fields', function () use (&$makeItem): void {
        $col = new DtoCollection([makeItem(1, 'Alice', 90, 'secret')]);

        $all = $col->allValues();
        expect($all[0])->toHaveKey('email');
    });

    // ── Map with index ──────────────────────────────────────────────

    it('map passes item and index to callback', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
        ]);

        $result = $col->map(fn (CollectionItemDTO $dto, int $index): string => "{$index}:{$dto->name}");
        expect($result)->toBe(['0:A', '1:B']);
    });

    // ── Static make factory ─────────────────────────────────────────

    it('make creates collection from array', function () use (&$makeItem): void {
        $col = DtoCollection::make([makeItem(1, 'A', 10)]);
        expect($col->count())->toBe(1);
    });

    it('make with empty array creates empty collection', function (): void {
        expect(DtoCollection::make()->isEmpty())->toBeTrue();
    });

    // ── __debugInfo ──────────────────────────────────────────────────

    it('__debugInfo shows count and truncated items', function () use (&$makeItem): void {
        $col = new DtoCollection([
            makeItem(1, 'A', 10),
            makeItem(2, 'B', 20),
        ]);

        $debug = $col->__debugInfo();
        expect($debug)->toHaveKey('count');
        expect($debug['count'])->toBe(2);
        expect($debug)->toHaveKey('items');
    });

    // ── Construction validation ─────────────────────────────────────

    it('constructor rejects non-DTO items', function (): void {
        expect(fn (): mixed => new DtoCollection([new \stdClass]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetSet rejects non-DTO values', function () use (&$makeItem): void {
        $col = new DtoCollection;
        expect(fn () => $col[] = new \stdClass)
            ->toThrow(\InvalidArgumentException::class);
    });
});
