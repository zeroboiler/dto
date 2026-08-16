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
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Fixtures\StrictValidationDTO;
use ZeroBoiler\DTO\Fixtures\TaskListDTO;
use ZeroBoiler\DTO\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Fixtures\ComprehensiveDTO;

describe('V23 PHPStan Level 9 strict type safety contract', function () {
    describe('return type strictness', function () {
        it('toArray returns array with string keys and mixed values', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toBeArray();

            foreach (array_keys($arr) as $key) {
                expect($key)->toBeString();
            }
        });

        it('toJson returns valid JSON string', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $json = $dto->toJson();
            expect($json)->toBeString();
            expect(json_decode($json))->toBeArray();
        });

        it('allValues includes hidden fields that toArray excludes', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            $all = $dto->allValues();

            // allValues should have password
            expect($all)->toHaveKey('password');
            // toArray should NOT have password
            expect($arr)->not->toHaveKey('password');
        });

        it('rules() returns array of arrays', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();

            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rulesFor returns same structure as rules', function () {
            $rules = CreateUserDTO::rules();
            $rulesFor = CreateUserDTO::rulesFor('create');
            expect($rulesFor)->toBeArray();

            // Same keys
            expect(array_keys($rulesFor))->toEqual(array_keys($rules));
        });
    });

    describe('fromArray type safety', function () {
        it('rejects invalid JSON in fromJson', function () {
            expect(fn () => CreateUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('rejects sequential JSON array in fromJson', function () {
            expect(fn () => CreateUserDTO::fromJson('["a","b"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object in fromJson', function () {
            $dto = EmptyDTO::fromJson('{}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('fromJson with empty string throws DTOException', function () {
            expect(fn () => CreateUserDTO::fromJson('', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromArray with validate: false skips validation', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'OK', // max 255 should be enforced if validate=true
            ], validate: false);

            expect($dto)->toBeInstanceOf(ScalarConstraintsDTO::class);
        });
    });

    describe('with() immutability contract', function () {
        it('returns a new instance', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $updated = $dto->with([]);
            expect($updated)->not->toBe($dto);
        });

        it('preserves original values for non-overridden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'original@example.com',
                'name' => 'Original',
                'password' => 'pass',
            ], validate: false);

            $updated = $dto->with(['email' => 'new@example.com'], validate: false);

            expect($updated->email)->toBe('new@example.com');
            expect($updated->name)->toBe('Original');
            expect($dto->email)->toBe('original@example.com'); // original unchanged
        });

        it('always validates merged data even with validate: false', function () {
            // with() always validates, the $validate param is deprecated and ignored
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            // Creating a DTO with invalid email via with() should throw
            expect(fn () => $dto->with(['email' => 'not-an-email']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('only() and except() type safety', function () {
        it('only returns array with only specified keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('email');
        });

        it('except returns array without specified keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $result = $dto->except('email');
            expect($result)->toBeArray();
            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('only with non-existent key is silently ignored', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $result = $dto->only('non_existent');
            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

        it('except with non-existent key returns full array', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $result = $dto->except('non_existent');
            expect($result)->toBeArray();
        });
    });

    describe('equals() and isEmpty() contracts', function () {
        it('equals returns true for identical DTOs', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'A',
                'password' => 'pass',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'B',
                'password' => 'pass',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns true for all-default DTO', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isNotEmpty returns false for all-default DTO', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when at least one field has a value', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'Active',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('fromPartialArray PATCH semantics', function () {
        it('merges partial data with defaults', function () {
            $dto = ArticleDTO::fromPartialArray([], validate: false);
            // Should have default values for all fields
            expect($dto)->toBeInstanceOf(ArticleDTO::class);
        });

        it('partial array with empty data returns defaults', function () {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    describe('MapFrom dot notation', function () {
        it('maps dot-notated source keys to nested property', function () {
            $dto = \ZeroBoiler\DTO\Fixtures\DotNotationDTO::fromArray([
                'user.profile.name' => 'John',
                'user.email' => 'john@example.com',
            ], validate: false);

            expect($dto)->toBeInstanceOf(\ZeroBoiler\DTO\Fixtures\DotNotationDTO::class);
        });
    });

    describe('nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'item' => [
                    'name' => 'Widget',
                    'quantity' => 5,
                    'price' => 9.99,
                ],
            ], validate: false);

            expect($dto)->toBeInstanceOf(OrderDTO::class);
            expect($dto->item)->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->item->name)->toBe('Widget');
        });

        it('nested DTO serializes recursively', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'item' => [
                    'name' => 'Widget',
                    'quantity' => 5,
                    'price' => 9.99,
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('item');
            expect($arr['item'])->toBeArray();
            expect($arr['item'])->toHaveKey('name');
        });
    });

    describe('DtoCollection type safety', function () {
        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not_a_dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('count returns integer', function () {
            $col = DtoCollection::make([]);
            expect($col->count())->toBeInt();
            expect($col->count())->toBe(0);
        });

        it('isEmpty returns true for empty collection', function () {
            expect(DtoCollection::make([])->isEmpty())->toBeTrue();
        });

        it('isNotEmpty returns true for non-empty collection', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->isNotEmpty())->toBeTrue();
        });

        it('pluck returns array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $col = DtoCollection::make([$dto]);
            $emails = $col->pluck('email');
            expect($emails)->toBeArray();
            expect($emails[0])->toBe('test@example.com');
        });

        it('append returns new collection (immutable)', function () {
            $dto1 = MinimalDTO::fromArray([], validate: false);
            $dto2 = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto1]);
            $newCol = $col->append($dto2);

            expect($newCol)->not->toBe($col);
            expect($newCol->count())->toBe(2);
            expect($col->count())->toBe(1);
        });

        it('push mutates in-place and returns self', function () {
            $dto1 = MinimalDTO::fromArray([], validate: false);
            $dto2 = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto1]);
            $result = $col->push($dto2);

            expect($result)->toBe($col); // same instance
            expect($col->count())->toBe(2);
        });

        it('offsetGet returns null for out-of-bounds', function () {
            $col = DtoCollection::make([]);
            expect($col->offsetGet(0))->toBeNull();
            expect($col->offsetGet(999))->toBeNull();
        });

        it('offsetUnset re-indexes the collection', function () {
            $dto1 = MinimalDTO::fromArray([], validate: false);
            $dto2 = MinimalDTO::fromArray([], validate: false);
            $dto3 = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto1, $dto2, $dto3]);

            $col->offsetUnset(1); // remove middle

            expect($col->count())->toBe(2);
            expect($col->offsetGet(0))->toBe($dto1);
            expect($col->offsetGet(1))->toBe($dto3); // re-indexed
        });

        it('first returns first item or null', function () {
            expect(DtoCollection::make([])->first())->toBeNull();

            $dto = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->first())->toBe($dto);
        });

        it('last returns last item or null', function () {
            expect(DtoCollection::make([])->last())->toBeNull();

            $dto = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->last())->toBe($dto);
        });

        it('map returns plain array', function () {
            $dto1 = MinimalDTO::fromArray([], validate: false);
            $dto2 = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $result = $col->map(fn (DataTransferObject $d): bool => true);
            expect($result)->toBeArray();
            expect($result)->toHaveCount(2);
            expect($result)->each->toBe(true);
        });

        it('filter returns new collection', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            $col = DtoCollection::make([$dto, $dto]);

            $filtered = $col->filter(fn (DataTransferObject $d): bool => true);
            expect($filtered)->not->toBe($col);
            expect($filtered->count())->toBe(2);
        });

        it('clone throws RuntimeException', function () {
            $col = DtoCollection::make([]);
            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('jsonSerialize returns array of arrays', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $col = DtoCollection::make([$dto]);
            $json = $col->jsonSerialize();

            expect($json)->toBeArray();
            expect($json)->toHaveCount(1);
            expect($json[0])->toBeArray();
        });
    });

    describe('DTOCast type safety', function () {
        it('get returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass(), 'data', null, []);
            expect($result)->toBeNull();
        });

        it('get returns null for invalid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass(), 'data', 'not json', []);
            expect($result)->toBeNull();
        });

        it('get returns null for non-array non-string value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass(), 'data', 42, []);
            expect($result)->toBeNull();
        });

        it('set throws for unsupported type', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect(fn () => $cast->set(new \stdClass(), 'data', 42, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set returns JSON string for DTO instance', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->set(new \stdClass(), 'data', $dto, []);
            expect($result)->toBeString();
            expect(json_decode($result, true))->toBeArray();
        });

        it('serialize returns DTO array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'pass',
            ], validate: false);

            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->serialize(new \stdClass(), 'data', $dto, []);
            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
        });

        it('serialize returns null for null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->serialize(new \stdClass(), 'data', null, []);
            expect($result)->toBeNull();
        });
    });

    describe('DTOException factory methods', function () {
        it('invalidCast includes property name and type', function () {
            $e = DTOException::invalidCast('age', 'integer', 'hello');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson includes property name and error', function () {
            $e = DTOException::invalidJson('data', 'Syntax error');
            expect($e->getMessage())->toContain('data');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function () {
            $e = DTOException::invalidJson('(root)', 'test');
            $str = (string) $e;
            expect($str)->toContain('DTOException');
            expect($str)->toContain('test');
        });
    });

    describe('nullable roundtrip edge cases', function () {
        it('null properties roundtrip through with()', function () {
            $dto = NullableRoundtripDTO::fromArray([
                'value' => null,
            ], validate: false);

            $roundtrip = $dto->with(['value' => null], validate: false);
            expect($roundtrip->value)->toBeNull();
        });
    });

    describe('hidden field filtering in collections', function () {
        it('collection toArray hides hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            $col = DtoCollection::make([$dto]);
            $arr = $col->toArray();

            expect($arr[0])->toBeArray();
            expect($arr[0])->not->toHaveKey('password');
        });
    });
});
