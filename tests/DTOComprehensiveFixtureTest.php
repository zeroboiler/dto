<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection — basic operations', function (): void {
    it('creates from array of DTOs', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);

        expect($collection->count())->toBe(2);
        expect($collection->isEmpty())->toBeFalse();
        expect($collection->isNotEmpty())->toBeTrue();
    });

    it('creates empty collection', function (): void {
        $collection = new DtoCollection();

        expect($collection->count())->toBe(0);
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();
    });

    it('first() and last() return correct items', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);

        $collection = new DtoCollection([$d1, $d2, $d3]);

        expect($collection->first()->email)->toBe('a@b.com');
        expect($collection->last()->email)->toBe('e@f.com');
    });

    it('first() returns null for empty collection', function (): void {
        $collection = new DtoCollection();

        expect($collection->first())->toBeNull();
    });

    it('last() returns null for empty collection', function (): void {
        $collection = new DtoCollection();

        expect($collection->last())->toBeNull();
    });
});

describe('DtoCollection — pluck and pluckKey', function (): void {
    it('plucks single field from all DTOs', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    it('plucks key/value pairs', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('pluckKey without valueField returns full toArray', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        $collection = new DtoCollection([$d1]);
        $map = $collection->pluckKey('email');

        expect($map)->toBeArray();
        expect($map)->toHaveKey('a@b.com');
    });
});

describe('DtoCollection — map and filter', function (): void {
    it('maps over items and returns plain array', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('filters items and returns new collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'status' => 'inactive'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $active = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->status === 'active');

        expect($active->count())->toBe(1);
        expect($active->first()->name)->toBe('Alice');
    });

    it('filter returns empty collection when nothing matches', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'], validate: false);

        $collection = new DtoCollection([$d1]);
        $filtered = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->status === 'deleted');

        expect($filtered->count())->toBe(0);
        expect($filtered->isEmpty())->toBeTrue();
    });
});

describe('DtoCollection — append and merge (immutable)', function (): void {
    it('append returns new collection with added item', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1]);
        $appended = $collection->append($d2);

        expect($collection->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended->last()->name)->toBe('Charlie');
    });

    it('merge returns new collection with combined items', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);

        $col1 = new DtoCollection([$d1]);
        $col2 = new DtoCollection([$d2, $d3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });
});

describe('DtoCollection — push (mutating)', function (): void {
    it('push adds item and returns same collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1]);
        $result = $collection->push($d2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection); // same instance
    });
});

describe('DtoCollection — ArrayAccess', function (): void {
    it('offsetExists, offsetGet, offsetSet work', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1]);

        expect(isset($collection[0]))->toBeTrue();
        expect($collection[0]->email)->toBe('a@b.com');
        expect(isset($collection[1]))->toBeFalse();

        $collection[1] = $d2;
        expect($collection->count())->toBe(2);
    });

    it('offsetUnset removes and re-indexes', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);

        $collection = new DtoCollection([$d1, $d2, $d3]);
        unset($collection[1]);

        expect($collection->count())->toBe(2);
        expect($collection[1]->email)->toBe('e@f.com'); // re-indexed
    });
});

describe('DtoCollection — items() and toArray()', function (): void {
    it('items() returns raw DTO instances', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$d1]);

        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });

    it('toArray() returns serialized arrays', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$d1]);

        $arrays = $collection->toArray();

        expect($arrays)->toHaveCount(1);
        expect($arrays[0])->toBeArray();
        expect($arrays[0])->toHaveKey('email');
    });

    it('allValues() includes hidden fields', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
        $collection = new DtoCollection([$d1]);

        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });

    it('toArray() excludes hidden fields', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
        $collection = new DtoCollection([$d1]);

        $public = $collection->toArray();

        expect($public[0])->not->toHaveKey('password');
    });
});

