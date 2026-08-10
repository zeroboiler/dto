<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

describe('NoConstructorDTO edge cases', function () {
    it('can be created from empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
    });

    it('toArray returns empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->toArray())->toBe([]);
    });

    it('allValues returns empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->allValues())->toBe([]);
    });

    it('toJson returns empty JSON object', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->toJson())->toBe('{}');
    });

    it('jsonSerialize returns empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->jsonSerialize())->toBe([]);
    });

    it('isEmpty returns true', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns false', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('equals another empty instance', function () {
        $a = NoConstructorDTO::fromArray([]);
        $b = NoConstructorDTO::fromArray([]);

        expect($a->equals($b))->toBeTrue();
    });

    it('only returns empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->only('anything'))->toBe([]);
    });

    it('except returns empty array', function () {
        $dto = NoConstructorDTO::fromArray([]);

        expect($dto->except('anything'))->toBe([]);
    });

    it('rules returns empty array', function () {
        expect(NoConstructorDTO::rules())->toBe([]);
    });

    it('rulesFor returns empty array for any action', function () {
        expect(NoConstructorDTO::rulesFor('create'))->toBe([]);
        expect(NoConstructorDTO::rulesFor('update'))->toBe([]);
    });

    it('fromJson with empty object works', function () {
        $dto = NoConstructorDTO::fromJson('{}');

        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
        expect($dto->toArray())->toBe([]);
    });

    it('fromPartialArray with empty data works', function () {
        $dto = NoConstructorDTO::fromPartialArray([]);

        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
    });

    it('fromArray ignores extra keys gracefully', function () {
        $dto = NoConstructorDTO::fromArray(['foo' => 'bar', 'baz' => 123]);

        expect($dto->toArray())->toBe([]);
    });
});

describe('RoundtripDTO hydration and serialization', function () {
    it('creates from array with all fields', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => '30',
            'active' => '1',
            'score' => '95.5',
            'tags' => '["php","laravel"]',
            'source_bio' => 'Software engineer',
            'secret' => 'password123',
            'role' => 'admin',
        ]);

        expect($dto->name)->toBe('Alice');
        expect($dto->age)->toBe(30);
        expect($dto->active)->toBeTrue();
        expect($dto->score)->toBe(95.5);
        expect($dto->tags)->toBe(['php', 'laravel']);
        expect($dto->bio)->toBe('Software engineer');
        expect($dto->secret)->toBe('password123');
        expect($dto->role)->toBe('admin');
    });

    it('uses defaults for missing fields', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 25,
            'active' => true,
        ]);

        expect($dto->score)->toBe(0.0);
        expect($dto->tags)->toBe([]);
        expect($dto->bio)->toBeNull();
        expect($dto->secret)->toBeNull();
        expect($dto->role)->toBe('user');
    });

    it('roundtrips toArray correctly', function () {
        $data = [
            'name' => 'Charlie',
            'age' => 40,
            'active' => false,
            'score' => 88.5,
            'tags' => ['dev'],
            'source_bio' => null,
            'secret' => 'hidden',
            'role' => 'editor',
        ];

        $dto = RoundtripDTO::fromArray($data);
        $result = $dto->toArray();

        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('age');
        expect($result)->toHaveKey('active');
        expect($result)->toHaveKey('score');
        expect($result)->toHaveKey('tags');
        expect($result)->toHaveKey('bio');
        expect($result)->toHaveKey('role');
        // Hidden field excluded
        expect($result)->not->toHaveKey('secret');
    });

    it('allValues includes hidden field', function () {
        $data = [
            'name' => 'Dave',
            'age' => 35,
            'active' => true,
            'secret' => 's3cret',
        ];

        $dto = RoundtripDTO::fromArray($data);
        $all = $dto->allValues();

        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('s3cret');
    });

    it('maps source_bio to bio via MapFrom', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Eve',
            'age' => 28,
            'active' => true,
            'source_bio' => 'Mapped value',
        ]);

        expect($dto->bio)->toBe('Mapped value');
    });

    it('validates Min constraint on name', function () {
        expect(fn () => RoundtripDTO::fromArray([
            'name' => '',
            'age' => 25,
            'active' => true,
        ]))->toThrow(ValidationException::class);
    });

    it('validates Max constraint on name', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => str_repeat('x', 100),
            'age' => 25,
            'active' => true,
        ]);

        expect($dto->name)->toBe(str_repeat('x', 100));

        expect(fn () => RoundtripDTO::fromArray([
            'name' => str_repeat('x', 101),
            'age' => 25,
            'active' => true,
        ]))->toThrow(ValidationException::class);
    });

    it('casts age from string to int', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Frank',
            'age' => '42',
            'active' => true,
        ]);

        expect($dto->age)->toBe(42);
        expect(is_int($dto->age))->toBeTrue();
    });

    it('casts tags from JSON string to array', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Grace',
            'age' => 30,
            'active' => true,
            'tags' => '{"key":"value"}',
        ]);

        expect($dto->tags)->toBeArray();
    });

    it('equals works for identical instances', function () {
        $data = [
            'name' => 'Hank',
            'age' => 50,
            'active' => false,
            'score' => 0.0,
            'tags' => [],
            'role' => 'user',
        ];

        $a = RoundtripDTO::fromArray($data);
        $b = RoundtripDTO::fromArray($data);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different instances', function () {
        $a = RoundtripDTO::fromArray(['name' => 'A', 'age' => 20, 'active' => true]);
        $b = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => true]);

        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty returns false when name is set', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Ivy',
            'age' => 25,
            'active' => true,
        ]);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('with() creates new instance with overrides', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Jack',
            'age' => 30,
            'active' => true,
            'score' => 50.0,
            'tags' => ['a'],
            'role' => 'user',
        ]);

        $updated = $dto->with(['name' => 'Jack Updated', 'role' => 'admin']);

        expect($updated->name)->toBe('Jack Updated');
        expect($updated->role)->toBe('admin');
        expect($updated->age)->toBe(30); // unchanged
        expect($dto->name)->toBe('Jack'); // original unchanged
    });

    it('only returns specified fields', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Kate',
            'age' => 27,
            'active' => true,
            'score' => 90.0,
        ]);

        $subset = $dto->only('name', 'age');

        expect($subset)->toHaveCount(2);
        expect($subset)->toHaveKey('name');
        expect($subset)->toHaveKey('age');
    });

    it('except excludes specified fields', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Leo',
            'age' => 35,
            'active' => true,
            'score' => 77.5,
        ]);

        $subset = $dto->except('age', 'score');

        expect($subset)->not->toHaveKey('age');
        expect($subset)->not->toHaveKey('score');
        expect($subset)->toHaveKey('name');
    });

    it('fromJson roundtrips correctly', function () {
        $json = json_encode([
            'name' => 'Mona',
            'age' => 33,
            'active' => true,
            'score' => 100.0,
            'tags' => ['art'],
            'source_bio' => 'Painter',
            'secret' => 'dont-tell',
            'role' => 'admin',
        ]);

        $dto = RoundtripDTO::fromJson($json);

        expect($dto->name)->toBe('Mona');
        expect($dto->age)->toBe(33);
        expect($dto->bio)->toBe('Painter');
        // Secret excluded from toArray
        expect($dto->toArray())->not->toHaveKey('secret');
    });
});

