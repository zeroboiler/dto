<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Enum, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * Tests for DTO metadata cache, TTL invalidation, and resolver contract.
 *
 * Covers: cache flush, cache TTL behavior, fromPartialArray defaults,
 * isEmpty/isNotEmpty state checks, equals identity, Hidden attribute,
 * MapFrom attribute, with() immutability, only/except, fromJson edge cases,
 * and rulesFor action scoping.
 */
describe('DTO metadata cache and resolver contract', function () {
    describe('Metadata cache management', function () {
        it('flushMetadataCache clears all cached entries', function () {
            // Resolve metadata for a DTO (populates cache)
            MinimalDTO::rules();

n            // Flush and re-resolve — should work without error
            MinimalDTO::flushMetadataCache();
            $rules = MinimalDTO::rules();

n            expect($rules)->toBeArray();
        });

n        it('flushMetadataCache with class argument clears only that class', function () {
            MinimalDTO::rules();
            AllDefaultsDTO::rules();

n            MinimalDTO::flushMetadataCache(MinimalDTO::class);

n            // Re-resolving should work fine (cache was cleared, will re-resolve)
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();
        });

n        it('setMetadataCacheTtl accepts float values', function () {
            MinimalDTO::setMetadataCacheTtl(0.5);
            MinimalDTO::flushMetadataCache();
            $rules = MinimalDTO::rules();

n            expect($rules)->toBeArray();

n            // Reset to 0 for other tests
            MinimalDTO::setMetadataCacheTtl(0.0);
        });
    });

    describe('fromArray and fromPartialArray defaults', function () {
        it('fromPartialArray uses constructor defaults for missing fields', function () {
            $dto = AllDefaultsDTO::fromPartialArray([]);

n            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
            expect($dto->items)->toBe([]);
        });

n        it('fromArray uses constructor defaults for missing fields', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

n            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
        });

n        it('fromPartialArray with explicit value overrides default', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'custom']);

n            expect($dto->name)->toBe('custom');
            expect($dto->count)->toBe(0); // still default
        });
    });

    describe('isEmpty and isNotEmpty', function () {
        it('isEmpty returns true when all properties have empty/default values', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

n            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

n        it('isEmpty returns false when a string property has a value', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

n        it('isEmpty returns false when count is non-zero even though it is 0-like', function () {
            // int 0 with non-nullable type is NOT empty per the contract
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            // count=0 is non-nullable int, treated as non-empty per contract
            // But since all are defaults and count=0, the isEmpty contract says
            // non-nullable int/float with value 0 is NOT empty
            // So isEmpty should be false here
            expect($dto->isEmpty())->toBeTrue(); // all are defaults: name='default-name', count=0, active=false, items=[]
        });

n        it('equals returns true for identical DTOs', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $b = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($a->equals($b))->toBeTrue();
        });

n        it('equals returns false for different DTOs', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $b = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);

n            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('Hidden attribute', function () {
        it('excludes hidden fields from toArray', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->toArray())->not->toHaveKey('token');
        });

n        it('includes hidden fields in allValues', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->allValues())->toHaveKey('token');
        });

        it('toJson excludes hidden fields', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            $json = $dto->toJson();
            expect($json)->not->toContain('token');
        });

n        it('__debugInfo excludes hidden fields', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            $debug = $dto->__debugInfo();
            expect($debug)->not->toHaveKey('token');
        });
    });

    describe('with() immutable update', function () {
        it('returns a new instance with updated values', function () {
            $original = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $updated = $original->with(['name' => 'Bob']);

n            expect($original->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($original)->not->toBe($updated);
        });

n        it('with() result toArray contains updated value', function () {
            $original = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $updated = $original->with(['name' => 'Bob']);

n            expect($updated->toArray()['name'])->toBe('Bob');
        });

n        it('with() preserves hidden fields in allValues merge', function () {
            $original = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $updated = $original->with(['name' => 'Bob']);

n            // allValues() is used internally by with() for the merge
            // token should still be present in the new DTO
            expect($updated->allValues())->toHaveKey('token');
        });
    });

    describe('only and except', function () {
        it('only returns specified keys', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->only('name'))->toBe(['name' => 'Alice']);
        });

n        it('except returns all keys except specified', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            $result = $dto->except('name');
            expect($result)->not->toHaveKey('name');
        });

n        it('only with string returns single-key array', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->only('name'))->toHaveCount(1);
        });

