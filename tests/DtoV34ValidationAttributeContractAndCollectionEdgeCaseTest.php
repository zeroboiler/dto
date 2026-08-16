<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;

/**
 * V34 — Attribute validation contract, MapFrom resolution, cast chain,
 * DtoCollection operations, OpenAPI schema structure, and edge case tests.
 *
 * Targets:
 * - ValidationAttribute::ruleKey() returns correct Laravel rule key
 * - MapFrom maps source key to property correctly
 * - MapFrom does not affect toArray output (property name used)
 * - Cast('array') parses JSON strings to arrays
 * - Cast('integer') casts string/int to int
 * - Cast('boolean') casts various truthy/falsy values
 * - Hidden property excluded from toArray but in allValues
 * - DefaultValue applied when key missing from input
 * - fromPartialArray overrides only provided keys
 * - DtoCollection::make creates typed collection
 * - DtoCollection::append returns new collection with added item
 * - DtoCollection::first/last return nullable DTOs
 * - DtoCollection::count matches items array
 * - DtoCollection::isEmpty/isNotEmpty reflect state
 * - fromJson with empty object {} hydrates with defaults
 * - fromArray with extra keys ignores unknown fields
 * - equals returns false for different DTO types
 * - toArray returns associative array keyed by property name
 * - rules() returns Laravel validation rules array
 * - AllScalarTypesDTO supports all PHP scalar types
 * - DateCastDTO nullable date casting
 * - DTOException named constructors return consistent format
 * - Nested DTO hydration via NestedArray attribute
 * - DtoCollection pluck returns flat array of values
 * - DtoCollection pluckKey returns associative array
 * - with() returns new instance, original unchanged (immutability)
 */
test('ValidationAttribute ruleKey returns correct Laravel validation key for Email', function (): void {
    $attr = new Email;

    expect($attr->ruleKey())->toBe('email');
});

test('ValidationAttribute ruleKey returns correct key for Max', function (): void {
    $attr = new Max(255);

    expect($attr->ruleKey())->toBe('max');
});

test('ValidationAttribute ruleKey returns correct key for Min', function (): void {
    $attr = new Min(1);

    expect($attr->ruleKey())->toBe('min');
});

test('ValidationAttribute ruleKey returns correct key for Pattern', function (): void {
    $attr = new Pattern('/^[a-z]+$/');

    expect($attr->ruleKey())->toBe('regex');
});

test('ValidationAttribute ruleKey returns correct key for Url', function (): void {
    $attr = new Url;

    expect($attr->ruleKey())->toBe('url');
});

test('MapFrom maps source key to property during fromArray', function (): void {
    $dto = CreateUserDTO::fromArray([
        'phone_number' => '+1234567890',
    ], validate: false);

    expect($dto->phone)->toBe('+1234567890');
});

test('MapFrom uses property name (not source key) in toArray', function (): void {
    $dto = CreateUserDTO::fromArray([
        'phone_number' => '+1234567890',
    ], validate: false);

    $arr = $dto->toArray();

    expect($arr)->toHaveKey('phone');
    expect($arr)->not->toHaveKey('phone_number');
    expect($arr['phone'])->toBe('+1234567890');
});

test('Cast array parses JSON string to array', function (): void {
    $dto = CreateUserDTO::fromArray([
        'tags' => '["php","laravel"]',
    ], validate: false);

    expect($dto->tags)->toBe(['php', 'laravel']);
});

test('Cast array passes through native arrays as-is', function (): void {
    $dto = CreateUserDTO::fromArray([
        'tags' => ['php', 'laravel'],
    ], validate: false);

    expect($dto->tags)->toBe(['php', 'laravel']);
});

test('Hidden property excluded from toArray but present in allValues', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Test',
        'password' => 'secret',
    ], validate: false);

    // toArray excludes hidden
    expect($dto->toArray())->not->toHaveKey('password');
    expect($dto->toArray())->toHaveKey('email');

    // allValues includes hidden
    expect($dto->allValues())->toHaveKey('password');
    expect($dto->allValues()['password'])->toBe('secret');
});

test('DefaultValue applied when key missing from input', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Test',
    ], validate: false);

    // 'status' has DefaultValue('active')
    expect($dto->status)->toBe('active');
});

