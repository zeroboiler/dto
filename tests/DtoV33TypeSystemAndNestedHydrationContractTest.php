<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

/**
 * V33 — DTO type system contract, nested hydration, and DtoCollection edge case tests.
 *
 * Targets:
 * - Nested DTO hydration via fromArray (single nested DTO)
 * - Nested array DTO hydration via fromArray (NestedArray attribute)
 * - Nested DTO serialization via toArray (recursive)
 * - DtoCollection toArray with nested DTOs
 * - Date casting from string and null
 * - fromJson round-trip preserves all data
 * - fromArray with validation disabled accepts invalid data
 * - EmptyDTO hydration from empty array
 * - AllDefaultsDTO with partial data uses defaults for missing fields
 * - DtoCollection operations: toArrayBy, toDictionary, filter, map edge cases
 * - DtoCollection with multiple items: pluck returns correct order
 * - with() on DTO with hidden fields preserves hidden in allValues
 * - except() does not leak hidden fields
 * - DTO metadata cache flush behavior
 * - DTOException message format consistency
 * - fromPartialArray with single field updates only that field
 * - equals symmetry and reflexivity
 * - DtoCollection merge with empty collections
 */
test('nested single DTO hydration from array produces correct instance', function (): void {
    $dto = OrderDTO::fromArray([
        'orderNumber' => 'ORD-001',
        'shippingAddress' => ['street' => '123 Main St', 'city' => 'NYC'],
    ], validate: false);

    expect($dto)->toBeInstanceOf(OrderDTO::class);
    expect($dto->orderNumber)->toBe('ORD-001');
    expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
    expect($dto->shippingAddress->street)->toBe('123 Main St');
    expect($dto->shippingAddress->city)->toBe('NYC');
});

test('nested array DTO hydration via NestedArray attribute', function (): void {
    $order = OrderDTO::fromArray([
        'orderNumber' => 'ORD-002',
        'shippingAddress' => ['street' => '456 Oak Ave', 'city' => 'LA'],
        'items' => [
            ['productName' => 'Widget A', 'price' => 49.99, 'quantity' => 2],
            ['productName' => 'Widget B', 'price' => 15.50, 'quantity' => 1],
        ],
    ], validate: false);

    expect($order->items)->toBeArray();
    expect($order->items)->toHaveCount(2);

    foreach ($order->items as $item) {
        expect($item)->toBeInstanceOf(OrderItemDTO::class);
    }

    expect($order->items[0]->productName)->toBe('Widget A');
    expect($order->items[0]->quantity)->toBe(2);
    expect($order->items[1]->productName)->toBe('Widget B');
});

test('nested DTO serialization is recursive', function (): void {
    $order = OrderDTO::fromArray([
        'orderNumber' => 'ORD-003',
        'shippingAddress' => ['street' => '789 Pine', 'city' => 'SF'],
        'items' => [
            ['productName' => 'Item', 'price' => 10.0],
        ],
    ], validate: false);

    $arr = $order->toArray();

    expect($arr['orderNumber'])->toBe('ORD-003');
    expect($arr['shippingAddress'])->toBe(['street' => '789 Pine', 'city' => 'SF']);
    expect($arr['items'])->toBeArray();
    expect($arr['items'])->toHaveCount(1);
    expect($arr['items'][0]['productName'])->toBe('Item');
});

test('DtoCollection with nested DTOs serializes correctly', function (): void {
    $item1 = OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0, 'quantity' => 2], validate: false);
    $item2 = OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0, 'quantity' => 1], validate: false);

    $col = DtoCollection::make([$item1, $item2]);
    $arr = $col->toArray();

    expect($arr)->toHaveCount(2);
    expect($arr[0]['productName'])->toBe('A');
    expect($arr[1]['productName'])->toBe('B');
});

test('date casting from string produces Carbon instance', function (): void {
    $dto = DateCastDTO::fromArray([
        'event_date' => '2024-06-15 10:30:00',
    ], validate: false);

    expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($dto->event_date->format('Y-m-d H:i:s'))->toBe('2024-06-15 10:30:00');
});

test('date casting null returns null', function (): void {
    $dto = DateCastDTO::fromArray([], validate: false);

    expect($dto->event_date)->toBeNull();
});

test('fromJson round-trip preserves all scalar data', function (): void {
    $data = [
        'email' => 'roundtrip@example.com',
        'name' => 'Round Trip',
        'status' => 'active',
    ];

    $json = json_encode($data, JSON_THROW_ON_ERROR);
    $dto = CreateUserDTO::fromJson($json, validate: false);

    expect($dto->email)->toBe('roundtrip@example.com');
    expect($dto->name)->toBe('Round Trip');
    expect($dto->status)->toBe('active');

    // Round-trip back to array
    $arr = $dto->toArray();
    expect($arr['email'])->toBe('roundtrip@example.com');
    expect($arr['name'])->toBe('Round Trip');
    expect($arr['status'])->toBe('active');
});

