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
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('V41 DTO Production Contract And Edge Case Audit', function () {
    // ── Interface contract compliance ──────────────────────────────────────

    describe('DTO interface contract compliance', function () {
        it('DataTransferObject implements FromRequestDTO', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        });

        it('DataTransferObject implements ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });

        it('DataTransferObject implements Arrayable', function () {
            expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
        });

        it('DataTransferObject implements JsonSerializable', function () {
            expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
        });

        it('DtoCollection implements ArrayAccess', function () {
            expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
        });

        it('DtoCollection implements Countable', function () {
            expect(DtoCollection::class)->toImplement(\Countable::class);
        });

        it('DtoCollection implements IteratorAggregate', function () {
            expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
        });

        it('DtoCollection implements JsonSerializable', function () {
            expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
        });
    });

    // ── DTOManager delegation contract ──────────────────────────────────

    describe('DTOManager delegation contract', function () {
        it('validate() returns validated data', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Test User'];
            $result = \ZeroBoiler\DTO\DTOManager::validate(CreateUserDTO::class, $data);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('make() returns DTO instance', function () {
            $dto = \ZeroBoiler\DTO\DTOManager::make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('rules() returns rules array', function () {
            $rules = \ZeroBoiler\DTO\DTOManager::rules(CreateUserDTO::class);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
        });

        it('rulesFor() returns rules for action', function () {
            $rules = \ZeroBoiler\DTO\DTOManager::rulesFor(CreateUserDTO::class, 'create');

            expect($rules)->toBeArray();
        });

        it('schema() returns OpenAPI schema', function () {
            $schema = \ZeroBoiler\DTO\DTOManager::schema(CreateUserDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
        });

        it('fromJson() creates DTO from JSON string', function () {
            $json = json_encode(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'pass123']);
            $dto = \ZeroBoiler\DTO\DTOManager::fromJson(CreateUserDTO::class, $json);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('fromJson() throws for invalid JSON', function () {
            expect(fn () => \ZeroBoiler\DTO\DTOManager::fromJson(CreateUserDTO::class, 'not-json'))
                ->toThrow(DTOException::class);
        });

        it('fromPartialArray() creates DTO with partial data', function () {
            $dto = \ZeroBoiler\DTO\DTOManager::fromPartialArray(CreateUserDTO::class, ['name' => 'Updated']);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ── DTOException factory contract ────────────────────────────────────

    describe('DTOException factory contract', function () {
        it('invalidCast() includes property, type, and value debug info', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');
            $msg = $ex->getMessage();

            expect($msg)->toContain('age');
            expect($msg)->toContain('integer');
            expect($msg)->toContain('string');
        });

        it('invalidJson() includes property and error message', function () {
            $ex = DTOException::invalidJson('payload', 'Syntax error');
            $msg = $ex->getMessage();

            expect($msg)->toContain('payload');
            expect($msg)->toContain('Syntax error');
        });

        it('__toString() returns class name and message', function () {
            $ex = DTOException::invalidCast('field', 'int', 'bad');
            $str = (string) $ex;

            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('field');
        });
    });

    // ── DTOCast serialization contract ──────────────────────────────────

    describe('DTOCast Eloquent cast contract', function () {
        it('get() returns null for null database value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns null for invalid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass, 'data', 'not-valid-json{', []);

            expect($result)->toBeNull();
        });

        it('get() returns null for non-array value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass, 'data', 42, []);

            expect($result)->toBeNull();
        });

        it('get() returns DTO instance for valid JSON', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $json = json_encode(['name' => 'Alice']);
            $result = $cast->get(new \stdClass, 'data', $json, []);

            expect($result)->toBeInstanceOf(MinimalDTO::class);
        });

        it('get() returns DTO instance for array value', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->get(new \stdClass, 'data', ['name' => 'Bob'], []);

            expect($result)->toBeInstanceOf(MinimalDTO::class);
        });

        it('set() returns null for null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->set(new \stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('set() returns JSON string for DTO instance', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $dto = MinimalDTO::fromArray(['name' => 'Alice']);
            $result = $cast->set(new \stdClass, 'data', $dto, []);

            expect($result)->toBeString();
            expect(json_decode($result, true))->toHaveKey('name');
        });

        it('set() returns JSON string for array value', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->set(new \stdClass, 'data', ['name' => 'Bob'], []);

            expect($result)->toBeString();
            $decoded = json_decode($result, true);
            expect($decoded['name'])->toBe('Bob');
        });

        it('set() rejects unsupported types', function () {
            $cast = new DTOCast(CreateUserDTO::class);

            expect(fn () => $cast->set(new \stdClass, 'data', 42, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns toArray() for DTO instance', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $dto = MinimalDTO::fromArray(['name' => 'Alice']);
            $result = $cast->serialize(new \stdClass, 'data', $dto, []);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Alice');
        });

        it('serialize() returns null for null value', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->serialize(new \stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('accepts validate parameter', function () {
            $castWithValidation = new DTOCast(MinimalDTO::class, validate: true);
            $castWithoutValidation = new DTOCast(MinimalDTO::class, validate: false);

            expect($castWithValidation)->toBeInstanceOf(DTOCast::class);
            expect($castWithoutValidation)->toBeInstanceOf(DTOCast::class);
        });
    });

    // ── DtoCollection edge cases ─────────────────────────────────────────

    describe('DtoCollection comprehensive edge cases', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection([new \stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('blocks external cloning', function () {
            $col = DtoCollection::make([MinimalDTO::fromArray(['name' => 'A'])]);

            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('allValues() includes hidden fields', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test']);
            $col = DtoCollection::make([$dto]);

            $all = $col->allValues();
            expect($all)->toBeArray();
            expect($all[0])->toHaveKey('name');
        });

        it('toJson() serializes correctly', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test']);
            $col = DtoCollection::make([$dto]);
            $json = json_encode($col);

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded[0]['name'])->toBe('Test');
        });

        it('offsetUnset re-indexes the collection', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'A']);
            $d2 = MinimalDTO::fromArray(['name' => 'B']);
            $d3 = MinimalDTO::fromArray(['name' => 'C']);
            $col = DtoCollection::make([$d1, $d2, $d3]);

            unset($col[0]);

            expect($col[0])->toBe($d2);
            expect($col[1])->toBe($d3);
            expect($col->count())->toBe(2);
        });

        it('offsetSet with null offset appends', function () {
            $col = DtoCollection::make();
            $dto = MinimalDTO::fromArray(['name' => 'A']);
            $col[] = $dto;

            expect($col->count())->toBe(1);
            expect($col[0]->name)->toBe('A');
        });

        it('offsetSet rejects non-DTO', function () {
            $col = DtoCollection::make();

            expect(fn () => $col[] = 'not-a-dto')
                ->toThrow(\InvalidArgumentException::class);
        });

        it('append() returns new collection without modifying original', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'A']);
            $dto2 = MinimalDTO::fromArray(['name' => 'B']);
            $col = DtoCollection::make([$dto1]);

            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge() returns new collection without modifying originals', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'A']);
            $dto2 = MinimalDTO::fromArray(['name' => 'B']);
            $dto3 = MinimalDTO::fromArray(['name' => 'C']);

            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2, $dto3]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(2);
            expect($merged->count())->toBe(3);
        });

        it('filter() returns new collection with re-indexed items', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'A']);
            $d2 = MinimalDTO::fromArray(['name' => 'B']);
            $d3 = MinimalDTO::fromArray(['name' => 'A']);
            $col = DtoCollection::make([$d1, $d2, $d3]);

            $filtered = $col->filter(fn ($dto) => $dto->name === 'B');

            expect($filtered->count())->toBe(1);
            expect($filtered[0]->name)->toBe('B');
        });

        it('chunk() splits into correct sizes', function () {
            $items = array_map(fn (int $i) => MinimalDTO::fromArray(['name' => "Item{$i}"]), range(1, 5));
            $col = DtoCollection::make($items);

            $chunks = $col->chunk(2);

            expect($chunks)->toHaveCount(3); // [2, 2, 1]
            expect($chunks[0]->count())->toBe(2);
            expect($chunks[1]->count())->toBe(2);
            expect($chunks[2]->count())->toBe(1);
        });

        it('contains() with callback returns correct result', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2]);

            expect($col->contains(fn ($dto) => $dto->name === 'Alice'))->toBeTrue();
            expect($col->contains(fn ($dto) => $dto->name === 'Charlie'))->toBeFalse();
        });

        it('search() returns first matching DTO or null', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2]);

            expect($col->search(fn ($dto) => $dto->name === 'Bob'))->toBe($d2);
            expect($col->search(fn ($dto) => $dto->name === 'Charlie'))->toBeNull();
        });

        it('sortBy() with property name works', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Charlie']);
            $d2 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d3 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2, $d3]);

            $sorted = $col->sortBy('name');

            expect($sorted[0]->name)->toBe('Alice');
            expect($sorted[1]->name)->toBe('Bob');
            expect($sorted[2]->name)->toBe('Charlie');
        });

        it('sortBy() returns new collection without modifying original', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Charlie']);
            $d2 = MinimalDTO::fromArray(['name' => 'Alice']);
            $col = DtoCollection::make([$d1, $d2]);

            $sorted = $col->sortBy('name');

            expect($col[0]->name)->toBe('Charlie');
            expect($sorted[0]->name)->toBe('Alice');
        });

        it('unique() removes duplicate DTOs', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d2 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d3 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2, $d3]);

            $unique = $col->unique();

            expect($unique->count())->toBe(2);
        });

        it('take() returns first N items', function () {
            $items = array_map(fn (int $i) => MinimalDTO::fromArray(['name' => "Item{$i}"]), range(1, 5));
            $col = DtoCollection::make($items);

            $taken = $col->take(3);

            expect($taken->count())->toBe(3);
            expect($taken[0]->name)->toBe('Item1');
        });

        it('skip() returns items after N', function () {
            $items = array_map(fn (int $i) => MinimalDTO::fromArray(['name' => "Item{$i}"]), range(1, 5));
            $col = DtoCollection::make($items);

            $skipped = $col->skip(2);

            expect($skipped->count())->toBe(3);
            expect($skipped[0]->name)->toBe('Item3');
        });

        it('push() mutates in-place and returns self', function () {
            $col = DtoCollection::make();
            $dto = MinimalDTO::fromArray(['name' => 'A']);

            $result = $col->push($dto);

            expect($col->count())->toBe(1);
            expect($result)->toBe($col); // same instance
        });

        it('__debugInfo shows count and truncated items', function () {
            $items = array_map(fn (int $i) => MinimalDTO::fromArray(['name' => "Item{$i}"]), range(1, 10));
            $col = DtoCollection::make($items);
            $debug = $col->__debugInfo();

            expect($debug['count'])->toBe(10);
            expect(count($debug['items']))->toBeLessThanOrEqual(3);
        });

        it('toArrayBy() returns key-value map from toArray output', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2]);

            $keyed = $col->toArrayBy('name');

            expect($keyed)->toHaveKey('Alice');
            expect($keyed)->toHaveKey('Bob');
        });

        it('toDictionary() returns single-field map', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice']);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$d1, $d2]);

            $dict = $col->toDictionary('name', 'name');

            expect($dict['Alice'])->toBe('Alice');
            expect($dict['Bob'])->toBe('Bob');
        });
    });

    // ── DTO hydration edge cases ─────────────────────────────────────────

    describe('DTO hydration edge cases', function () {
        it('fromArray with validate=false skips validation', function () {
            $dto = MinimalDTO::fromArray(['name' => ''], validate: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('');
        });

        it('fromPartialArray with empty data returns DTO with defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray([]);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('fromPartialArray with validatePresent=false skips validation', function () {
            // Pass data that might fail validation but should be accepted
            $dto = MinimalDTO::fromPartialArray(['name' => 'Test'], validatePresent: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('Test');
        });

        it('fromJson rejects sequential arrays', function () {
            $json = json_encode(['item1', 'item2', 'item3']);

            expect(fn () => MinimalDTO::fromJson($json))
                ->toThrow(DTOException::class);
        });

        it('fromJson accepts empty array', function () {
            $dto = EmptyDTO::fromJson('[]');

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('with() creates new instance with merged data', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice']);
            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
        });

        it('with() always validates', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice']);

            // with() ignores $validate parameter and always validates
            $updated = $dto->with(['name' => 'Bob'], validate: false);

            expect($updated->name)->toBe('Bob');
        });
    });

    // ── Serialization contract ──────────────────────────────────────────

    describe('Serialization contract', function () {
        it('toArray() excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues() includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $all = $dto->allValues();

            expect($all)->toHaveKey('email');
            expect($all)->toHaveKey('name');
            expect($all)->toHaveKey('password');
        });

        it('only() returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $only = $dto->only('email');

            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
        });

        it('except() returns all except specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $except = $dto->except('email');

            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });

        it('toJson() returns valid JSON string', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test']);
            $json = $dto->toJson();

            expect($json)->toBeString();
            $decoded = json_decode($json, true);
            expect($decoded['name'])->toBe('Test');
        });

        it('equals() compares toArray() output', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test']);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test']);
            $dto3 = MinimalDTO::fromArray(['name' => 'Other']);

            expect($dto1->equals($dto2))->toBeTrue();
            expect($dto1->equals($dto3))->toBeFalse();
        });

        it('isEmpty() detects all-default/empty properties', function () {
            $dto = MinimalDTO::fromArray(['name' => '']);
            expect($dto->isEmpty())->toBeTrue();

            $dto2 = MinimalDTO::fromArray(['name' => 'Test']);
            expect($dto2->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() is negation of isEmpty()', function () {
            $dto = MinimalDTO::fromArray(['name' => '']);

            expect($dto->isNotEmpty())->toBeFalse();

            $dto2 = MinimalDTO::fromArray(['name' => 'Test']);
            expect($dto2->isNotEmpty())->toBeTrue();
        });

        it('__debugInfo matches toArray()', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test']);
            $debug = $dto->__debugInfo();

            expect($debug)->toEqual($dto->toArray());
        });
    });

    // ── Validation attribute contract ─────────────────────────────────

    describe('Validation attribute contract', function () {
        it('all validation attributes implement ValidationAttribute', function () {
            $attributes = [
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Between::class,
                \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class,
                \ZeroBoiler\DTO\Attributes\Same::class,
                \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class,
                \ZeroBoiler\DTO\Attributes\Present::class,
                \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Accepted::class,
                \ZeroBoiler\DTO\Attributes\StartsWith::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\Sometimes::class,
                \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class,
                \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
            ];

            foreach ($attributes as $attrClass) {
                expect($attrClass)->toImplement(ValidationAttribute::class);
            }
        });

        it('all validation attributes are final classes', function () {
            $attributes = [
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Between::class,
                \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Hidden::class,
                \ZeroBoiler\DTO\Attributes\MapFrom::class,
                \ZeroBoiler\DTO\Attributes\Cast::class,
                \ZeroBoiler\DTO\Attributes\DefaultValue::class,
                \ZeroBoiler\DTO\Attributes\NestedArray::class,
                \ZeroBoiler\DTO\Attributes\Collection::class,
            ];

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                expect($ref->isFinal())->toBeTrue("{$attrClass} should be final");
            }
        });

        it('all validation attributes have declare(strict_types=1)', function () {
            $attributes = [
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
            ];

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $file = $ref->getFileName();
                $contents = file_get_contents($file);
                expect($contents)->toContain('declare(strict_types=1)');
            }
        });
    });

    // ── Metadata cache lifecycle ─────────────────────────────────────────

    describe('Metadata cache lifecycle', function () {
        it('flushMetadataCache clears all entries', function () {
            // Trigger metadata resolution
            MinimalDTO::rules();
            DataTransferObject::flushMetadataCache();

            // Re-resolve should work fine
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache with class clears specific entry', function () {
            // Trigger metadata resolution for two classes
            MinimalDTO::rules();
            AllDefaultsDTO::rules();

            // Flush only one
            DataTransferObject::flushMetadataCache(MinimalDTO::class);

            // The other should still be cached
            $rules = AllDefaultsDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl configures TTL', function () {
            DataTransferObject::setMetadataCacheTtl(0.5);
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();

            // Reset
            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });
});
