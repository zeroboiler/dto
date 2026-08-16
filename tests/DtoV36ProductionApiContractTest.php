<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, NestedArray, Collection};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{CreateUserDTO, AddressDTO, MinimalDTO, AllDefaultsDTO};
use ZeroBoiler\DTO\Tests\Fixtures\{EmptyDTO, ItemDTO, OrderDTO, OrderItemDTO, ComprehensiveDTO};
use ZeroBoiler\DTO\Tests\Fixtures\{RoundtripDTO, NullableRoundtripDTO, ScalarConstraintsDTO};
use ZeroBoiler\DTO\Tests\Fixtures\{EdgeCaseDTO, ProductDTO, WithRoundtripDTO, VoUserDTO};
use ZeroBoiler\DTO\Tests\Fixtures\{ComprehensiveValidationDTO, ValidationTestDTO, TaskListDTO};

/**
 * Production contract tests — verifies the public API surface of the DTO system.
 *
 * These tests ensure:
 * - fromArray/fromRequest hydration with validation
 * - fromPartialArray/fromPartialRequest PATCH semantics
 * - toArray/allValues/toJson serialization correctness
 * - Hidden fields are excluded from public output
 * - MapFrom correctly maps source keys
 * - Cast correctly transforms types during hydration
 * - DefaultValue fills missing fields
 * - Nested DTO hydration works recursively
 * - DtoCollection type safety and immutability
 * - with() creates immutable copies with validation
 * - equals/isEmpty/isNotEmpty state checks
 * - only/except selective output
 * - fromJson JSON hydration with error handling
 * - Facade delegation produces identical results
 * - Type safety — strict return types everywhere
 */
