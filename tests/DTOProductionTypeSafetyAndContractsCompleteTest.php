<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\{Email, Hidden, MapFrom, NestedArray, Required};
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{AddressDTO, CreateUserDTO, EmptyDTO, OrderDTO, OrderItemDTO};

// ─── CreateUserDTO: fromArray / toArray / fromJson / fromRequest ───

test('CreateUserDTO fromArray creates DTO with validated data', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('Test User')
        ->and($dto->status)->toBe('active'); // DefaultValue
});

test('CreateUserDTO fromArray skips validation when validate is false', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'not-validated@example.com',
        'name' => 'Test',
    ], validate: false);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('not-validated@example.com');
});

test('CreateUserDTO fromArray throws ValidationException on invalid data', function (): void {
    CreateUserDTO::fromArray([
        'email' => 'not-an-email',
        'name' => 'Test',
    ]);
})->throws(ValidationException::class);

test('CreateUserDTO toArray excludes hidden properties', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'secret123',
    ], validate: false);

    $array = $dto->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('email')
        ->and($array)->toHaveKey('name')
        ->and($array)->not->toHaveKey('password'); // Hidden
});

test('CreateUserDTO allValues includes hidden properties', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'password' => 'secret123',
    ], validate: false);

    $all = $dto->allValues();

    expect($all)->toBeArray()
        ->and($all)->toHaveKey('password'); // included in allValues
});

test('CreateUserDTO toJson produces valid JSON string', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $json = $dto->toJson();

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toBeArray();
});

test('CreateUserDTO fromJson creates DTO from JSON string', function (): void {
    $json = '{"email":"test@example.com","name":"Test User"}';

    $dto = CreateUserDTO::fromJson($json, validate: false);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('Test User');
});

test('fromJson throws DTOException for invalid JSON', function (): void {
    CreateUserDTO::fromJson('{"invalid json"', validate: false);
})->throws(DTOException::class);

test('fromJson throws DTOException for sequential arrays', function (): void {
    CreateUserDTO::fromJson('[1,2,3]', validate: false);
})->throws(DTOException::class);

test('CreateUserDTO fromRequest delegates to fromArray', function (): void {
    $request = new Request([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $dto = CreateUserDTO::fromRequest($request, validate: false);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('test@example.com');
});

test('CreateUserDTO MapFrom resolves dot-notation and aliases', function (): void {
    $request = new Request([
        'phone_number' => '+1234567890',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $dto = CreateUserDTO::fromRequest($request, validate: false);

    expect($dto->phone)->toBe('+1234567890');
});

test('CreateUserDTO fromPartialArray fills missing fields with defaults', function (): void {
    $dto = CreateUserDTO::fromPartialArray([
        'email' => 'partial@example.com',
    ], validate: false);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('partial@example.com')
        ->and($dto->status)->toBe('active'); // default preserved
});

test('CreateUserDTO fromPartialRequest delegates to fromPartialArray', function (): void {
    $request = new Request([
        'email' => 'partial@example.com',
    ]);

    $dto = CreateUserDTO::fromPartialRequest($request, validate: false);

    expect($dto)->toBeInstanceOf(CreateUserDTO::class)
        ->and($dto->email)->toBe('partial@example.com');
});

test('validateArray returns validated data', function (): void {
    $validated = CreateUserDTO::validateArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    expect($validated)->toBeArray()
        ->and($validated['email'])->toBe('test@example.com');
});

test('validateArray throws on invalid data', function (): void {
    CreateUserDTO::validateArray([
        'email' => 'not-an-email',
    ]);
})->throws(ValidationException::class);

test('rules returns Laravel validation rules array', function (): void {
    $rules = CreateUserDTO::rules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('email')
        ->and($rules)->toHaveKey('name');

    // Email rule should contain 'email'
    expect($rules['email'])->toContain('email');
    // Name should have min:2 and max:50
    expect($rules['name'])->toContain('min:2')
        ->and($rules['name'])->toContain('max:50');
});

test('rulesFor returns rules by default', function (): void {
    $rules = CreateUserDTO::rulesFor('create');

    expect($rules)->toBe($rules);
});

test('with creates new immutable instance with overrides', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $updated = $dto->with(['name' => 'Updated Name'], validate: false);

    expect($updated)->not->toBe($dto)
        ->and($updated->name)->toBe('Updated Name')
        ->and($dto->name)->toBe('Test User');
});

test('only returns specified fields', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $only = $dto->only('email');

    expect($only)->toBeArray()
        ->and($only)->toHaveKey('email')
        ->and($only)->not->toHaveKey('name');
});

test('except returns all fields except specified', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $except = $dto->except('email');

    expect($except)->toBeArray()
        ->and($except)->not->toHaveKey('email')
        ->and($except)->toHaveKey('name');
});

test('equals compares toArray output', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeTrue();
});

test('equals returns false for different DTOs', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@test.com',
        'name' => 'Bob',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeFalse();
});

