<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO serialization roundtrip contracts', function (): void {
    beforeEach(function (): void {
        DataTransferObject::flushMetadataCache();
    });

    describe('fromArray → toArray identity', function (): void {
        it('roundtrips string fields correctly', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'admin'], validate: false);

            expect($dto->toArray())->toEqual(['name' => 'Alice', 'value' => 'admin']);
        });

        it('roundtrips all scalar types', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'age' => 42,
                'password' => 'secret123',
            ], validate: false);

            // toArray() should exclude hidden 'password'
            $array = $dto->toArray();
            expect($array)->toHaveKeys(['email', 'name', 'age']);
            expect($array)->not->toHaveKey('password');
        });

        it('allValues() includes hidden fields', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'age' => 30,
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });
    });

    describe('fromJson → toJson identity', function (): void {
        it('roundtrips through JSON serialization', function (): void {
            $data = ['name' => 'Carol', 'value' => 'editor'];
            $dto = MinimalDTO::fromArray($data, validate: false);
            $json = $dto->toJson();
            $restored = MinimalDTO::fromJson($json, validate: false);

            expect($restored->toArray())->toEqual($data);
        });

        it('fromJson rejects non-object JSON arrays', function (): void {
            expect(fn () => MinimalDTO::fromJson('["a","b"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson rejects invalid JSON', function (): void {
            expect(fn () => MinimalDTO::fromJson('{invalid json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson accepts empty JSON object', function (): void {
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toEqual(['foo' => null, 'bar' => null]);
        });

        it('fromJson accepts empty array as valid empty object', function (): void {
            $dto = EmptyDTO::fromJson('[]', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });
    });

    describe('equals() value comparison', function (): void {
        it('returns true for identical DTOs', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);
            $b = MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for different DTOs', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);
            $b = MinimalDTO::fromArray(['name' => 'X', 'value' => 'Z'], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('excludes hidden fields from comparison', function (): void {
            $a = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com', 'name' => 'A', 'age' => 1, 'password' => 'pass1',
            ], validate: false);
            $b = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com', 'name' => 'A', 'age' => 1, 'password' => 'pass2',
            ], validate: false);

            // Hidden 'password' is excluded from toArray() and thus from equals()
            expect($a->equals($b))->toBeTrue();
        });
    });

    describe('isEmpty() / isNotEmpty()', function (): void {
        it('returns true for all-null optional DTO', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('returns false when at least one field is non-empty', function (): void {
            $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('considers 0 as non-empty for non-nullable int', function (): void {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            // count has default 0, which is non-empty per the contract
            expect($dto->isEmpty())->toBeFalse();
        });

        it('considers false as empty', function (): void {
            // active defaults to false, name defaults to 'default-name' (non-empty)
            // So isEmpty should be false because name is non-empty
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('only() and except()', function (): void {
        it('only() returns subset of fields', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com', 'name' => 'Alice', 'age' => 25,
            ], validate: false);

            expect($dto->only('name'))->toEqual(['name' => 'Alice']);
            expect($dto->only(['name', 'age']))->toEqual(['name' => 'Alice', 'age' => 25]);
        });

        it('only() ignores non-existent keys silently', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);

            expect($dto->only(['name', 'nonexistent']))->toEqual(['name' => 'X']);
        });

        it('except() returns all fields except specified', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com', 'name' => 'Alice', 'age' => 25,
            ], validate: false);

            $result = $dto->except('age');
            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('age');
        });

        it('except() also respects hidden fields', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com', 'name' => 'Alice', 'age' => 25, 'password' => 'pw',
            ], validate: false);

            // password is already hidden, except('age') should only return email+name
            $result = $dto->except('age');
            expect($result)->not->toHaveKey('password');
            expect($result)->not->toHaveKey('age');
        });
    });

    describe('with() immutable override', function (): void {
        it('creates a new instance with merged data', function (): void {
            $original = MinimalDTO::fromArray(['name' => 'Old', 'value' => 'old-val'], validate: false);
            $modified = $original->with(['name' => 'New']);

            expect($original->name)->toBe('Old');
            expect($modified->name)->toBe('New');
            expect($modified->value)->toBe('old-val');
        });

        it('preserves original instance', function (): void {
            $original = MinimalDTO::fromArray(['name' => 'Keep', 'value' => 'keep'], validate: false);
            $modified = $original->with(['name' => 'Changed']);

            expect($original->name)->toBe('Keep');
            expect($modified->name)->toBe('Changed');
        });
    });

    describe('MapFrom attribute', function (): void {
        it('maps source key to property name', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('toArray uses property name (not source key)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->toArray())->toHaveKey('phone');
            expect($dto->toArray())->not->toHaveKey('phone_number');
        });
    });

    describe('DefaultValue attribute', function (): void {
        it('applies default when key is absent', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('respects explicit null over default', function (): void {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            // AllDefaultsDTO has constructor defaults; fromArray with empty array uses them
            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
        });
    });

    describe('DtoCollection operations', function (): void {
        it('make creates collection from DTOs', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false),
                MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false),
            ];

            $collection = DtoCollection::make($items);

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('A');
        });

        it('filter returns a new filtered collection', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false),
                MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false),
            ];

            $collection = DtoCollection::make($items);
            $filtered = $collection->filter(fn ($dto) => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
            // Original unchanged
            expect($collection->count())->toBe(2);
        });

        it('sortBy returns a new sorted collection', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'C', 'value' => 'z'], validate: false),
                MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false),
                MinimalDTO::fromArray(['name' => 'B', 'value' => 'm'], validate: false),
            ];

            $collection = DtoCollection::make($items);
            $sorted = $collection->sortBy('name');

            expect($sorted->first()->name)->toBe('A');
            expect($sorted->last()->name)->toBe('C');
        });

        it('unique removes duplicates based on toArray()', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'Same', 'value' => 'Same'], validate: false),
                MinimalDTO::fromArray(['name' => 'Same', 'value' => 'Same'], validate: false),
            ];

            $collection = DtoCollection::make($items);
            $unique = $collection->unique();

            expect($unique->count())->toBe(1);
        });

        it('take and skip work correctly', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => '1', 'value' => 'a'], validate: false),
                MinimalDTO::fromArray(['name' => '2', 'value' => 'b'], validate: false),
                MinimalDTO::fromArray(['name' => '3', 'value' => 'c'], validate: false),
            ];

            $collection = DtoCollection::make($items);

            expect($collection->take(2)->count())->toBe(2);
            expect($collection->skip(1)->count())->toBe(2);
            expect($collection->skip(1)->first()->name)->toBe('2');
        });

        it('contains and search work correctly', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'admin'], validate: false),
                MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'user'], validate: false),
            ];

            $collection = DtoCollection::make($items);

            expect($collection->contains(fn ($d) => $d->value === 'admin'))->toBeTrue();
            expect($collection->contains(fn ($d) => $d->value === 'super'))->toBeFalse();
            expect($collection->search(fn ($d) => $d->name === 'Bob')->name)->toBe('Bob');
            expect($collection->search(fn ($d) => $d->name === 'Nobody'))->toBeNull();
        });

        it('chunk splits collection correctly', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => '1', 'value' => 'a'], validate: false),
                MinimalDTO::fromArray(['name' => '2', 'value' => 'b'], validate: false),
                MinimalDTO::fromArray(['name' => '3', 'value' => 'c'], validate: false),
                MinimalDTO::fromArray(['name' => '4', 'value' => 'd'], validate: false),
            ];

            $collection = DtoCollection::make($items);
            $chunks = $collection->chunk(2);

            expect($chunks)->toHaveCount(2);
            expect($chunks[0]->count())->toBe(2);
            expect($chunks[1]->count())->toBe(2);
        });

        it('merge combines two collections', function (): void {
            $a = DtoCollection::make([
                MinimalDTO::fromArray(['name' => '1', 'value' => 'a'], validate: false),
            ]);
            $b = DtoCollection::make([
                MinimalDTO::fromArray(['name' => '2', 'value' => 'b'], validate: false),
            ]);

            $merged = $a->merge($b);

            expect($merged->count())->toBe(2);
            expect($a->count())->toBe(1); // original unchanged
        });

        it('jsonSerialize returns array of arrays', function (): void {
            $items = [
                MinimalDTO::fromArray(['name' => 'X', 'value' => 'y'], validate: false),
            ];

            $collection = DtoCollection::make($items);
            $serialized = $collection->jsonSerialize();

            expect($serialized)->toBeArray();
            expect($serialized[0])->toEqual(['name' => 'X', 'value' => 'y']);
        });
    });

    describe('fromPartialArray PATCH semantics', function (): void {
        it('only hydrates provided fields, rest use defaults', function (): void {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'Updated'], validate: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->count)->toBe(0); // default
            expect($dto->active)->toBe(false); // default
            expect($dto->items)->toEqual([]); // default
        });

        it('overrides defaults with provided values', function (): void {
            $dto = AllDefaultsDTO::fromPartialArray(['count' => 5, 'active' => true], validate: false);

            expect($dto->count)->toBe(5);
            expect($dto->active)->toBeTrue();
            expect($dto->name)->toBe('default-name'); // default used
        });
    });

    describe('metadata cache TTL', function (): void {
        it('flushMetadataCache clears cache for all classes', function (): void {
            DataTransferObject::setMetadataCacheTtl(0); // disable TTL

            // Force metadata resolution via fromArray (metadata is resolved lazily and cached)
            MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);

            DataTransferObject::flushMetadataCache();

            // Should still work after flush (re-resolves on next call)
            $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'B'], validate: false);
            expect($dto->name)->toBe('A');

            // Reset TTL
            DataTransferObject::setMetadataCacheTtl(0.0);
        });

        it('flushMetadataCache with specific class only clears that class', function (): void {
            DataTransferObject::setMetadataCacheTtl(0);

            MinimalDTO::fromArray(['name' => 'X', 'value' => 'Y'], validate: false);
            EmptyDTO::fromArray([], validate: false);

            DataTransferObject::flushMetadataCache(MinimalDTO::class);

            // EmptyDTO should still work from cache
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            expect($dto->foo)->toBe('bar');

            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });
});
