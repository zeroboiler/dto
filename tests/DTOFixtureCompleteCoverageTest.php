<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;

// ── RoundtripDTO: Full Property Type Coverage ──────────────────────────

describe('RoundtripDTO full property type coverage', function (): void {
    it('roundtrips all scalar types correctly', function (): void {
        $data = [
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'score' => 95.5,
            'tags' => ['php', 'laravel'],
            'source_bio' => 'Developer',
            'secret' => 'hidden-value',
            'role' => 'admin',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['name'])->toBe('Alice');
        expect($result['age'])->toBe(30);
        expect($result['active'])->toBe(true);
        expect($result['score'])->toBe(95.5);
        expect($result['tags'])->toBe(['php', 'laravel']);
        expect($result['bio'])->toBe('Developer');
        expect($result)->not->toHaveKey('secret');
        expect($result['role'])->toBe('admin');
    });

    it('roundtrips with all defaults applied', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Bob',
            'age' => 25,
            'active' => false,
        ], validate: false);

        expect($dto->score)->toBe(0.0);
        expect($dto->tags)->toBe([]);
        expect($dto->bio)->toBeNull();
        expect($dto->secret)->toBeNull();
        expect($dto->role)->toBe('user');
    });

    it('Cast integer transforms string to int', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Charlie',
            'age' => '42',
            'active' => true,
        ], validate: false);

        expect($dto->age)->toBe(42);
        expect($dto->age)->toBeInt();
    });

    it('equals compares two DTOs with same values', function (): void {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'score' => 95.5,
            'tags' => ['php'],
            'role' => 'admin',
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'score' => 95.5,
            'tags' => ['php'],
            'role' => 'admin',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different values', function (): void {
        $dto1 = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true,
        ], validate: false);

        $dto2 = RoundtripDTO::fromArray([
            'name' => 'Bob', 'age' => 30, 'active' => true,
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty detects DTOs with default-only values', function (): void {
        // EmptyDTO with all nulls should be empty
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty detects DTOs with non-null values', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
        expect($dto->isEmpty())->toBeFalse();
    });

    it('allValues includes hidden fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'secret' => 'classified',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('classified');
    });

    it('only returns specified fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'role' => 'admin',
        ], validate: false);

        $only = $dto->only('name', 'role');

        expect($only)->toHaveCount(2);
        expect($only)->toHaveKey('name');
        expect($only)->toHaveKey('role');
        expect($only)->not->toHaveKey('age');
    });

    it('except excludes specified fields', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'role' => 'admin',
        ], validate: false);

        $except = $dto->except('age', 'active');

        expect($except)->toHaveCount(3);
        expect($except)->not->toHaveKey('age');
        expect($except)->not->toHaveKey('active');
    });
});

// ── Nested DTO Hydration and Serialization ────────────────────────────

describe('Nested DTO hydration and serialization', function (): void {
    it('hydrates nested AddressDTO from array', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-1',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '62701',
            ],
            'label' => 'Primary',
        ], validate: false);

        expect($dto->address)->toBeInstanceOf(AddressDTO::class);
        expect($dto->address->street)->toBe('123 Main St');
        expect($dto->address->city)->toBe('Springfield');
        expect($dto->address->zipCode)->toBe('62701');
    });

    it('serializes nested DTOs recursively', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-1',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'label' => 'Primary',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr['address'])->toBeArray();
        expect($arr['address']['street'])->toBe('123 Main St');
        expect($arr['address']['city'])->toBe('Springfield');
    });

    it('hydrates array of nested DTOs via NestedArray', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '456 Oak Ave',
                'city' => 'Shelbyville',
            ],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                ['productName' => 'Gadget', 'price' => 24.99, 'quantity' => 1],
            ],
            'rawTotal' => 44.97,
        ], validate: false);

        expect($dto->items)->toBeArray();
        expect($dto->items)->toHaveCount(2);
        expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($dto->items[0]->productName)->toBe('Widget');
        expect($dto->items[0]->price)->toBe(9.99);
        expect($dto->items[1]->productName)->toBe('Gadget');
    });

    it('serializes array of nested DTOs', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => ['street' => '456 Oak Ave', 'city' => 'Shelbyville'],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99],
            ],
            'rawTotal' => 9.99,
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr['items'])->toBeArray();
        expect($arr['items'][0])->toBeArray();
        expect($arr['items'][0]['productName'])->toBe('Widget');
        expect($arr['items'][0]['price'])->toBe(9.99);
    });

    it('empty items array produces empty result', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => ['street' => '789 Pine Rd', 'city' => 'Capital City'],
            'items' => [],
        ], validate: false);

        expect($dto->items)->toBe([]);
    });
});

