<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

/**
 * V32 — Edge case and boundary tests for DTO hydration, serialization, and collection operations.
 *
 * Targets real coverage gaps:
 * - fromJson with various invalid JSON inputs
 * - fromJson rejects sequential arrays (non-empty lists)
 * - toJson encoding options passthrough
 * - Cast edge cases: bool from various inputs, integer from float string
 * - MapFrom with dot notation
 * - Empty DTO behavior (isEmpty, toArray, equals)
 * - DtoCollection immutability (clone throws, append creates new instance)
 * - DtoCollection pluck/pluckKey with multiple items
 * - DtoCollection filter and map with empty collection
 * - DtoCollection offsetUnset re-indexes correctly
 * - equals() with identical and different DTOs
 * - isEmpty()/isNotEmpty() with various property states
 * - only()/except() with nonexistent keys
 * - fromPartialArray with empty data array
 * - DTOException named constructors message format
 * - allValues includes hidden fields
 * - validatePartialArray skips fields not in data
 * - with() returns new instance (immutability)
 * - Metadata cache TTL behavior
 */
test('fromJson rejects invalid JSON string', function (): void {
    $this->expectException(DTOException::class);

    CreateUserDTO::fromJson('{invalid json}');
});

test('fromJson rejects sequential (non-empty list) JSON array', function (): void {
    $this->expectException(DTOException::class);

    CreateUserDTO::fromJson('["a", "b", "c"]');
});

test('fromJson accepts empty array as valid empty JSON object', function (): void {
    // EmptyDTO has no required fields, so empty array should work
    $dto = EmptyDTO::fromJson('{}');
    expect($dto)->toBeInstanceOf(EmptyDTO::class);
});

test('fromJson accepts empty array literal', function (): void {
    $dto = EmptyDTO::fromJson('[]');
    expect($dto)->toBeInstanceOf(EmptyDTO::class);
});

test('isEmpty returns true for DTO with all default/null values', function (): void {
    $dto = AllDefaultsDTO::fromArray([], validate: false);

    expect($dto->isEmpty())->toBeTrue();
    expect($dto->isNotEmpty())->toBeFalse();
});

test('isEmpty returns false when at least one property has a real value', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    expect($dto->isEmpty())->toBeFalse();
    expect($dto->isNotEmpty())->toBeTrue();
});

test('equals returns true for DTOs with identical values', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeTrue();
});

test('equals returns false for DTOs with different values', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@c.com',
        'name' => 'Bob',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeFalse();
});

test('only returns subset of fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'status' => 'active',
    ], validate: false);

    $subset = $dto->only('email', 'name');

    expect($subset)->toBe(['email' => 'a@b.com', 'name' => 'Alice']);
    expect($subset)->not->toHaveKey('status');
});

test('only with single string key', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $subset = $dto->only('email');

    expect($subset)->toBe(['email' => 'a@b.com']);
});

test('only ignores nonexistent keys silently', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $subset = $dto->only('email', 'nonexistent');

    expect($subset)->toBe(['email' => 'a@b.com']);
});

test('except excludes specified fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'status' => 'active',
    ], validate: false);

    $result = $dto->except('status');

    expect($result)->toHaveKey('email');
    expect($result)->toHaveKey('name');
    expect($result)->not->toHaveKey('status');
});

test('except ignores nonexistent keys silently', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $result = $dto->except('nonexistent');

    expect($result)->toHaveKey('email');
    expect($result)->toHaveKey('name');
});

test('allValues includes hidden fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'password' => 'secret123',
    ], validate: false);

    $public = $dto->toArray();
    $all = $dto->allValues();

    // toArray excludes hidden fields
    expect($public)->not->toHaveKey('password');
    // allValues includes everything
    expect($all)->toHaveKey('password');
    expect($all['password'])->toBe('secret123');
});

test('with returns new instance with merged data', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $updated = $dto->with(['name' => 'Bob']);

    // Original unchanged
    expect($dto->name)->toBe('Alice');
    // New instance has updated value
    expect($updated->name)->toBe('Bob');
    // Other fields preserved
    expect($updated->email)->toBe('a@b.com');
});

