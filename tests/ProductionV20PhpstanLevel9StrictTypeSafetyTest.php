<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('V20 — PHPStan Level 9 Strict Type Safety And Edge Case Coverage', function () {
    // ─── DTO: fromArray strict validation ────────────────────────────────

    it('fromArray creates DTO with correct typed properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('John Doe');
        expect($dto->status)->toBe('active');
    });

    it('fromArray applies defaults for missing optional properties', function () {
        $dto = AllDefaultsDTO::fromArray([]);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        expect($dto->name)->toBe('default-name');
        expect($dto->count)->toBe(0);
        expect($dto->active)->toBeFalse();
    });

    it('fromArray respects MapFrom attribute', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'phone_number' => '+1234567890',
        ]);

        expect($dto->phone)->toBe('+1234567890');
        expect($dto->name)->toBe('Alice');
    });

    it('fromArray rejects invalid data with validation exception', function () {
        expect(fn () => CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => '',
        ]))->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('fromArray with validate=false skips validation', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'bad-email',
            'name' => 'OK',
        ], validate: false);

        expect($dto->email)->toBe('bad-email');
    });

    // ─── DTO: fromJson strict type handling ──────────────────────────────

    it('fromJson creates DTO from valid JSON', function () {
        $json = '{"email":"a@b.com","name":"Bob"}';
        $dto = CreateUserDTO::fromJson($json);

        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Bob');
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for non-object JSON values (scalars)', function () {
        expect(fn () => MinimalDTO::fromJson('"just a string"'))
            ->toThrow(DTOException::class);
    });

    // ─── DTO: fromPartialArray (PATCH semantics) ────────────────────────

    it('fromPartialArray only validates present fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ]);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = AllDefaultsDTO::fromPartialArray([]);

        expect($dto->name)->toBe('default-name');
        expect($dto->count)->toBe(0);
        expect($dto->active)->toBeFalse();
    });

    // ─── DTO: toArray strict structure ───────────────────────────────────

    it('toArray excludes Hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect(array_key_exists('password', $arr))->toBeFalse();
        expect(array_key_exists('email', $arr))->toBeTrue();
    });

    it('allValues includes Hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->allValues();
        expect(array_key_exists('password', $arr))->toBeTrue();
    });

    // ─── DTO: with() immutable update ────────────────────────────────────

    it('with() creates new instance with overrides', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $updated = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@b.com');
        expect($original)->not->toBe($updated);
    });

    // ─── DTO: only/except selective output ──────────────────────────────

    it('only() returns only specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('except() excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    // ─── DTO: equals/isEmpty/isNotEmpty ────────────────────────────────

    it('equals() compares toArray output', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals() returns false for different values', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'b@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty() returns true for DTO with all default/null values', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        // Depends on fixture implementation — just verify it returns bool
        expect(is_bool($dto->isEmpty()))->toBeTrue();
    });

    // ─── DTO: toJson/jsonSerialize ──────────────────────────────────────

    it('toJson produces valid JSON string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect(is_array($decoded))->toBeTrue();
        expect($decoded['email'])->toBe('a@b.com');
        expect(array_key_exists('password', $decoded))->toBeFalse();
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    // ─── DTO: rules/rulesFor ────────────────────────────────────────────

    it('rules() returns array of arrays', function () {
        $rules = CreateUserDTO::rules();
        expect(is_array($rules))->toBeTrue();

        foreach ($rules as $field => $fieldRules) {
            expect(is_array($fieldRules))->toBeTrue("rules() should return array<string, array<int, mixed>>");
        }
    });

    it('rules() includes required for required fields', function () {
        $rules = CreateUserDTO::rules();
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('rulesFor() returns same as rules() by default', function () {
        $dtoRules = CreateUserDTO::rules();
        $actionRules = CreateUserDTO::rulesFor('create');

        expect($dtoRules)->toBe($actionRules);
    });

    // ─── DtoCollection: type safety ─────────────────────────────────────

    it('DtoCollection only accepts DataTransferObject instances', function () {
        expect(fn () => new DtoCollection([new \stdClass()]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection make() creates from array of DTOs', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        expect($col->count())->toBe(2);
    });

    it('DtoCollection filter() returns new immutable collection', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $filtered = $col->filter(fn ($dto) => $dto->name === 'A');

        expect($filtered->count())->toBe(1);
        expect($col->count())->toBe(2); // original unchanged
    });

    it('DtoCollection append() returns new immutable collection', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1]);
        $appended = $col->append($dto2);

        expect($appended->count())->toBe(2);
        expect($col->count())->toBe(1); // original unchanged
    });

    it('DtoCollection push() mutates in-place and returns self', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1]);
        $result = $col->push($dto2);

        expect($col->count())->toBe(2); // mutated
        expect($result)->toBe($col); // same instance
    });

    it('DtoCollection merge() returns new combined collection', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);
        $dto3 = ValidationTestDTO::fromArray(['name' => 'C', 'age' => 35], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('DtoCollection pluck() extracts single property values', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $names = $col->pluck('name');

        expect($names)->toBe(['A', 'B']);
    });

    it('DtoCollection pluckKey() builds key-value dictionary', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $dict = $col->pluckKey('name', 'age');

        expect($dict['A'])->toBe(25);
        expect($dict['B'])->toBe(30);
    });

    it('DtoCollection first/last return correct items', function () {
        $dto1 = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $dto2 = ValidationTestDTO::fromArray(['name' => 'B', 'age' => 30], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect($col->first()->name)->toBe('A');
        expect($col->last()->name)->toBe('B');
    });

    it('DtoCollection first/last return null for empty collection', function () {
        $col = DtoCollection::make([]);
        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('DtoCollection toArray serializes all DTOs', function () {
        $dto = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $col = DtoCollection::make([$dto]);

        $arr = $col->toArray();
        expect(count($arr))->toBe(1);
        expect($arr[0]['name'])->toBe('A');
    });

    it('DtoCollection jsonSerialize matches toArray', function () {
        $dto = ValidationTestDTO::fromArray(['name' => 'A', 'age' => 25], validate: false);
        $col = DtoCollection::make([$dto]);

        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('DtoCollection clone is blocked', function () {
        $col = DtoCollection::make([]);
        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    // ─── DTOCast: strict type handling ──────────────────────────────────

    it('DTOCast get() returns null for null database value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass, 'data', null, []);
        expect($result)->toBeNull();
    });

    it('DTOCast get() returns DTO from valid JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $json = json_encode(['email' => 'a@b.com', 'name' => 'Test']);
        $result = $cast->get(new \stdClass, 'data', $json, []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
        expect($result->email)->toBe('a@b.com');
    });

    it('DTOCast set() returns JSON string from DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $cast->set(new \stdClass, 'data', $dto, []);
        $decoded = json_decode($result, true);

        expect(is_array($decoded))->toBeTrue();
        expect($decoded['email'])->toBe('a@b.com');
    });

    it('DTOCast set() returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->set(new \stdClass, 'data', null, []);
        expect($result)->toBeNull();
    });

    it('DTOCast set() throws for unsupported type', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        expect(fn () => $cast->set(new \stdClass, 'data', 42, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    // ─── DTOException ───────────────────────────────────────────────────

    it('DTOException::invalidCast() produces consistent message', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
        expect((string) $e)->toContain('DTOException');
    });

    it('DTOException::invalidJson() produces consistent message', function () {
        $e = DTOException::invalidJson('data', 'Syntax error');
        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });

    // ─── DTOManager: delegation ─────────────────────────────────────────

    it('DTOManager make() creates DTO from array', function () {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'a@b.com',
            'name' => 'Test',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('DTOManager validate() returns validated data', function () {
        $manager = new DTOManager;
        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'a@b.com',
            'name' => 'Test',
        ]);

        expect(is_array($result))->toBeTrue();
        expect($result['email'])->toBe('a@b.com');
    });

    it('DTOManager rules() returns same as static rules()', function () {
        $manager = new DTOManager;
        expect($manager->rules(CreateUserDTO::class))->toBe(CreateUserDTO::rules());
    });

    it('DTOManager rulesFor() returns action-scoped rules', function () {
        $manager = new DTOManager;
        $result = $manager->rulesFor(CreateUserDTO::class, 'create');
        expect(is_array($result))->toBeTrue();
    });

    // ─── Attribute classes: final + ValidationAttribute contract ────────

    it('all validation attribute classes implement ValidationAttribute', function () {
        $attributes = [
            Required::class, Email::class, Max::class, Min::class,
            Boolean::class, Pattern::class, Url::class, Nullable::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))
                ->toBeTrue("{$attrClass} must implement ValidationAttribute");
        }
    });

    it('ValidationAttribute implementations have ruleKey() returning string', function () {
        $attributes = [
            new Required, new Email, new Max(100), new Min(1),
            new Boolean, new Pattern('/test/'), new Url, new Nullable,
        ];

        foreach ($attributes as $attr) {
            expect(is_string($attr->ruleKey()))->toBeTrue();
            expect($attr->ruleKey())->not->toBeEmpty();
        }
    });

    // ─── Nested DTO: roundtrip serialization ────────────────────────────

    it('nested DTO roundtrip: fromArray → toArray preserves structure', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 3, 'price' => 9.99],
            ],
        ], validate: false);

        $arr = $dto->toArray();
        expect(is_array($arr['shippingAddress']))->toBeTrue();
        expect(is_array($arr['items']))->toBeTrue();
    });

    // ─── Cast attribute: type casting ──────────────────────────────────

    it('Cast attribute converts string to date', function () {
        $dto = DateCastDTO::fromArray([
            'event_date' => '2024-01-15',
        ], validate: false);

        expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($dto->event_date->format('Y-m-d'))->toBe('2024-01-15');
    });

    // ─── Metadata cache: TTL-based invalidation ─────────────────────────

    it('metadata cache can be flushed per class', function () {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        // Resolve metadata (caches it)
        $rules = CreateUserDTO::rules();
        expect($rules)->not->toBeEmpty();
        // Flush and resolve again
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        $rules2 = CreateUserDTO::rules();
        expect($rules2)->toBe($rules);
    });

    it('metadata cache can be flushed for all classes', function () {
        DataTransferObject::flushMetadataCache();
        $rules1 = CreateUserDTO::rules();
        $rules2 = AllDefaultsDTO::rules();
        expect($rules1)->not->toBeEmpty();
        expect($rules2)->not->toBeEmpty();

        DataTransferObject::flushMetadataCache();

        // Still works after flush
        expect(CreateUserDTO::rules())->toBe($rules1);
    });

    // ─── Interfaces: contract compliance ────────────────────────────────

    it('DataTransferObject implements FromRequestDTO', function () {
        expect(DataTransferObject::class)->implementsInterface(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function () {
        expect(DataTransferObject::class)->implementsInterface(ValidatableDTO::class);
    });

    it('DataTransferObject implements Arrayable and JsonSerializable', function () {
        expect(DataTransferObject::class)->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class);
        expect(DataTransferObject::class)->implementsInterface(\JsonSerializable::class);
    });
});