describe('DtoCollection — rejects non-DTO items', function (): void {
    it('throws on construction with non-DTO', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws on offsetSet with non-DTO', function (): void {
        $collection = new DtoCollection();

        expect(fn () => $collection[0] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('DtoCollection — static make factory', function (): void {
    it('creates from array', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        $collection = DtoCollection::make([$d1]);

        expect($collection->count())->toBe(1);
    });

    it('creates empty from no arguments', function (): void {
        $collection = DtoCollection::make();

        expect($collection->isEmpty())->toBeTrue();
    });
});

describe('DtoCollection — jsonSerialize', function (): void {
    it('serializes to JSON array', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$d1]);

        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0]['email'])->toBe('a@b.com');
    });
});

describe('DtoCollection — iteration', function (): void {
    it('iterates via foreach', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $names = [];

        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Charlie']);
    });
});

describe('fromJson — error handling', function (): void {
    it('throws DTOException for invalid JSON syntax', function (): void {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function (): void {
        expect(fn () => CreateUserDTO::fromJson('["email","a@b.com"]'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON null', function (): void {
        expect(fn () => CreateUserDTO::fromJson('null'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON boolean', function (): void {
        expect(fn () => CreateUserDTO::fromJson('true'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON number', function (): void {
        expect(fn () => CreateUserDTO::fromJson('42'))
            ->toThrow(DTOException::class);
    });

    it('successfully parses valid JSON object', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"test@example.com","name":"Test"}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });
});

describe('Nested DTO hydration and serialization', function (): void {
    it('hydrates nested DTO from array', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'label' => 'Primary',
        ], validate: false);

        expect($dto->id)->toBe('order-123');
        expect($dto->address)->toBeInstanceOf(AddressDTO::class);
        expect($dto->address->street)->toBe('123 Main St');
        expect($dto->address->city)->toBe('Springfield');
        expect($dto->label)->toBe('Primary');
    });

    it('serializes nested DTO recursively', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '62701',
            ],
            'label' => 'Primary',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['id'])->toBe('order-123');
        expect($array['address'])->toBeArray();
        expect($array['address']['street'])->toBe('123 Main St');
        expect($array['address']['city'])->toBe('Springfield');
        expect($array['address']['zipCode'])->toBe('62701');
    });

    it('round-trips nested DTO through JSON', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'label' => 'Primary',
        ], validate: false);

        $json = $dto->toJson();
        $restored = DeepNestedDTO::fromJson($json, validate: false);

        expect($restored->id)->toBe('order-123');
        expect($restored->address->street)->toBe('123 Main St');
    });
});

describe('fromPartialArray — partial updates', function (): void {
    it('hydrates only provided fields with defaults', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validate: false);

        expect($dto->name)->toBe('Updated Name');
        expect($dto->status)->toBe('active'); // default
    });

    it('preserves existing values via with()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob']);

        expect($updated->email)->toBe('a@b.com');
        expect($updated->name)->toBe('Bob');
        expect($updated->status)->toBe('active');
    });

    it('handles empty partial data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->status)->toBe('active'); // default
    });
});

describe('only() and except() — selective output', function (): void {
    it('only returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['email', 'name']);
    });

    it('only with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result['email'])->toBe('a@b.com');
    });

    it('except removes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        expect($result)->not->toHaveKey('status');
        expect($result)->toHaveKey('email');
    });

    it('only ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1);
    });
});

describe('isEmpty and isNotEmpty — state checks', function (): void {
    it('EmptyDTO with all nulls is empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with required fields populated is not empty', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 42,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('DTO with zero int is not empty', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '0.00',
            'stock' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('MapFrom — field aliasing', function (): void {
    it('maps source key to property name', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('uses property name when mapped key not present', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });
});

describe('Cast — type casting', function (): void {
    it('casts array from JSON string', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'tags' => '["laravel","php"]',
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });

    it('casts empty JSON string to empty array', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });
});

describe('Validation rules derivation', function (): void {
    it('derives correct rules for CreateUserDTO', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('derives correct rules for ProductDTO', function (): void {
        $rules = ProductDTO::rules();

        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:1');
        expect($rules['name'])->toContain('max:255');
        expect($rules['price'])->toContain('required');
        expect($rules['price'])->toContain('numeric');
        expect($rules['stock'])->toContain('integer');
        expect($rules['stock'])->toContain('min:0');
    });
});