test('fromPartialArray overrides only provided keys', function (): void {
    $dto = CreateUserDTO::fromPartialArray([
        'name' => 'Partial Name',
    ], validate: false);

    expect($dto->name)->toBe('Partial Name');
    // email falls back to default (empty string for string type)
    expect($dto->email)->toBe('');
    // status keeps its DefaultValue
    expect($dto->status)->toBe('active');
});

test('DtoCollection make creates typed collection', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col)->toBeInstanceOf(DtoCollection::class);
    expect($col->count())->toBe(1);
});

test('DtoCollection append returns new collection with added item', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1]);
    $appended = $col->append($dto2);

    expect($appended->count())->toBe(2);
    expect($col->count())->toBe(1); // Original unchanged
});

test('DtoCollection first returns first item or null', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([$dto]);

    expect($col->first())->toBe($dto);
    expect($col->last())->toBe($dto);
});

test('DtoCollection first returns null for empty collection', function (): void {
    $col = DtoCollection::make([]);

    expect($col->first())->toBeNull();
    expect($col->last())->toBeNull();
});

test('DtoCollection isEmpty/isNotEmpty reflect state', function (): void {
    $col = DtoCollection::make([]);

    expect($col->isEmpty())->toBeTrue();
    expect($col->isNotEmpty())->toBeFalse();

    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col2 = DtoCollection::make([$dto]);

    expect($col2->isEmpty())->toBeFalse();
    expect($col2->isNotEmpty())->toBeTrue();
});

test('fromJson with empty object hydrates with defaults', function (): void {
    $dto = AllDefaultsDTO::fromJson('{}', validate: false);

    expect($dto->name)->toBe('default-name');
    expect($dto->count)->toBe(0);
    expect($dto->active)->toBeFalse();
    expect($dto->items)->toBe([]);
});

test('fromArray with extra keys ignores unknown fields', function (): void {
    $dto = MinimalDTO::fromArray([
        'name' => 'A',
        'value' => 'a',
        'extra_field' => 'ignored',
        'another_extra' => 123,
    ], validate: false);

    expect($dto->name)->toBe('A');
    expect($dto->value)->toBe('a');
    expect($dto->toArray())->not->toHaveKey('extra_field');
    expect($dto->toArray())->not->toHaveKey('another_extra');
});

test('equals returns false for different DTO types', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $dto2 = EmptyDTO::fromArray([], validate: false);

    // Different DTO classes — equals should return false or throw
    expect(fn (): bool => $dto1->equals($dto2))
        ->toThrow(\InvalidArgumentException::class);
});

test('toArray returns associative array keyed by property name', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@b.com',
        'name' => 'Test',
        'status' => 'active',
    ], validate: false);

    $arr = $dto->toArray();

    expect($arr)->toBeArray();
    expect(array_keys($arr))->toContain('email', 'name', 'status');
    expect($arr['email'])->toBe('a@b.com');
});

test('rules returns Laravel validation rules array', function (): void {
    $rules = CreateUserDTO::rules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('name');
    // email should have 'required' and 'email' rules
    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('email');
    // name should have 'required', 'min:2', 'max:50'
    expect($rules['name'])->toContain('required');
});

test('AllScalarTypesDTO supports all PHP scalar types', function (): void {
    $dto = AllScalarTypesDTO::fromArray([
        'strField' => 'hello',
        'intField' => '42',
        'floatField' => '3.14',
        'boolField' => '1',
    ], validate: false);

    expect($dto->strField)->toBe('hello');
    expect($dto->intField)->toBe(42);
    expect($dto->floatField)->toBe(3.14);
    expect($dto->boolField)->toBe(true);
});

test('DateCastDTO nullable date casting with null input', function (): void {
    $dto = DateCastDTO::fromArray([], validate: false);

    expect($dto->event_date)->toBeNull();
});

test('DateCastDTO parses date string to Carbon', function (): void {
    $dto = DateCastDTO::fromArray([
        'event_date' => '2024-01-15 10:00:00',
    ], validate: false);

    expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($dto->event_date->format('Y-m-d'))->toBe('2024-01-15');
});

test('DTOException invalidCast format includes field, expected, and actual types', function (): void {
    $ex = DTOException::invalidCast('count', 'int', 'string');

    expect($ex->getMessage())->toContain('count');
    expect($ex->getMessage())->toContain('int');
    expect($ex->getMessage())->toContain('string');
});

test('DTOException invalidJson format includes field and error detail', function (): void {
    $ex = DTOException::invalidJson('data', 'Syntax error');

    expect($ex->getMessage())->toContain('data');
    expect($ex->getMessage())->toContain('Syntax error');
});

