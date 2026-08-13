<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DTOManager direct usage', function (): void {
    it('validates data against a DTO class', function (): void {
        $manager = new DTOManager;
        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Alice',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('creates DTO from data', function (): void {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Bob',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('creates DTO from JSON string', function (): void {
        $manager = new DTOManager;
        $dto = $manager->makeFromJson(CreateUserDTO::class, '{"email":"a@b.com","name":"Charlie"}');

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Charlie');
    });

    it('returns rules for a DTO class', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
    });

    it('returns action-scoped rules', function (): void {
        $manager = new DTOManager;
        $updateRules = $manager->rulesFor(ActionScopedDTO::class, 'update');

        expect($updateRules)->toBeArray();
        expect($updateRules['email'])->toContain('sometimes');
    });

    it('generates OpenAPI schema for simple DTO', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(EmptyDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
        expect($schema)->toHaveKey('properties');
    });
});

describe('Facade delegation', function (): void {
    it('DTO::make creates a DTO instance', function (): void {
        $dto = DTO::make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Facade Test',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Facade Test');
    });

    it('DTO::validate returns validated data', function (): void {
        $result = DTO::validate(CreateUserDTO::class, [
            'email' => 'facade@test.com',
            'name' => 'Doruk',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('facade@test.com');
    });

    it('DTO::rules returns correct structure', function (): void {
        $rules = DTO::rules(CreateUserDTO::class);

        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('DTO::rulesFor returns action-specific rules', function (): void {
        $updateRules = DTO::rulesFor(ActionScopedDTO::class, 'update');

        expect($updateRules['password'])->toContain('sometimes');
    });
});

describe('fromJson edge cases', function (): void {
    it('rejects sequential arrays', function (): void {
        CreateUserDTO::fromJson('["email","a@b.com"]');
    })->throws(DTOException::class);

    it('rejects invalid JSON', function (): void {
        CreateUserDTO::fromJson('not json at all');
    })->throws(DTOException::class);

    it('accepts empty JSON object', function (): void {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->foo)->toBeNull();
    });

    it('accepts valid JSON with nested data', function (): void {
        $dto = OrderDTO::fromJson('{"orderNumber":"ORD-001","shippingAddress":{"street":"123 Main St","city":"NYC"}}', validate: false);

        expect($dto)->toBeInstanceOf(OrderDTO::class);
        expect($dto->orderNumber)->toBe('ORD-001');
        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->city)->toBe('NYC');
    });
});

describe('fromPartialArray edge cases', function (): void {
    it('only hydrates fields present in data', function (): void {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->name)->toBe('Alice');
        // age, active should have type-appropriate empty values since no default
        // bio and secret should be null (nullable with defaults)
        expect($dto->bio)->toBeNull();
        expect($dto->secret)->toBeNull();
    });

    it('preserves defaults for missing fields', function (): void {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Bob', 'age' => 30, 'active' => true], validate: false);

        expect($dto->score)->toBe(0.0); // DefaultValue
        expect($dto->tags)->toBe([]); // DefaultValue
        expect($dto->role)->toBe('user'); // DefaultValue
    });

    it('handles empty data with all-defaults DTO', function (): void {
        $dto = EmptyDTO::fromPartialArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('accepts null values for nullable fields', function (): void {
        $dto = RoundtripDTO::fromPartialArray([
            'name' => 'Charlie',
            'bio' => null,
        ], validate: false);

        expect($dto->bio)->toBeNull();
    });
});

describe('RoundtripDTO comprehensive tests', function (): void {
    it('fromArray → toArray is a perfect roundtrip', function (): void {
        $data = [
            'name' => 'Alice',
            'age' => '30',       // Cast('integer') normalizes string to int
            'active' => true,
            'score' => '95.5',   // default overridden
            'tags' => ['php'],
            'source_bio' => 'Developer', // MapFrom('source_bio') → bio
            'secret' => 'hidden',        // Hidden field
            'role' => 'admin',           // DefaultValue overridden
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $array = $dto->toArray();

        expect($array['name'])->toBe('Alice');
        expect($array['age'])->toBe(30); // Cast integer
        expect($array['active'])->toBe(true);
        expect($array['score'])->toBe(95.5);
        expect($array['tags'])->toBe(['php']);
        expect($array['bio'])->toBe('Developer');
        expect($array['role'])->toBe('admin');
        expect($array)->not->toHaveKey('secret'); // Hidden
    });

    it('allValues includes hidden fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 25,
            'active' => false,
            'secret' => 's3cret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('s3cret');
    });

    it('with() creates new instance with overrides', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Charlie',
            'age' => 40,
            'active' => true,
        ], validate: false);

        $updated = $dto->with(['name' => 'Dave', 'score' => 88.5]);

        expect($updated)->not->toBe($dto); // Different instance
        expect($updated->name)->toBe('Dave');
        expect($updated->age)->toBe(40); // Preserved
        expect($updated->active)->toBe(true); // Preserved
        expect($updated->score)->toBe(88.5); // Overridden
        expect($dto->name)->toBe('Charlie'); // Original unchanged
    });

    it('equals() compares correctly', function (): void {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 20,
            'active' => true,
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 20,
            'active' => true,
        ], validate: false);

        $dto3 = RoundtripDTO::fromArray([
            'name' => 'Other',
            'age' => 20,
            'active' => true,
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty() and isNotEmpty() boundary checks', function (): void {
        // EmptyDTO with all nulls should be empty
        $empty = EmptyDTO::fromArray([], validate: false);
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
    });
});

