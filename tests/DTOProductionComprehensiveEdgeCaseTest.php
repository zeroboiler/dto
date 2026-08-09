<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO production comprehensive edge cases', function (): void {
    describe('CreateUserDTO full lifecycle', function (): void {
        it('creates from array with all fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John Doe',
                'status' => 'active',
                'tags' => ['admin', 'user'],
                'phone_number' => '+1234567890',
                'password' => 'secret123',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('John Doe');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe(['admin', 'user']);
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->password)->toBe('secret123');
        });

        it('uses defaults for missing fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('toArray excludes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'password' => 'secret',
            ], validate: false);

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('password');
            expect($array)->toHaveKeys(['email', 'name', 'status', 'tags', 'phone']);
        });

        it('allValues includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'password' => 'secret',
            ], validate: false);

            $array = $dto->allValues();

            expect($array)->toHaveKey('password');
            expect($array['password'])->toBe('secret');
        });

        it('respects MapFrom for key aliasing', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'phone_number' => '+9998887777',
            ], validate: false);

            expect($dto->phone)->toBe('+9998887777');
        });

        it('toJson produces valid JSON', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            expect(json_decode($json, true))->toHaveKey('email');
            expect(json_decode($json, true))->not->toHaveKey('password');
        });

        it('fromJson round-trips correctly', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Jane Doe',
                'status' => 'inactive',
                'tags' => ['editor'],
                'phone_number' => '+111',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe('test@example.com');
            expect($restored->name)->toBe('Jane Doe');
            expect($restored->status)->toBe('inactive');
            expect($restored->tags)->toBe(['editor']);
            expect($restored->phone)->toBe('+111');
        });
    });

    describe('equals() and isEmpty() behavior', function (): void {
        it('equals returns true for identical data', function (): void {
            $data = ['email' => 'test@example.com', 'name' => 'John'];

            $dto1 = CreateUserDTO::fromArray($data, validate: false);
            $dto2 = CreateUserDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different data', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'John'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'John'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns false when required fields have values', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('EmptyDTO with all nulls is considered empty', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('EmptyDTO with one non-null field is not empty', function (): void {
            $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('only() and except() selective output', function (): void {
        it('only returns specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'phone_number' => '+123',
            ], validate: false);

            $filtered = $dto->only(['email', 'name']);

            expect($filtered)->toHaveKeys(['email', 'name']);
            expect($filtered)->not->toHaveKey('phone');
            expect($filtered)->not->toHaveKey('password');
        });

        it('only with single string key', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $filtered = $dto->only('email');

            expect($filtered)->toHaveKey('email');
            expect($filtered)->not->toHaveKey('name');
        });

        it('only ignores non-existent keys silently', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $filtered = $dto->only(['email', 'nonexistent']);

            expect($filtered)->toHaveKey('email');
            expect($filtered)->not->toHaveKey('nonexistent');
        });

        it('except excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'phone_number' => '+123',
            ], validate: false);

            $filtered = $dto->except(['email']);

            expect($filtered)->not->toHaveKey('email');
            expect($filtered)->toHaveKey('name');
            expect($filtered)->toHaveKey('phone');
        });

        it('except with single string key', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $filtered = $dto->except('email');

            expect($filtered)->not->toHaveKey('email');
            expect($filtered)->toHaveKey('name');
        });
    });

    describe('with() immutable update', function (): void {
        it('returns a new instance with overrides', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $modified = $dto->with(['name' => 'Jane']);

            expect($modified)->not->toBe($dto);
            expect($modified->name)->toBe('Jane');
            expect($dto->name)->toBe('John'); // original unchanged
        });

        it('with() merges all existing fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'status' => 'active',
                'tags' => ['user'],
            ], validate: false);

            $modified = $dto->with(['status' => 'inactive']);

            expect($modified->email)->toBe('test@example.com');
            expect($modified->name)->toBe('John');
            expect($modified->status)->toBe('inactive');
            expect($modified->tags)->toBe(['user']);
        });
    });

    describe('fromPartialArray PATCH semantics', function (): void {
        it('hydrates only present fields, defaults for rest', function (): void {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('John');
            expect($dto->status)->toBe('active'); // DefaultValue
            expect($dto->tags)->toBe([]); // constructor default
            expect($dto->phone)->toBeNull();
        });

        it('partial update overrides existing values', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'status' => 'active',
            ], validate: false);

            $modified = $dto->with(['status' => 'banned']);

            expect($modified->status)->toBe('banned');
            expect($modified->email)->toBe('test@example.com');
        });
    });

    describe('rules() and rulesFor() API', function (): void {
        it('rules returns array of rule sets', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rulesFor returns same as rules by default', function (): void {
            $rules = CreateUserDTO::rules();
            $rulesForCreate = CreateUserDTO::rulesFor('create');

            expect($rules)->toBe($rulesForCreate);
        });
    });

    describe('DtoCollection advanced operations', function (): void {
        it('append returns a new collection without modifying original', function (): void {
            $item1 = OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false);
            $item2 = OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0], validate: false);

            $original = new DtoCollection([$item1]);
            $appended = $original->append($item2);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
            expect($appended->last()->productName)->toBe('B');
        });

        it('filter returns a new collection', function (): void {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'C', 'price' => 30.0], validate: false),
            ];

            $collection = new DtoCollection($items);
            $filtered = $collection->filter(
                fn (DataTransferObject $dto): bool => $dto->price > 15.0
            );

            expect($collection->count())->toBe(3);
            expect($filtered->count())->toBe(2);
        });

        it('merge combines two collections immutably', function (): void {
            $col1 = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false),
            ]);
            $col2 = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0], validate: false),
            ]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
            expect($merged->count())->toBe(2);
        });

        it('push mutates and returns same instance', function (): void {
            $item1 = OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false);
            $item2 = OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0], validate: false);

            $collection = new DtoCollection([$item1]);
            $result = $collection->push($item2);

            expect($result)->toBe($collection); // same instance
            expect($collection->count())->toBe(2);
        });

        it('pluck extracts a single field from all DTOs', function (): void {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0, 'quantity' => 2], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0, 'quantity' => 5], validate: false),
            ];

            $collection = new DtoCollection($items);
            $names = $collection->pluck('productName');

            expect($names)->toBe(['A', 'B']);
        });

        it('map transforms DTOs to plain values', function (): void {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0, 'quantity' => 2], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0, 'quantity' => 5], validate: false),
            ];

            $collection = new DtoCollection($items);
            $totals = $collection->map(
                fn (DataTransferObject $dto, int $index): float => $dto->price * $dto->quantity
            );

            expect($totals)->toBe([20.0, 100.0]);
        });

        it('first and last work correctly', function (): void {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 20.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'C', 'price' => 30.0], validate: false),
            ];

            $collection = new DtoCollection($items);

            expect($collection->first()?->productName)->toBe('A');
            expect($collection->last()?->productName)->toBe('C');
        });

        it('first returns null for empty collection', function (): void {
            $collection = new DtoCollection;

            expect($collection->first())->toBeNull();
            expect($collection->last())->toBeNull();
            expect($collection->isEmpty())->toBeTrue();
        });

        it('isIterable and countable', function (): void {
            $item = OrderItemDTO::fromArray(['productName' => 'A', 'price' => 10.0], validate: false);
            $collection = new DtoCollection([$item]);

            expect($collection->count())->toBe(1);
            expect(iterator_to_array($collection))->toHaveCount(1);
        });
    });

    describe('nested DTO hydration', function (): void {
        it('hydrates nested DTO from array', function (): void {
            $dto = AddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '62701',
            ], validate: false);

            expect($dto)->toBeInstanceOf(AddressDTO::class);
            expect($dto->street)->toBe('123 Main St');
            expect($dto->city)->toBe('Springfield');
            expect($dto->zipCode)->toBe('62701');
        });

        it('serializes nested DTO to array', function (): void {
            $dto = AddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
            ], validate: false);

            $array = $dto->toArray();

            expect($array)->toBe([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => null,
            ]);
        });
    });

    describe('metadata cache management', function (): void {
        it('flushMetadataCache clears all cached metadata', function (): void {
            // Warm the cache
            CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);

            // Flush
            DataTransferObject::flushMetadataCache();

            // Should still work after flush (rebuilds from scratch)
            $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);

            expect($dto->email)->toBe('test@example.com');
        });

        it('flushMetadataCache for specific class', function (): void {
            // Warm both caches
            CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);
            EmptyDTO::fromArray([], validate: false);

            // Flush only one
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Other DTOs should still work
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->foo)->toBeNull();
        });
    });
});
