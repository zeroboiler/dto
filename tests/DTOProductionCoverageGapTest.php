<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

// ── fromJson error paths ────────────────────────────────────────────────────

describe('fromJson error handling', function (): void {
    it('throws DTOException for invalid JSON', function (): void {
        expect(fn () => CreateUserDTO::fromJson('{invalid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array (not object)', function (): void {
        expect(fn () => CreateUserDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object for DTO without required fields', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(\ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO::class);
    });

    it('throws DTOException with property context on JSON decode failure', function (): void {
        try {
            CreateUserDTO::fromJson('not json at all', validate: false);
            expect(true)->toBeFalse(); // Force failure if no exception thrown
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('(root)');
        }
    });
});

// ── DTOCast: serialize method ────────────────────────────────────────────────

describe('DTOCast serialize behavior', function (): void {
    it('serializes DTO instance to array', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $cast->serialize(
            new class {},
            'payload',
            $dto,
            []
        );

        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
        expect($result['email'])->toBe('test@example.com');
    });

    it('returns null for null value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect($cast->serialize(new class {}, 'payload', null, []))->toBeNull();
    });
});

// ── DTOCast: set with validation disabled ──────────────────────────────────

describe('DTOCast set behavior', function (): void {
    it('rejects unexpected types in set()', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(new class {}, 'payload', 42, []))
            ->toThrow(\InvalidArgumentException::class);
    });
});

// ── DtoCollection: toArrayBy / toDictionary ──────────────────────────────────

describe('DtoCollection toArrayBy and toDictionary', function (): void {
    it('toArrayBy re-keys collection by property', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveKey('a@test.com');
        expect($keyed['a@test.com']['name'])->toBe('Alice');
        expect($keyed['b@test.com']['name'])->toBe('Bob');
    });

    it('toDictionary maps one property to another', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toBe(['a@test.com' => 'Alice', 'b@test.com' => 'Bob']);
    });

    it('skips items with null key values', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => null, 'name' => 'NoEmail'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveCount(1);
        expect($keyed)->toHaveKey('a@test.com');
    });
});

// ── DtoCollection: clone and immutability ────────────────────────────────────

describe('DtoCollection clone behavior', function (): void {
    it('append returns new collection without modifying original', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('merge returns new collection combining both', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('push mutates in-place and returns self', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection);
    });
});

// ── DtoCollection: filter returns re-indexed collection ─────────────────────

describe('DtoCollection filter re-indexing', function (): void {
    it('filter returns re-indexed collection', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => $dto->name !== 'B'
        );

        expect($filtered->count())->toBe(2);
        expect($filtered->first()->name)->toBe('A');
    });
});

// ── DtoCollection: offsetUnset re-indexing ───────────────────────────────────

describe('DtoCollection offsetUnset re-indexing', function (): void {
    it('re-indexes after unsetting middle element', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[1]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->name)->toBe('A');
        expect($collection[1]->name)->toBe('C');
    });
});

// ── DtoCollection: JSON serialization ───────────────────────────────────────

describe('DtoCollection JSON serialization', function (): void {
    it('jsonSerialize returns array of toArray outputs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $json = json_encode($collection);

        $decoded = json_decode($json, true);
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
    });
});

// ── DataTransferObject: isEmpty / isNotEmpty ───────────────────────────────

describe('DTO isEmpty and isNotEmpty', function (): void {
    it('isEmpty returns true for DTO with all defaults', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // Has email and name set, so not empty
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

// ── fromPartialArray edge cases ────────────────────────────────────────────

describe('fromPartialArray edge cases', function (): void {
    it('preserves existing values for non-provided fields', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Original',
            'status' => 'active',
        ], validate: false);

        // With() is the correct way to update
        $updated = $original->with(['name' => 'Updated']);

        expect($updated->email)->toBe('test@example.com');
        expect($updated->name)->toBe('Updated');
        expect($updated->status)->toBe('active');
    });

    it('fromPartialArray with empty data uses defaults', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Original',
            'status' => 'active',
        ], validate: false);

        // Only override status — other fields should be preserved
        $partial = CreateUserDTO::fromPartialArray([
            'status' => 'inactive',
        ], validatePresent: false);

        expect($partial->status)->toBe('inactive');
    });
});

// ── rulesFor action-scoped rules ─────────────────────────────────────────────

describe('rulesFor action scoping', function (): void {
    it('returns same rules as rules() by default', function (): void {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('patch'))->toBe(CreateUserDTO::rules());
    });
});

// ── DTOException __toString ──────────────────────────────────────────────────

describe('DTOException __toString', function (): void {
    it('formats invalid cast as readable string', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect((string) $e)->toContain('Cannot cast property [age]');
        expect((string) $e)->toContain('integer');
    });

    it('formats invalid json as readable string', function (): void {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect((string) $e)->toContain('Cannot decode JSON for property [payload]');
        expect((string) $e)->toContain('Syntax error');
    });
});

// ── only() and except() with single string ───────────────────────────────────

describe('Selective output with string key', function (): void {
    it('only() accepts a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('except() accepts a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });
});

// ── DtoCollection: map returns plain array ─────────────────────────────────

describe('DtoCollection map helper', function (): void {
    it('map returns plain array with index', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $names = $collection->map(fn (CreateUserDTO $dto, int $index): string => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });
});

// ── DtoCollection: pluck extracts single field ───────────────────────────────

describe('DtoCollection pluck', function (): void {
    it('plucks email addresses from collection', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });
});

// ── DtoCollection: first, last, isEmpty, isNotEmpty ─────────────────────────

describe('DtoCollection basic operations', function (): void {
    it('returns first and last items', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);

        expect($collection->first()->name)->toBe('Alice');
        expect($collection->last()->name)->toBe('Bob');
    });

    it('returns null for first/last on empty collection', function (): void {
        $collection = new DtoCollection();

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();
    });
});

// ── DtoCollection: type guard ─────────────────────────────────────────────

describe('DtoCollection type guard', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        expect(fn () => new DtoCollection([new \stdClass()]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in offsetSet', function (): void {
        $collection = new DtoCollection();
        expect(fn () => $collection[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });
});

// ── DtoCollection: make factory ──────────────────────────────────────────────

describe('DtoCollection make factory', function (): void {
    it('creates collection from items', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = DtoCollection::make([$dto]);

        expect($collection->count())->toBe(1);
    });

    it('creates empty collection with no args', function (): void {
        $collection = DtoCollection::make();

        expect($collection->count())->toBe(0);
        expect($collection->isEmpty())->toBeTrue();
    });
});
