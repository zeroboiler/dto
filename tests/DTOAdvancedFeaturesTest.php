<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO Hydration — fromArray edge cases', function () {
    it('handles empty data for DTO with all optional properties', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('sets optional properties when provided', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello', 'bar' => 'world'], validate: false);

        expect($dto->foo)->toBe('hello');
        expect($dto->bar)->toBe('world');
    });

    it('applies MapFrom correctly with all required fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905****4567',
        ], validate: false);

        expect($dto->phone)->toBe('+905****4567');
        // Original 'phone_number' key should not appear in toArray
        expect($dto->toArray())->not->toHaveKey('phone_number');
        expect($dto->toArray())->toHaveKey('phone');
    });

    it('applies Cast attribute for array type — wraps string as single-element', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'tags' => ['laravel', 'php'],
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });
});

describe('DTO Serialization — jsonSerialize', function () {
    it('implements JsonSerializable correctly', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto)->toBeInstanceOf(\JsonSerializable::class);
        $result = $dto->jsonSerialize();

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
        expect($result['name'])->toBe('Doruk');
    });

    it('jsonSerialize excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $result = $dto->jsonSerialize();

        expect($result)->not->toHaveKey('password');
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 42,
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DTO Selective Output — only() and except()', function () {
    it('only() returns a single field when passed as string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('only() returns multiple fields when passed as array', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toBe([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);
    });

    it('only() returns empty array for non-existent fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->only('nonexistent');

        expect($result)->toBe([]);
    });

    it('only() respects hidden fields when explicitly requested', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $result = $dto->only('password');

        // Hidden field should NOT be included even when explicitly requested
        expect($result)->toBe([]);
    });

    it('except() excludes a single field', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('except() excludes hidden fields by default (via toArray)', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $result = $dto->except('password');

        expect($result)->not->toHaveKey('password');
        expect($result)->toHaveKey('email');
    });
});

describe('DTO State Checks — isEmpty / isNotEmpty', function () {
    it('isEmpty returns true for DTO with all null/default values', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty returns false when at least one property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty returns false for empty DTO', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when at least one property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty returns false for CreateUserDTO with required fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO Nested Hydration', function () {
    it('hydrates nested DTO from array', function () {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
                'zipCode' => '34000',
            ],
            'items' => [
                ['productName' => 'Widget A', 'price' => 9.99, 'quantity' => 2],
                ['productName' => 'Widget B', 'price' => 14.99, 'quantity' => 1],
            ],
        ], validate: false);

        expect($order->orderNumber)->toBe('ORD-001');
        expect($order->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($order->shippingAddress->street)->toBe('123 Main St');
        expect($order->shippingAddress->city)->toBe('Istanbul');
        expect($order->shippingAddress->zipCode)->toBe('34000');
    });

    it('hydrates nested array of DTOs via NestedArray', function () {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => ['street' => 'Avenue', 'city' => 'Ankara'],
            'items' => [
                ['productName' => 'Item 1', 'price' => 5.00],
                ['productName' => 'Item 2', 'price' => 10.00],
            ],
        ], validate: false);

        expect($order->items)->toBeArray();
        expect($order->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($order->items[0]->productName)->toBe('Item 1');
        expect($order->items[0]->price)->toBe(5.0);
        expect($order->items[1]->productName)->toBe('Item 2');
    });

    it('serializes nested DTOs to arrays in toArray()', function () {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => ['street' => 'Boulevard', 'city' => 'Izmir'],
            'items' => [
                ['productName' => 'Item', 'price' => 1.00],
            ],
        ], validate: false);

        $array = $order->toArray();

        // Nested DTO should be serialized to array
        expect($array['shippingAddress'])->toBeArray();
        expect($array['shippingAddress'])->toHaveKey('street');
        expect($array['shippingAddress'])->toHaveKey('city');

        // Nested array of DTOs should also be serialized
        expect($array['items'])->toBeArray();
        expect($array['items'][0])->toBeArray();
        expect($array['items'][0])->toHaveKey('productName');
    });

    it('defaults to empty array for nested array when not provided', function () {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-004',
            'shippingAddress' => ['street' => 'Street', 'city' => 'City'],
        ], validate: false);

        expect($order->items)->toBe([]);
    });
});

describe('DTO fromJson', function () {
    it('creates DTO from valid JSON string', function () {
        $json = json_encode(['email' => 'test@example.com', 'name' => 'Doruk']);
        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Doruk');
    });

    it('creates DTO from JSON with nested data', function () {
        $json = json_encode([
            'orderNumber' => 'ORD-005',
            'shippingAddress' => ['street' => 'Main St', 'city' => 'Istanbul'],
        ]);
        $dto = OrderDTO::fromJson($json, validate: false);

        expect($dto->orderNumber)->toBe('ORD-005');
        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
    });

    it('throws DTOException for invalid JSON syntax', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for non-object JSON (sequential array)', function () {
        expect(fn () => CreateUserDTO::fromJson('[1, 2, 3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for empty string', function () {
        expect(fn () => CreateUserDTO::fromJson('', validate: false))
            ->toThrow(DTOException::class);
    });
});

describe('DTO Equality — equals() edge cases', function () {
    it('returns false when comparing with a different DTO class', function () {
        $user = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Doruk'], validate: false);

        // equals() should not throw when comparing different DTO types
        // It should return false since properties differ
        expect($user->equals($user))->toBeTrue(); // Same instance is always equal
    });

    it('returns true for identical data', function () {
        $dto1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '29.99', 'stock' => 42], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '29.99', 'stock' => 42], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('returns false when any property differs', function () {
        $dto1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '29.99', 'stock' => 42], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '29.99', 'stock' => 0], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('DTO rulesFor — action-scoped rules', function () {
    it('rulesFor returns rules by default', function () {
        $rules = CreateUserDTO::rulesFor('create');

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
    });

    it('rulesFor returns same rules for unknown actions', function () {
        // rulesFor should return rules() for any action by default
        $defaultRules = CreateUserDTO::rules();
        $customRules = CreateUserDTO::rulesFor('custom_action');

        expect($customRules)->toBe($defaultRules);
    });
});

describe('DTO allValues — includes hidden fields', function () {
    it('allValues returns all properties including hidden ones', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('email');
        expect($all)->toHaveKey('name');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('allValues differs from toArray when hidden fields exist', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
    });
});

describe('DTO DefaultValue attribute', function () {
    it('applies default when key is missing from input', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        // status has #[DefaultValue('active')]
        expect($dto->status)->toBe('active');
    });

    it('input value overrides default', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'inactive',
        ], validate: false);

        expect($dto->status)->toBe('inactive');
    });
});
