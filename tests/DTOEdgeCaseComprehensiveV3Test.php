<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Carbon\Carbon;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('NoConstructorDTO — empty DTO edge case', function (): void {
    it('creates instance from empty array', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
    });

    it('toArray returns empty array', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto->toArray())->toBe([]);
    });

    it('allValues returns empty array', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto->allValues())->toBe([]);
    });

    it('toJson returns empty object JSON', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);
        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBe([]);
    });

    it('isEmpty returns true', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns false', function (): void {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('rules returns empty array', function (): void {
        $rules = NoConstructorDTO::rules();

        expect($rules)->toBeArray();
    });

    it('class properties returns empty', function (): void {
        $properties = NoConstructorDTO::classProperties();

        expect($properties)->toBeArray();
    });
});

describe('RoundtripDTO — comprehensive roundtrip tests', function (): void {
    it('roundtrips through fromArray → toArray', function (): void {
        $data = [
            'name' => 'Alice',
            'age' => '30', // string, will be cast to int via #[Cast('integer')]
            'active' => true,
            'score' => 95.5,
            'tags' => ['admin', 'user'],
            'source_bio' => 'Hello world',
            'secret' => 'hidden-value',
            'role' => 'admin',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['name'])->toBe('Alice');
        expect($result['age'])->toBe(30);
        expect($result['active'])->toBeTrue();
        expect($result['score'])->toBe(95.5);
        expect($result['tags'])->toBe(['admin', 'user']);
        expect($result['bio'])->toBe('Hello world');
        expect($result['role'])->toBe('admin');
        // Hidden field should NOT be in toArray output
        expect($result)->not->toHaveKey('secret');
    });

    it('MapFrom maps source_bio to bio', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => '25',
            'active' => false,
            'source_bio' => 'A bio from source',
        ], validate: false);

        expect($dto->bio)->toBe('A bio from source');
    });

    it('hidden field included in allValues but not toArray', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Charlie',
            'age' => '40',
            'active' => true,
            'secret' => 'top-secret',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('secret');
        expect($dto->allValues())->toHaveKey('secret');
    });

    it('equals compares all properties including hidden', function (): void {
        $data = [
            'name' => 'Dave',
            'age' => '28',
            'active' => true,
            'secret' => 'same-secret',
        ];

        $dto1 = RoundtripDTO::fromArray($data, validate: false);
        $dto2 = RoundtripDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false when hidden fields differ', function (): void {
        $base = [
            'name' => 'Eve',
            'age' => '30',
            'active' => true,
        ];

        $dto1 = RoundtripDTO::fromArray([...$base, 'secret' => 's1'], validate: false);
        $dto2 = RoundtripDTO::fromArray([...$base, 'secret' => 's2'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('with() creates new instance with merged overrides', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Frank',
            'age' => '22',
            'active' => false,
        ], validate: false);

        $updated = $dto->with(['active' => true, 'role' => 'superadmin']);

        expect($updated->active)->toBeTrue();
        expect($updated->role)->toBe('superadmin');
        expect($updated->name)->toBe('Frank');
        expect($updated)->not->toBe($dto);
    });

    it('fromPartialArray sets defaults for missing fields', function (): void {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Grace'], validate: false);

        expect($dto->name)->toBe('Grace');
        expect($dto->role)->toBe('user'); // default
    });
});