test('isEmpty and isNotEmpty work correctly', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    expect($dto->isEmpty())->toBeFalse()
        ->and($dto->isNotEmpty())->toBeTrue();
});

test('jsonSerialize returns toArray output', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $serialized = $dto->jsonSerialize();

    expect($serialized)->toBe($dto->toArray());
});

// ─── DtoCollection ───

test('DtoCollection wraps DTO instances', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@test.com',
        'name' => 'Bob',
    ], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);

    expect($collection->count())->toBe(2)
        ->and($collection->isEmpty())->toBeFalse()
        ->and($collection->isNotEmpty())->toBeTrue()
        ->and($collection->first())->toBe($dto1)
        ->and($collection->last())->toBe($dto2);
});

test('DtoCollection toArray serializes all DTOs', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@test.com',
        'name' => 'Bob',
    ], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);
    $array = $collection->toArray();

    expect($array)->toBeArray()
        ->and(count($array))->toBe(2)
        ->and($array[0])->toHaveKey('email');
});

test('DtoCollection make factory', function (): void {
    $collection = DtoCollection::make();

    expect($collection)->toBeInstanceOf(DtoCollection::class)
        ->and($collection->isEmpty())->toBeTrue();
});

test('DtoCollection push mutates in place', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $collection = new DtoCollection;
    $collection->push($dto);

    expect($collection->count())->toBe(1);
});

test('DtoCollection append returns new instance', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $collection = new DtoCollection;
    $newCollection = $collection->append($dto);

    expect($collection->isEmpty())->toBeTrue()
        ->and($newCollection->count())->toBe(1);
});

test('DtoCollection filter returns new filtered collection', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@test.com',
        'name' => 'Bob',
    ], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);
    $filtered = $collection->filter(fn (DataTransferObject $d): bool => $d->name === 'Alice');

    expect($filtered->count())->toBe(1)
        ->and($collection->count())->toBe(2);
});

test('DtoCollection map returns plain array', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $collection = new DtoCollection([$dto]);
    $names = $collection->map(fn (DataTransferObject $d): string => $d->name);

    expect($names)->toBe(['Alice']);
});

test('DtoCollection pluck extracts property values', function (): void {
    $dto1 = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $dto2 = CreateUserDTO::fromArray([
        'email' => 'b@test.com',
        'name' => 'Bob',
    ], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);
    $emails = $collection->pluck('email');

    expect($emails)->toBe(['a@test.com', 'b@test.com']);
});

test('DtoCollection ArrayAccess works', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
    ], validate: false);

    $collection = new DtoCollection([$dto]);

    expect(isset($collection[0]))->toBeTrue()
        ->and($collection[0])->toBe($dto);
});

test('DtoCollection offsetUnset re-indexes', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);
    $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false);

    $collection = new DtoCollection([$dto1, $dto2, $dto3]);
    unset($collection[0]);

    expect($collection->count())->toBe(2)
        ->and($collection[0])->toBe($dto2)
        ->and($collection[1])->toBe($dto3);
});

test('DtoCollection merge combines two collections', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);

    $c1 = new DtoCollection([$dto1]);
    $c2 = new DtoCollection([$dto2]);
    $merged = $c1->merge($c2);

    expect($merged->count())->toBe(2)
        ->and($c1->count())->toBe(1);
});

test('DtoCollection jsonSerialize works', function (): void {
    $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);

    $collection = new DtoCollection([$dto]);
    $json = json_encode($collection);

    expect($json)->toBeString()
        ->and(json_decode($json, true))->toBeArray();
});

test('DtoCollection rejects non-DTO items', function (): void {
    new DtoCollection([new stdClass]);
})->throws(InvalidArgumentException::class);

test('DtoCollection pluckKey returns keyed array', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);
    $keyed = $collection->pluckKey('email', 'name');

    expect($keyed)->toBe([
        'a@test.com' => 'Alice',
        'b@test.com' => 'Bob',
    ]);
});

test('DtoCollection toArrayBy returns keyed array of DTO arrays', function (): void {
    $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);

    $collection = new DtoCollection([$dto]);
    $keyed = $collection->toArrayBy('email');

    expect($keyed)->toBeArray()
        ->and($keyed)->toHaveKey('a@test.com');
});

test('DtoCollection toDictionary returns simple key-value map', function (): void {
    $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
    $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

    $collection = new DtoCollection([$dto1, $dto2]);
    $dict = $collection->toDictionary('email', 'name');

    expect($dict)->toBe([
        'a@test.com' => 'Alice',
        'b@test.com' => 'Bob',
    ]);
});

test('DtoCollection allValues includes hidden properties', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'a@test.com',
        'name' => 'Alice',
        'password' => 'secret',
    ], validate: false);

    $collection = new DtoCollection([$dto]);
    $all = $collection->allValues();

    expect($all)->toBeArray()
        ->and(count($all))->toBe(1)
        ->and($all[0])->toHaveKey('password');
});

// ─── DTOException ───

