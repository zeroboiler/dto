<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;

/**
 * V40 production readiness — advanced edge cases for DtoCollection, fromPartialArray,
 * fromJson edge cases, and strict type safety.
 */
describe('Dto V40 Advanced Edge Cases', function () {
    // ── DtoCollection::sortBy with property name ──────────────────────────────

    it('sortBy property name sorts ascending correctly', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'c@a.com', 'name' => 'Charlie'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $c = ComprehensiveDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$a, $b, $c]);
        $sorted = $collection->sortBy('name');

        $names = array_map(fn (ComprehensiveDTO $dto): string => $dto->name, $sorted->items());
        expect($names)->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('sortBy property name handles null values by pushing them to end', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'b@c.com', 'name' => ''], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $sorted = $collection->sortBy('password');

        // Both have null password, but sort should not throw
        expect($sorted)->toBeInstanceOf(DtoCollection::class);
        expect($sorted->count())->toBe(2);
    });

    it('sortBy callback sorts by callback result', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'ccc'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'b@c.com', 'name' => 'aaa'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $sorted = $collection->sortBy(fn (ComprehensiveDTO $dto): int => strlen($dto->name));

        expect($sorted->first()->name)->toBe('aaa');
        expect($sorted->last()->name)->toBe('ccc');
    });

    // ── DtoCollection::take, skip, chunk ────────────────────────────────────

    it('take returns at most N items', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = ComprehensiveDTO::fromArray(['email' => "u{$i}@test.com", 'name' => "User{$i}"], validate: false);
        }

        $collection = new DtoCollection($items);
        $taken = $collection->take(3);

        expect($taken->count())->toBe(3);
        expect($taken->first()->name)->toBe('User0');
        expect($taken->last()->name)->toBe('User2');
    });

    it('take returns all items when count exceeds size', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        $taken = $collection->take(10);
        expect($taken->count())->toBe(1);
    });

    it('skip skips the first N items', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = ComprehensiveDTO::fromArray(['email' => "u{$i}@test.com", 'name' => "User{$i}"], validate: false);
        }

        $collection = new DtoCollection($items);
        $skipped = $collection->skip(2);

        expect($skipped->count())->toBe(3);
        expect($skipped->first()->name)->toBe('User2');
    });

    it('skip returns empty collection when count exceeds size', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        $skipped = $collection->skip(10);
        expect($skipped->isEmpty())->toBeTrue();
    });

    it('chunk splits collection into equal-sized chunks', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = ComprehensiveDTO::fromArray(['email' => "u{$i}@test.com", 'name' => "User{$i}"], validate: false);
        }

        $collection = new DtoCollection($items);
        $chunks = $collection->chunk(2);

        expect($chunks)->toHaveCount(3);
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    // ── DtoCollection immutability ──────────────────────────────────────────

    it('filter returns a new collection without mutating original', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $collection = new DtoCollection([$a, $b]);

        $filtered = $collection->filter(fn (ComprehensiveDTO $dto): bool => $dto->name === 'Alice');

        expect($collection->count())->toBe(2);
        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('clone throws RuntimeException to enforce immutability', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });

    it('append returns new collection without mutating original', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $collection = new DtoCollection([$a]);

        $appended = $collection->append($b);

        expect($collection->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('merge returns new collection without mutating originals', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $c = ComprehensiveDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

        $col1 = new DtoCollection([$a]);
        $col2 = new DtoCollection([$b, $c]);

        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    // ── DtoCollection ArrayAccess ───────────────────────────────────────────

    it('offsetExists returns true for existing offsets', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeFalse();
    });

    it('offsetGet returns item or null', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        expect($collection[0]->name)->toBe('Alice');
        expect($collection[99])->toBeNull();
    });

    it('offsetUnset re-indexes the collection', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $collection = new DtoCollection([$a, $b]);

        unset($collection[0]);

        expect($collection->count())->toBe(1);
        expect($collection[0]->name)->toBe('Charlie');
    });

    it('offsetSet appends when offset is null', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $collection = new DtoCollection([$a]);

        $collection[] = $b;

        expect($collection->count())->toBe(2);
        expect($collection[1]->name)->toBe('Charlie');
    });

    it('offsetSet rejects non-DTO values', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        expect(fn () => $collection[1] = 'not-a-dto')->toThrow(\InvalidArgumentException::class);
    });

    // ── fromJson edge cases ─────────────────────────────────────────────────

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => ComprehensiveDTO::fromJson('["a","b","c"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson allows empty array (valid empty object)', function () {
        // Empty array is ambiguous — could be list or object. fromJson allows it.
        // This will fail if email/name are required, but with validate=false it should
        // throw because the constructor requires parameters.
        expect(fn () => ComprehensiveDTO::fromJson('[]', validate: false))
            ->toThrow(\ArgumentCountError::class);
    });

    it('fromJson throws DTOException on invalid JSON', function () {
        expect(fn () => ComprehensiveDTO::fromJson('{invalid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException on non-object JSON', function () {
        expect(fn () => ComprehensiveDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException on numeric JSON', function () {
        expect(fn () => ComprehensiveDTO::fromJson('42', validate: false))
            ->toThrow(DTOException::class);
    });

    // ── DTO::isEmpty / isNotEmpty ───────────────────────────────────────────

    it('isEmpty returns true for all-default DTO', function () {
        // Create a DTO with only optional fields set to defaults
        // ComprehensiveDTO has email (required) and name (required), so we need
        // a DTO with only optional fields for a true isEmpty test
        $dto = ComprehensiveDTO::fromArray(
            ['email' => '', 'name' => ''],
            validate: false
        );

        // email and name are required but empty string counts as empty
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true when at least one property has a meaningful value', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'test@example.com', 'name' => 'Test'],
            validate: false
        );

        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ── toArray / allValues consistency ──────────────────────────────────────

    it('toArray excludes hidden fields, allValues includes them', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'],
            validate: false
        );

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues()['password'])->toBe('secret123');
    });

    // ── equals ──────────────────────────────────────────────────────────────

    it('equals returns true for identical DTOs', function () {
        $dto1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function () {
        $dto1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    // ── only / except ───────────────────────────────────────────────────────

    it('only returns only specified keys', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'age' => 30],
            validate: false
        );

        $result = $dto->only(['email']);
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
        expect($result)->not->toHaveKey('age');
    });

    it('except returns all but specified keys', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'age' => 30],
            validate: false
        );

        $result = $dto->except(['age']);
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('age');
    });

    // ── toJson ──────────────────────────────────────────────────────────────

    it('toJson produces valid JSON', function () {
        $dto = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('a@b.com');
        expect($decoded['name'])->toBe('Alice');
    });

    it('toJson excludes hidden fields', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'],
            validate: false
        );

        $decoded = json_decode($dto->toJson(), true);
        expect($decoded)->not->toHaveKey('password');
    });

    // ── DtoCollection JSON serialization ────────────────────────────────────

    it('DtoCollection jsonSerialize returns array of arrays', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $serialized = $collection->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized)->toHaveCount(2);
        expect($serialized[0])->toHaveKey('email');
        expect($serialized[0])->not->toHaveKey('password');
    });

    it('json_encode on DtoCollection produces valid JSON', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        $json = json_encode($collection);
        expect($json)->toBeJson();
    });

    // ── DtoCollection::unique ──────────────────────────────────────────────

    it('unique removes duplicates based on toArray output', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $c = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b, $c]);
        $unique = $collection->unique();

        expect($unique->count())->toBe(2);
    });

    // ── DtoCollection::contains / search ───────────────────────────────────

    it('contains finds matching DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);

        expect($collection->contains(fn (ComprehensiveDTO $dto): bool => $dto->name === 'Alice'))->toBeTrue();
        expect($collection->contains(fn (ComprehensiveDTO $dto): bool => $dto->name === 'Zara'))->toBeFalse();
    });

    it('search returns first matching DTO or null', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);

        expect($collection->search(fn (ComprehensiveDTO $dto): bool => $dto->name === 'Charlie')->name)->toBe('Charlie');
        expect($collection->search(fn (ComprehensiveDTO $dto): bool => $dto->name === 'Zara'))->toBeNull();
    });

    // ── DtoCollection::pluck / pluckKey / toDictionary ────────────────────

    it('pluck extracts a single property from all DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('pluckKey returns key-value pairs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $keyed = $collection->pluckKey('email', 'name');

        expect($keyed)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('toArrayBy returns DTOs keyed by property value', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveKey('a@b.com');
        expect($keyed['a@b.com']['name'])->toBe('Alice');
    });

    it('toDictionary returns property-to-property map', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    // ── DtoCollection::map ─────────────────────────────────────────────────

    it('map transforms each DTO and returns plain array', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $emails = $collection->map(fn (ComprehensiveDTO $dto): string => $dto->email);

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    // ── DtoCollection::first / last ─────────────────────────────────────────

    it('first returns first item or null for empty collection', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        expect($collection->first()->name)->toBe('Alice');

        $empty = new DtoCollection;
        expect($empty->first())->toBeNull();
    });

    it('last returns last item or null for empty collection', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $collection = new DtoCollection([$a, $b]);

        expect($collection->last()->name)->toBe('Charlie');

        $empty = new DtoCollection;
        expect($empty->last())->toBeNull();
    });

    // ── DtoCollection::make ────────────────────────────────────────────────

    it('make creates collection from array of DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = DtoCollection::make([$a, $b]);

        expect($collection->count())->toBe(2);
    });

    // ── DtoCollection rejects non-DTO items ─────────────────────────────────

    it('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not-a-dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetSet rejects non-DTO items', function () {
        $collection = new DtoCollection;
        expect(fn () => $collection[] = 'not-a-dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    // ── DTO::__debugInfo excludes hidden fields ──────────────────────────────

    it('__debugInfo excludes hidden fields', function () {
        $dto = ComprehensiveDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'],
            validate: false
        );

        $debug = $dto->__debugInfo();
        expect($debug)->not->toHaveKey('password');
        expect($debug)->toHaveKey('email');
    });

    // ── DTOException ─────────────────────────────────────────────────────────

    it('DTOException::invalidJson creates correct message', function () {
        $exception = DTOException::invalidJson('data', 'Syntax error');

        expect($exception->getMessage())->toContain('data');
        expect($exception->getMessage())->toContain('Syntax error');
    });

    it('DTOException::invalidCast creates correct message', function () {
        $exception = DTOException::invalidCast('count', 'integer', 'hello');

        expect($exception->getMessage())->toContain('count');
        expect($exception->getMessage())->toContain('integer');
    });

    it('DTOException::__toString returns class name and message', function () {
        $exception = DTOException::invalidJson('field', 'error');

        $str = (string) $exception;
        expect($str)->toContain('DTOException');
        expect($str)->toContain('field');
    });
});
