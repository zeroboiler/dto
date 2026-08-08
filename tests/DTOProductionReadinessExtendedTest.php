<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('fromJson — JSON deserialization edge cases', function (): void {
    it('creates DTO from valid JSON object', function (): void {
        $json = json_encode(['email' => 'a@b.com', 'name' => 'Test'], JSON_THROW_ON_ERROR);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Test');
    });

    it('throws DTOException for invalid JSON', function (): void {
        $this->expectException(DTOException::class);
        CreateUserDTO::fromJson('{invalid json}', validate: false);
    });

    it('throws DTOException for JSON array (sequential)', function (): void {
        $json = json_encode(['a@b.com', 'Test'], JSON_THROW_ON_ERROR);

        $this->expectException(DTOException::class);
        CreateUserDTO::fromJson($json, validate: false);
    });

    it('throws DTOException for JSON null', function (): void {
        $this->expectException(DTOException::class);
        CreateUserDTO::fromJson('null', validate: false);
    });

    it('throws DTOException for JSON boolean', function (): void {
        $this->expectException(DTOException::class);
        CreateUserDTO::fromJson('true', validate: false);
    });

    it('throws DTOException for JSON string', function (): void {
        $this->expectException(DTOException::class);
        CreateUserDTO::fromJson('"hello"', validate: false);
    });

    it('round-trips correctly: fromArray → toJson → fromJson', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'roundtrip@test.com',
            'name' => 'Round Trip',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        $restored = CreateUserDTO::fromJson($original->toJson(), validate: false);

        expect($restored->email)->toBe('roundtrip@test.com');
        expect($restored->name)->toBe('Round Trip');
    });

    it('accepts empty JSON object', function (): void {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });
});

describe('DtoCollection immutability semantics', function (): void {
    it('append() returns a new collection without mutating original', function (): void {
        $item1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 1], validate: false);
        $item2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 2], validate: false);

        $original = DtoCollection::make([$item1]);
        $extended = $original->append($item2);

        expect($original->count())->toBe(1);
        expect($extended->count())->toBe(2);
        expect($extended->last()->name)->toBe('B');
    });

    it('merge() combines two collections into a new one', function (): void {
        $a = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 1], validate: false);
        $b = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 2], validate: false);
        $c = ProductDTO::fromArray(['name' => 'C', 'price' => '30', 'stock' => 3], validate: false);

        $col1 = DtoCollection::make([$a]);
        $col2 = DtoCollection::make([$b, $c]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('filter() returns new collection with only matching items', function (): void {
        $items = [
            ProductDTO::fromArray(['name' => 'Active', 'price' => '10', 'stock' => 5], validate: false),
            ProductDTO::fromArray(['name' => 'Zero', 'price' => '0', 'stock' => 0], validate: false),
            ProductDTO::fromArray(['name' => 'High', 'price' => '50', 'stock' => 10], validate: false),
        ];
        $collection = DtoCollection::make($items);

        $inStock = $collection->filter(fn (ProductDTO $dto): bool => $dto->stock > 0);

        expect($inStock->count())->toBe(2);
        expect($collection->count())->toBe(3);
    });

    it('pluck() extracts a single field from all items', function (): void {
        $items = [
            ProductDTO::fromArray(['name' => 'Alpha', 'price' => '10', 'stock' => 1], validate: false),
            ProductDTO::fromArray(['name' => 'Beta', 'price' => '20', 'stock' => 2], validate: false),
        ];
        $collection = DtoCollection::make($items);

        expect($collection->pluck('name'))->toBe(['Alpha', 'Beta']);
    });

    it('first() returns null for empty collection', function (): void {
        $collection = DtoCollection::make([]);

        expect($collection->first())->toBeNull();
    });

    it('last() returns null for empty collection', function (): void {
        $collection = DtoCollection::make([]);

        expect($collection->last())->toBeNull();
    });

    it('items() returns raw DTO instances', function (): void {
        $item = ProductDTO::fromArray(['name' => 'X', 'price' => '10', 'stock' => 1], validate: false);
        $collection = DtoCollection::make([$item]);

        expect($collection->items()[0])->toBe($item);
    });

    it('toArray() serializes all DTOs', function (): void {
        $items = [
            ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 1], validate: false),
            ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 2], validate: false),
        ];
        $collection = DtoCollection::make($items);

        $array = $collection->toArray();

        expect($array)->toHaveCount(2);
        expect($array[0])->toHaveKey('name');
        expect($array[0]['name'])->toBe('A');
    });

    it('map() returns plain array (not collection)', function (): void {
        $items = [
            ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 1], validate: false),
            ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 2], validate: false),
        ];
        $collection = DtoCollection::make($items);

        $result = $collection->map(fn (ProductDTO $dto): string => $dto->name);

        expect($result)->toBe(['A', 'B']);
        expect($result)->not->toBeInstanceOf(DtoCollection::class);
    });

    it('isEmpty() and isNotEmpty() work correctly', function (): void {
        $item = ProductDTO::fromArray(['name' => 'X', 'price' => '10', 'stock' => 1], validate: false);

        $empty = DtoCollection::make([]);
        $nonEmpty = DtoCollection::make([$item]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });
});

