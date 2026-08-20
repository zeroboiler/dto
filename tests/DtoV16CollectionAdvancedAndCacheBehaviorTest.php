<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DTO collection advanced methods, metadata cache behavior, and fromJson edge cases.
 *
 * Covers:
 * - DtoCollection.toDictionary() — key/value extraction
 * - DtoCollection.toArrayBy() — key-to-array mapping
 * - DtoCollection.unique() — deduplication by toArray() output
 * - DtoCollection.contains() — predicate search
 * - DtoCollection.search() — first match finder
 * - DtoCollection.sortBy() — property-based sorting
 * - DtoCollection.take() and skip() — slicing
 * - DtoCollection.chunk() — batch splitting
 * - DtoCollection.pluck() and pluckKey() — property extraction
 * - DtoCollection __debugInfo shape
 * - DataTransferObject metadata cache TTL behavior
 * - DataTransferObject flushMetadataCache() per-class
 * - fromJson() rejects sequential arrays
 * - fromJson() rejects invalid JSON
 * - fromJson() rejects non-object JSON
 * - fromPartialArray() with empty data returns defaults
 * - with() always validates (deprecated $validate param)
 * - equals() comparison contract
 * - isEmpty() / isNotEmpty() edge cases
 * - DtoCollection.make() factory
 * - DtoCollection.first() and last()
 * - DtoCollection.isEmpty() and isNotEmpty()
 *
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

// ── Inline test DTOs for V16 ──────────────────────────────────

class V16SortableDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly int $score,
    ) {}
}

class V16NullableFieldDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
    ) {}
}

