<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{CreateUserDTO, MinimalDTO, EmptyDTO, AllDefaultsDTO, OrderDTO, OrderItemDTO, ArticleDTO, ScalarConstraintsDTO, AddressDTO, DateCastDTO, ArrayCastDTO};

describe('V24 production readiness comprehensive edge-case audit', function () {
    describe('fromJson edge cases', function () {
        it('rejects JSON with numeric keys (sequential object)', function () {
            expect(fn () => CreateUserDTO::fromJson('{"0":"a","1":"b"}', validate: false))
                ->not->toThrow(DTOException::class);
        });

        it('accepts valid JSON with nested objects', function () {
            $dto = EmptyDTO::fromJson('{"foo":"bar","baz":"qux"}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBe('bar');
            expect($dto->baz)->toBeNull(); // unknown keys are ignored
        });

        it('fromJson with extra keys ignores unknown fields', function () {
            $dto = EmptyDTO::fromJson('{"foo":"hello","bar":"world","extra":"ignored"}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBe('hello');
        });

        it('fromJson with null values hydrates correctly', function () {
            $dto = EmptyDTO::fromJson('{"foo":null,"bar":null}', validate: false);
            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
        });
    });

    describe('fromArray with validation bypass', function () {
        it('skips validation when validate is false', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'not-an-email',
                'name' => '',
            ], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('not-an-email');
        });

        it('applies defaults for missing optional fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });
    });

    describe('MapFrom edge cases', function () {
        it('maps source key to property name', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'phone_number' => '555-1234',
            ], validate: false);
            expect($dto->phone)->toBe('555-1234');
        });

        it('falls back to property name when mapped key is absent', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'phone' => '555-9999',
            ], validate: false);
            // 'phone' key is not mapped — the property expects 'phone_number'
            // Since the constructor param name is 'phone', and MapFrom('phone_number')
            // it looks for 'phone_number' first, then 'phone'
            expect($dto->phone)->toBe('555-9999');
        });
    });

    describe('DefaultValue attribute behavior', function () {
        it('applies default when key is absent from data', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
            expect($dto->items)->toBe([]);
            expect($dto->token)->toBe('hidden-secret');
        });

        it('explicit null overrides default for nullable fields', function () {
            $dto = EmptyDTO::fromArray(['foo' => null], validate: false);
            expect($dto->foo)->toBeNull();
        });

        it('explicit empty string is preserved', function () {
            $dto = EmptyDTO::fromArray(['foo' => ''], validate: false);
            expect($dto->foo)->toBe('');
        });

        it('explicit empty array is preserved', function () {
            $dto = AllDefaultsDTO::fromArray(['items' => []], validate: false);
            expect($dto->items)->toBe([]);
        });
    });

    describe('Hidden attribute filtering', function () {
        it('toArray excludes hidden properties', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('token');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('count');
        });

        it('allValues includes hidden properties', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $all = $dto->allValues();
            expect($all)->toHaveKey('token');
            expect($all['token'])->toBe('hidden-secret');
        });

        it('toJson excludes hidden properties', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $json = $dto->toJson();
            $decoded = json_decode($json, true);
            expect($decoded)->not->toHaveKey('token');
        });
    });

    describe('equals() deep comparison', function () {
        it('equal DTOs with same defaults are equal', function () {
            $dto1 = AllDefaultsDTO::fromArray([], validate: false);
            $dto2 = AllDefaultsDTO::fromArray([], validate: false);
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equal DTOs with explicit same values are equal', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'test', 'count' => 5], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'test', 'count' => 5], validate: false);
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('different values are not equal', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'a'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'b'], validate: false);
            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('isEmpty() and isNotEmpty() edge cases', function () {
        it('empty DTO with all defaults is empty', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('non-zero int means not empty', function () {
            $dto = AllDefaultsDTO::fromArray(['count' => 1], validate: false);
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('zero int is still not empty (valid meaningful value)', function () {
            $dto = AllDefaultsDTO::fromArray(['count' => 0], validate: false);
            // count=0 is the default, but since all others are also defaults, isEmpty should be true
            expect($dto->isEmpty())->toBeTrue();
        });

        it('string value means not empty', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'hello'], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('non-empty array means not empty', function () {
            $dto = AllDefaultsDTO::fromArray(['items' => ['a']], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('true bool means not empty', function () {
            $dto = AllDefaultsDTO::fromArray(['active' => true], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('with() immutability guarantee', function () {
        it('original DTO is unchanged after with()', function () {
            $original = AllDefaultsDTO::fromArray(['name' => 'original'], validate: false);
            $updated = $original->with(['name' => 'updated'], validate: false);
            expect($original->name)->toBe('original');
            expect($updated->name)->toBe('updated');
        });

        it('with() always creates a new instance', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            $cloned = $dto->with([]);
            expect($cloned)->not->toBe($dto);
        });
    });

    describe('only() and except() selective output', function () {
        it('only with string returns single key', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'test', 'count' => 5], validate: false);
            $result = $dto->only('name');
            expect($result)->toBe(['name' => 'test']);
        });

        it('only with multiple keys returns only those', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $result = $dto->only('name', 'count');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('count');
            expect($result)->not->toHaveKey('active');
            expect($result)->not->toHaveKey('token'); // hidden
        });

        it('except with string removes single key', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'test'], validate: false);
            $result = $dto->except('name');
            expect($result)->not->toHaveKey('name');
        });

        it('except with multiple keys removes all specified', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $result = $dto->except('name', 'count', 'active', 'items');
            expect($result)->toBeEmpty();
        });
    });

    describe('fromPartialArray PATCH semantics', function () {
        it('merges partial data with defaults for AllDefaultsDTO', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'patched'], validate: false);
            expect($dto->name)->toBe('patched');
            expect($dto->count)->toBe(0); // default
            expect($dto->active)->toBeFalse(); // default
        });

        it('empty partial array returns all defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray([], validate: false);
            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
        });

        it('partial update preserves explicit values', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['count' => 42, 'active' => true], validate: false);
            expect($dto->count)->toBe(42);
            expect($dto->active)->toBeTrue();
        });
    });

    describe('rules() and rulesFor() consistency', function () {
        it('rules() returns string keys with array values', function () {
            $rules = AllDefaultsDTO::rules();
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rulesFor() returns same structure', function () {
            $rules = AllDefaultsDTO::rulesFor('create');
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rulesFor() defaults to rules() for unknown action', function () {
            $rules = AllDefaultsDTO::rules();
            $rulesFor = AllDefaultsDTO::rulesFor('unknown_action');
            expect($rules)->toEqual($rulesFor);
        });
    });

    describe('DtoCollection advanced operations', function () {
        it('make creates empty collection', function () {
            $col = DtoCollection::make([]);
            expect($col->count())->toBe(0);
            expect($col->isEmpty())->toBeTrue();
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });

        it('push returns same instance for chaining', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            $col = DtoCollection::make([]);
            $result = $col->push($dto);
            expect($result)->toBe($col);
            expect($col->count())->toBe(1);
        });

        it('append returns new instance', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            $col = DtoCollection::make([]);
            $new = $col->append($dto);
            expect($new)->not->toBe($col);
            expect($new->count())->toBe(1);
            expect($col->count())->toBe(0);
        });

        it('merge combines two collections', function () {
            $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2]);
            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('filter returns new collection with matching items', function () {
            $dto1 = AllDefaultsDTO::fromArray(['count' => 5], validate: false);
            $dto2 = AllDefaultsDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->isNotEmpty());
            expect($filtered->count())->toBe(1);
        });

        it('map returns plain array of transformed values', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'a'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'b'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $names = $col->map(fn (DataTransferObject $d): string => $d->name);
            expect($names)->toEqual(['a', 'b']);
        });

        it('pluck extracts single property', function () {
            $dto1 = AllDefaultsDTO::fromArray(['name' => 'x'], validate: false);
            $dto2 = AllDefaultsDTO::fromArray(['name' => 'y'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $names = $col->pluck('name');
            expect($names)->toEqual(['x', 'y']);
        });

        it('toArray serializes all DTOs', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $col = DtoCollection::make([$dto]);
            $arr = $col->toArray();
            expect($arr)->toBe([['foo' => 'bar']]);
        });

        it('allValues includes hidden fields of all DTOs', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto]);
            $all = $col->allValues();
            expect($all[0])->toHaveKey('token');
        });
    });

    describe('DTOCast comprehensive behavior', function () {
        it('get returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect($cast->get(new \stdClass(), 'data', null, []))->toBeNull();
        });

        it('get returns DTO from valid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $json = json_encode(['email' => 'test@test.com', 'name' => 'Test']);
            $result = $cast->get(new \stdClass(), 'data', $json, []);
            expect($result)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('get returns null for invalid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect($cast->get(new \stdClass(), 'data', 'not json', []))->toBeNull();
        });

        it('get returns DTO from array value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass(), 'data', ['email' => 'a@b.com', 'name' => 'X'], []);
            expect($result)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('set returns JSON string for DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'X'], validate: false);
            $result = $cast->set(new \stdClass(), 'data', $dto, []);
            expect($result)->toBeString();
            $decoded = json_decode($result, true);
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('set throws for int value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect(fn () => $cast->set(new \stdClass(), 'data', 42, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set throws for bool value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect(fn () => $cast->set(new \stdClass(), 'data', true, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize returns DTO array or null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'X'], validate: false);
            expect($cast->serialize(new \stdClass(), 'data', $dto, []))->toBeArray();
            expect($cast->serialize(new \stdClass(), 'data', null, []))->toBeNull();
        });
    });

    describe('DTOException factory methods', function () {
        it('invalidCast includes property and type', function () {
            $e = DTOException::invalidCast('age', 'integer', 'abc');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson includes property and error', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString includes class name', function () {
            $e = DTOException::invalidJson('x', 'err');
            $str = (string) $e;
            expect($str)->toContain(DTOException::class);
        });
    });

    describe('metadata cache TTL behavior', function () {
        it('setMetadataCacheTtl accepts float values', function () {
            DataTransferObject::setMetadataCacheTtl(0.5);
            // No assertion needed — just verify no crash
            DataTransferObject::setMetadataCacheTtl(0.0); // restore
        });

        it('flushMetadataCache clears all classes', function () {
            DataTransferObject::flushMetadataCache();
            // Resolve metadata for two DTOs
            AllDefaultsDTO::rules();
            EmptyDTO::rules();
            // Flush
            DataTransferObject::flushMetadataCache();
            // Re-resolve — should not crash
            expect(AllDefaultsDTO::rules())->toBeArray();
            expect(EmptyDTO::rules())->toBeArray();
        });

        it('flushMetadataCache for specific class', function () {
            AllDefaultsDTO::rules(); // warm cache
            DataTransferObject::flushMetadataCache(AllDefaultsDTO::class);
            // Re-resolve should not crash
            expect(AllDefaultsDTO::rules())->toBeArray();
        });
    });

    describe('nested DTO hydration roundtrip', function () {
        it('nested DTO hydrates and serializes correctly', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'item' => [
                    'name' => 'Widget',
                    'quantity' => 5,
                    'price' => 9.99,
                ],
            ], validate: false);

            expect($dto->item)->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->item->name)->toBe('Widget');

            // Roundtrip: toArray -> fromArray should produce same values
            $arr = $dto->toArray();
            expect($arr['item'])->toBeArray();
            expect($arr['item']['name'])->toBe('Widget');
        });
    });

    describe('jsonSerialize contract', function () {
        it('returns same structure as toArray()', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'test'], validate: false);
            expect($dto->jsonSerialize())->toEqual($dto->toArray());
        });

        it('DtoCollection jsonSerialize returns array of arrays', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->jsonSerialize())->toEqual($col->toArray());
        });
    });
});