describe('only/except field filtering', function (): void {
    it('only() returns only specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
            'tags' => ['php'],
        ], validate: false);

        $filtered = $dto->only(['email', 'name']);

        expect($filtered)->toHaveCount(2);
        expect($filtered)->toHaveKey('email');
        expect($filtered)->toHaveKey('name');
        expect($filtered)->not->toHaveKey('status');
    });

    it('only() with string parameter returns single field', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $filtered = $dto->only('email');

        expect($filtered)->toHaveCount(1);
        expect($filtered['email'])->toBe('test@example.com');
    });

    it('only() silently ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $filtered = $dto->only(['email', 'nonexistent']);

        expect($filtered)->toHaveCount(1);
    });

    it('except() excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $filtered = $dto->except(['status']);

        expect($filtered)->toHaveKey('email');
        expect($filtered)->toHaveKey('name');
        expect($filtered)->not->toHaveKey('status');
    });

    it('except() with string parameter excludes single field', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $filtered = $dto->except('name');

        expect($filtered)->toHaveCount(1);
        expect($filtered)->toHaveKey('email');
    });

    it('except() silently ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $filtered = $dto->except('nonexistent');

        // Should not throw — all real fields remain
        expect($filtered)->toHaveKey('email');
        expect($filtered)->toHaveKey('name');
    });
});

describe('with() immutable override guarantees', function (): void {
    it('returns a new instance with merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'original@test.com',
            'name' => 'Original',
        ], validate: false);

        $modified = $dto->with(['name' => 'Modified']);

        expect($dto->name)->toBe('Original');
        expect($modified->name)->toBe('Modified');
        expect($modified->email)->toBe('original@test.com');
    });

    it('preserves hidden field semantics in result', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $modified = $dto->with(['email' => 'new@example.com']);

        // password should still be hidden in toArray output
        expect($modified->toArray())->not->toHaveKey('password');
        // password should be present in allValues
        expect($modified->allValues())->toHaveKey('password');
    });

    it('fromPartialArray creates with defaults for missing fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'partial@test.com',
        ], validate: false);

        expect($dto->email)->toBe('partial@test.com');
        // name is required but fromPartialArray should handle defaults
        // tags should default to []
        expect($dto->tags)->toBe([]);
    });
});

describe('allValues includes hidden fields', function (): void {
    it('toArray() excludes hidden, allValues() includes hidden', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $visible = $dto->toArray();
        $all = $dto->allValues();

        expect($visible)->not->toHaveKey('password');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });
});

describe('isEmpty / isNotEmpty', function (): void {
    it('reports empty when all nullable properties are null', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('reports not empty when at least one property has a value', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('jsonSerialize (JsonSerializable)', function (): void {
    it('returns an array matching toArray()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'json@test.com',
            'name' => 'Json',
        ], validate: false);

        $serialized = $dto->jsonSerialize();
        $array = $dto->toArray();

        expect($serialized)->toBe($array);
    });

    it('works with json_encode directly', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'direct@test.com',
            'name' => 'Direct',
        ], validate: false);

        $result = json_encode($dto);

        expect($result)->toBeJson();
        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('direct@test.com');
    });
});

describe('metadata cache management', function (): void {
    it('flushMetadataCache clears specific class cache', function (): void {
        // Populate cache by resolving
        CreateUserDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Next call should rebuild (no way to verify directly, but should not throw)
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('flushMetadataCache() with no arg clears all', function (): void {
        CreateUserDTO::rules();
        ProductDTO::rules();

        DataTransferObject::flushMetadataCache();

        // Should rebuild without error
        expect(CreateUserDTO::rules())->toBeArray();
        expect(ProductDTO::rules())->toBeArray();
    });
});

describe('rules() attribute derivation completeness', function (): void {
    it('EmptyDTO returns empty or minimal rules', function (): void {
        $rules = EmptyDTO::rules();
        expect($rules)->toBeArray();
    });

    it('CreateUserDTO derives correct rules for all attributes', function (): void {
        $rules = CreateUserDTO::rules();

        // Email: Required + Email
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');

        // Name: Required + Min(2) + Max(50)
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('ProductDTO derives integer and numeric rules', function (): void {
        $rules = ProductDTO::rules();

        expect($rules['stock'])->toContain('integer');
        expect($rules['stock'])->toContain('min:0');
        expect($rules['price'])->toContain('numeric');
    });
});

describe('DTOException factory methods', function (): void {
    it('invalidCast() includes property name and type', function (): void {
        $exception = DTOException::invalidCast('age', 'int', 'not_a_number');

        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('int');
    });

    it('invalidJson() includes property name and error detail', function (): void {
        $exception = DTOException::invalidJson('metadata', 'Syntax error');

        expect($exception->getMessage())->toContain('metadata');
        expect($exception->getMessage())->toContain('Syntax error');
    });
});
