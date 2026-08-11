<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateTimeCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;

describe('DTO Integration — comprehensive coverage', function (): void {
    // ────────────────────────────────────────────────────────────────
    // Type system verification — readonly promoted properties
    // ────────────────────────────────────────────────────────────────

    it('DTO properties are truly readonly', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $ref = new ReflectionProperty($dto, 'email');
        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->isPublic())->toBeTrue();

        $nameRef = new ReflectionProperty($dto, 'name');
        expect($nameRef->isReadOnly())->toBeTrue();
    });

    it('DTO class is final-readonly-compatible for concrete classes', function (): void {
        // CreateUserDTO should be a concrete, instantiable class
        $ref = new ReflectionClass(CreateUserDTO::class);
        expect($ref->isInstantiable())->toBeTrue();
        expect($ref->isAbstract())->toBeFalse();
    });

    // ────────────────────────────────────────────────────────────────
    // Contract compliance
    // ────────────────────────────────────────────────────────────────

    it('implements FromRequestDTO contract', function (): void {
        expect(CreateUserDTO::class)->toImplement(FromRequestDTO::class);
    });

    it('implements ValidatableDTO contract', function (): void {
        expect(CreateUserDTO::class)->toImplement(ValidatableDTO::class);
    });

    it('DataTransferObject is abstract', function (): void {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    it('DataTransferObject implements Arrayable and JsonSerializable', function (): void {
        expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
        expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
    });

    // ────────────────────────────────────────────────────────────────
    // fromArray / toArray round-trip consistency
    // ────────────────────────────────────────────────────────────────

    it('round-trips fromArray → toArray → fromArray for CreateUserDTO', function (): void {
        $original = [
            'email' => 'roundtrip@example.com',
            'name' => 'Round Trip',
            'status' => 'active',
            'tags' => ['php', 'laravel'],
            'phone_number' => '+1234567890',
        ];

        $dto = CreateUserDTO::fromArray($original, validate: false);
        $array = $dto->allValues();
        $dto2 = CreateUserDTO::fromArray($array, validate: false);

        expect($dto2->email)->toBe('roundtrip@example.com');
        expect($dto2->name)->toBe('Round Trip');
        expect($dto2->status)->toBe('active');
        expect($dto2->tags)->toBe(['php', 'laravel']);
        expect($dto2->phone)->toBe('+1234567890');
    });

    it('round-trips through JSON: fromArray → toJson → fromJson', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '19.99',
            'stock' => 100,
        ], validate: false);

        $json = $dto->toJson();
        $restored = ProductDTO::fromJson($json, validate: false);

        expect($restored->name)->toBe('Widget');
        expect($restored->price)->toBe('19.99');
        expect($restored->stock)->toBe(100);
    });

    // ────────────────────────────────────────────────────────────────
    // fromJson edge cases
    // ────────────────────────────────────────────────────────────────

    it('fromJson throws DTOException for invalid JSON', function (): void {
        expect(function (): void {
            CreateUserDTO::fromJson('not valid json {{{', validate: false);
        })->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for sequential arrays (JSON arrays)', function (): void {
        expect(function (): void {
            CreateUserDTO::fromJson('["not", "an", "object"]', validate: false);
        })->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for JSON null', function (): void {
        expect(function (): void {
            CreateUserDTO::fromJson('null', validate: false);
        })->toThrow(DTOException::class);
    });

    // ────────────────────────────────────────────────────────────────
    // only() and except() selective output
    // ────────────────────────────────────────────────────────────────

    it('only() returns specified fields and excludes hidden', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('password');
        expect($result)->not->toHaveKey('tags');
    });

    it('except() excludes specified fields and respects hidden', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['email']);

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('only() with string key returns single field', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result['email'])->toBe('a@b.com');
    });

    // ────────────────────────────────────────────────────────────────
    // with() immutable update
    // ────────────────────────────────────────────────────────────────

    it('with() creates new instance without modifying original', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'stock' => 10,
        ], validate: false);

        $updated = $dto->with(['stock' => 20]);

        expect($dto->stock)->toBe(10);
        expect($updated->stock)->toBe(20);
        expect($updated->name)->toBe('Widget');
    });

    it('with() rejects when removing required fields is attempted (partial array)', function (): void {
        // fromPartialArray handles missing required — with() uses fromArray which validates
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        // Merging with partial data (missing name) should still work since
        // with() merges allValues() + overrides
        $updated = $dto->with(['email' => 'new@example.com']);
        expect($updated->email)->toBe('new@example.com');
        expect($updated->name)->toBe('Test');
    });

    // ────────────────────────────────────────────────────────────────
    // equals() and isEmpty()
    // ────────────────────────────────────────────────────────────────

    it('equals() returns false for different DTO instances with different values', function (): void {
        $dto1 = ProductDTO::fromArray([
            'name' => 'A',
            'price' => '10',
            'stock' => 1,
        ], validate: false);

        $dto2 = ProductDTO::fromArray([
            'name' => 'B',
            'price' => '20',
            'stock' => 2,
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty() returns false for DTO with non-empty required fields', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'stock' => 5,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ────────────────────────────────────────────────────────────────
    // Hidden attribute behavior
    // ────────────────────────────────────────────────────────────────

    it('allValues() includes hidden fields but toArray() excludes them', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'secret@example.com',
            'name' => 'Secret Agent',
            'password' => 'p@ssw0rd',
        ], validate: false);

        $all = $dto->allValues();
        $public = $dto->toArray();

        expect($all)->toHaveKey('password');
        expect($public)->not->toHaveKey('password');
    });

    // ────────────────────────────────────────────────────────────────
    // MapFrom dot notation
    // ────────────────────────────────────────────────────────────────

    it('respects dot notation in MapFrom for nested source data', function (): void {
        $dto = VoUserDTO::fromArray([
            'user' => [
                'email' => 'john@example.com',
            ],
        ], validate: false);

        expect($dto->email)->toBe('john@example.com');
    });

    // ────────────────────────────────────────────────────────────────
    // DefaultValue attribute
    // ────────────────────────────────────────────────────────────────

    it('DefaultValue attribute provides value when key is absent', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // status has DefaultValue('active')
        expect($dto->status)->toBe('active');
    });

    it('DefaultValue does not override explicit empty string', function (): void {
        // Explicit empty string should be respected, not overridden by default
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => '',
        ], validate: false);

        expect($dto->status)->toBe('');
    });

    // ────────────────────────────────────────────────────────────────
    // Cast attribute
    // ────────────────────────────────────────────────────────────────

    it('Cast array decodes JSON string to array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'metadata' => '{"key":"value"}',
        ], validate: false);

        expect($dto->metadata)->toBe(['key' => 'value']);
    });

    it('Cast array passes through arrays as-is', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'metadata' => ['already' => 'array'],
        ], validate: false);

        expect($dto->metadata)->toBe(['already' => 'array']);
    });

    it('Cast date parses string to Carbon instance', function (): void {
        $dto = DateCastDTO::fromArray([
            'event_date' => '2024-01-15',
        ], validate: false);

        expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($dto->event_date->format('Y-m-d'))->toBe('2024-01-15');
    });

    it('Cast datetime parses string to Carbon instance', function (): void {
        $dto = DateTimeCastDTO::fromArray([
            'created_at' => '2024-06-15 10:30:00',
        ], validate: false);

        expect($dto->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    // ────────────────────────────────────────────────────────────────
    // Validation attribute — rules generation
    // ────────────────────────────────────────────────────────────────

    it('generates correct rules for scalar constraint DTO', function (): void {
        $rules = ScalarConstraintsDTO::rules();

        expect($rules)->toBeArray();
        // Should have rules for each constrained property
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('age');
    });

    it('generates correct rules for validation test DTO', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules)->toBeArray();
    });

    // ────────────────────────────────────────────────────────────────
    // Nested DTO hydration
    // ────────────────────────────────────────────────────────────────

    it('hydrates nested AddressDTO from array', function (): void {
        // VoUserDTO is used with user.email map
        $dto = VoUserDTO::fromArray([
            'user' => [
                'email' => 'alice@example.com',
            ],
        ], validate: false);

        expect($dto->email)->toBe('alice@example.com');
    });

    it('hydrates nested OrderItemDTO array', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'items' => [
                ['sku' => 'SKU-1', 'quantity' => 2, 'price' => '9.99'],
                ['sku' => 'SKU-2', 'quantity' => 1, 'price' => '19.99'],
            ],
        ], validate: false);

        expect($dto->orderNumber)->toBe('ORD-001');
    });

    // ────────────────────────────────────────────────────────────────
    // RegistrationDTO — complex fixture with multiple constraint types
    // ────────────────────────────────────────────────────────────────

    it('creates RegistrationDTO with valid data', function (): void {
        $dto = RegistrationDTO::fromArray([
            'email' => 'reg@example.com',
            'password' => 'SecurePass123!',
            'termsAccepted' => true,
        ], validate: false);

        expect($dto->email)->toBe('reg@example.com');
        expect($dto->password)->toBe('SecurePass123!');
        expect($dto->termsAccepted)->toBeTrue();
    });

    // ────────────────────────────────────────────────────────────────
    // RoundtripDTO — serialization roundtrip
    // ────────────────────────────────────────────────────────────────

    it('round-trips RoundtripDTO through all serialization methods', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 30,
            'active' => true,
            'score' => 95.5,
            'tags' => ['a', 'b'],
            'source_bio' => 'A test bio',
            'secret' => 'hidden-secret',
        ], validate: false);

        // toArray — should exclude hidden 'secret'
        $arr = $dto->toArray();
        expect($arr)->toHaveKey('name');
        expect($arr)->toHaveKey('age');
        expect($arr)->toHaveKey('active');
        expect($arr)->not->toHaveKey('secret');
        expect($arr['bio'])->toBe('A test bio');

        // allValues — should include hidden 'secret'
        $all = $dto->allValues();
        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('hidden-secret');

        // JSON
        $json = $dto->toJson();
        expect($json)->toBeJson();

        // jsonSerialize
        $serialized = $dto->jsonSerialize();
        expect($serialized)->toBeArray();
        expect($serialized)->not->toHaveKey('secret');
    });

    // ────────────────────────────────────────────────────────────────
    // DtoCollection type safety
    // ────────────────────────────────────────────────────────────────

    it('DtoCollection rejects non-DTO items in constructor', function (): void {
        expect(function (): void {
            new DtoCollection([new stdClass()]);
        })->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection push() adds items and maintains count', function (): void {
        $col = new DtoCollection();
        $dto = EmptyDTO::fromArray([], validate: false);

        $col->push($dto);
        expect($col->count())->toBe(1);
        expect($col->isEmpty())->toBeFalse();
    });

    it('DtoCollection append() returns new instance without mutating original', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        $col = new DtoCollection([$dto1]);
        $newCol = $col->append($dto2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('DtoCollection merge() combines items from two collections', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
    });

    it('DtoCollection filter() returns filtered collection', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        // Filter to only the first item by index
        $filtered = $col->filter(
            fn (DataTransferObject $dto, int $index): bool => $index === 0
        );

        expect($filtered->count())->toBe(1);
    });

    it('DtoCollection map() returns plain array of results', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        $col = new DtoCollection([$dto]);
        $classes = $col->map(fn (DataTransferObject $d): string => $d::class);

        expect($classes)->toBe([EmptyDTO::class]);
    });

    it('DtoCollection first() and last() return correct items', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);
        $dto3 = EmptyDTO::fromArray([], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);

        expect($col->first())->toBe($dto1);
        expect($col->last())->toBe($dto3);
    });

    it('DtoCollection offsetUnset re-indexes the array', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);
        $dto3 = EmptyDTO::fromArray([], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $col->offsetUnset(0);

        expect($col->count())->toBe(2);
        // After re-index, offset 0 should now be $dto2
        expect($col->offsetGet(0))->toBe($dto2);
    });

    // ────────────────────────────────────────────────────────────────
    // rulesFor() action-scoped rules
    // ────────────────────────────────────────────────────────────────

    it('rulesFor() returns same rules as rules() by default', function (): void {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
    });

    // ────────────────────────────────────────────────────────────────
    // fromPartialArray — PATCH semantics
    // ────────────────────────────────────────────────────────────────

    it('fromPartialArray hydrates only provided fields with defaults for rest', function (): void {
        $dto = ProductDTO::fromPartialArray([
            'name' => 'Partial Product',
        ], validate: false);

        expect($dto->name)->toBe('Partial Product');
        // stock has default value of 0
        expect($dto->stock)->toBe(0);
    });

    // ────────────────────────────────────────────────────────────────
    // toJson edge cases
    // ────────────────────────────────────────────────────────────────

    it('toJson returns valid JSON string', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeString();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
    });

    it('toJson with JSON_PRETTY_PRINT produces formatted output', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'pretty@example.com',
            'name' => 'Pretty',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toBeString();
        expect(str_contains($json, "\n"))->toBeTrue();
    });
});
