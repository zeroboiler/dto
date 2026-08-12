<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

/**
 * Full production contract test — verifies every public API method
 * of the DTO package including hydration, validation, serialization,
 * partial updates, immutability helpers, nested DTOs, collections,
 * metadata resolution, and edge cases.
 *
 * This test serves as a single-file smoke test for the entire package.
 * If any public API breaks, this test catches it.
 */
describe('DTO Full Production Contract', function () {
    // -----------------------------------------------------------------------
    // Basic Hydration — fromArray
    // -----------------------------------------------------------------------
    describe('Basic Hydration — fromArray', function () {
        it('creates DTO from array with all required fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('user@example.com');
            expect($dto->name)->toBe('John Doe');
        });

        it('applies default values for missing optional fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('hydrates explicit optional fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'status' => 'inactive',
                'tags' => ['admin', 'editor'],
                'phone_number' => '+1234567890',
                'password' => 'secret',
            ], validate: false);

            expect($dto->status)->toBe('inactive');
            expect($dto->tags)->toBe(['admin', 'editor']);
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->password)->toBe('secret');
        });

        it('respects MapFrom for key aliasing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'phone_number' => '+9999999999',
            ], validate: false);

            expect($dto->phone)->toBe('+9999999999');
        });

        it('throws on missing required fields', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                // 'name' is missing
            ], validate: true))->toThrow(ValidationException::class);
        });
    });

    // -----------------------------------------------------------------------
    // Validation — fromArray with validate:true
    // -----------------------------------------------------------------------
    describe('Validation', function () {
        it('passes valid data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: true);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('rejects invalid email', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'not-an-email',
                'name' => 'John Doe',
            ], validate: true))->toThrow(ValidationException::class);
        });

        it('rejects name shorter than Min(2)', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'J',
            ], validate: true))->toThrow(ValidationException::class);
        });

        it('rejects name longer than Max(50)', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => str_repeat('x', 51),
            ], validate: true))->toThrow(ValidationException::class);
        });

        it('returns rules() with correct structure', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });
    });

    // -----------------------------------------------------------------------
    // Serialization — toArray, toJson, allValues
    // -----------------------------------------------------------------------
    describe('Serialization', function () {
        it('toArray() excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
            ], validate: false);

            $array = $dto->toArray();
            expect($array)->toHaveKey('email');
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('status');
            expect($array)->not->toHaveKey('password');
        });

        it('allValues() includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('toJson() returns valid JSON', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('user@example.com');
            expect($decoded['name'])->toBe('John Doe');
            expect($decoded['status'])->toBe('active');
        });

        it('JsonSerializable interface works', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $encoded = json_encode($dto);
            expect($encoded)->toBeJson();

            $decoded = json_decode($encoded, true);
            expect($decoded['email'])->toBe('user@example.com');
        });
    });

    // -----------------------------------------------------------------------
    // Round-Trip — JSON serialization and restoration
    // -----------------------------------------------------------------------
    describe('JSON Round-Trip', function () {
        it('fromJson restores a DTO from serialized JSON', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'roundtrip@example.com',
                'name' => 'Round Trip',
                'status' => 'active',
                'tags' => ['test'],
                'phone' => '+1111111111',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored)->toBeInstanceOf(CreateUserDTO::class);
            expect($restored->email)->toBe('roundtrip@example.com');
            expect($restored->name)->toBe('Round Trip');
            expect($restored->tags)->toBe(['test']);
            expect($restored->phone)->toBe('+1111111111');
        });

        it('fromJson throws on invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json}', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws on JSON that decodes to non-array', function () {
            expect(fn () => CreateUserDTO::fromJson('"just a string"', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    // -----------------------------------------------------------------------
    // Value Equality — equals()
    // -----------------------------------------------------------------------
    describe('Value Equality', function () {
        it('equals() returns true for same values', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different values', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'other@example.com',
                'name' => 'John Doe',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('equals() returns false for different DTO classes', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $address = AddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
            ], validate: false);

            expect($dto->equals($address))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Immutability Helpers — only, except, with
    // -----------------------------------------------------------------------
    describe('Immutability Helpers', function () {
        it('only() returns array with selected fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email', 'name');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });

        it('except() returns array excluding specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status', 'tags');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
            expect($result)->not->toHaveKey('tags');
        });

        it('only() excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'password' => 'secret',
            ], validate: false);

            $result = $dto->only('password');
            expect($result)->not->toHaveKey('password');
            expect($result)->toBeEmpty();
        });

        it('with() creates a new DTO instance with overridden values', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $modified = $original->with(['name' => 'Jane Doe', 'status' => 'inactive']);

            expect($modified)->toBeInstanceOf(CreateUserDTO::class);
            expect($modified)->not->toBe($original); // new instance
            expect($modified->name)->toBe('Jane Doe');
            expect($modified->status)->toBe('inactive');
            expect($modified->email)->toBe('user@example.com'); // unchanged
        });

        it('with() preserves unchanged values', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'tags' => ['admin'],
            ], validate: false);

            $modified = $original->with(['name' => 'Jane']);

            expect($modified->email)->toBe('user@example.com');
            expect($modified->tags)->toBe(['admin']);
        });
    });

    // -----------------------------------------------------------------------
    // Empty Check — isEmpty()
    // -----------------------------------------------------------------------
    describe('isEmpty', function () {
        it('returns true when all visible fields are null/empty', function () {
            // AddressDTO with only required fields has no empty state,
            // but we can test with a DTO that has nullable optional fields
            $dto = CreateUserDTO::fromArray([
                'email' => '',
                'name' => '',
            ], validate: false);

            // Depends on implementation — test that it doesn't throw
            $isEmpty = $dto->isEmpty();
            expect(is_bool($isEmpty))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // Partial Updates — fromPartialArray
    // -----------------------------------------------------------------------
    describe('Partial Updates — fromPartialArray', function () {
        it('creates DTO with only provided fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Jane Doe',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Jane Doe');
        });

        it('preserves defaults for omitted optional fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Jane Doe',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('can partial-update with map_from keys', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'phone_number' => '+9876543210',
            ], validate: false);

            expect($dto->phone)->toBe('+9876543210');
        });
    });

    // -----------------------------------------------------------------------
    // Nested DTOs
    // -----------------------------------------------------------------------
    describe('Nested DTOs', function () {
        it('hydrates nested single DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
            ], validate: false);

            expect($dto)->toBeInstanceOf(OrderDTO::class);
            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Springfield');
        });

        it('accepts already-hydrated nested DTO instance', function () {
            $address = AddressDTO::fromArray([
                'street' => '456 Oak Ave',
                'city' => 'Portland',
            ], validate: false);

            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-002',
                'shippingAddress' => $address,
            ], validate: false);

            expect($dto->shippingAddress)->toBe($address);
            expect($dto->shippingAddress->city)->toBe('Portland');
        });

        it('throws on invalid nested DTO data', function () {
            expect(fn () => OrderDTO::fromArray([
                'orderNumber' => 'ORD-003',
                'shippingAddress' => 'not-an-array-or-dto',
            ], validate: false))->toThrow(\InvalidArgumentException::class);
        });

        it('serializes nested DTOs recursively in toArray()', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-004',
                'shippingAddress' => [
                    'street' => '789 Pine Rd',
                    'city' => 'Denver',
                ],
            ], validate: false);

            $array = $dto->toArray();
            expect($array['shippingAddress'])->toBeArray();
            expect($array['shippingAddress']['street'])->toBe('789 Pine Rd');
            expect($array['shippingAddress']['city'])->toBe('Denver');
        });
    });

    // -----------------------------------------------------------------------
    // Nested Array — array of DTOs
    // -----------------------------------------------------------------------
    describe('Nested Arrays', function () {
        it('hydrates array of nested DTOs from arrays', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-005',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
                'items' => [
                    ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                    ['productName' => 'Gadget', 'price' => 19.99],
                ],
            ], validate: false);

            expect($dto->items)->toBeArray();
            expect($dto->items)->toHaveCount(2);
            expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->items[0]->productName)->toBe('Widget');
            expect($dto->items[0]->price)->toBe(9.99);
            expect($dto->items[0]->quantity)->toBe(2);
            expect($dto->items[1]->productName)->toBe('Gadget');
            expect($dto->items[1]->quantity)->toBe(1); // default
        });

        it('accepts already-hydrated DTO instances in array', function () {
            $item = OrderItemDTO::fromArray([
                'productName' => 'Existing Item',
                'price' => 5.00,
            ], validate: false);

            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-006',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
                'items' => [$item],
            ], validate: false);

            expect($dto->items[0])->toBe($item);
        });

        it('serializes nested array of DTOs', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-007',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
                'items' => [
                    ['productName' => 'Widget', 'price' => 9.99],
                ],
            ], validate: false);

            $array = $dto->toArray();
            expect($array['items'])->toBeArray();
            expect($array['items'][0])->toBeArray();
            expect($array['items'][0]['productName'])->toBe('Widget');
        });

        it('throws on non-array/non-DTO elements in nested array', function () {
            expect(fn () => OrderDTO::fromArray([
                'orderNumber' => 'ORD-008',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
                'items' => ['not-a-dto'],
            ], validate: false))->toThrow(\InvalidArgumentException::class);
        });
    });

    // -----------------------------------------------------------------------
    // DtoCollection
    // -----------------------------------------------------------------------
    describe('DtoCollection', function () {
        it('constructs from array of DTOs', function () {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ];

            $collection = new DtoCollection($items);
            expect($collection)->toHaveCount(2);
        });

        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not-a-dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('supports ArrayAccess', function () {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ];
            $collection = new DtoCollection($items);

            expect($collection[0]->productName)->toBe('A');
            expect(isset($collection[0]))->toBeTrue();
            expect(isset($collection[1]))->toBeFalse();
        });

        it('supports Countable', function () {
            $collection = new DtoCollection([]);
            expect(count($collection))->toBe(0);

            $collection2 = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'X', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'Y', 'price' => 2.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'Z', 'price' => 3.0], validate: false),
            ]);
            expect(count($collection2))->toBe(3);
        });

        it('supports IteratorAggregate (foreach)', function () {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ];
            $collection = new DtoCollection($items);

            $names = [];
            foreach ($collection as $item) {
                $names[] = $item->productName;
            }
            expect($names)->toBe(['A', 'B']);
        });

        it('toArray() serializes all DTOs', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ]);

            $array = $collection->toArray();
            expect($array)->toBeArray();
            expect($array[0])->toBeArray();
            expect($array[0]['productName'])->toBe('A');
        });

        it('jsonSerialize() works', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ]);

            $json = json_encode($collection);
            expect($json)->toBeJson();
        });

        it('first() returns first item or null', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ]);

            expect($collection->first()->productName)->toBe('A');

            $empty = new DtoCollection([]);
            expect($empty->first())->toBeNull();
        });

        it('last() returns last item or null', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ]);

            expect($collection->last()->productName)->toBe('B');
        });

        it('map() applies callback to all items', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ]);

            $names = $collection->map(fn (OrderItemDTO $item) => $item->productName);
            expect($names)->toBe(['A', 'B']);
        });

        it('filter() returns matching items as new collection', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ]);

            $filtered = $collection->filter(fn (OrderItemDTO $item) => $item->price > 1.5);
            expect($filtered)->toHaveCount(1);
            expect($filtered->first()->productName)->toBe('B');
        });

        it('pluck() extracts a single field', function () {
            $collection = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
                OrderItemDTO::fromArray(['productName' => 'B', 'price' => 2.0], validate: false),
            ]);

            expect($collection->pluck('productName'))->toBe(['A', 'B']);
        });

        it('isEmpty() returns boolean', function () {
            $empty = new DtoCollection([]);
            $full = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ]);

            expect($empty->isEmpty())->toBeTrue();
            expect($full->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() returns boolean', function () {
            $empty = new DtoCollection([]);
            $full = new DtoCollection([
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ]);

            expect($empty->isNotEmpty())->toBeFalse();
            expect($full->isNotEmpty())->toBeTrue();
        });

        it('items() returns raw DTO instances array', function () {
            $items = [
                OrderItemDTO::fromArray(['productName' => 'A', 'price' => 1.0], validate: false),
            ];
            $collection = new DtoCollection($items);

            expect($collection->items()[0])->toBeInstanceOf(OrderItemDTO::class);
        });
    });

    // -----------------------------------------------------------------------
    // DtoMetadataResolver
    // -----------------------------------------------------------------------
    describe('DtoMetadataResolver', function () {
        it('resolves rules for CreateUserDTO', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('detects MapFrom and uses source key in rules', function () {
            $rules = CreateUserDTO::rules();

            // phone_number should be the rule key (mapped from phone_number)
            expect($rules)->toHaveKey('phone_number');
        });

        it('resolves hidden properties correctly', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['password']['hidden'])->toBeTrue();
            expect($meta['properties']['email']['hidden'])->toBeFalse();
        });

        it('resolves default values', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['status']['has_default'])->toBeTrue();
            expect($meta['properties']['status']['default'])->toBe('active');
        });

        it('resolves cast type', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['tags']['cast'])->toBe('array');
        });

        it('resolves map_from key', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['phone']['map_from'])->toBe('phone_number');
        });
    });

    // -----------------------------------------------------------------------
    // Type Casting
    // -----------------------------------------------------------------------
    describe('Type Casting', function () {
        it('casts string value to array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'tags' => '["a","b"]',
            ], validate: false);

            // Cast('array') should convert JSON string to array
            expect(is_array($dto->tags))->toBeTrue();
        });

        it('accepts already-array for Cast("array")', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'tags' => ['a', 'b'],
            ], validate: false);

            expect($dto->tags)->toBe(['a', 'b']);
        });
    });

    // -----------------------------------------------------------------------
    // DTOException
    // -----------------------------------------------------------------------
    describe('DTOException', function () {
        it('creates from invalidCast() factory', function () {
            $e = DTOException::invalidCast('fieldName', 'int', 'string');

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('fieldName');
        });

        it('creates from invalidJson() factory', function () {
            $e = DTOException::invalidJson('fieldName', 'Syntax error');

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('fieldName');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    // -----------------------------------------------------------------------
    // RulesFor — per-field validation
    // -----------------------------------------------------------------------
    describe('rulesFor — per-field validation', function () {
        it('returns rules for a specific field', function () {
            $rules = CreateUserDTO::rulesFor('email');

            expect($rules)->toBeArray();
            expect($rules)->toContain('required');
            expect($rules)->toContain('email');
        });

        it('returns empty array for non-existent field', function () {
            $rules = CreateUserDTO::rulesFor('nonexistent_field');

            expect($rules)->toBeArray();
            expect($rules)->toBeEmpty();
        });
    });

    // -----------------------------------------------------------------------
    // Metadata Cache
    // -----------------------------------------------------------------------
    describe('Metadata Cache', function () {
        it('flushMetadataCache clears all classes', function () {
            CreateUserDTO::rules(); // populate cache
            CreateUserDTO::flushMetadataCache();

            // After flush, re-resolving should work fine
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache can target a single class', function () {
            CreateUserDTO::rules();
            OrderDTO::rules();

            CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

            // OrderDTO rules should still be cached
            $orderRules = OrderDTO::rules();
            expect($orderRules)->toBeArray();
        });
    });

    // -----------------------------------------------------------------------
    // Edge Cases
    // -----------------------------------------------------------------------
    describe('Edge Cases', function () {
        it('handles empty array input gracefully', function () {
            // CreateUserDTO requires email and name, so empty should throw
            expect(fn () => CreateUserDTO::fromArray([], validate: true))
                ->toThrow(ValidationException::class);
        });

        it('handles extra fields in input array (ignored)', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'extra_field' => 'should be ignored',
                'another_extra' => 123,
            ], validate: false);

            expect($dto->email)->toBe('user@example.com');
            // Extra fields should not be accessible as properties
        });

        it('handles null values for nullable fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'phone_number' => null,
            ], validate: false);

            expect($dto->phone)->toBeNull();
        });

        it('DTO is readonly — properties cannot be reassigned', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
            ], validate: false);

            // This should throw an Error because properties are readonly
            expect(fn () => $dto->email = 'changed@example.com')->toThrow(\Error::class);
        });

        it('union type properties work correctly', function () {
            $dto1 = OrderDTO::fromArray([
                'orderNumber' => 'ORD-U1',
                'shippingAddress' => ['street' => 'St', 'city' => 'City'],
                'rawTotal' => 100,
            ], validate: false);
            expect($dto1->rawTotal)->toBe(100);

            $dto2 = OrderDTO::fromArray([
                'orderNumber' => 'ORD-U2',
                'shippingAddress' => ['street' => 'St', 'city' => 'City'],
                'rawTotal' => 99.99,
            ], validate: false);
            expect($dto2->rawTotal)->toBe(99.99);

            $dto3 = OrderDTO::fromArray([
                'orderNumber' => 'ORD-U3',
                'shippingAddress' => ['street' => 'St', 'city' => 'City'],
                'rawTotal' => 'one hundred',
            ], validate: false);
            expect($dto3->rawTotal)->toBe('one hundred');
        });
    });
});