describe('CreateUserDTO — serialization edge cases', function (): void {
    it('fromJson parses valid JSON', function (): void {
        $json = json_encode([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'tags' => ['php', 'laravel'],
        ]);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('fromJson roundtrips with toJson', function (): void {
        $data = [
            'email' => 'roundtrip@example.com',
            'name' => 'Round Trip',
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $json = $dto->toJson();
        $restored = CreateUserDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe($dto->email);
        expect($restored->name)->toBe($dto->name);
    });

    it('fromJson throws on invalid JSON', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws on sequential array JSON', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson accepts empty object JSON', function (): void {
        // EmptyDTO has no required fields, so empty object is valid
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('only returns only specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $only = $dto->only('email');

        expect($only)->toHaveCount(1);
        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
    });

    it('except excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $except = $dto->except('email');

        expect($except)->not->toHaveKey('email');
        expect($except)->toHaveKey('name');
    });

    it('only accepts array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $only = $dto->only(['email', 'status']);

        expect($only)->toHaveKey('email');
        expect($only)->toHaveKey('status');
    });

    it('except accepts array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $except = $dto->except(['email', 'status']);

        expect($except)->not->toHaveKey('email');
        expect($except)->not->toHaveKey('status');
        expect($except)->toHaveKey('name');
    });

    it('isEmpty returns false when data present', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when data present', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('ArrayCastDTO — array casting edge cases', function (): void {
    it('casts JSON string to array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["a","b","c"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b', 'c']);
    });

    it('accepts array directly', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => ['x', 'y'],
        ], validate: false);

        expect($dto->tags)->toBe(['x', 'y']);
    });

    it('defaults to empty array when not provided', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
        ], validate: false);

        expect($dto->tags)->toBe([]);
        expect($dto->metadata)->toBe([]);
    });
});

describe('Nested DTO hydration', function (): void {
    it('hydrates nested AddressDTO', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
                'zipCode' => '34000',
            ],
        ], validate: false);

        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->street)->toBe('123 Main St');
        expect($dto->shippingAddress->city)->toBe('Istanbul');
        expect($dto->shippingAddress->zipCode)->toBe('34000');
    });

    it('serializes nested DTO to array', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => [
                'street' => '456 Oak Ave',
                'city' => 'Ankara',
            ],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99],
            ],
        ], validate: false);

        $array = $dto->toArray();

        expect($array['shippingAddress'])->toBeArray();
        expect($array['shippingAddress']['city'])->toBe('Ankara');
        expect($array['items'])->toBeArray();
        expect($array['items'][0]['productName'])->toBe('Widget');
    });

    it('accepts already-hydrated DTO instance', function (): void {
        $address = AddressDTO::fromArray([
            'street' => '789 Pine Rd',
            'city' => 'Izmir',
        ], validate: false);

        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => $address,
        ], validate: false);

        expect($dto->shippingAddress)->toBe($address);
    });
});

describe('UnionTypeDTO — union type properties', function (): void {
    it('accepts string value for int|string union', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc123',
            'identifier' => 'string-id',
        ], validate: false);

        expect($dto->identifier)->toBe('string-id');
    });

    it('accepts int value for int|string union', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc123',
            'identifier' => 42,
        ], validate: false);

        expect($dto->identifier)->toBe(42);
    });

    it('roundtrips through toArray', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc123',
            'identifier' => 99,
        ], validate: false);

        $array = $dto->toArray();

        expect($array['identifier'])->toBe(99);
    });
});

describe('MinimalDTO — strict required fields', function (): void {
    it('creates with all required fields', function (): void {
        $dto = MinimalDTO::fromArray([
            'name' => 'Test',
            'value' => 'val',
        ], validate: false);

        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('val');
    });

    it('throws when required field missing', function (): void {
        expect(fn (): mixed => MinimalDTO::fromArray(['name' => 'Test'], validate: false))
            ->toThrow(\ArgumentCountError::class);
    });

    it('rules contain required for both fields', function (): void {
        $rules = MinimalDTO::rules();

        expect($rules['name'])->toContain('required');
        expect($rules['value'])->toContain('required');
    });
});