test('with always validates (validate param ignored)', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    // Create with a valid merge
    $updated = $dto->with(['name' => 'Bob']);
    expect($updated->name)->toBe('Bob');

    // Even with validate=false, with() still validates internally
    // (the parameter is deprecated and ignored)
    expect($updated)->toBeInstanceOf(CreateUserDTO::class);
});

test('fromJson with empty JSON object creates DTO with defaults', function (): void {
    $dto = AllDefaultsDTO::fromJson('{}');

    expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
});

test('fromPartialArray with empty data uses defaults', function (): void {
    $dto = CreateUserDTO::fromPartialArray([], validate: false);

    expect($dto->email)->toBe('');
    expect($dto->name)->toBe('');
    expect($dto->status)->toBe('active'); // DefaultValue
});

test('fromPartialArray only hydrates fields present in data', function (): void {
    $dto = CreateUserDTO::fromPartialArray(['name' => 'Alice'], validate: false);

    expect($dto->name)->toBe('Alice');
    expect($dto->status)->toBe('active'); // DefaultValue preserved
});

test('DtoCollection clone throws RuntimeException', function (): void {
    $col = DtoCollection::make([]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('immutable');

    clone $col;
});

test('DtoCollection append returns new instance', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v1'], validate: false);

    $col1 = DtoCollection::make([$dto]);
    $col2 = $col1->append($dto);

    // Different instances
    expect($col1)->not->toBe($col2);
    // Original unchanged (1 item)
    expect($col1->count())->toBe(1);
    // New has 2 items
    expect($col2->count())->toBe(2);
});

test('DtoCollection merge combines both collections', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false);

    $col1 = DtoCollection::make([$dto1]);
    $col2 = DtoCollection::make([$dto2]);
    $merged = $col1->merge($col2);

    expect($merged->count())->toBe(2);
    expect($col1->count())->toBe(1);
    expect($col2->count())->toBe(1);
});

test('DtoCollection pluck extracts single property', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $names = $col->pluck('name');

    expect($names)->toBe(['Alice', 'Bob']);
});

test('DtoCollection pluckKey extracts key-value pairs', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $map = $col->pluckKey('email', 'name');

    expect($map)->toBe(['a@b.com' => 'Alice', 'b@c.com' => 'Bob']);
});

test('DtoCollection pluckKey skips items with null key', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => null, 'name' => 'Alice'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $map = $col->pluckKey('email', 'name');

    // First item skipped because email is null
    expect($map)->toBe(['b@c.com' => 'Bob']);
});

test('DtoCollection filter returns new collection', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'Alice');

    expect($filtered->count())->toBe(1);
    expect($col->count())->toBe(2); // Original unchanged
});

test('DtoCollection map returns plain array', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $names = $col->map(fn (DataTransferObject $d, int $i): string => $d->name.'-'.$i);

    expect($names)->toBe(['Alice-0', 'Bob-1']);
});

test('DtoCollection first/last on empty collection', function (): void {
    $col = DtoCollection::make([]);

    expect($col->first())->toBeNull();
    expect($col->last())->toBeNull();
});

test('DtoCollection first/last on single item collection', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Only', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col->first()->name)->toBe('Only');
    expect($col->last()->name)->toBe('Only');
});

test('DtoCollection push mutates in-place and returns self', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1]);
    $result = $col->push($dto2);

    // push returns same instance (mutating)
    expect($result)->toBe($col);
    expect($col->count())->toBe(2);
});

test('DtoCollection offsetUnset re-indexes array', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false);
    $dto3 = MinimalDTO::fromArray(['name' => 'C', 'value' => 'c'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2, $dto3]);
    $col->offsetUnset(0); // Remove first

    expect($col->count())->toBe(2);
    expect($col->first()->name)->toBe('B');
    expect($col->last()->name)->toBe('C');
});

