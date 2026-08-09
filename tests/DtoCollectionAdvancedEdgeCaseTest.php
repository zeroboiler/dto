<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Minimal test DTOs for collection edge case testing.
 */
final class ColTestUserDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

final class ColTestProductDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $sku,
        public readonly float $price,
    ) {}
}

describe('DtoCollection advanced edge cases', function () {
    describe('Construction guards', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection([new ColTestUserDTO('Alice', 'a@b.com'), 'not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('rejects int items', function () {
            expect(fn () => new DtoCollection([1, 2, 3]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('accepts empty array', function () {
            $col = new DtoCollection([]);
            expect($col->isEmpty())->toBeTrue();
            expect($col->count())->toBe(0);
        });

        it('static make() also validates types', function () {
            expect(fn () => DtoCollection::make(['string', 42]))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('ArrayAccess edge cases', function () {
        it('offsetGet returns null for non-existent index', function () {
            $col = new DtoCollection([new ColTestUserDTO('Alice', 'a@b.com')]);
            expect($col->offsetGet(99))->toBeNull();
        });

        it('offsetExists returns false for non-existent index', function () {
            $col = new DtoCollection([new ColTestUserDTO('Alice', 'a@b.com')]);
            expect($col->offsetExists(99))->toBeFalse();
        });

        it('offsetUnset re-indexes the array', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');
            $c = new ColTestUserDTO('Charlie', 'c@b.com');

            $col = new DtoCollection([$a, $b, $c]);
            expect($col->count())->toBe(3);

            $col->offsetUnset(0);
            // After removing first, indices should be re-indexed
            expect($col->count())->toBe(2);
            expect($col->offsetExists(0))->toBeTrue(); // Re-indexed
            expect($col->first()->name)->toBe('Bob');
        });

        it('offsetSet with null offset appends', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');

            $col = new DtoCollection([$a]);
            $col->offsetSet(null, $b);
            expect($col->count())->toBe(2);
            expect($col->last()->name)->toBe('Bob');
        });

        it('offsetSet with int offset replaces at index', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');
            $c = new ColTestUserDTO('Charlie', 'c@b.com');

            $col = new DtoCollection([$a, $b]);
            $col->offsetSet(0, $c);
            expect($col->first()->name)->toBe('Charlie');
            expect($col->last()->name)->toBe('Bob');
        });

        it('offsetSet rejects non-DTO values', function () {
            $col = new DtoCollection([]);
            expect(fn () => $col->offsetSet(0, 'invalid'))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('Iterator', function () {
        it('foreach iterates all items in order', function () {
            $items = [
                new ColTestUserDTO('Alice', 'a@b.com'),
                new ColTestUserDTO('Bob', 'b@b.com'),
                new ColTestUserDTO('Charlie', 'c@b.com'),
            ];

            $col = new DtoCollection($items);
            $iterated = [];

            foreach ($col as $key => $dto) {
                $iterated[$key] = $dto->name;
            }

            expect($iterated)->toBe([0 => 'Alice', 1 => 'Bob', 2 => 'Charlie']);
        });

        it('getIterator returns Traversable', function () {
            $col = new DtoCollection([]);
            expect($col->getIterator())->toBeInstanceOf(\Traversable::class);
        });
    });

    describe('first/last', function () {
        it('first returns null on empty collection', function () {
            $col = new DtoCollection([]);
            expect($col->first())->toBeNull();
        });

        it('last returns null on empty collection', function () {
            $col = new DtoCollection([]);
            expect($col->last())->toBeNull();
        });

        it('first/last return correct items', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');

            $col = new DtoCollection([$a, $b]);
            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Bob');
        });

        it('single-item collection: first === last', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $col = new DtoCollection([$a]);
            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Alice');
        });
    });

    describe('push/append/merge', function () {
        it('push mutates in place and returns self', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');

            $col = new DtoCollection([$a]);
            $result = $col->push($b);

            expect($col->count())->toBe(2);
            expect($result)->toBe($col); // Same instance (fluent)
        });

        it('append returns a new collection without mutating original', function () {
            $a = new ColTestUserDTO('Alice', 'a@b.com');
            $b = new ColTestUserDTO('Bob', 'b@b.com');

            $original = new DtoCollection([$a]);
            $new = $original->append($b);

            expect($original->count())->toBe(1);
            expect($new->count())->toBe(2);
            expect($new)->not->toBe($original);
        });

        it('merge combines two collections immutably', function () {
            $col1 = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
            ]);
            $col2 = new DtoCollection([
                new ColTestUserDTO('Bob', 'b@b.com'),
            ]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
            expect($merged->count())->toBe(2);
            expect($merged->pluck('name'))->toBe(['Alice', 'Bob']);
        });
    });

    describe('map/filter', function () {
        it('map returns plain array with correct values', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
                new ColTestUserDTO('Bob', 'b@b.com'),
            ]);

            $names = $col->map(fn (ColTestUserDTO $dto, int $key): string => $dto->name);

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('filter returns new collection with matching items', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
                new ColTestUserDTO('Bobby', 'b@b.com'),
                new ColTestUserDTO('Ann', 'c@b.com'),
            ]);

            $filtered = $col->filter(fn (ColTestUserDTO $dto): bool => str_starts_with($dto->name, 'A'));

            expect($filtered->count())->toBe(2);
            expect($filtered->first()->name)->toBe('Alice');
            expect($filtered->last()->name)->toBe('Ann');
        });

        it('filter on empty collection returns empty collection', function () {
            $col = new DtoCollection([]);
            $filtered = $col->filter(fn () => true);

            expect($filtered->isEmpty())->toBeTrue();
        });
    });

    describe('pluck/pluckKey', function () {
        it('pluck extracts field values', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'alice@test.com'),
                new ColTestUserDTO('Bob', 'bob@test.com'),
            ]);

            expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
            expect($col->pluck('email'))->toBe(['alice@test.com', 'bob@test.com']);
        });

        it('pluckKey builds key-value map', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'alice@test.com'),
                new ColTestUserDTO('Bob', 'bob@test.com'),
            ]);

            $map = $col->pluckKey('email', 'name');
            expect($map)->toBe([
                'alice@test.com' => 'Alice',
                'bob@test.com' => 'Bob',
            ]);
        });

        it('pluckKey without valueField uses full toArray()', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'alice@test.com'),
            ]);

            $map = $col->pluckKey('name');
            expect($map)->toHaveKey('Alice');
            expect($map['Alice'])->toBeArray();
            expect($map['Alice'])->toHaveKey('email');
        });
    });

    describe('Serialization', function () {
        it('jsonSerialize returns array of DTO arrays', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
                new ColTestUserDTO('Bob', 'b@b.com'),
            ]);

            $json = $col->jsonSerialize();
            expect($json)->toBe([
                ['name' => 'Alice', 'email' => 'a@b.com'],
                ['name' => 'Bob', 'email' => 'b@b.com'],
            ]);
        });

        it('toArray returns same as jsonSerialize', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
            ]);

            expect($col->toArray())->toBe($col->jsonSerialize());
        });

        it('allValues includes hidden fields of nested DTOs', function () {
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
            ]);

            // ColTestUserDTO has no hidden fields, so allValues === toArray
            expect($col->allValues())->toBe($col->toArray());
        });
    });

    describe('isEmpty/isNotEmpty', function () {
        it('isEmpty returns true for empty collection', function () {
            expect((new DtoCollection([]))->isEmpty())->toBeTrue();
        });

        it('isNotEmpty returns false for empty collection', function () {
            expect((new DtoCollection([]))->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false for non-empty collection', function () {
            $col = new DtoCollection([new ColTestUserDTO('Alice', 'a@b.com')]);
            expect($col->isEmpty())->toBeFalse();
            expect($col->isNotEmpty())->toBeTrue();
        });
    });

    describe('Mixed DTO types in same collection', function () {
        it('collection accepts different DTO subclasses', function () {
            // DtoCollection accepts any DataTransferObject subclass
            $col = new DtoCollection([
                new ColTestUserDTO('Alice', 'a@b.com'),
                new ColTestProductDTO('SKU-001', 9.99),
            ]);

            expect($col->count())->toBe(2);
            $items = $col->items();
            expect($items[0])->toBeInstanceOf(ColTestUserDTO::class);
            expect($items[1])->toBeInstanceOf(ColTestProductDTO::class);
        });
    });
});