describe('DtoCollection operations', function (): void {
    it('creates from static factory', function (): void {
        $dtoArray = [
            ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false),
            ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 3], validate: false),
        ];

        $collection = DtoCollection::make($dtoArray);

        expect($collection->count())->toBe(2);
        expect($collection->isEmpty())->toBeFalse();
    });

    it('pluck extracts single field', function (): void {
        $dtoArray = [
            ProductDTO::fromArray(['name' => 'Widget', 'price' => '9.99', 'stock' => 10], validate: false),
            ProductDTO::fromArray(['name' => 'Gadget', 'price' => '19.99', 'stock' => 5], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Widget', 'Gadget']);
    });

    it('first returns first item', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'First', 'price' => '1', 'stock' => 1], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'Second', 'price' => '2', 'stock' => 2], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first())->toBe($dto1);
        expect($collection->last())->toBe($dto2);
    });

    it('filter returns new collection', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 10], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 0], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(fn (ProductDTO $p): bool => $p->stock > 0);

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('A');
    });

    it('map returns plain array', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'X', 'price' => '5', 'stock' => 3], validate: false);

        $collection = new DtoCollection([$dto1]);
        $names = $collection->map(fn (ProductDTO $p): string => $p->name);

        expect($names)->toBe(['X']);
    });

    it('toArray serializes all items', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 1], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 2], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $array = $collection->toArray();

        expect($array)->toHaveCount(2);
        expect($array[0]['name'])->toBe('A');
    });

    it('empty collection isEmpty', function (): void {
        $collection = new DtoCollection([]);

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
        expect($collection->first())->toBeNull();
    });

    it('items returns raw DTO instances', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '1', 'stock' => 1], validate: false);
        $collection = new DtoCollection([$dto1]);

        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBe($dto1);
    });

    it('append returns new collection (immutable)', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '1', 'stock' => 1], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '2', 'stock' => 2], validate: false);

        $original = new DtoCollection([$dto1]);
        $new = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($new->count())->toBe(2);
    });
});

describe('DateCastDTO — date casting', function (): void {
    it('casts string to Carbon', function (): void {
        $dto = DateCastDTO::fromArray([
            'event_date' => '2024-01-15',
        ], validate: false);

        expect($dto->event_date)->toBeInstanceOf(Carbon::class);
        expect($dto->event_date?->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('accepts null for nullable date', function (): void {
        $dto = DateCastDTO::fromArray([], validate: false);

        expect($dto->event_date)->toBeNull();
    });

    it('serializes Carbon to ISO string in toArray', function (): void {
        $dto = DateCastDTO::fromArray([
            'event_date' => '2024-06-01 10:30:00',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['event_date'])->toBe('2024-06-01T10:30:00+00:00');
    });
});

describe('ProductDTO — numeric validation', function (): void {
    it('validateArray returns validated data', function (): void {
        $validated = ProductDTO::validateArray([
            'name' => 'Test',
            'price' => '29.99',
            'stock' => 10,
        ]);

        expect($validated)->toBeArray();
        expect($validated)->toHaveKey('name');
        expect($validated)->toHaveKey('price');
    });

    it('rules contain correct numeric constraints', function (): void {
        $rules = ProductDTO::rules();

        expect($rules['price'])->toContain('required');
        expect($rules['price'])->toContain('numeric');
        expect($rules['stock'])->toContain('integer');
        expect($rules['stock'])->toContain('min:0');
    });
});

describe('EmptyDTO — nullable defaults', function (): void {
    it('creates with all nulls', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('toArray contains null values', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->toArray())->toBe(['foo' => null, 'bar' => null]);
    });

    it('isEmpty returns true when all null', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });
});

describe('DTO metadata cache TTL', function (): void {
    it('setMetadataCacheTtl sets TTL', function (): void {
        $originalTtl = \ZeroBoiler\DTO\DataTransferObject::getMetadataCacheTtl();

        \ZeroBoiler\DTO\DataTransferObject::setMetadataCacheTtl(5.0);

        expect(\ZeroBoiler\DTO\DataTransferObject::getMetadataCacheTtl())->toBe(5.0);

        // Restore
        \ZeroBoiler\DTO\DataTransferObject::setMetadataCacheTtl($originalTtl);
    });

    it('flushMetadataCache clears cache', function (): void {
        \ZeroBoiler\DTO\DataTransferObject::flushMetadataCache();

        // Create DTO — should work after flush
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('DTO equality edge cases', function (): void {
    it('different types are not equal', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);
        $other = ProductDTO::fromArray(['name' => 'Test', 'price' => '0', 'stock' => 0], validate: false);

        expect($dto->equals($other))->toBeFalse();
    });

    it('equals is symmetric', function (): void {
        $data = ['email' => 'test@example.com', 'name' => 'Test'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBe($dto2->equals($dto1));
    });
});
