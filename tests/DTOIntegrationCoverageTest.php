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

describe('DataTransferObject — fromJson edge cases', function (): void {
    it('rejects sequential JSON array (not object)', function (): void {
        expect(fn () => CreateUserDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('rejects invalid JSON syntax', function (): void {
        expect(fn () => CreateUserDTO::fromJson('{invalid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException with descriptive message for invalid JSON', function (): void {
        $exception = null;

        try {
            CreateUserDTO::fromJson('not json at all', validate: false);
        } catch (DTOException $e) {
            $exception = $e;
        }

        expect($exception)->not->toBeNull()
            ->and($exception->getMessage())->toContain('Cannot decode JSON');
    });

    it('accepts empty JSON object', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->foo)->toBeNull()
            ->and($dto->bar)->toBeNull();
    });
});

describe('DataTransferObject — only/except with string argument', function (): void {
    it('only() accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('email');
    });

    it('only() accepts multiple string keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveCount(2)
            ->and($result)->not->toHaveKey('status');
    });

    it('only() ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('email');
    });

    it('except() accepts single string key', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'stock' => 42,
        ], validate: false);

        $result = $dto->except('stock');

        expect($result)->not->toHaveKey('stock')
            ->and($result)->toHaveKey('name')
            ->and($result)->toHaveKey('price');
    });

    it('except() accepts multiple string keys', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'stock' => 42,
        ], validate: false);

        $result = $dto->except('price', 'stock');

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('name');
    });

    it('except() ignores non-existent keys silently', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
        ], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveCount(2);
    });
});

describe('DataTransferObject — rulesFor action scoping', function (): void {
    it('rulesFor returns same as rules by default', function (): void {
        $rules = CreateUserDTO::rules();
        $rulesForCreate = CreateUserDTO::rulesFor('create');

        expect($rules)->toBe($rulesForCreate);
    });

    it('rulesFor accepts any action string', function (): void {
        $result = CreateUserDTO::rulesFor('update');

        expect($result)->toBeArray();
    });
});

describe('DataTransferObject — isEmpty / isNotEmpty', function (): void {
    it('isEmpty returns true for DTO with all null/empty defaults', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue()
            ->and($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when at least one property has value', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue()
            ->and($dto->isEmpty())->toBeFalse();
    });
});

describe('DataTransferObject — jsonSerialize consistency', function (): void {
    it('jsonSerialize returns same as toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('jsonSerialize excludes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $serialized = $dto->jsonSerialize();

        expect($serialized)->toBeArray()
            ->and($serialized)->not->toHaveKey('password');
    });
});

describe('DtoCollection — append (immutable)', function (): void {
    it('append returns a new collection with added DTO', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1)
            ->and($appended->count())->toBe(2)
            ->and($appended->last())->toBe($dto2);
    });

    it('append does not mutate the original collection', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $original = new DtoCollection([$dto1]);
        $original->append($dto2);

        expect($original->count())->toBe(1);
    });

    it('merge returns a new collection combining both', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3)
            ->and($merged->items())->toBe([$dto1, $dto2, $dto3]);
    });

    it('merge does not mutate either original collection', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $col1->merge($col2);

        expect($col1->count())->toBe(1)
            ->and($col2->count())->toBe(1);
    });
});

describe('DtoCollection — pluckKey', function (): void {
    it('pluckKey builds key-value map from two properties', function (): void {
        $dto1 = new ProductDTO(name: 'Widget A', price: '1.00');
        $dto2 = new ProductDTO(name: 'Widget B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('name', 'price');

        expect($map)->toBe([
            'Widget A' => '1.00',
            'Widget B' => '2.00',
        ]);
    });

    it('pluckKey with only keyField uses toArray as value', function (): void {
        $dto1 = new ProductDTO(name: 'Widget A', price: '1.00');
        $dto2 = new ProductDTO(name: 'Widget B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('name');

        expect($map)->toHaveKey('Widget A')
            ->and($map['Widget A'])->toBeArray();
    });
});

describe('DTOException — factory methods', function (): void {
    it('invalidCast formats message correctly', function (): void {
        $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect($exception->getMessage())->toContain('age')
            ->and($exception->getMessage())->toContain('integer')
            ->and($exception->getMessage())->toContain('not-a-number');
    });

    it('invalidJson formats message correctly', function (): void {
        $exception = DTOException::invalidJson('payload', 'Syntax error');

        expect($exception->getMessage())->toContain('payload')
            ->and($exception->getMessage())->toContain('Syntax error');
    });

    it('invalidCast uses get_debug_type for object values', function (): void {
        $exception = DTOException::invalidCast('data', 'array', new \stdClass);

        expect($exception->getMessage())->toContain('stdClass');
    });
});

describe('DataTransferObject — flushMetadataCache', function (): void {
    it('flushes metadata for a specific class', function (): void {
        // Populate cache by resolving metadata
        $rules = CreateUserDTO::rules();
        expect($rules)->not->toBeEmpty();

        // Flush specific class
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Rules should still work (re-resolves metadata)
        $rulesAfter = CreateUserDTO::rules();
        expect($rulesAfter)->toBe($rules);
    });

    it('flushes all metadata when class is null', function (): void {
        DataTransferObject::flushMetadataCache();

        // Should not throw, and should still resolve correctly
        $rules = ProductDTO::rules();
        expect($rules)->toBeArray();
    });
});