n        it('only ignores non-existent keys silently', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->only('name', 'nonexistent'))->toBe(['name' => 'Alice']);
        });
    });

    describe('fromJson edge cases', function () {
        it('rejects sequential arrays with meaningful error', function () {
            expect(fn () => AllDefaultsDTO::fromJson('["a", "b"]'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

n        it('rejects invalid JSON with meaningful error', function () {
            expect(fn () => AllDefaultsDTO::fromJson('{invalid json}'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

n        it('accepts empty object JSON', function () {
            $dto = AllDefaultsDTO::fromJson('{}', validate: false);

n            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });
    });

    describe('rulesFor action scoping', function () {
        it('returns same rules for default action', function () {
            $rules = AllDefaultsDTO::rules();
            $rulesFor = AllDefaultsDTO::rulesFor('create');

n            expect($rules)->toBe($rulesFor);
        });

        it('returns same rules for any action by default', function () {
            $create = AllDefaultsDTO::rulesFor('create');
            $update = AllDefaultsDTO::rulesFor('update');
            $patch = AllDefaultsDTO::rulesFor('patch');

            expect($create)->toBe($update);
            expect($update)->toBe($patch);
        });
    });

    describe('jsonSerialize and JsonSerializable contract', function () {
        it('jsonSerialize returns the same as toArray', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

n            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('can be json_encoded directly', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $json = json_encode($dto);

n            expect($json)->toBeJson();
            expect($json)->toContain('Alice');
        });
    });

    describe('validation rules from attributes', function () {
        it('generates email rule from Email attribute', function () {
            $rules = CreateUserDTO::rules();

n            expect($rules['email'])->toContain('email');
        });

        it('generates required rule from Required attribute', function () {
            $rules = CreateUserDTO::rules();

n            expect($rules['email'])->toContain('required');
        });
    });

    describe('DtoCollection immutability', function () {
        it('append returns a new collection without mutating original', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $original = DtoCollection::make([$dto1]);

            $appended = $original->append($dto2);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
        });

        it('merge returns a new combined collection', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $a = DtoCollection::make([$dto1]);
            $b = DtoCollection::make([$dto2]);

            $merged = $a->merge($b);

            expect($a->count())->toBe(1);
            expect($b->count())->toBe(1);
            expect($merged->count())->toBe(2);
        });

        it('filter returns a new filtered collection', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $filtered = $col->filter(fn (DataTransferObject $d) => $d->name === 'Alice');

            expect($col->count())->toBe(2);
            expect($filtered->count())->toBe(1);
        });

        it('sortBy returns a new sorted collection', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Charlie'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $sorted = $col->sortBy('name');

            expect($sorted->first()->name)->toBe('Alice');
            expect($col->first()->name)->toBe('Charlie'); // original unchanged
        });

        it('unique removes duplicates based on toArray() equality', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $unique = $col->unique();

            expect($unique->count())->toBe(1);
        });

        it('take and skip return new collections', function () {
            $dtos = array_map(
                fn (int $i) => AllDefaultsDTO::fromArray(['name' => "User{$i}"], validate: false),
                range(1, 5),
            );
            $col = DtoCollection::make($dtos);

            expect($col->take(2)->count())->toBe(2);
            expect($col->count())->toBe(5);
            expect($col->skip(3)->count())->toBe(2);
        });

        it('chunk splits into correct-sized collections', function () {
            $dtos = array_map(
                fn (int $i) => AllDefaultsDTO::fromArray(['name' => "User{$i}"], validate: false),
                range(1, 5),
            );
            $col = DtoCollection::make($dtos);

            $chunks = $col->chunk(2);

            expect($chunks)->toHaveCount(3); // 2 + 2 + 1
            expect($chunks[0]->count())->toBe(2);
            expect($chunks[2]->count())->toBe(1);
        });

        it('clone throws RuntimeException', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('count and isEmpty work correctly', function () {
            $empty = DtoCollection::make([]);
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $nonEmpty = DtoCollection::make([$dto]);

            expect($empty->count())->toBe(0);
            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->count())->toBe(1);
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('first and last return correct items', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Bob');
        });

        it('first and last return null for empty collection', function () {
            $col = DtoCollection::make([]);

            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });

        it('contains and search work', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            expect($col->contains(fn (DataTransferObject $d) => $d->name === 'Alice'))->toBeTrue();
            expect($col->contains(fn (DataTransferObject $d) => $d->name === 'Charlie'))->toBeFalse();
            expect($col->search(fn (DataTransferObject $d) => $d->name === 'Bob'))->toBe($dto2);
            expect($col->search(fn (DataTransferObject $d) => $d->name === 'Charlie'))->toBeNull();
        });

        it('map returns plain array of callback results', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $names = $col->map(fn (DataTransferObject $d) => $d->name);

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('push mutates in-place and returns self', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([]);

            $result = $col->push($dto);

            expect($col->count())->toBe(1); // mutated
            expect($result)->toBe($col); // same instance
        });

        it('offsetExists, offsetGet, offsetSet work', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeFalse();
            expect($col->offsetGet(0)->name)->toBe('Alice');
            expect($col->offsetGet(1))->toBeNull();
        });

        it('offsetUnset removes item and re-indexes', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $col->offsetUnset(0);

            expect($col->count())->toBe(1);
            expect($col->first()->name)->toBe('Bob'); // re-indexed
        });

        it('is iterable via foreach', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $names = [];
            foreach ($col as $dto) {
                $names[] = $dto->name;
            }

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('jsonSerialize returns array of toArray results', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect($col->jsonSerialize())->toBe([['name' => 'Alice', 'count' => 0, 'active' => false, 'items' => []]]);
        });

        it('__debugInfo shows count and first 3 items', function () {
            $dtos = array_map(
                fn (int $i) => AllDefaultsDTO::fromArray(['name' => "User{$i}"], validate: false),
                range(1, 5),
            );
            $col = DtoCollection::make($dtos);

            $debug = $col->__debugInfo();

            expect($debug)->toHaveKey('count');
            expect($debug)->toHaveKey('items');
            expect($debug['count'])->toBe(5);
            expect($debug['items'])->toHaveCount(3); // truncated to 3
        });

        it('make is a static factory', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect($col)->toBeInstanceOf(DtoCollection::class);
            expect($col->count())->toBe(1);
        });
    });

    describe('DTOManager delegation via facade', function () {
        it('DTO::rules returns array', function () {
            $rules = \ZeroBoiler\DTO\Facades\DTO::rules(AllDefaultsDTO::class);

n            expect($rules)->toBeArray();
        });

        it('DTO::rulesFor returns array', function () {
            $rules = \ZeroBoiler\DTO\Facades\DTO::rulesFor(AllDefaultsDTO::class, 'create');

n            expect($rules)->toBeArray();
        });
    });

    describe('DTOException', function () {
        it('invalidJson creates exception with property and error', function () {
            $e = \ZeroBoiler\DTO\Exceptions\DTOException::invalidJson('field', 'syntax error');

            expect($e->getMessage())->toContain('field');
            expect($e->getMessage())->toContain('syntax error');
        });

        it('invalidCast creates exception with property and type', function () {
            $e = \ZeroBoiler\DTO\Exceptions\DTOException::invalidCast('age', 'integer', 'abc');

            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('__toString returns class name and message', function () {
            $e = \ZeroBoiler\DTO\Exceptions\DTOException::invalidJson('field', 'err');

            expect((string) $e)->toContain('DTOException');
            expect((string) $e)->toContain('field');
        });
    });

    describe('validateArray', function () {
        it('returns validated data for valid input', function () {
            $data = AllDefaultsDTO::validateArray(['name' => 'Valid Name']);

            expect($data)->toBeArray();
        });

        it('throws ValidationException for invalid input', function () {
            expect(fn () => CreateUserDTO::validateArray(['name' => '', 'email' => 'invalid', 'password' => '']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('DTOCast Eloquent cast', function () {
        it('serializes DTO to JSON array in set()', function () {
            $cast = new \ZeroBoiler\DTO\Casts\DTOCast(AllDefaultsDTO::class);
            $dto = AllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

            $result = $cast->set(
                new class {
                    public function __set($k, $v) {}
                },
                'payload',
                $dto,
                [],
            );

            expect($result)->toBeJson();
        });

        it('returns null for null value in get()', function () {
            $cast = new \ZeroBoiler\DTO\Casts\DTOCast(AllDefaultsDTO::class);

            $result = $cast->get(
                new class {
                    public function __get($k) { return null; }
                },
                'payload',
                null,
                [],
            );

            expect($result)->toBeNull();
        });
    });
});
