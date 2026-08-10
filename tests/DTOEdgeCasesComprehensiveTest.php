<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;

/**
 * Tests for DTO edge cases: fromJson error handling, equals, isEmpty, partial updates,
 * nested DTOs, collections, and serialization roundtrips.
 */
describe('DTO edge cases — fromJson error handling', function () {
    it('fromJson throws DTOException on invalid JSON syntax', function () {
        expect(fn () => CreateUserDTO::fromJson('not valid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException on sequential JSON array', function () {
        expect(fn () => CreateUserDTO::fromJson('["test@example.com", "Alice"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson succeeds on valid JSON object', function () {
        $dto = CreateUserDTO::fromJson('{"email":"test@example.com","name":"Alice"}', validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });
});

describe('DTO edge cases — equality and state', function () {
    it('equals returns true for same data', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different data', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Other'], validate: false);
        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns true when all properties are empty/default', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when at least one property has value', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('only returns specified fields only', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'phone' => '123'], validate: false);
        $only = $dto->only('email');
        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
        expect($only)->not->toHaveKey('phone');
    });

    it('except returns all except specified fields', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'phone' => '123'], validate: false);
        $except = $dto->except('name');
        expect($except)->toHaveKey('email');
        expect($except)->not->toHaveKey('name');
        expect($except)->toHaveKey('phone');
    });

    it('toArray excludes Hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes Hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });
});

describe('DTO edge cases — MapFrom and Cast', function () {
    it('MapFrom maps source key to property name', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('Cast applies integer casting', function () {
        $dto = RoundtripDTO::fromArray(['name' => 'Test', 'age' => '25'], validate: false);
        expect($dto->age)->toBeInt();
        expect($dto->age)->toBe(25);
    });
});

describe('DTO edge cases — nested DTOs', function () {
    it('nested DTO hydrates from array', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
            ],
        ], validate: false);

        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->city)->toBe('Istanbul');
    });

    it('nested array of DTOs hydrates correctly', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => [
                'street' => '456 Oak Ave',
                'city' => 'Ankara',
            ],
            'items' => [
                ['name' => 'Widget', 'quantity' => 5, 'price' => 10.0],
                ['name' => 'Gadget', 'quantity' => 2, 'price' => 25.0],
            ],
        ], validate: false);

        expect($dto->items)->toBeArray();
        expect($dto->items)->toHaveCount(2);
        expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($dto->items[0]->productName)->toBe('Widget');
        expect($dto->items[1]->productName)->toBe('Gadget');
    });

    it('nested DTO serializes recursively in toArray', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => [
                'street' => '789 Elm',
                'city' => 'Izmir',
            ],
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr['shippingAddress'])->toBeArray();
        expect($arr['shippingAddress']['city'])->toBe('Izmir');
    });
});

describe('DTO edge cases — partial updates', function () {
    it('fromPartialArray hydrates only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated'], validate: false);
        expect($dto->name)->toBe('Updated');
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['email' => 'a@b.com'], validate: false);
        expect($dto->email)->toBe('a@b.com');
        expect($dto->status)->toBe('active'); // default value
    });

    it('with() creates new immutable instance', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = $dto1->with(['name' => 'Updated'], validate: false);

        expect($dto1->name)->toBe('Test');
        expect($dto2->name)->toBe('Updated');
        expect($dto2->email)->toBe('a@b.com');
    });
});

describe('DTO edge cases — DtoCollection', function () {
    it('collection pluck extracts single field', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@b.com', 'x@y.com']);
    });

    it('collection pluckKey builds key-value map', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'x@y.com' => 'Bob']);
    });

    it('collection append returns new instance', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = $col1->append($dto2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('collection push mutates in-place', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob'], validate: false);

        $col = new DtoCollection([$dto1]);
        $col->push($dto2);

        expect($col->count())->toBe(2);
    });

    it('collection rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('collection filter returns new collection', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $filtered = $col->filter(fn ($d) => str_starts_with($d->email, 'alice'));

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('collection merge combines two collections', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });
});
