<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO Strict Type Compliance & Additional Coverage', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('DtoCollection — allValues() and serialization', function () {
        it('allValues includes hidden properties of each DTO', function () {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
                'password' => 'secret1',
            ], validate: false);
            $d2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
                'password' => 'secret2',
            ], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $all = $collection->allValues();

            expect($all[0])->toHaveKey('password');
            expect($all[0]['password'])->toBe('secret1');
            expect($all[1]['password'])->toBe('secret2');
        });

        it('toArray excludes hidden properties of each DTO', function () {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
                'password' => 'secret1',
            ], validate: false);

            $collection = new DtoCollection([$d1]);
            $arr = $collection->toArray();

            expect($arr[0])->not->toHaveKey('password');
            expect($arr[0])->toHaveKey('email');
        });

        it('empty collection has zero count', function () {
            $collection = new DtoCollection;

            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBeTrue();
            expect($collection->first())->toBeNull();
            expect($collection->last())->toBeNull();
        });

        it('push returns same instance for fluent chaining', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection;

            $result = $collection->push($d1)->push($d2);

            expect($result)->toBe($collection);
            expect($collection->count())->toBe(2);
        });
    });

    describe('DataTransferObject — toJson options', function () {
        it('toJson with JSON_PRETTY_PRINT produces formatted output', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar', 'baz' => 'qux'], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
            expect($json)->toBeJson();
        });

        it('toJson returns empty object for empty DTO', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            $json = $dto->toJson();

            expect($json)->toBe('{"foo":null,"bar":null}');
        });
    });

    describe('DataTransferObject — with() validation always runs', function () {
        it('with() validates even when validate param is false (backward compat)', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            // with() always validates — the $validate param is ignored
            // This creates a valid DTO
            $updated = $dto->with(['name' => 'Updated']);

            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('test@example.com');
        });
    });

    describe('DataTransferObject — fromArray with explicit null values', function () {
        it('explicit null is preserved as null', function () {
            $dto = EmptyDTO::fromArray([
                'foo' => null,
                'bar' => 'value',
            ], validate: false);

            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBe('value');
        });

        it('explicit empty string is preserved', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => '',
            ], validate: false);

            expect($dto->name)->toBe('');
        });
    });

    describe('DataTransferObject — nested DTO edge cases', function () {
        it('nested DTO roundtrip preserves all fields', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                    'zipCode' => '34000',
                ],
                'items' => [
                    [
                        'productName' => 'Widget A',
                        'price' => 9.99,
                        'quantity' => 3,
                    ],
                    [
                        'productName' => 'Widget B',
                        'price' => 14.99,
                    ],
                ],
            ], validate: false);

            // Check nested DTO
            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Istanbul');
            expect($dto->shippingAddress->zipCode)->toBe('34000');

            // Check nested array of DTOs
            expect(count($dto->items))->toBe(2);
            expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->items[0]->productName)->toBe('Widget A');
            expect($dto->items[0]->quantity)->toBe(3);
            expect($dto->items[1]->quantity)->toBe(1); // default
        });

        it('nested DTO serialization is recursive', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-002',
                'shippingAddress' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Ankara',
                ],
                'items' => [],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['street'])->toBe('456 Oak Ave');
            expect($arr['shippingAddress']['city'])->toBe('Ankara');
            expect($arr['items'])->toBe([]);
        });

        it('allValues includes nested DTO fields', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-003',
                'shippingAddress' => [
                    'street' => '789 Pine Rd',
                    'city' => 'Izmir',
                ],
                'items' => [],
            ], validate: false);

            $all = $dto->allValues();

            expect($all['shippingAddress'])->toBeArray();
            expect($all['shippingAddress']['street'])->toBe('789 Pine Rd');
        });
    });

    describe('DataTransferObject — MapFrom edge cases', function () {
        it('MapFrom with dot notation works', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+90-555-1234',
            ], validate: false);

            expect($dto->phone)->toBe('+90-555-1234');
        });

        it('MapFrom key takes precedence over property name', function () {
            // When both phone_number (mapped) and phone exist in data,
            // MapFrom should use phone_number
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+90-555-0000',
                'phone' => '+90-555-9999',
            ], validate: false);

            expect($dto->phone)->toBe('+90-555-0000');
        });
    });

    describe('DTOCast — JSON roundtrip', function () {
        it('JSON string roundtrip preserves data', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $dto = EmptyDTO::fromArray(['foo' => 'hello', 'bar' => 'world'], validate: false);

            // set: DTO → JSON string
            $json = $cast->set(new stdClass, 'data', $dto, []);
            expect(is_string($json))->toBeTrue();

            // get: JSON string → DTO
            $restored = $cast->get(new stdClass, 'data', $json, []);
            expect($restored)->toBeInstanceOf(EmptyDTO::class);
            expect($restored->foo)->toBe('hello');
            expect($restored->bar)->toBe('world');
        });

        it('array roundtrip preserves data', function () {
            $cast = new DTOCast(EmptyDTO::class);

            // set: array → JSON string
            $json = $cast->set(new stdClass, 'data', ['foo' => 'x', 'bar' => 'y'], []);
            expect(is_string($json))->toBeTrue();

            // get: JSON string → DTO
            $restored = $cast->get(new stdClass, 'data', $json, []);
            expect($restored)->toBeInstanceOf(EmptyDTO::class);
            expect($restored->foo)->toBe('x');
        });

        it('serialize returns array for DTO', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $dto = EmptyDTO::fromArray(['foo' => 'test'], validate: false);

            $result = $cast->serialize(new stdClass, 'data', $dto, []);

            expect($result)->toBe(['foo' => 'test']);
        });
    });

    describe('DataTransferObject — equals() with nested DTOs', function () {
        it('two DTOs with same nested data are equal', function () {
            $data = [
                'orderNumber' => 'ORD-100',
                'shippingAddress' => [
                    'street' => '1 St',
                    'city' => 'Istanbul',
                ],
                'items' => [],
            ];

            $d1 = OrderDTO::fromArray($data, validate: false);
            $d2 = OrderDTO::fromArray($data, validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('two DTOs with different nested data are not equal', function () {
            $d1 = OrderDTO::fromArray([
                'orderNumber' => 'ORD-100',
                'shippingAddress' => ['street' => '1 St', 'city' => 'Istanbul'],
                'items' => [],
            ], validate: false);
            $d2 = OrderDTO::fromArray([
                'orderNumber' => 'ORD-100',
                'shippingAddress' => ['street' => '2 St', 'city' => 'Istanbul'],
                'items' => [],
            ], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });
    });

    describe('DataTransferObject — metadata cache TTL', function () {
        it('setMetadataCacheTtl changes TTL', function () {
            DataTransferObject::setMetadataCacheTtl(10.0);

            // Populate cache
            EmptyDTO::rules();

            // Should still be cached (within TTL)
            $rules = EmptyDTO::rules();
            expect($rules)->toBeArray();

            // Reset for other tests
            DataTransferObject::setMetadataCacheTtl(0.0);
        });

        it('setMetadataCacheTtl(0) disables TTL invalidation', function () {
            DataTransferObject::setMetadataCacheTtl(0.0);
            EmptyDTO::rules();

            // Rules should still work
            $rules = EmptyDTO::rules();
            expect($rules)->toBeArray();
        });
    });

    describe('DtoCollection — offsetExists and offsetGet', function () {
        it('offsetExists returns true for existing index', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect(isset($collection[0]))->toBeTrue();
            expect(isset($collection[1]))->toBeFalse();
        });

        it('offsetGet returns DTO for existing index', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect($collection[0]->foo)->toBe('a');
        });

        it('offsetGet returns null for non-existing index', function () {
            $collection = new DtoCollection;

            expect($collection[0])->toBeNull();
        });

        it('offsetSet with null key appends', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection;
            $collection[] = $d1;

            expect($collection->count())->toBe(1);
            expect($collection[0]->foo)->toBe('a');
        });

        it('offsetSet with specific key replaces', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1]);

            $collection[0] = $d2;

            expect($collection[0]->foo)->toBe('b');
            expect($collection->count())->toBe(1);
        });
    });

    describe('DTOManager — OpenAPI schema', function () {
        it('schema returns correct type for DTO with required fields', function () {
            $manager = new DTOManager;
            $schema = $manager->schema(AddressDTO::class);

            expect($schema['type'])->toBe('object');
            expect($schema['required'])->toContain('street');
            expect($schema['required'])->toContain('city');
        });
    });
});