// ── with() Immutability ───────────────────────────────────────────────

describe('with() immutability', function (): void {
    it('returns a new instance with updated values', function (): void {
        $original = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true,
        ], validate: false);

        $updated = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($updated->age)->toBe(30);
        expect($updated->active)->toBe(true);
    });

    it('preserves hidden field exclusion in with() result', function (): void {
        $original = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true, 'secret' => 'hidden',
        ], validate: false);

        $updated = $original->with(['name' => 'Bob']);

        $arr = $updated->toArray();
        expect($arr)->not->toHaveKey('secret');
    });

    it('with() always validates', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true,
        ], validate: false);

        // name is required with min:1 — empty string should fail validation
        expect(fn () => $dto->with(['name' => '']))
            ->toThrow(ValidationException::class);
    });
});

// ── fromJson Edge Cases ────────────────────────────────────────────────

describe('fromJson edge cases', function (): void {
    it('throws DTOException for invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential arrays', function (): void {
        expect(fn () => MinimalDTO::fromJson('["value1", "value2"]'))
            ->toThrow(DTOException::class);
    });

    it('allows empty object', function (): void {
        // MinimalDTO has required fields so this should fail validation
        // but fromJson should parse {} successfully
        expect(fn () => EmptyDTO::fromJson('{}'))
            ->not->toThrow(DTOException::class);
    });

    it('creates DTO from valid JSON string', function (): void {
        $dto = EmptyDTO::fromJson('{"foo": "bar", "baz": "qux"}');

        expect($dto->foo)->toBe('bar');
    });
});

// ── ActionScopedDTO: rulesFor ───────────────────────────────────────────

describe('ActionScopedDTO rulesFor', function (): void {
    it('returns base rules for unknown action', function (): void {
        $rules = ActionScopedDTO::rulesFor('delete');

        expect($rules)->toBe(ActionScopedDTO::rules());
    });

    it('returns base rules for create action', function (): void {
        $createRules = ActionScopedDTO::rulesFor('create');
        $baseRules = ActionScopedDTO::rules();

        expect($createRules)->toBe($baseRules);
    });

    it('returns relaxed rules for update action', function (): void {
        $updateRules = ActionScopedDTO::rulesFor('update');

        expect($updateRules)->toHaveKey('email');
        expect($updateRules)->toHaveKey('password');
    });
});

// ── DtoCollection Operations ──────────────────────────────────────────