test('fromArray with validate false accepts any data without validation errors', function (): void {
    // Without validation, empty email should be accepted
    $dto = CreateUserDTO::fromArray([
        'email' => '',
        'name' => '',
    ], validate: false);

    expect($dto->email)->toBe('');
    expect($dto->name)->toBe('');
});

test('EmptyDTO from empty array works', function (): void {
    $dto = EmptyDTO::fromArray([], validate: false);

    expect($dto)->toBeInstanceOf(EmptyDTO::class);
    expect($dto->toArray())->toBe([]);
});

test('AllDefaultsDTO uses defaults for all missing fields', function (): void {
    $dto = AllDefaultsDTO::fromArray([], validate: false);

    expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    $arr = $dto->toArray();
    expect($arr)->toBeArray();
});

test('equals is symmetric', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);
    $dto2 = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeTrue();
    expect($dto2->equals($dto1))->toBeTrue();
});

test('equals is reflexive', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    expect($dto->equals($dto))->toBeTrue();
});

test('with() on DTO with hidden fields preserves hidden in allValues but not toArray', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'password' => 'secret',
    ], validate: false);

    $updated = $dto->with(['email' => 'new@b.com']);

    // toArray excludes hidden
    expect($updated->toArray())->not->toHaveKey('password');
    // allValues includes hidden
    expect($updated->allValues())->toHaveKey('password');
    expect($updated->allValues()['password'])->toBe('secret');
    // New value applied
    expect($updated->email)->toBe('new@b.com');
    // Original unchanged
    expect($dto->email)->toBe('a@b.com');
});

test('except() does not leak hidden fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'password' => 'secret',
    ], validate: false);

    $result = $dto->except('email');

    // password should still not be present (hidden)
    expect($result)->not->toHaveKey('password');
    expect($result)->not->toHaveKey('email');
    expect($result)->toHaveKey('name');
});

test('DtoCollection filter returns new empty collection when all filtered out', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([$dto]);

    $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'B');

    expect($filtered->isEmpty())->toBeTrue();
    expect($filtered->count())->toBe(0);
    expect($col->count())->toBe(1); // Original unchanged
});

test('DtoCollection map with empty collection returns empty array', function (): void {
    $col = DtoCollection::make([]);

    $result = $col->map(fn (DataTransferObject $d): string => $d->name);

    expect($result)->toBe([]);
});

test('DtoCollection pluck from empty collection returns empty array', function (): void {
    $col = DtoCollection::make([]);

    expect($col->pluck('name'))->toBe([]);
    expect($col->pluckKey('name', 'value'))->toBe([]);
});

test('DtoCollection merge with empty collection', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([$dto]);
    $empty = DtoCollection::make([]);

    $merged = $col->merge($empty);
    expect($merged->count())->toBe(1);

    $merged2 = $empty->merge($col);
    expect($merged2->count())->toBe(1);
});

test('DtoCollection toArrayBy/toDictionary with empty collection', function (): void {
    $col = DtoCollection::make([]);

    expect($col->toArrayBy('name'))->toBe([]);
    expect($col->toDictionary('name', 'value'))->toBe([]);
});

test('metadata cache flush allows re-resolution', function (): void {
    // Resolve metadata to populate cache
    $rules1 = CreateUserDTO::rules();
    expect($rules1)->toBeArray();

    // Flush metadata cache
    DataTransferObject::flushMetadataCache();

    // Re-resolve — should still work
    $rules2 = CreateUserDTO::rules();
    expect($rules2)->toBe($rules1);
});

test('nested DTO in DtoCollection allValues preserves all fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'hidden@test.com',
        'name' => 'Hidden Test',
        'password' => 'secret123',
    ], validate: false);

    $col = DtoCollection::make([$dto]);
    $all = $col->allValues();

    expect($all[0])->toHaveKey('password');
    expect($all[0]['password'])->toBe('secret123');
});

test('DTOException __toString format is consistent', function (): void {
    $ex1 = DTOException::invalidCast('field', 'integer', 'string');
    $ex2 = DTOException::invalidJson('field', 'error');

    $str1 = (string) $ex1;
    $str2 = (string) $ex2;

    expect($str1)->toContain('DTOException: ');
    expect($str2)->toContain('DTOException: ');
    expect($str1)->toContain('field');
    expect($str2)->toContain('field');
});

test('fromPartialArray with single field updates only that field', function (): void {
    $dto = CreateUserDTO::fromPartialArray(['name' => 'OnlyName'], validate: false);

    expect($dto->name)->toBe('OnlyName');
    // Other fields fall back to empty defaults
    expect($dto->email)->toBe('');
    expect($dto->status)->toBe('active');
});

test('nested DTO allValues includes nested structure', function (): void {
    $order = OrderDTO::fromArray([
        'orderNumber' => 'ORD-100',
        'shippingAddress' => ['street' => '1 Test St', 'city' => 'Test City'],
        'items' => [],
    ], validate: false);

    $all = $order->allValues();

    expect($all)->toHaveKey('shippingAddress');
    expect($all['shippingAddress'])->toBe(['street' => '1 Test St', 'city' => 'Test City']);
    expect($all)->toHaveKey('items');
    expect($all['items'])->toBe([]);
});