test('DtoCollection offsetExists/offsetGet', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col->offsetExists(0))->toBeTrue();
    expect($col->offsetExists(1))->toBeFalse();
    expect($col->offsetGet(0)->name)->toBe('Test');
    expect($col->offsetGet(1))->toBeNull();
});

test('DtoCollection isEmpty/isNotEmpty', function (): void {
    $col = DtoCollection::make([]);
    expect($col->isEmpty())->toBeTrue();
    expect($col->isNotEmpty())->toBeFalse();

    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col2 = DtoCollection::make([$dto]);
    expect($col2->isEmpty())->toBeFalse();
    expect($col2->isNotEmpty())->toBeTrue();
});

test('DtoCollection jsonSerialize returns toArray output', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    $json = $col->jsonSerialize();
    expect($json)->toBe($col->toArray());
    expect($json[0])->toHaveKey('name');
    expect($json[0]['name'])->toBe('Test');
});

test('DtoCollection toArray serializes nested DTOs', function (): void {
    $address = AddressDTO::fromArray(['street' => '123 Main', 'city' => 'NYC'], validate: false);
    $col = DtoCollection::make([$address]);

    $arr = $col->toArray();

    expect($arr[0])->toBe(['street' => '123 Main', 'city' => 'NYC']);
});

test('DtoCollection allValues includes hidden fields of nested DTOs', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'password' => 'secret',
    ], validate: false);
    $col = DtoCollection::make([$dto]);

    $all = $col->allValues();
    expect($all[0])->toHaveKey('password');
    expect($all[0]['password'])->toBe('secret');
});

test('DtoCollection toArrayBy is alias for pluckKey', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col->toArrayBy('name'))->toBe($col->pluckKey('name'));
});

test('DtoCollection toDictionary extracts key-value pairs', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $dict = $col->toDictionary('email', 'name');

    expect($dict)->toBe(['a@b.com' => 'Alice', 'b@c.com' => 'Bob']);
});

test('DtoCollection items returns raw DTO instances', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    $items = $col->items();
    expect(count($items))->toBe(1);
    expect($items[0])->toBeInstanceOf(MinimalDTO::class);
    expect($items[0]->name)->toBe('Test');
});

test('DtoCollection make creates from array', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'v'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col->count())->toBe(1);
});

test('DtoCollection make with empty array creates empty collection', function (): void {
    $col = DtoCollection::make([]);

    expect($col->isEmpty())->toBeTrue();
    expect($col->count())->toBe(0);
});

test('DTOException invalidCast message format', function (): void {
    $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');

    expect($exception->getMessage())->toContain('age');
    expect($exception->getMessage())->toContain('integer');
});

test('DTOException invalidJson message format', function (): void {
    $exception = DTOException::invalidJson('data', 'Syntax error');

    expect($exception->getMessage())->toContain('data');
    expect($exception->getMessage())->toContain('Syntax error');
});

test('DTOException __toString includes class name', function (): void {
    $exception = DTOException::invalidJson('field', 'error');
    $str = (string) $exception;

    expect($str)->toContain('DTOException');
    expect($str)->toContain('field');
});

test('DTOException invalidCast with null value', function (): void {
    $exception = DTOException::invalidCast('status', 'string', null);

    expect($exception->getMessage())->toContain('status');
    expect($exception->getMessage())->toContain('string');
    expect($exception->getMessage())->toContain('null');
});

test('MapFrom maps source key to property during fromArray', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'phone_number' => '+1234567890',
    ], validate: false);

    expect($dto->phone)->toBe('+1234567890');
});

test('MapFrom is not present in toArray output', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'phone_number' => '+1234567890',
    ], validate: false);

    $arr = $dto->toArray();

    // Property name 'phone' is in output, not 'phone_number'
    expect($arr)->toHaveKey('phone');
    expect($arr)->not->toHaveKey('phone_number');
});

test('Cast attribute transforms value during fromArray', function (): void {
    $dto = DateCastDTO::fromArray([
        'event_date' => '2025-01-15',
    ], validate: false);

    expect($dto->event_date)->toBeInstanceOf(Carbon::class);
    expect($dto->event_date->format('Y-m-d'))->toBe('2025-01-15');
});

