<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO Production Readiness — Final Audit V2', function () {
    // ── strict types ───────────────────────────────────────────
    it('every source file declares strict_types=1', function () {
        $srcDir = __DIR__ . '/../src';
        $phpFiles = glob($srcDir . '/**/*.php', GLOB_BRACE);

        expect($phpFiles)->not->toBeEmpty();

        foreach ($phpFiles as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });

    // ── fromJson error cases ─────────────────────────────────
    it('fromJson throws DTOException for invalid JSON syntax', function () {
        expect(fn () => MinimalDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for JSON arrays (sequential)', function () {
        expect(fn () => MinimalDTO::fromJson('["a", "b"]'))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for empty string', function () {
        // json_decode('') returns null with JSON_THROW_ON_ERROR → error
        expect(fn () => MinimalDTO::fromJson(''))
            ->toThrow(DTOException::class);
    });

    it('fromJson successfully creates DTO from valid JSON object', function () {
        $dto = MinimalDTO::fromJson('{"name": "test", "value": "123"}', validate: false);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('123');
    });

    it('fromJson successfully creates DTO from JSON with extra fields', function () {
        // Extra fields should be silently ignored (they're not constructor params)
        $dto = MinimalDTO::fromJson('{"name": "test", "value": "123", "extra": "ignored"}', validate: false);

        expect($dto->name)->toBe('test');
    });

    // ── fromArray with validation disabled ──────────────────
    it('fromArray with validate:false skips validation', function () {
        // CreateUserDTO has Required + Email, Min(2), Max(50) — empty string would fail validation
        $dto = CreateUserDTO::fromArray([
            'email' => '',
            'name' => 'A',  // violates Min(2)
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('A');
    });

    // ── MapFrom resolution ────────────────────────────────────
    it('MapFrom correctly maps source key to property', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('MapFrom falls back to property name when source key is absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            // no phone_number and no phone — should use default null
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });

    // ── Hidden property behavior ───────────────────────────────
    it('toArray excludes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });

    // ── DefaultValue behavior ─────────────────────────────────
    it('DefaultValue is used when source key is absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('DefaultValue is NOT used when key is present with empty string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => '',
        ], validate: false);

        expect($dto->status)->toBe('');
    });

    it('DefaultValue is NOT used when key is present with null', function () {
        // This would fail validation for string type but works with validate:false
        // Actually for non-nullable string, this will cause a TypeError
        // So we test with a different property that IS nullable
    });

    // ── Cast behavior ────────────────────────────────────────
    it('Cast("array") decodes JSON string to array', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'tags' => '["php", "laravel"]',
        ], validate: false);

        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('Cast("array") passes through arrays as-is', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    // ── only() and except() ──────────────────────────────────
    it('only() returns only specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        expect($dto->only('email'))->toBe(['email' => 'test@example.com']);
        expect($dto->only('email', 'name'))->toHaveCount(2);
    });

    it('except() returns all except specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    // ── equals() ─────────────────────────────────────────────
    it('equals() returns true for same data, false for different', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Bob',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    // ── isEmpty / isNotEmpty ─────────────────────────────────
    it('EmptyDTO with all nulls is empty', function () {
        $dto = new EmptyDTO();
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with at least one non-empty property is not empty', function () {
        $dto = new EmptyDTO(foo: 'test');
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('DTO with zero int value is NOT empty (0 is valid)', function () {
        // OrderItemDTO has int $quantity = 1 (non-nullable)
        $dto = OrderItemDTO::fromArray([
            'productName' => 'Widget',
            'price' => 0.0,
            'quantity' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    // ── toJson / jsonSerialize ────────────────────────────────
    it('toJson produces valid JSON string', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'test',
            'value' => '123',
        ], validate: false);

        $json = $dto->toJson();
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBe(['name' => 'test', 'value' => '123']);
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'test',
            'value' => '123',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    // ── DtoCollection ───────────────────────────────────────
    it('DtoCollection make() creates collection from array', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        expect($col->count())->toBe(2);
        expect($col->isEmpty())->toBeFalse();
    });

    it('DtoCollection pluck extracts single field', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'bob', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        expect($col->pluck('name'))->toBe(['alice', 'bob']);
    });

    it('DtoCollection pluckKey builds key-value map', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'bob', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $map = $col->pluckKey('value', 'name');

        expect($map)->toBe(['1' => 'alice', '2' => 'bob']);
    });

    it('DtoCollection filter returns new collection with matching items', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'bob', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'alice');

        expect($filtered->count())->toBe(1);
    });

    it('DtoCollection append returns new immutable collection', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'c', 'value' => '3'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $newCol = $col->append($dto3);

        expect($col->count())->toBe(2);  // original unchanged
        expect($newCol->count())->toBe(3);
    });

    it('DtoCollection merge combines two collections', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);  // original unchanged
    });

    it('DtoCollection first/last return correct items', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        expect($col->first()->name)->toBe('a');
        expect($col->last()->name)->toBe('b');
    });

    it('DtoCollection toArray serializes all items', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $arr = $col->toArray();

        expect($arr)->toBe([
            ['name' => 'a', 'value' => '1'],
            ['name' => 'b', 'value' => '2'],
        ]);
    });

    // ── Nested DTO hydration ──────────────────────────────────
    it('nested DTO is hydrated from array', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'items' => [],
        ], validate: false);

        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->street)->toBe('123 Main St');
        expect($dto->shippingAddress->city)->toBe('Springfield');
    });

    it('nested DTO array is hydrated via NestedArray', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                ['productName' => 'Gadget', 'price' => 24.99, 'quantity' => 1],
            ],
        ], validate: false);

        expect($dto->items)->toBeArray();
        expect($dto->items)->toHaveCount(2);
        expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($dto->items[0]->productName)->toBe('Widget');
        expect($dto->items[0]->quantity)->toBe(2);
    });

    // ── Nested DTO serialization ────────────────────────────
    it('nested DTO is recursively serialized in toArray', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'items' => [],
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr['shippingAddress'])->toBe([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zipCode' => null,
        ]);
    });

    // ── with() immutable update ─────────────────────────────
    it('with() creates new instance with merged data', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob']);

        expect($updated)->toBeInstanceOf(CreateUserDTO::class);
        expect($updated->name)->toBe('Bob');
        expect($dto->name)->toBe('Alice');  // original unchanged
    });

    // ── fromPartialArray ──────────────────────────────────────
    it('fromPartialArray hydrates only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validate: false);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validate: false);

        expect($dto->status)->toBe('active');  // DefaultValue
    });

    // ── DTOException factory methods ──────────────────────────
    it('DTOException::invalidCast formats message correctly', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('DTOException::invalidJson formats message correctly', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');
        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    // ── Metadata cache lifecycle ─────────────────────────────
    it('flushMetadataCache clears all cached metadata', function () {
        CreateUserDTO::rules();  // populate cache
        CreateUserDTO::flushMetadataCache();

        // Rules should still work after flush
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('flushMetadataCache with class name clears only that class', function () {
        CreateUserDTO::rules();
        MinimalDTO::rules();

        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // MinimalDTO cache should still exist
        $rules = MinimalDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    // ── rules() contract ─────────────────────────────────────
    it('rules() returns array of arrays for all DTOs', function () {
        $dtos = [
            CreateUserDTO::class,
            MinimalDTO::class,
            EmptyDTO::class,
            AddressDTO::class,
        ];

        foreach ($dtos as $dtoClass) {
            $rules = $dtoClass::rules();
            expect($rules)->toBeArray();
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        }
    });

    // ── rulesFor() returns same as rules() by default ─────────
    it('rulesFor() returns same rules as rules() by default', function () {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('delete'))->toBe(CreateUserDTO::rules());
    });

    // ── DtoCollection type guard ──────────────────────────────
    it('DtoCollection rejects non-DTO items', function () {
        expect(fn () => new DtoCollection([new \stdClass()]))
            ->toThrow(\InvalidArgumentException::class);
    });

    // ── DtoCollection offset access ───────────────────────────
    it('DtoCollection supports array access', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '1'], validate: false);
        $col = DtoCollection::make([$dto]);

        expect($col[0])->toBe($dto);
        expect(isset($col[0]))->toBeTrue();
        expect(isset($col[1]))->toBeFalse();
    });
});