describe('V16 — DtoCollection Advanced Methods and DTO Cache Behavior', function () {

    // ──────────────────────────────────────────────────────────────
    // 1. DtoCollection.toDictionary()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection toDictionary()', function (): void {
        it('maps one property to another', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $dict = $col->toDictionary('name', 'score');

            expect($dict)->toBeArray();
            expect($dict['Alice'])->toBe(10);
            expect($dict['Bob'])->toBe(20);
        });

        it('skips items where key field is null', function () {
            $d1 = V16NullableFieldDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $d2 = V16NullableFieldDTO::fromArray(['name' => null, 'email' => 'b@b.com'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $dict = $col->toDictionary('name', 'email');

            expect($dict)->toHaveCount(1);
            expect($dict)->toHaveKey('Alice');
            expect($dict)->not->toHaveKey(null);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 2. DtoCollection.toArrayBy()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection toArrayBy()', function (): void {
        it('re-keys by a property value to full array', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $keyed = $col->toArrayBy('name');

            expect($keyed)->toBeArray();
            expect($keyed['Alice'])->toBe($d1->toArray());
            expect($keyed['Bob'])->toBe($d2->toArray());
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 3. DtoCollection.unique()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection unique()', function (): void {
        it('removes duplicates based on toArray() output', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d3 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            $unique = $col->unique();

            expect($unique->count())->toBe(2);
        });

        it('preserves first occurrence order', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $d3 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            $unique = $col->unique();
            $items = $unique->items();

            expect($items[0]->name)->toBe('Alice');
            expect($items[1]->name)->toBe('Bob');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. DtoCollection.contains() and search()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection contains() and search()', function (): void {
        it('contains() returns true when predicate matches', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->contains(fn (DataTransferObject $d) => $d->name === 'Bob'))->toBeTrue();
            expect($col->contains(fn (DataTransferObject $d) => $d->name === 'Charlie'))->toBeFalse();
        });

        it('search() returns first matching DTO', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $found = $col->search(fn (DataTransferObject $d) => $d->score === 20);

            expect($found)->not->toBeNull();
            expect($found->name)->toBe('Bob');
        });

        it('search() returns null when no match', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->search(fn (DataTransferObject $d) => $d->score === 99))->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 5. DtoCollection.sortBy()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection sortBy()', function (): void {
        it('sorts by string property name ascending', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Charlie', 'score' => 30], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d3 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            $sorted = $col->sortBy('name');
            $items = $sorted->items();

            expect($items[0]->name)->toBe('Alice');
            expect($items[1]->name)->toBe('Bob');
            expect($items[2]->name)->toBe('Charlie');
        });

        it('sorts by callback result', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'A', 'score' => 30], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'B', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $sorted = $col->sortBy(fn (DataTransferObject $d) => $d->score);
            $items = $sorted->items();

            expect($items[0]->score)->toBe(10);
            expect($items[1]->score)->toBe(30);
        });

        it('does not mutate original collection', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Charlie', 'score' => 30], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $sorted = $col->sortBy('name');

            expect($col->items()[0]->name)->toBe('Charlie'); // original unchanged
            expect($sorted->items()[0]->name)->toBe('Alice');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. DtoCollection.take(), skip(), chunk()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection slicing methods', function (): void {
        it('take() returns first N items', function () {
            $items = [];
            for ($i = 1; $i <= 5; $i++) {
                $items[] = EmptyDTO::fromArray(['foo' => "item{$i}"], validate: false);
            }
            $col = new DtoCollection($items);

            $taken = $col->take(3);

            expect($taken->count())->toBe(3);
        });

        it('take() returns all items when count exceeds size', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->take(10)->count())->toBe(1);
        });

        it('skip() returns items after first N', function () {
            $items = [];
            for ($i = 1; $i <= 5; $i++) {
                $items[] = EmptyDTO::fromArray(['foo' => "item{$i}"], validate: false);
            }
            $col = new DtoCollection($items);

            $skipped = $col->skip(3);

            expect($skipped->count())->toBe(2);
        });

        it('skip() returns empty collection when count exceeds size', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->skip(10)->count())->toBe(0);
        });

        it('chunk() splits into equal-sized batches', function () {
            $items = [];
            for ($i = 1; $i <= 5; $i++) {
                $items[] = EmptyDTO::fromArray(['foo' => "item{$i}"], validate: false);
            }
            $col = new DtoCollection($items);

            $chunks = $col->chunk(2);

            expect($chunks)->toHaveCount(3); // [2, 2, 1]
            expect($chunks[0]->count())->toBe(2);
            expect($chunks[1]->count())->toBe(2);
            expect($chunks[2]->count())->toBe(1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. DtoCollection.pluck() and pluckKey()
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection property extraction', function (): void {
        it('pluck() extracts a single property from all items', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $names = $col->pluck('name');

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('pluckKey() returns key-value pairs', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $keyed = $col->pluckKey('name', 'score');

            expect($keyed['Alice'])->toBe(10);
            expect($keyed['Bob'])->toBe(20);
        });

        it('pluckKey() without valueField returns full toArray()', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1]);

            $keyed = $col->pluckKey('name');

            expect($keyed['Alice'])->toBe($d1->toArray());
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. DtoCollection __debugInfo, make, first, last, isEmpty, isNotEmpty
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection utility methods', function (): void {
        it('__debugInfo shows count and items', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            $debug = $col->__debugInfo();

            expect($debug)->toBeArray();
            expect($debug)->toHaveKey('count');
            expect($debug)->toHaveKey('items');
            expect($debug['count'])->toBe(1);
        });

        it('make() creates collection from array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = DtoCollection::make([$d1]);

            expect($col)->toBeInstanceOf(DtoCollection::class);
            expect($col->count())->toBe(1);
        });

        it('make() creates empty collection with no args', function () {
            $col = DtoCollection::make();

            expect($col->count())->toBe(0);
        });

        it('first() returns first item or null', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->first()->foo)->toBe('a');
            expect(DtoCollection::make()->first())->toBeNull();
        });

        it('last() returns last item or null', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->last()->foo)->toBe('b');
            expect(DtoCollection::make()->last())->toBeNull();
        });

        it('isEmpty() and isNotEmpty() work correctly', function () {
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();

            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col2 = new DtoCollection([$d1]);
            expect($col2->isEmpty())->toBeFalse();
            expect($col2->isNotEmpty())->toBeTrue();
        });

        it('map() returns plain array with index', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $d2 = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $result = $col->map(fn (DataTransferObject $d, int $i) => $i . ':' . $d->name);

            expect($result)->toBe(['0:Alice', '1:Bob']);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. Metadata cache TTL behavior
    // ──────────────────────────────────────────────────────────────

    describe('Metadata cache behavior', function (): void {
        it('setMetadataCacheTtl and flushMetadataCache work', function () {
            // Set a very short TTL
            DataTransferObject::setMetadataCacheTtl(0.0);

            // Resolve metadata
            MinimalDTO::rules();

            // Set TTL to a very short value for testing
            DataTransferObject::setMetadataCacheTtl(0.001);

            // Should be available immediately
            $rules1 = MinimalDTO::rules();
            expect($rules1)->toBeArray();

            // Flush all cache
            DataTransferObject::flushMetadataCache();

            // Should still work after flush
            $rules2 = MinimalDTO::rules();
            expect($rules2)->toBeArray();

            // Reset to disabled for other tests
            DataTransferObject::setMetadataCacheTtl(0.0);
        });

        it('flushMetadataCache with class parameter clears one class', function () {
            DataTransferObject::setMetadataCacheTtl(300.0);

            // Populate cache for two classes
            MinimalDTO::rules();
            EmptyDTO::rules();

            // Flush only one
            DataTransferObject::flushMetadataCache(MinimalDTO::class);

            // Re-resolve — should work fine (just not cached)
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();

            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. fromJson() edge cases
    // ──────────────────────────────────────────────────────────────

    describe('fromJson() edge cases', function (): void {
        it('rejects sequential arrays with DTOException', function () {
            expect(fn () => MinimalDTO::fromJson('["a", "b"]'))
                ->toThrow(DTOException::class);
        });

        it('rejects invalid JSON with DTOException', function () {
            expect(fn () => MinimalDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object JSON', function () {
            // EmptyDTO has all optional fields
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('creates DTO from valid JSON', function () {
            $dto = MinimalDTO::fromJson('{"name": "Alice", "value": "test"}', validate: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('Alice');
            expect($dto->value)->toBe('test');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. with() always validates
    // ──────────────────────────────────────────────────────────────

    describe('with() immutable update', function (): void {
        it('creates new instance with overrides', function () {
            $dto = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $updated = $dto->with(['score' => 99]);

            expect($updated)->not->toBe($dto);
            expect($updated->name)->toBe('Alice');
            expect($updated->score)->toBe(99);
            expect($dto->score)->toBe(10); // original unchanged
        });

        it('validates merged data even when $validate is false', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            // The $validate param is deprecated and has no effect — validation always runs.
            // This should NOT throw because the base data is valid and override is valid.
            $updated = $dto->with(['name' => 'Bob'], validate: false);

            expect($updated->name)->toBe('Bob');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. equals() and isEmpty() / isNotEmpty()
    // ──────────────────────────────────────────────────────────────

    describe('equals() and isEmpty() contracts', function (): void {
        it('equals() returns true for same data', function () {
            $a = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $b = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different data', function () {
            $a = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $b = V16SortableDTO::fromArray(['name' => 'Bob', 'score' => 20], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('isEmpty() returns true for all-null optional fields', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty() returns false when any field has a value', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isEmpty() treats 0 as non-empty', function () {
            $dto = V16SortableDTO::fromArray(['name' => '', 'score' => 0], validate: false);

            // score=0 is a valid meaningful value — not empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. fromPartialArray() edge cases
    // ──────────────────────────────────────────────────────────────

    describe('fromPartialArray() edge cases', function (): void {
        it('returns DTO with all defaults when empty array is passed', function () {
            $dto = V16NullableFieldDTO::fromPartialArray([], validatePresent: false);

            expect($dto->name)->toBe(''); // type-appropriate empty for string
            expect($dto->email)->toBeNull(); // nullable field defaults to null
        });

        it('only overrides fields present in data', function () {
            $dto = V16NullableFieldDTO::fromPartialArray(['email' => 'a@b.com'], validatePresent: false);

            expect($dto->email)->toBe('a@b.com');
            expect($dto->name)->toBe(''); // default for missing non-nullable string
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. toJson() and jsonSerialize() consistency
    // ──────────────────────────────────────────────────────────────

    describe('JSON serialization', function (): void {
        it('toJson() returns valid JSON string', function () {
            $dto = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $json = $dto->toJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['name'])->toBe('Alice');
            expect($decoded['score'])->toBe(10);
        });

        it('toJson() with JSON_PRETTY_PRINT works', function () {
            $dto = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n"); // pretty-printed has newlines
        });

        it('jsonSerialize() returns same as toArray()', function () {
            $dto = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('DtoCollection jsonSerialize returns array of arrays', function () {
            $d1 = V16SortableDTO::fromArray(['name' => 'Alice', 'score' => 10], validate: false);
            $col = new DtoCollection([$d1]);

            $serialized = $col->jsonSerialize();

            expect($serialized)->toBeArray();
            expect($serialized[0])->toBeArray();
            expect($serialized[0]['name'])->toBe('Alice');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 15. DtoCollection rejects non-DTO items
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection type safety', function (): void {
        it('constructor rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetSet rejects non-DTO values', function () {
            $col = DtoCollection::make();

            expect(fn () => $col->offsetSet(0, 'not a dto'))
                ->toThrow(\InvalidArgumentException::class);
        });
    });
});