describe('Nested DTO hydration and serialization', function (): void {
    it('hydrates single nested DTO', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => '1',
            'address' => ['street' => '123 Main', 'city' => 'NYC'],
            'label' => 'Home',
        ], validate: false);

        expect($dto->address)->toBeInstanceOf(AddressDTO::class);
        expect($dto->address->street)->toBe('123 Main');
        expect($dto->address->city)->toBe('NYC');
    });

    it('serializes nested DTO to array recursively', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => '1',
            'address' => ['street' => '456 Oak', 'city' => 'LA'],
            'label' => 'Office',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['address'])->toBe(['street' => '456 Oak', 'city' => 'LA', 'zipCode' => null]);
    });

    it('hydrates nested array of DTOs', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => ['street' => '1 St', 'city' => 'NYC'],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                ['productName' => 'Gadget', 'price' => 19.99],
            ],
        ], validate: false);

        expect($dto->items)->toHaveCount(2);
        expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($dto->items[0]->productName)->toBe('Widget');
        expect($dto->items[0]->price)->toBe(9.99);
        expect($dto->items[0]->quantity)->toBe(2);
        // Second item uses default quantity
        expect($dto->items[1]->quantity)->toBe(1);
    });

    it('serializes nested array of DTOs to arrays', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => ['street' => '2 St', 'city' => 'LA'],
            'items' => [['productName' => 'Item1', 'price' => 5.0]],
        ], validate: false);

        $array = $dto->toArray();

        expect($array['items'])->toBeArray();
        expect($array['items'][0])->toBe([
            'productName' => 'Item1',
            'price' => 5.0,
            'quantity' => 1,
        ]);
    });

    it('serializes full nested order to JSON correctly', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => ['street' => '3 St', 'city' => 'SF'],
            'items' => [],
            'rawTotal' => '100',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['orderNumber'])->toBe('ORD-003');
        expect($decoded['shippingAddress']['city'])->toBe('SF');
        expect($decoded['items'])->toBe([]);
        expect($decoded['rawTotal'])->toBe('100');
    });
});

