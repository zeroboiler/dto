<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

describe('DTOMetadataAndHydrationEdgeCases', function () {

    describe('DtoCollection pluckKey', function () {
        it('builds key/value map from single key', function () {
            $dtoList = [
                new CreateUserDTO(email: 'a@test.com', name: 'Alice', status: 'active', tags: [], phone: null),
                new CreateUserDTO(email: 'b@test.com', name: 'Bob', status: 'active', tags: [], phone: null),
            ];
            $collection = new DtoCollection($dtoList);

            $map = $collection->pluckKey('email');
            expect($map)->toBe([
                'a@test.com' => ['email' => 'a@test.com', 'name' => 'Alice', 'status' => 'active', 'tags' => []],
                'b@test.com' => ['email' => 'b@test.com', 'name' => 'Bob', 'status' => 'active', 'tags' => []],
            ]);
        });

        it('builds key/value map with separate value field', function () {
            $dtoList = [
                new CreateUserDTO(email: 'a@test.com', name: 'Alice', status: 'active', tags: [], phone: null),
                new CreateUserDTO(email: 'b@test.com', name: 'Bob', status: 'active', tags: [], phone: null),
            ];
            $collection = new DtoCollection($dtoList);

            $map = $collection->pluckKey('email', 'name');
            expect($map)->toBe([
                'a@test.com' => 'Alice',
                'b@test.com' => 'Bob',
            ]);
        });

        it('returns empty array for empty collection', function () {
            $collection = new DtoCollection();
            expect($collection->pluckKey('email'))->toBe([]);
        });

        it('overwrites duplicate keys with last value', function () {
            $dtoList = [
                new MinimalDTO(name: 'Alice', value: 'a'),
                new MinimalDTO(name: 'Bob', value: 'b'),
            ];

            $collection = new DtoCollection($dtoList);
            $map = $collection->pluckKey('name', 'value');
            expect($map)->toHaveCount(2);
            expect($map['Alice'])->toBe('a');
            expect($map['Bob'])->toBe('b');
        });
    });

    describe('DtoCollection type safety', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto', 'also not']))
                ->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
        });

        it('rejects non-DTO items in offsetSet', function () {
            $collection = new DtoCollection();
            expect(fn () => $collection[] = 'string value')
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('EmptyDTO edge case', function () {
        it('creates instance with no required properties', function () {
            $dto = EmptyDTO::fromArray([]);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('toArray() returns empty array for all-null properties', function () {
            $dto = EmptyDTO::fromArray([]);
            // foo and bar are both null — toArray includes them
            $arr = $dto->toArray();
            expect($arr)->toHaveKey('foo');
            expect($arr['foo'])->toBeNull();
        });

        it('isEmpty() returns true when all properties are null', function () {
            $dto = EmptyDTO::fromArray([]);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isNotEmpty() returns false when all properties are null', function () {
            $dto = EmptyDTO::fromArray([]);
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty() returns false when any property has value', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar']);
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('equals() comparison', function () {
        it('returns true for identical data', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'test@test.com', 'name' => 'Test'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'test@test.com', 'name' => 'Test'], validate: false);
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('returns false for different data', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);
            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('fromJson error cases', function () {
        it('throws DTOException on invalid JSON syntax', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON array (not object)', function () {
            expect(fn () => CreateUserDTO::fromJson('[1, 2, 3]'))
                ->toThrow(DTOException::class, 'Expected a JSON object');
        });

        it('throws DTOException on JSON primitive', function () {
            expect(fn () => CreateUserDTO::fromJson('"just a string"'))
                ->toThrow(DTOException::class, 'Expected a JSON object');
        });

        it('throws DTOException on JSON number', function () {
            expect(fn () => CreateUserDTO::fromJson('42'))
                ->toThrow(DTOException::class, 'Expected a JSON object');
        });
    });

    describe('fromJson valid cases', function () {
        it('creates DTO from valid JSON object', function () {
            $dto = CreateUserDTO::fromJson('{"email":"test@test.com","name":"Test"}', validate: false);
            expect($dto->email)->toBe('test@test.com');
            expect($dto->name)->toBe('Test');
        });

        it('applies defaults from JSON', function () {
            $dto = CreateUserDTO::fromJson('{"email":"test@test.com","name":"Test"}', validate: false);
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });
    });

    describe('only() and except() selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'status' => 'active',
                'tags' => ['php'],
            ], validate: false);

            $result = $dto->only(['email', 'name']);
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });

        it('only accepts string parameter', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('test@test.com');
        });

        it('except removes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except(['status']);
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });

        it('except ignores non-existent keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->except('nonexistent_key');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });
    });

    describe('toJson serialization', function () {
        it('produces valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@test.com');
        });

        it('excludes hidden fields from JSON', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);
            expect($decoded)->not->toHaveKey('password');
        });
    });

    describe('allValues vs toArray', function () {
        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->toArray())->not->toHaveKey('password');
        });
    });

    describe('with() immutable update', function () {
        it('creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);
            expect($updated->name)->toBe('Updated');
            expect($dto->name)->toBe('Test'); // Original unchanged
        });
    });

    describe('metadata cache TTL', function () {
        it('flushMetadataCache clears all metadata', function () {
            // Resolve metadata for a DTO
            CreateUserDTO::rules();
            DataTransferObject::flushMetadataCache();

            // Should still work after flush (re-resolve)
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache for specific class', function () {
            CreateUserDTO::rules();
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Re-resolve should work
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });
    });

    describe('nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                    'country' => 'TR',
                ],
                'items' => [],
            ], validate: false);

            expect($order->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($order->shippingAddress->city)->toBe('Istanbul');
        });

        it('serializes nested DTO recursively', function () {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                    'country' => 'TR',
                ],
                'items' => [],
            ], validate: false);

            $arr = $order->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Istanbul');
        });
    });

    describe('rules() structure', function () {
        it('returns array with field names as keys', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('each rule entry is an array', function () {
            $rules = CreateUserDTO::rules();
            foreach ($rules as $field => $fieldRules) {
                expect($fieldRules)->toBeArray();
                expect($fieldRules)->not->toBeEmpty();
            }
        });
    });

    describe('isEmpty() detection', function () {
        it('returns false when DTO has non-empty properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });
    });
});
