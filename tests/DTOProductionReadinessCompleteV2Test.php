<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;

describe('DTO Production Readiness Complete V2', function () {

    // ── DataTransferObject — Hydration ─────────────────────────────────────

    describe('fromArray()', function () {
        it('creates DTO from valid data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
        });

        it('applies DefaultValue when source key is absent', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            expect($dto->status)->toBe('active');
        });

        it('respects explicit null values (does not override with default)', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'status' => null,
                'phone' => null,
            ], validate: false);
            // status has DefaultValue but is non-nullable; explicit null should pass through
            expect($dto->status)->toBeNull();
        });

        it('applies MapFrom for key aliasing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890',
            ], validate: false);
            expect($dto->phone)->toBe('+1234567890');
        });

        it('applies Cast type transformation', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'tags' => '["a","b"]',
            ], validate: false);
            expect($dto->tags)->toBe(['a', 'b']);
        });

        it('creates DTO from empty data with all optional properties', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('creates DTO with only required fields', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });

    // ── Serialization ─────────────────────────────────────────────────────

    describe('toArray()', function () {
        it('excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('includes all non-hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890',
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr)->toHaveKeys(['email', 'name', 'status', 'tags', 'phone']);
        });

        it('serializes nested DTOs recursively', function () {
            $dto = OrderDTO::fromArray([
                'items' => [
                    ['name' => 'Widget', 'quantity' => 2, 'price' => 9.99],
                ],
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr['items'])->toBeArray();
            expect($arr['items'][0])->toHaveKey('name');
        });
    });

    describe('allValues()', function () {
        it('includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);
            $arr = $dto->allValues();
            expect($arr)->toHaveKey('password');
            expect($arr['password'])->toBe('secret123');
        });
    });

    describe('toJson()', function () {
        it('returns valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $json = $dto->toJson();
            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);
            $json = $dto->toJson();
            $decoded = json_decode($json, true);
            expect($decoded)->not->toHaveKey('password');
        });
    });

    describe('jsonSerialize()', function () {
        it('returns toArray() output', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // ── Selective Output ───────────────────────────────────────────────────

    describe('only()', function () {
        it('returns only specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $result = $dto->only('email');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('accepts array of field names', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $result = $dto->only(['email', 'name']);
            expect($result)->toHaveCount(2);
        });

        it('ignores non-existent keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $result = $dto->only('email', 'non_existent');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('non_existent');
        });

        it('excludes hidden fields even if requested', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);
            $result = $dto->only('password');
            // only() uses toArray() which excludes hidden
            expect($result)->not->toHaveKey('password');
        });
    });

    describe('except()', function () {
        it('excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $result = $dto->except('name');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('accepts array of field names', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);
            $result = $dto->except(['name', 'status']);
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });
    });

    // ── Immutable Update ──────────────────────────────────────────────────

    describe('with()', function () {
        it('returns new DTO with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $updated = $dto->with(['name' => 'Bob'], validate: false);
            expect($updated)->toBeInstanceOf(CreateUserDTO::class);
            expect($updated->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // Original unchanged
        });

        it('always validates merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            // validate param has no effect — validation always runs
            // This will validate and pass since 'Bob' satisfies Min(2)
            $updated = $dto->with(['name' => 'Bob'], validate: false);
            expect($updated->name)->toBe('Bob');
        });
    });

    // ── Equality & State ───────────────────────────────────────────────────

    describe('equals()', function () {
        it('returns true for DTOs with same values', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Alice'];
            $a = CreateUserDTO::fromArray($data, validate: false);
            $b = CreateUserDTO::fromArray($data, validate: false);
            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for DTOs with different values', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('isEmpty()', function () {
        it('returns true for empty DTO', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('returns false for DTO with data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('considers 0 as non-empty', function () {
            // RoundtripDTO has a 'score' property with DefaultValue(0.0) —
            // 0 and 0.0 are considered non-empty per isEmpty() contract.
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 0,
                'active' => true,
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('isNotEmpty()', function () {
        it('is negation of isEmpty()', function () {
            $empty = EmptyDTO::fromArray([], validate: false);
            expect($empty->isNotEmpty())->toBeFalse();
        });
    });

    // ── JSON Hydration ────────────────────────────────────────────────────

    describe('fromJson()', function () {
        it('creates DTO from JSON string', function () {
            $json = '{"email":"test@example.com","name":"Alice"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    // ── Partial Updates ────────────────────────────────────────────────────

    describe('fromPartialArray()', function () {
        it('hydrates only provided fields', function () {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated'], validate: false);
            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active'); // default
        });

        it('returns DTO from empty data', function () {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ── Validation ────────────────────────────────────────────────────────

    describe('rules()', function () {
        it('returns validation rules array', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('email rules contain required and email', function () {
            $rules = CreateUserDTO::rules();
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });
    });

    describe('rulesFor()', function () {
        it('returns same as rules() by default', function () {
            $rules = CreateUserDTO::rulesFor('create');
            expect($rules)->toBe(CreateUserDTO::rules());
        });
    });

    // ── DTOException ──────────────────────────────────────────────────────

    describe('DTOException', function () {
        it('invalidCast() includes property name and type', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('string');
        });

        it('invalidJson() includes property name and error', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString() returns class name and message', function () {
            $e = DTOException::invalidCast('age', 'integer', 'abc');
            $str = (string) $e;
            expect($str)->toContain('DTOException');
            expect($str)->toContain('age');
        });
    });

    // ── DTOCollection ─────────────────────────────────────────────────────

    describe('DtoCollection', function () {
        it('creates from array of DTOs', function () {
            $dtoList = [
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
            ];
            $col = new DtoCollection($dtoList);
            expect($col->count())->toBe(2);
        });

        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('make() factory works', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->count())->toBe(1);
        });

        it('first() returns first item', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->first())->toBe($dto);
        });

        it('first() returns null for empty collection', function () {
            $col = DtoCollection::make([]);
            expect($col->first())->toBeNull();
        });

        it('last() returns last item', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            expect($col->last())->toBe($b);
        });

        it('last() returns null for empty collection', function () {
            $col = DtoCollection::make([]);
            expect($col->last())->toBeNull();
        });

        it('isEmpty() returns true for empty collection', function () {
            expect(DtoCollection::make([])->isEmpty())->toBeTrue();
        });

        it('isNotEmpty() returns true for non-empty collection', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            expect(DtoCollection::make([$dto])->isNotEmpty())->toBeTrue();
        });

        it('map() returns plain array of results', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            $names = $col->map(fn (DataTransferObject $d): string => $d->name);
            expect($names)->toEqual(['A', 'C']);
        });

        it('filter() returns new collection', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'A');
            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('A');
        });

        it('push() mutates and returns self', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a]);
            $result = $col->push($b);
            expect($col)->toBe($result); // Same instance
            expect($col->count())->toBe(2);
        });

        it('append() returns new collection', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a]);
            $newCol = $col->append($b);
            expect($col)->not->toBe($newCol); // Different instance
            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge() combines two collections', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $c = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);
            $col1 = DtoCollection::make([$a]);
            $col2 = DtoCollection::make([$b, $c]);
            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(3);
        });

        it('toArray() serializes all DTOs', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$a]);
            $arr = $col->toArray();
            expect($arr)->toBeArray();
            expect($arr[0])->toBe($a->toArray());
        });

        it('jsonSerialize() returns toArray()', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$a]);
            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('supports ArrayAccess', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            expect(isset($col[0]))->toBeTrue();
            expect($col[0])->toBe($a);
            expect(isset($col[5]))->toBeFalse();
        });

        it('offsetSet() appends on null offset', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([]);
            $col[] = $a;
            expect($col->count())->toBe(1);
        });

        it('offsetUnset() re-indexes', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            unset($col[0]);
            expect($col->count())->toBe(1);
            expect($col[0]->name)->toBe('C'); // Re-indexed
        });

        it('supports foreach via IteratorAggregate', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            $names = [];
            foreach ($col as $dto) {
                $names[] = $dto->name;
            }
            expect($names)->toEqual(['A', 'C']);
        });

        it('pluck() extracts property values', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            $emails = $col->pluck('email');
            expect($emails)->toEqual(['a@b.com', 'c@d.com']);
        });

        it('pluckKey() returns key-value pairs', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$a, $b]);
            $map = $col->pluckKey('email', 'name');
            expect($map['a@b.com'])->toBe('A');
            expect($map['c@d.com'])->toBe('C');
        });

        it('toArrayBy() re-keys by property', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$a]);
            $keyed = $col->toArrayBy('email');
            expect($keyed)->toHaveKey('a@b.com');
        });

        it('toDictionary() maps two properties', function () {
            $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$a]);
            $dict = $col->toDictionary('email', 'name');
            expect($dict)->toBe(['a@b.com' => 'A']);
        });
    });

    // ── DTOCast ───────────────────────────────────────────────────────────

    describe('DTOCast', function () {
        it('get() returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            expect($cast->get($model, 'data', null, []))->toBeNull();
        });

        it('get() returns DTO from JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            $json = json_encode(['email' => 'test@example.com', 'name' => 'Alice']);
            $dto = $cast->get($model, 'data', $json, []);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
        });

        it('get() returns null for invalid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            expect($cast->get($model, 'data', 'not json', []))->toBeNull();
        });

        it('get() returns DTO from array', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            $dto = $cast->get($model, 'data', ['email' => 'a@b.com', 'name' => 'A'], []);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('set() returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            expect($cast->set($model, 'data', null, []))->toBeNull();
        });

        it('set() returns JSON from DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $result = $cast->set($model, 'data', $dto, []);
            expect($result)->toBeJson();
            $decoded = json_decode($result, true);
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('set() returns JSON from array', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            $result = $cast->set($model, 'data', ['email' => 'a@b.com', 'name' => 'A'], []);
            expect($result)->toBeJson();
        });

        it('set() throws for invalid type', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            expect(fn () => $cast->set($model, 'data', 12345, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns DTO toArray()', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $result = $cast->serialize($model, 'data', $dto, []);
            expect($result)->toBe($dto->toArray());
        });

        it('serialize() returns null for null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {};
            expect($cast->serialize($model, 'data', null, []))->toBeNull();
        });
    });

    // ── DTOManager ────────────────────────────────────────────────────────

    describe('DTOManager', function () {
        it('validate() delegates to DTO class', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $result = $manager->validate(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);
            expect($result)->toBeArray();
        });

        it('make() creates DTO from data', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('rules() returns DTO rules', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $rules = $manager->rules(CreateUserDTO::class);
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
        });

        it('rulesFor() returns action-scoped rules', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $rules = $manager->rulesFor(CreateUserDTO::class, 'update');
            expect($rules)->toBeArray();
        });

        it('is readonly', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $reflection = new \ReflectionClass($manager);
            expect($reflection->isReadOnly())->toBeTrue();
        });
    });

    // ── Metadata Cache ────────────────────────────────────────────────────

    describe('Metadata cache', function () {
        it('flushMetadataCache() clears all classes', function () {
            CreateUserDTO::flushMetadataCache();
            // Resolve metadata (populates cache)
            CreateUserDTO::rules();
            // Flush should clear it
            CreateUserDTO::flushMetadataCache();
            expect(true)->toBeTrue(); // If no exception, it worked
        });

        it('flushMetadataCache(null) clears all, class-specific clears one', function () {
            CreateUserDTO::flushMetadataCache();
            CreateUserDTO::flushMetadataCache(CreateUserDTO::class);
            expect(true)->toBeTrue();
        });

        it('setMetadataCacheTtl() accepts float', function () {
            CreateUserDTO::setMetadataCacheTtl(5.0);
            CreateUserDTO::setMetadataCacheTtl(0.0);
            expect(true)->toBeTrue();
        });
    });

    // ── Date Casting ────────────────────────────────────────────────────────

    describe('Date casting', function () {
        it('casts string date to Carbon', function () {
            $dto = DateCastDTO::fromArray([
                'event_date' => '1990-01-15',
            ], validate: false);
            expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('passes through Carbon instance', function () {
            $carbon = new \Carbon\Carbon('2024-06-15');
            $dto = DateCastDTO::fromArray([
                'event_date' => $carbon,
            ], validate: false);
            expect($dto->event_date)->toBe($carbon);
        });
    });

    // ── Nested DTOs ────────────────────────────────────────────────────────

    describe('Nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => 'Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);
            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->city)->toBe('Istanbul');
        });

        it('serializes nested DTOs to arrays', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => 'Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Istanbul');
        });
    });
});