describe('DtoCollection comprehensive tests', function (): void {
    it('make creates collection from DTOs', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);

        expect($col->count())->toBe(2);
        expect($col->isEmpty())->toBeFalse();
    });

    it('push appends and returns collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$d1]);
        $result = $col->push($d2);

        expect($result)->toBe($col); // Same instance (mutable)
        expect($col->count())->toBe(2);
    });

    it('append returns new immutable collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$d1]);
        $newCol = $col->append($d2);

        expect($newCol)->not->toBe($col); // Different instance
        expect($col->count())->toBe(1); // Original unchanged
        expect($newCol->count())->toBe(2);
    });

    it('merge combines two collections', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

        $col1 = DtoCollection::make([$d1]);
        $col2 = DtoCollection::make([$d2, $d3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1); // Original unchanged
    });

    it('map returns plain array of results', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $names = $col->map(fn (CreateUserDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('filter returns new collection with matching items', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $filtered = $col->filter(fn (CreateUserDTO $dto): bool => str_starts_with($dto->name, 'A'));

        expect($filtered->count())->toBe(1);
    });

    it('pluck extracts single property values', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);

        expect($col->pluck('email'))->toBe(['a@b.com', 'c@d.com']);
        expect($col->pluck('name'))->toBe(['A', 'C']);
    });

    it('pluckKey builds dictionary from properties', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '10.00', 'stock' => 5], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'Gadget', 'price' => '20.00', 'stock' => 3], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $dict = $col->pluckKey('name', 'price');

        expect($dict)->toBe(['Widget' => '10.00', 'Gadget' => '20.00']);
    });

    it('toArrayBy re-keys collection', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'W', 'price' => '5', 'stock' => 10], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'G', 'price' => '10', 'stock' => 20], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $keyed = $col->toArrayBy('name');

        expect($keyed)->toHaveKey('W');
        expect($keyed)->toHaveKey('G');
    });

    it('first() and last() return correct items', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'First'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Last'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);

        expect($col->first()->name)->toBe('First');
        expect($col->last()->name)->toBe('Last');
    });

    it('empty collection has first() and last() as null', function (): void {
        $col = DtoCollection::make([]);

        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('offsetSet and offsetGet work correctly', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

        $col = DtoCollection::make([]);
        $col[0] = $d1;

        expect($col[0])->toBe($d1);
        expect($col->count())->toBe(1);
    });

    it('offsetUnset re-indexes collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

        $col = DtoCollection::make([$d1, $d2, $d3]);
        unset($col[0]);

        expect($col->count())->toBe(2);
        expect($col->first()->name)->toBe('C');
    });

    it('jsonSerialize produces correct JSON', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

        $col = DtoCollection::make([$d1]);
        $json = json_encode($col);

        $decoded = json_decode($json, true);

        expect($decoded)->toBe([['email' => 'a@b.com', 'name' => 'A', 'status' => 'active', 'tags' => [], 'phone' => null]]);
    });

    it('items() returns raw DTO instances', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

        $col = DtoCollection::make([$d1]);
        $items = $col->items();

        expect($items[0])->toBe($d1);
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });

    it('rejects non-DTO items in constructor', function (): void {
        new DtoCollection(['not_a_dto']);
    })->throws(\InvalidArgumentException::class);
});

describe('Union type DTO', function (): void {
    it('accepts string value for int|string union', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc',
            'identifier' => 'string-value',
        ], validate: false);

        expect($dto->identifier)->toBe('string-value');
    });

    it('accepts int value for int|string union', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc',
            'identifier' => 42,
        ], validate: false);

        expect($dto->identifier)->toBe(42);
    });

    it('serializes union type correctly', function (): void {
        $dto = UnionTypeDTO::fromArray([
            'id' => 'abc',
            'identifier' => 'hello',
        ], validate: false);

        expect($dto->toArray()['identifier'])->toBe('hello');
    });
});

describe('only() and except() selective output', function (): void {
    it('only returns specified fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $result = $dto->only('name', 'active');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('active');
        expect($result)->not->toHaveKey('age');
    });

    it('only accepts string key', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 25,
            'active' => false,
        ], validate: false);

        $result = $dto->only('name');

        expect($result)->toHaveCount(1);
        expect($result['name'])->toBe('Bob');
    });

    it('except excludes specified fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Charlie',
            'age' => 35,
            'active' => true,
            'score' => 90.0,
        ], validate: false);

        $result = $dto->except('age', 'score');

        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('active');
        expect($result)->not->toHaveKey('age');
        expect($result)->not->toHaveKey('score');
    });

    it('except ignores non-existent keys silently', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Dave',
            'age' => 40,
            'active' => true,
        ], validate: false);

        $result = $dto->except('nonexistent');

        // All fields should still be present
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('age');
    });
});

describe('rules() and rulesFor() edge cases', function (): void {
    it('rulesFor returns base rules for unknown action', function (): void {
        $rules = ActionScopedDTO::rulesFor('unknown_action');

        // Should fall through to default (same as rules())
        expect($rules['email'])->toContain('required');
    });

    it('ProductDTO has numeric and integer rules', function (): void {
        $rules = ProductDTO::rules();

        expect($rules['price'])->toContain('numeric');
        expect($rules['stock'])->toContain('integer');
        expect($rules['stock'])->toContain('min:0');
    });
});

describe('DTOException named constructors', function (): void {
    it('invalidCast creates with property and type', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson creates with property and error', function (): void {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class and message', function (): void {
        $e = DTOException::invalidJson('data', 'error');

        $str = (string) $e;

        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('data');
    });
});

describe('Metadata cache behavior', function (): void {
    beforeEach(function (): void {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function (): void {
        DataTransferObject::flushMetadataCache();
    });

    it('resolveMetadata caches after first call', function (): void {
        $rules1 = CreateUserDTO::rules();
        $rules2 = CreateUserDTO::rules();

        // Both should be identical
        expect($rules1)->toBe($rules2);
    });

    it('flushMetadataCache clears class-specific cache', function (): void {
        CreateUserDTO::rules();
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Should not throw — metadata should be re-resolved
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });
});