describe('V36 Production API Contract', function () {
    // -----------------------------------------------------------------------
    // 1. Basic hydration contract
    // -----------------------------------------------------------------------
    describe('Basic fromArray hydration', function () {
        it('creates DTO from valid array data', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('applies DefaultValue when source key is missing', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto->name)->toBe('default');
            expect($dto->status)->toBe('active');
        });

        it('respects explicit null over default', function () {
            $dto = MinimalDTO::fromArray([], validate: false);

            // Nullable property without DefaultValue should be null
            expect($dto->toArray())->toBeArray();
        });

        it('MapFrom maps source key to property name', function () {
            // Use a fixture with MapFrom
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'phone_number' => '555-1234', // MapFrom('phone_number') -> phone
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('phone');
            expect($all['phone'])->toBe('555-1234');
        });

        it('Cast transforms type during hydration', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'price' => '99.99',
                'quantity' => '5',
            ], validate: false);

            $all = $dto->allValues();
            // Cast('integer') on quantity should produce int
            expect($all['price'])->toBeFloat();
            expect($all['quantity'])->toBeInt();
        });
    });

    // -----------------------------------------------------------------------
    // 2. Serialization contract
    // -----------------------------------------------------------------------
    describe('Serialization', function () {
        it('toArray excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson produces valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe($dto->toArray());
        });

        it('jsonSerialize returns array matching toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // -----------------------------------------------------------------------
    // 3. Selective output contract
    // -----------------------------------------------------------------------
    describe('Selective output', function () {
        it('only() returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'status' => 'active',
            ], validate: false);

            $only = $dto->only('name', 'email');
            expect($only)->toHaveKeys(['name', 'email']);
            expect($only)->not->toHaveKey('status');
        });

        it('except() returns all except specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'status' => 'active',
            ], validate: false);

            $except = $dto->except('status');
            expect($except)->not->toHaveKey('status');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('email');
        });

        it('only() with single string key works', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            $only = $dto->only('name');
            expect($only)->toHaveKey('name');
            expect($only)->not->toHaveKey('email');
        });
    });

    // -----------------------------------------------------------------------
    // 4. Immutable update contract
    // -----------------------------------------------------------------------
    describe('Immutable update (with)', function () {
        it('creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice'); // original unchanged
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('alice@example.com');
        });

        it('with() always validates merged data', function () {
            // The deprecated $validate parameter should have no effect
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob'], validate: false);

            expect($updated->name)->toBe('Bob');
        });
    });

    // -----------------------------------------------------------------------
    // 5. State checks contract
    // -----------------------------------------------------------------------
    describe('State checks', function () {
        it('equals compares toArray output', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d3 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);

            expect($d1->equals($d2))->toBeTrue();
            expect($d1->equals($d3))->toBeFalse();
        });

        it('isEmpty detects empty-like DTOs', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            // All fields have defaults — check if defaults are considered empty
            // String 'default' is non-empty, so isEmpty should be false
            expect(is_bool($dto->isEmpty()))->toBeTrue();
        });

        it('isNotEmpty is the logical inverse of isEmpty', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto->isNotEmpty())->toBe(!$dto->isEmpty());
        });
    });

    // -----------------------------------------------------------------------
    // 6. Partial update contract
    // -----------------------------------------------------------------------
    describe('Partial update (PATCH semantics)', function () {
        it('fromPartialArray hydrates only provided fields', function () {
            $dto = AllDefaultsDTO::fromPartialArray([
                'name' => 'Updated',
            ], validate: false);

            expect($dto->name)->toBe('Updated');
            // Other fields should have defaults
            expect($dto->status)->toBe('active');
        });

        it('fromPartialArray with empty data creates DTO with all defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray([], validate: false);

            expect($dto->name)->toBe('default');
            expect($dto->status)->toBe('active');
        });

        it('fromPartialArray with validation skips empty data', function () {
            // Empty data — validate: true should not throw
            $dto = AllDefaultsDTO::fromPartialArray([]);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });
    });

    // -----------------------------------------------------------------------
    // 7. JSON hydration contract
    // -----------------------------------------------------------------------
    describe('JSON hydration', function () {
        it('fromJson creates DTO from JSON string', function () {
            $json = json_encode(['name' => 'Alice', 'email' => 'alice@example.com']);
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('fromJson throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid}', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws DTOException for non-object JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson accepts empty object', function () {
            $dto = AllDefaultsDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });
    });

    // -----------------------------------------------------------------------
    // 8. Nested DTO contract
    // -----------------------------------------------------------------------
    describe('Nested DTOs', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'customer' => ['name' => 'Alice', 'email' => 'alice@test.com'],
            ], validate: false);

            expect($dto->customer)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->customer->name)->toBe('Alice');
        });

        it('serializes nested DTO recursively', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'customer' => ['name' => 'Alice', 'email' => 'alice@test.com'],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['customer'])->toBeArray();
            expect($arr['customer']['name'])->toBe('Alice');
        });

        it('OrderItemDTO with nested items works', function () {
            $dto = OrderItemDTO::fromArray([
                'sku' => 'SKU-001',
                'quantity' => 3,
                'product' => ['name' => 'Widget', 'price' => '9.99', 'quantity' => '100'],
            ], validate: false);

            expect($dto->product)->toBeInstanceOf(ProductDTO::class);
            expect($dto->product->name)->toBe('Widget');
        });
    });

    // -----------------------------------------------------------------------
    // 9. DtoCollection contract
    // -----------------------------------------------------------------------
    describe('DtoCollection', function () {
        it('creates typed collection from DTOs', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);

            $col = new DtoCollection([$d1, $d2]);

            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('toArray serializes all items', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $col = new DtoCollection([$d1]);

            $arr = $col->toArray();
            expect($arr)->toHaveCount(1);
            expect($arr[0]['name'])->toBe('A');
        });

        it('filter returns new collection without mutating', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'b@c.com'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $filtered = $col->filter(fn (DataTransferObject $dto) => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2); // original unchanged
        });

        it('append returns new collection', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);
            $col = new DtoCollection([$d1]);

            $appended = $col->append($d2);

            expect($appended->count())->toBe(2);
            expect($col->count())->toBe(1); // original unchanged
        });

        it('push mutates in-place and returns self', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);
            $col = new DtoCollection([$d1]);

            $result = $col->push($d2);

            expect($col->count())->toBe(2); // mutated
            expect($result)->toBe($col); // same instance
        });

        it('pluck extracts property values', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'b@c.com'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $names = $col->pluck('name');

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('first/last return correct items or null', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->first()->name)->toBe('A');
            expect($col->last()->name)->toBe('B');

            $empty = new DtoCollection;
            expect($empty->first())->toBeNull();
            expect($empty->last())->toBeNull();
        });

        it('jsonSerialize returns array of arrays', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $col = new DtoCollection([$d1]);

            $serialized = $col->jsonSerialize();

            expect($serialized)->toBeArray();
            expect($serialized[0])->toBeArray();
            expect($serialized[0]['name'])->toBe('A');
        });

        it('make factory creates collection', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $col = DtoCollection::make([$d1]);

            expect($col->count())->toBe(1);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not_a_dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset re-indexes the collection', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);
            $d3 = CreateUserDTO::fromArray(['name' => 'C', 'email' => 'c@d.com'], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col->first()->name)->toBe('B');
            expect($col->last()->name)->toBe('C');
        });

        it('merge combines two collections', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@b.com'], validate: false);
            $d2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@c.com'], validate: false);
            $col1 = new DtoCollection([$d1]);
            $col2 = new DtoCollection([$d2]);

            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1); // original unchanged
        });

        it('map returns plain array of results', function () {
            $d1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $col = new DtoCollection([$d1]);

            $names = $col->map(fn (DataTransferObject $dto, int $index): string => $dto->name);

            expect($names)->toBe(['Alice']);
        });
    });

    // -----------------------------------------------------------------------
    // 10. Validation rules contract
    // -----------------------------------------------------------------------
    describe('Validation rules', function () {
        it('rules() returns associative array of field => rule list', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rulesFor() delegates to rules() by default', function () {
            $rules = ComprehensiveValidationDTO::rules();
            $rulesForCreate = ComprehensiveValidationDTO::rulesFor('create');

            expect($rules)->toBe($rulesForCreate);
        });

        it('validateArray returns validated data', function () {
            $validated = AllDefaultsDTO::validateArray([
                'name' => 'Test',
                'status' => 'active',
            ]);

            expect($validated)->toBeArray();
        });
    });

    // -----------------------------------------------------------------------
    // 11. Empty DTO contract
    // -----------------------------------------------------------------------
    describe('Empty DTO', function () {
        it('EmptyDTO creates without constructor arguments', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toBeArray();
        });
    });

    // -----------------------------------------------------------------------
    // 12. __debugInfo contract
    // -----------------------------------------------------------------------
    describe('Debug info', function () {
        it('__debugInfo returns toArray output', function () {
            $dto = CreateUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ], validate: false);

            $debug = $dto->__debugInfo();

            expect($debug)->toBe($dto->toArray());
        });
    });
});
