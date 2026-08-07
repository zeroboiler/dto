<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DtoCollection operation edge cases', function () {
    describe('filter returning empty', function () {
        it('returns empty collection when all items filtered out', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ];
            $collection = new DtoCollection($dtoList);
            $filtered = $collection->filter(fn (MinimalDTO $dto): bool => $dto->name === 'Charlie');

            expect($filtered)->toBeInstanceOf(DtoCollection::class);
            expect($filtered->count())->toBe(0);
            expect($filtered->isEmpty())->toBeTrue();
            expect($filtered->isNotEmpty())->toBeFalse();
        });
    });

    describe('push on filtered collection', function () {
        it('pushes item onto a filtered collection independently', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
                new MinimalDTO(name: 'Charlie', value: 'c'),
            ];
            $collection = new DtoCollection($dtoList);
            $filtered = $collection->filter(fn (MinimalDTO $dto): bool => $dto->name !== 'Bob');

            $new = new MinimalDTO(name: 'Dave', value: 'd');
            $result = $filtered->push($new);

            expect($result->count())->toBe(3);
            expect($result->last()->name)->toBe('Dave');
            // Original collection should be unchanged
            expect($collection->count())->toBe(3);
        });
    });

    describe('map with index', function () {
        it('receives correct index for each item', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
                new MinimalDTO(name: 'Charlie', value: 'c'),
            ];
            $collection = new DtoCollection($dtoList);
            $result = $collection->map(fn (MinimalDTO $dto, int $index): array => [
                'index' => $index,
                'name' => $dto->name,
            ]);

            expect($result)->toBe([
                ['index' => 0, 'name' => 'Alice'],
                ['index' => 1, 'name' => 'Bob'],
                ['index' => 2, 'name' => 'Charlie'],
            ]);
        });
    });

    describe('offsetSet with null offset', function () {
        it('appends when offset is null', function () {
            $collection = new DtoCollection([new MinimalDTO(name: 'Alice', value: 'a')]);
            $collection[] = new MinimalDTO(name: 'Bob', value: 'b');

            expect($collection->count())->toBe(2);
            expect($collection->last()->name)->toBe('Bob');
        });
    });

    describe('offsetSet with explicit offset', function () {
        it('replaces item at given offset', function () {
            $collection = new DtoCollection([
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ]);
            $collection[0] = new MinimalDTO(name: 'Charlie', value: 'c');

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('Charlie');
        });
    });

    describe('offsetUnset re-indexing', function () {
        it('re-indexes after unsetting middle element', function () {
            $collection = new DtoCollection([
                new MinimalDTO(name: 'Alice', value: 'a'),   // index 0
                new MinimalDTO(name: 'Bob', value: 'b'),     // index 1
                new MinimalDTO(name: 'Charlie', value: 'c'), // index 2
            ]);

            unset($collection[1]);

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('Alice');
            expect($collection->last()->name)->toBe('Charlie');
        });
    });

    describe('iteration protocol', function () {
        it('implements IteratorAggregate via foreach', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ];
            $collection = new DtoCollection($dtoList);

            $names = [];
            foreach ($collection as $dto) {
                $names[] = $dto->name;
            }

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('implements Countable via count()', function () {
            $collection = new DtoCollection([
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
                new MinimalDTO(name: 'Charlie', value: 'c'),
            ]);

            expect(count($collection))->toBe(3);
        });
    });

    describe('type safety', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto', 123, true]))
                ->toThrow(InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
        });

        it('rejects non-DTO items in offsetSet', function () {
            $collection = new DtoCollection([new MinimalDTO(name: 'Alice', value: 'a')]);

            expect(fn () => $collection[] = 'not a dto')
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('pluck and pluckKey edge cases', function () {
        it('pluck returns empty array for empty collection', function () {
            $collection = new DtoCollection([]);
            expect($collection->pluck('name'))->toBe([]);
        });

        it('pluckKey with single param returns dto arrays as values', function () {
            $collection = new DtoCollection([
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ]);

            $result = $collection->pluckKey('name');
            expect($result)->toHaveKeys(['Alice', 'Bob']);
            expect($result['Alice'])->toHaveKey('name');
        });
    });

    describe('JSON serialization', function () {
        it('jsonSerialize returns toArray output', function () {
            $collection = new DtoCollection([new MinimalDTO(name: 'Alice', value: 'a')]);
            $json = json_encode($collection);

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded[0])->toHaveKey('name');
            expect($decoded[0]['name'])->toBe('Alice');
        });
    });

    describe('make factory', function () {
        it('creates collection from array of DTOs', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ];
            $collection = DtoCollection::make($dtoList);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('creates empty collection from empty array', function () {
            $collection = DtoCollection::make([]);

            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBeTrue();
        });
    });

    describe('allValues includes all fields', function () {
        it('allValues returns all properties for each DTO', function () {
            $collection = new DtoCollection([new MinimalDTO(name: 'Alice', value: 'x')]);
            $all = $collection->allValues();

            expect($all[0])->toHaveKeys(['name', 'value']);
        });
    });
});
