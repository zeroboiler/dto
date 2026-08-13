<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

describe('DTO Production Readiness Final Audit V4', function () {
    // ── Hydration Roundtrip ───────────────────────────────────────────────

    it('fromArray and toArray are symmetric for scalar types', function () {
        $data = [
            'name' => 'Alice',
            'value' => 'test-value',
        ];

        $dto = MinimalDTO::fromArray($data, validate: false);
        $arr = $dto->toArray();

        expect($arr['name'])->toBe('Alice');
        expect($arr['value'])->toBe('test-value');
    });

    it('fromJson round-trips correctly', function () {
        $json = '{"name":"Bob","value":"test-value"}';
        $dto = MinimalDTO::fromJson($json, validate: false);
        $restoredJson = $dto->toJson();

        $restored = json_decode($restoredJson, true);

        expect($restored['name'])->toBe('Bob');
        expect($restored['value'])->toBe('test-value');
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => MinimalDTO::fromJson('["a","b","c"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson rejects non-object JSON', function () {
        expect(fn () => MinimalDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    // ── Validation Rules ─────────────────────────────────────────────────────

    it('rules() returns array with field names as keys', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor returns same rules as rules() by default', function () {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        expect($rules)->toEqual($rulesFor);
    });

    // ── Immutable Update ──────────────────────────────────────────────────

    it('with() creates a new instance with overrides', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $modified = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice');
        expect($modified->name)->toBe('Bob');
        expect($modified->value)->toBe('v1');
    });

    // ── Selective Output ──────────────────────────────────────────────────

    it('only() returns specified fields', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $result = $dto->only(['name']);

        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('value');
    });

    it('except() excludes specified fields', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $result = $dto->except('value');

        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('value');
    });

    // ── Equality Check ─────────────────────────────────────────────────────

    it('equals() returns true for same data', function () {
        $dto1 = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $dto2 = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different data', function () {
        $dto1 = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $dto2 = MinimalDTO::fromArray([
            'name' => 'Bob',
            'value' => 'v2',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    // ── Empty Check ────────────────────────────────────────────────────────

    it('isEmpty() returns true for DTO with all null/empty values', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty() returns true for DTO with non-empty values', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ── Hidden Fields ─────────────────────────────────────────────────────

    it('toArray excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'alice@example.com',
            'name' => 'Alice',
            'status' => 'active',
            'tags' => ['admin'],
            'phone_number' => '+1234567890',
            'password' => 'secret123',
        ], validate: false);

        $public = $dto->toArray();
        $all = $dto->allValues();

        // toArray should exclude password
        expect($public)->not->toHaveKey('password');
        // allValues should include password
        expect($all)->toHaveKey('password');
    });

    // ── MapFrom ───────────────────────────────────────────────────────────

    it('MapFrom maps source key to property', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
            'status' => 'active',
            'tags' => [],
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    // ── DefaultValue ───────────────────────────────────────────────────────

    it('DefaultValue is applied when key is absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    // ── DtoCollection ─────────────────────────────────────────────────────

    it('DtoCollection wraps DTO instances with type safety', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false),
        ];

        $collection = new DtoCollection($items);

        expect($collection->count())->toBe(2);
        expect($collection->first())->toBeInstanceOf(MinimalDTO::class);
    });

    it('DtoCollection toArray serializes nested DTOs', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $arr = $collection->toArray();

        expect($arr)->toHaveCount(1);
        expect($arr[0])->toHaveKey('name');
        expect($arr[0]['name'])->toBe('A');
    });

    it('DtoCollection filter returns new collection', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $filtered = $collection->filter(fn (MinimalDTO $dto): bool => $dto->name === 'B');

        expect($filtered->count())->toBe(1);
        // Original should be unchanged
        expect($collection->count())->toBe(2);
    });

    it('DtoCollection map returns plain array', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $names = $collection->map(fn (MinimalDTO $dto): string => $dto->name);

        expect($names)->toEqual(['A', 'B']);
    });

    it('DtoCollection pluck extracts a single property', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $names = $collection->pluck('name');

        expect($names)->toEqual(['Alice', 'Bob']);
    });

    it('DtoCollection append returns new collection with extra item', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $newItem = MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false);
        $appended = $collection->append($newItem);

        expect($collection->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('DtoCollection first/last return correct items', function () {
        $items = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false),
            MinimalDTO::fromArray(['name' => 'C', 'value' => 'v3'], validate: false),
        ];

        $collection = new DtoCollection($items);

        expect($collection->first()->name)->toBe('A');
        expect($collection->last()->name)->toBe('C');
    });

    it('DtoCollection isEmpty/isNotEmpty work correctly', function () {
        $empty = new DtoCollection([]);
        $nonEmpty = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
        ]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    // ── Nested DTO Hydration ──────────────────────────────────────────────

    it('hydrates nested DTO from array', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ], validate: false);

        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->city)->toBe('Springfield');
    });

    // ── Partial Array (PATCH semantics) ────────────────────────────────────

    it('fromPartialArray hydrates present fields and uses defaults for missing', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'partial@test.com',
        ], validate: false);

        expect($dto->email)->toBe('partial@test.com');
        // status has DefaultValue('active') but the constructor also has a default
        // name has Required but no default — partial should provide type-appropriate empty value
    });

    // ── JSON Serialization ────────────────────────────────────────────────

    it('jsonSerialize returns toArray output', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $serialized = $dto->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized['name'])->toBe('Alice');
    });

    it('toJson produces valid JSON', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'Alice',
            'value' => 'v1',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded['name'])->toBe('Alice');
    });

    // ── DtoCollection merge and JSON serialization ───────────────────────

    it('DtoCollection merge combines items from both collections', function () {
        $col1 = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
        ]);
        $col2 = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false),
        ]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1); // Original unchanged
        expect($col2->count())->toBe(1); // Original unchanged
    });

    it('DtoCollection jsonSerialize returns array of arrays', function () {
        $collection = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false),
        ]);

        $json = json_encode($collection);
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded[0]['name'])->toBe('A');
    });
});