describe('MixedCollectionDTO — NestedArray vs Collection distinction', function () {
    it('hydrates NestedArray as plain array', function () {
        $dto = MixedCollectionDTO::fromArray([
            'orderId' => 'ORD-001',
            'items' => [
                ['street' => '123 Main St', 'city' => 'Istanbul'],
                ['street' => '456 Oak Ave', 'city' => 'Ankara'],
            ],
            'orders' => [
                ['name' => 'Widget A', 'price' => '9.99'],
                ['name' => 'Widget B', 'price' => '14.99'],
            ],
        ]);

        expect($dto->items)->toBeArray();
        expect($dto->items)->toHaveCount(2);
        expect($dto->items[0])->toBeInstanceOf(AddressDTO::class);
        expect($dto->items[0]->city)->toBe('Istanbul');
    });

    it('hydrates Collection as DtoCollection', function () {
        $dto = MixedCollectionDTO::fromArray([
            'orderId' => 'ORD-002',
            'items' => [],
            'orders' => [
                ['name' => 'Gadget X', 'price' => '29.99'],
            ],
        ]);

        expect($dto->orders)->toBeInstanceOf(DtoCollection::class);
        expect($dto->orders->count())->toBe(1);
        expect($dto->orders->first())->toBeInstanceOf(OrderItemDTO::class);
    });

    it('DtoCollection provides pluck on Collection property', function () {
        $dto = MixedCollectionDTO::fromArray([
            'orderId' => 'ORD-003',
            'items' => [],
            'orders' => [
                ['name' => 'Alpha', 'price' => '10.00'],
                ['name' => 'Beta', 'price' => '20.00'],
            ],
        ]);

        $names = $dto->orders->pluck('name');

        expect($names)->toBe(['Alpha', 'Beta']);
    });

    it('serializes NestedArray and Collection recursively', function () {
        $dto = MixedCollectionDTO::fromArray([
            'orderId' => 'ORD-004',
            'items' => [
                ['street' => '1 Test St', 'city' => 'Test City'],
            ],
            'orders' => [
                ['name' => 'Item 1', 'price' => '5.00'],
            ],
        ]);

        $array = $dto->toArray();

        expect($array['items'][0])->toBe(['street' => '1 Test St', 'city' => 'Test City']);
        expect($array['orders'][0])->toBe(['name' => 'Item 1', 'price' => '5.00']);
    });

    it('isEmpty returns false when orderId is set', function () {
        $dto = MixedCollectionDTO::fromArray([
            'orderId' => 'ORD-005',
            'items' => [],
            'orders' => [],
        ]);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('RoundtripDTO with boolean casting edge cases', function () {
    it('casts "true" string to boolean true', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => 'true',
        ]);

        expect($dto->active)->toBeTrue();
    });

    it('casts "false" string to boolean false', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => 'false',
        ]);

        expect($dto->active)->toBeFalse();
    });

    it('casts "0" to boolean false', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => '0',
        ]);

        expect($dto->active)->toBeFalse();
    });

    it('casts "1" to boolean true', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => '1',
        ]);

        expect($dto->active)->toBeTrue();
    });

    it('casts integer 0 to boolean false', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => 0,
        ]);

        expect($dto->active)->toBeFalse();
    });

    it('casts integer 1 to boolean true', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => 1,
        ]);

        expect($dto->active)->toBeTrue();
    });
});
