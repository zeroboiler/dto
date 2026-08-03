<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('only() — selective field extraction', function (): void {
    it('returns only the specified keys as array', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ]);

        $result = $dto->only('email', 'name');

        expect($result)->toBe([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);
    });

    it('accepts an array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->only(['email']);

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('silently ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('excludes hidden fields (uses toArray base)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ]);

        $result = $dto->only('email', 'password');

        // password is #[Hidden], so only email appears
        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('returns empty array when no keys match', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->only('nonexistent');

        expect($result)->toBe([]);
    });

    it('handles single string argument', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 100,
        ]);

        $result = $dto->only('name');

        expect($result)->toBe(['name' => 'Widget']);
    });
});

describe('except() — field exclusion', function (): void {
    it('returns all fields except the specified ones', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('accepts an array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->except(['email', 'name']);

        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('silently ignores non-existent keys in except', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('excludes hidden fields (uses toArray base)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ]);

        $result = $dto->except('email');

        // password is already hidden by toArray()
        expect($result)->not->toHaveKey('password');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('handles single string argument', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 100,
        ]);

        $result = $dto->except('price');

        expect($result)->not->toHaveKey('price');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('stock');
    });
});

describe('only() + except() integration', function (): void {
    it('only and except are complementary', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        $onlyResult = $dto->only('email', 'name');
        $exceptResult = $dto->except('email', 'name', 'status', 'tags', 'phone');

        // only returns selected keys, except returns the rest
        expect($onlyResult)->toHaveKey('email');
        expect($onlyResult)->toHaveKey('name');
        expect($exceptResult)->not->toHaveKey('email');
        expect($exceptResult)->not->toHaveKey('name');
    });
});

describe('DtoCollection::pluck() — extract single field', function (): void {
    it('plucks a single property from all DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'Alice']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@example.com', 'name' => 'Bob']);
        $collection = new DtoCollection([$dto1, $dto2]);

        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@example.com', 'b@example.com']);
    });

    it('plucks numeric properties', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '10.00', 'stock' => 5]);
        $dto2 = ProductDTO::fromArray(['name' => 'Gadget', 'price' => '25.00', 'stock' => 10]);
        $collection = new DtoCollection([$dto1, $dto2]);

        $stocks = $collection->pluck('stock');

        expect($stocks)->toBe([5, 10]);
    });

    it('returns empty array for empty collection', function (): void {
        $collection = new DtoCollection([]);

        expect($collection->pluck('email'))->toBe([]);
    });
});

describe('DtoCollection::pluckKey() — key/value extraction', function (): void {
    it('creates key/value map using one field as key', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'Alice']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@example.com', 'name' => 'Bob']);
        $collection = new DtoCollection([$dto1, $dto2]);

        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'a@example.com' => 'Alice',
            'b@example.com' => 'Bob',
        ]);
    });

    it('uses full DTO array as value when valueField is null', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'Alice']);
        $collection = new DtoCollection([$dto1]);

        $map = $collection->pluckKey('name');

        expect($map)->toHaveKey('Alice');
        expect($map['Alice'])->toHaveKey('email', 'a@example.com');
    });

    it('returns empty array for empty collection', function (): void {
        $collection = new DtoCollection([]);

        expect($collection->pluckKey('email', 'name'))->toBe([]);
    });
});