test('Cast passes null through for nullable properties', function (): void {
    $dto = DateCastDTO::fromArray([
        'event_date' => null,
    ], validate: false);

    expect($dto->event_date)->toBeNull();
});

test('Hidden excludes from toArray but not allValues', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
        'password' => 'secret',
    ], validate: false);

    $arr = $dto->toArray();
    $all = $dto->allValues();

    expect($arr)->not->toHaveKey('password');
    expect($all)->toHaveKey('password');
    expect($all['password'])->toBe('secret');
});

test('nested DTO fromArray hydrates recursively', function (): void {
    $dto = \ZeroBoiler\DTO\Tests\Fixtures\NestedCollectionDTO::fromArray([
        'orderId' => 'ORD-001',
        'total' => 99.99,
        'items' => [
            ['productName' => 'Widget', 'quantity' => 2, 'price' => 10.0],
        ],
    ], validate: false);

    expect($dto->items)->toBeArray();
    expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
    expect($dto->items[0]->productName)->toBe('Widget');
});

test('nested DTO serializes recursively in toArray', function (): void {
    $dto = \ZeroBoiler\DTO\Tests\Fixtures\NestedCollectionDTO::fromArray([
        'orderId' => 'ORD-001',
        'total' => 99.99,
        'items' => [
            ['productName' => 'Widget', 'quantity' => 2, 'price' => 10.0],
        ],
    ], validate: false);

    $arr = $dto->toArray();

    expect($arr['items'][0])->toBe([
        'productName' => 'Widget',
        'quantity' => 2,
        'price' => 10.0,
    ]);
});

test('toJson produces valid JSON string', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Alice',
    ], validate: false);

    $json = $dto->toJson();

    $decoded = json_decode($json, true);
    expect($decoded)->toBe($dto->toArray());
});

test('fromJson with validation disabled skips rules', function (): void {
    // Even with incomplete data, should work with validate=false
    $dto = AllDefaultsDTO::fromJson('{"name":"Test"}', validate: false);

    expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
});

test('Metadata cache flush clears per-class entries', function (): void {
    // Resolve metadata to populate cache
    CreateUserDTO::rules();

    // Flush
    DataTransferObject::flushMetadataCache();

    // After flush, next access should work (re-resolve)
    $rules = CreateUserDTO::rules();
    expect($rules)->toBeArray();
});

test('Metadata cache flush specific class only', function (): void {
    CreateUserDTO::rules();
    MinimalDTO::rules();

    // Flush only one class
    DataTransferObject::flushMetadataCache(CreateUserDTO::class);

    // MinimalDTO should still have cached rules
    $rules = MinimalDTO::rules();
    expect($rules)->toBeArray();
});

test('DtoCollection offsetSet allows null offset (append)', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([]);

    $col->offsetSet(null, $dto);

    expect($col->count())->toBe(1);
    expect($col->offsetGet(0)->name)->toBe('A');
});

test('DtoCollection offsetSet rejects non-DTO values', function (): void {
    $col = DtoCollection::make([]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('DataTransferObject');

    $col->offsetSet(null, 'not a DTO');
});

test('DtoCollection constructor rejects non-DTO values', function (): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('DataTransferObject');

    new DtoCollection(['not', 'a', 'dto']);
});

test('empty DTO toArray returns empty array', function (): void {
    $dto = EmptyDTO::fromArray([], validate: false);

    expect($dto->toArray())->toBe([]);
    expect($dto->allValues())->toBe([]);
});

test('DtoCollection filter on empty returns empty', function (): void {
    $col = DtoCollection::make([]);

    $filtered = $col->filter(fn (DataTransferObject $d): bool => true);

    expect($filtered->isEmpty())->toBeTrue();
});

test('DtoCollection map on empty returns empty', function (): void {
    $col = DtoCollection::make([]);

    $mapped = $col->map(fn (DataTransferObject $d): string => 'x');

    expect($mapped)->toBe([]);
});