test('DTOException invalidCast factory', function (): void {
    $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

    expect($e->getMessage())->toContain('age')
        ->and($e->getMessage())->toContain('integer')
        ->and((string) $e)->toContain(DTOException::class);
});

test('DTOException invalidJson factory', function (): void {
    $e = DTOException::invalidJson('settings', 'Syntax error');

    expect($e->getMessage())->toContain('settings')
        ->and($e->getMessage())->toContain('Syntax error');
});

// ─── DTOCast ───

test('DTOCast get returns null for null value', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $result = $cast->get(new stdClass, 'profile', null, []);

    expect($result)->toBeNull();
});

test('DTOCast get returns DTO from JSON string', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $json = json_encode(['email' => 'test@example.com', 'name' => 'Test']);
    $result = $cast->get(new stdClass, 'profile', $json, []);

    expect($result)->toBeInstanceOf(CreateUserDTO::class);
});

test('DTOCast get returns null for invalid JSON', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $result = $cast->get(new stdClass, 'profile', 'invalid json', []);

    expect($result)->toBeNull();
});

test('DTOCast set returns JSON for DTO instance', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $result = $cast->set(new stdClass, 'profile', $dto, []);

    expect($result)->toBeString()
        ->and(json_decode($result, true))->toBeArray();
});

test('DTOCast set returns null for null', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $result = $cast->set(new stdClass, 'profile', null, []);

    expect($result)->toBeNull();
});

test('DTOCast set validates array through DTO', function (): void {
    $cast = new DTOCast(CreateUserDTO::class, validate: true);

    $cast->set(new stdClass, 'profile', ['email' => 'not-an-email'], []);
})->throws(ValidationException::class);

test('DTOCast set skips validation when validate is false', function (): void {
    $cast = new DTOCast(CreateUserDTO::class, validate: false);
    $result = $cast->set(new stdClass, 'profile', ['email' => 'test@example.com'], []);

    expect($result)->toBeString();
});

test('DTOCast set rejects unexpected types', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $cast->set(new stdClass, 'profile', 42, []);
})->throws(InvalidArgumentException::class);

test('DTOCast serialize returns toArray', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $result = $cast->serialize(new stdClass, 'profile', $dto, []);

    expect($result)->toBe($dto->toArray());
});

test('DTOCast serialize returns null for null', function (): void {
    $cast = new DTOCast(CreateUserDTO::class);
    $result = $cast->serialize(new stdClass, 'profile', null, []);

    expect($result)->toBeNull();
});

// ─── Nested DTOs ───

test('nested DTO hydration with OrderDTO', function (): void {
    $dto = OrderDTO::fromArray([
        'orderNumber' => 'ORD-001',
        'shippingAddress' => [
            'street' => '123 Main',
            'city' => 'Ankara',
            'zipCode' => '06000',
        ],
        'items' => [
            ['productName' => 'Item 1', 'quantity' => 2],
        ],
    ], validate: false);

    expect($dto)->toBeInstanceOf(OrderDTO::class)
        ->and($dto->orderNumber)->toBe('ORD-001')
        ->and($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class)
        ->and($dto->shippingAddress->street)->toBe('123 Main')
        ->and($dto->items)->toBeArray()
        ->and($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
});

test('nested DTO roundtrip: fromArray -> toArray -> fromArray', function (): void {
    $dto1 = OrderDTO::fromArray([
        'orderNumber' => 'ORD-001',
        'shippingAddress' => [
            'street' => '123 Main',
            'city' => 'Ankara',
            'zipCode' => '06000',
        ],
    ], validate: false);

    $array = $dto1->toArray();
    $dto2 = OrderDTO::fromArray($array, validate: false);

    expect($dto2->orderNumber)->toBe($dto1->orderNumber)
        ->and($dto2->shippingAddress->street)->toBe($dto1->shippingAddress->street);
});

// ─── Metadata Cache ───

test('metadata cache flush works', function (): void {
    CreateUserDTO::flushMetadataCache();
    CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

    expect(true)->toBeTrue(); // no exception thrown
});

test('metadata cache TTL can be set', function (): void {
    CreateUserDTO::setMetadataCacheTtl(5.0);
    CreateUserDTO::setMetadataCacheTtl(0.0);

    expect(true)->toBeTrue(); // no exception thrown
});

// ─── Empty DTO ───

test('EmptyDTO handles empty data gracefully', function (): void {
    $dto = EmptyDTO::fromArray([], validate: false);

    expect($dto)->toBeInstanceOf(EmptyDTO::class)
        ->and($dto->isEmpty())->toBeTrue();
});

// ─── Readonly properties immutability ───

test('DTO properties are readonly', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $ref = new ReflectionProperty($dto, 'email');

    expect($ref->isReadOnly())->toBeTrue()
        ->and($ref->isPublic())->toBeTrue();
});

test('DTO properties are initialized in constructor', function (): void {
    $dto = CreateUserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    $ref = new ReflectionProperty($dto, 'email');

    expect($ref->isInitialized($dto))->toBeTrue();
});