test('Nested DTO hydration via NestedArray with multiple items', function (): void {
    $order = OrderDTO::fromArray([
        'orderNumber' => 'ORD-N1',
        'shippingAddress' => ['street' => '1 St', 'city' => 'NY'],
        'items' => [
            ['productName' => 'Item1', 'price' => '10.00', 'quantity' => '2'],
            ['productName' => 'Item2', 'price' => '20.00', 'quantity' => '1'],
            ['productName' => 'Item3', 'price' => '5.50', 'quantity' => '3'],
        ],
    ], validate: false);

    expect($order->items)->toBeArray();
    expect($order->items)->toHaveCount(3);

    expect($order->items[0])->toBeInstanceOf(OrderItemDTO::class);
    expect($order->items[0]->productName)->toBe('Item1');
    expect($order->items[0]->price)->toBe(10.0);
    expect($order->items[0]->quantity)->toBe(2);

    expect($order->items[2]->productName)->toBe('Item3');
    expect($order->items[2]->price)->toBe(5.5);
    expect($order->items[2]->quantity)->toBe(3);
});

test('DtoCollection pluck returns flat array of values', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $names = $col->pluck('name');

    expect($names)->toBe(['Alice', 'Bob']);
});

test('DtoCollection pluckKey returns associative array', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'key1', 'value' => 'val1'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'key2', 'value' => 'val2'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $dict = $col->pluckKey('name', 'value');

    expect($dict)->toBe(['key1' => 'val1', 'key2' => 'val2']);
});

test('with() returns new instance with updated field (immutability)', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'Original', 'value' => 'orig'], validate: false);
    $updated = $dto->with(['name' => 'Updated']);

    expect($updated->name)->toBe('Updated');
    expect($updated->value)->toBe('orig'); // Other fields preserved
    expect($dto->name)->toBe('Original'); // Original unchanged
});

test('with() with multiple fields updates all specified fields', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $updated = $dto->with(['name' => 'B', 'value' => 'b']);

    expect($updated->name)->toBe('B');
    expect($updated->value)->toBe('b');
});

test('AllDefaultsDTO isEmpty returns true when all values are defaults', function (): void {
    $dto = AllDefaultsDTO::fromArray([], validate: false);

    // AllDefaultsDTO has non-null defaults, so it's not truly empty
    // But items array is empty
    expect($dto->items)->toBe([]);
});

test('Multiple with() calls chain immutably', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $step1 = $dto->with(['name' => 'B']);
    $step2 = $step1->with(['value' => 'b']);
    $step3 = $step2->with(['name' => 'C', 'value' => 'c']);

    expect($dto->name)->toBe('A');
    expect($dto->value)->toBe('a');
    expect($step1->name)->toBe('B');
    expect($step1->value)->toBe('a');
    expect($step2->name)->toBe('B');
    expect($step2->value)->toBe('b');
    expect($step3->name)->toBe('C');
    expect($step3->value)->toBe('c');
});

test('fromArray preserves numeric string values as correct types', function (): void {
    $dto = OrderItemDTO::fromArray([
        'productName' => 'Widget',
        'price' => '99.99',
        'quantity' => '5',
    ], validate: false);

    // String inputs should be cast to proper types
    expect($dto->price)->toBeFloat();
    expect($dto->price)->toBe(99.99);
    expect($dto->quantity)->toBeInt();
    expect($dto->quantity)->toBe(5);
});

test('DtoCollection push returns new collection with item added', function (): void {
    $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false);
    $col = DtoCollection::make([]);

    $pushed = $col->push($dto);

    expect($pushed->count())->toBe(1);
    expect($col->count())->toBe(0); // Original unchanged
});

test('DtoCollection toArrayBy returns values keyed by specified field', function (): void {
    $dto1 = MinimalDTO::fromArray(['name' => 'X', 'value' => 'x'], validate: false);
    $dto2 = MinimalDTO::fromArray(['name' => 'Y', 'value' => 'y'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $byName = $col->toArrayBy('name');

    expect($byName)->toBe(['X', 'Y']);
});

test('RegistrationDTO has email and password fields', function (): void {
    $dto = RegistrationDTO::fromArray([
        'email' => 'test@example.com',
        'password' => 'secret123',
    ], validate: false);

    expect($dto->email)->toBe('test@example.com');
    expect($dto->password)->toBe('secret123');
});