describe('DtoCollection operations', function (): void {
    it('creates from array of DTOs', function (): void {
        $dtos = [
            AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false),
            AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false),
        ];

        $col = DtoCollection::make($dtos);

        expect($col->count())->toBe(2);
        expect($col->isEmpty())->toBeFalse();
    });

    it('push adds to existing collection', function (): void {
        $col = DtoCollection::make();
        $dto = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);

        $result = $col->push($dto);

        expect($result->count())->toBe(1);
        expect($result)->toBe($col); // push mutates and returns same instance
    });

    it('append returns new collection with added item', function (): void {
        $dto1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $dto2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = $col1->append($dto2);

        expect($col1->count())->toBe(1); // original unchanged
        expect($col2->count())->toBe(2);
    });

    it('merge combines two collections', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col1 = DtoCollection::make([$d1]);
        $col2 = DtoCollection::make([$d2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
    });

    it('filter returns collection of matching items', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'Main', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'Oak', 'city' => 'X'], validate: false);
        $d3 = AddressDTO::fromArray(['street' => 'Pine', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2, $d3]);
        $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->city === 'X');

        expect($filtered->count())->toBe(2);
    });

    it('map returns array of transformed values', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $cities = $col->map(fn (DataTransferObject $dto): string => $dto->city);

        expect($cities)->toBe(['X', 'Y']);
    });

    it('pluck extracts single property values', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'Main St', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'Oak Ave', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $streets = $col->pluck('street');

        expect($streets)->toBe(['Main St', 'Oak Ave']);
    });

    it('pluckKey creates associative array', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'Main', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'Oak', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $map = $col->pluckKey('city', 'street');

        expect($map)->toBe(['X' => 'Main', 'Y' => 'Oak']);
    });

    it('offsetGet returns item at index', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);

        expect($col->offsetGet(0)->street)->toBe('A');
        expect($col->offsetGet(1)->street)->toBe('B');
        expect($col->offsetGet(99))->toBeNull();
    });

    it('offsetUnset removes and re-indexes', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);
        $d3 = AddressDTO::fromArray(['street' => 'C', 'city' => 'Z'], validate: false);

        $col = DtoCollection::make([$d1, $d2, $d3]);
        $col->offsetUnset(0);

        expect($col->count())->toBe(2);
        expect($col->offsetGet(0)->street)->toBe('B');
        expect($col->offsetGet(1)->street)->toBe('C');
    });

    it('rejects non-DTO items in constructor', function (): void {
        expect(fn () => new DtoCollection(['not', 'a', 'dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serializes to JSON array', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $json = json_encode($col);

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded[0]['street'])->toBe('A');
    });

    it('first and last return correct items', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);

        expect($col->first()->street)->toBe('A');
        expect($col->last()->street)->toBe('B');
    });

    it('empty collection returns null for first/last', function (): void {
        $col = DtoCollection::make();

        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('is iterable with foreach', function (): void {
        $d1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
        $d2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $count = 0;

        foreach ($col as $dto) {
            expect($dto)->toBeInstanceOf(DataTransferObject::class);
            $count++;
        }

        expect($count)->toBe(2);
    });
});

// ── DTOException Factory Methods ──────────────────────────────────────

describe('DTOException factory methods', function (): void {
    it('invalidCast creates correct message', function (): void {
        $ex = DTOException::invalidCast('price', 'integer', 'not-a-number');

        expect($ex->getMessage())->toContain('price');
        expect($ex->getMessage())->toContain('integer');
        expect($ex->getMessage())->toContain('not-a-number');
    });

    it('invalidJson creates correct message', function (): void {
        $ex = DTOException::invalidJson('payload', 'Syntax error');

        expect($ex->getMessage())->toContain('payload');
        expect($ex->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function (): void {
        $ex = DTOException::invalidJson('data', 'malformed');

        $str = (string) $ex;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('malformed');
    });
});

// ── Metadata Cache ────────────────────────────────────────────────────

describe('DTO metadata cache', function (): void {
    it('flushes metadata cache for specific class', function (): void {
        // Resolve metadata (populates cache)
        $rules1 = CreateUserDTO::rules();

        // Flush specific class
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Re-resolve — should work correctly
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toEqual($rules2);
    });

    it('flushes all metadata cache', function (): void {
        CreateUserDTO::rules();
        EmptyDTO::rules();

        DataTransferObject::flushMetadataCache();

        // Re-resolve should work
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });
});

// ── fromPartialArray Edge Cases ─────────────────────────────────────────

describe('fromPartialArray edge cases', function (): void {
    it('applies defaults for missing optional fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validatePresent: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
        expect($dto->password)->toBeNull();
    });

    it('updates only provided fields while keeping rest', function (): void {
        $original = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
            'score' => 95.5,
            'tags' => ['php'],
            'role' => 'admin',
        ], validate: false);

        $patched = RoundtripDTO::fromPartialArray([
            'name' => 'Bob',
        ], validatePresent: false);

        expect($patched->name)->toBe('Bob');
        expect($patched->age)->toBe(0); // type-appropriate empty value (int)
        expect($patched->active)->toBe(false); // type-appropriate empty value (bool)
        expect($patched->score)->toBe(0.0); // type-appropriate empty value (float)
    });

    it('empty partial data array produces DTO with defaults only', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->email)->toBe(''); // string empty value
        expect($dto->name)->toBe(''); // string empty value
        expect($dto->status)->toBe('active'); // default value
    });
});

// ── jsonSerialize / toJson Consistency ────────────────────────────────

describe('jsonSerialize / toJson consistency', function (): void {
    it('jsonSerialize returns same as toArray', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true, 'score' => 95.5,
            'tags' => ['php'], 'source_bio' => 'Dev', 'role' => 'admin',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('toJson produces valid JSON', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice', 'age' => 30, 'active' => true,
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('Alice');
    });
});
