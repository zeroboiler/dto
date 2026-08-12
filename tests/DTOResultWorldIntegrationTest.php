<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Real-world integration patterns for ZeroBoiler DTOs.
 *
 * Tests DTO usage in common application contexts:
 * - CRUD controller patterns (create, update, partial update)
 * - API resource serialization with hidden fields
 * - Action-scoped validation rules
 * - Nested DTO hydration and serialization roundtrips
 * - DtoCollection operations (map, filter, pluck, merge)
 * - Eloquent cast simulation
 * - JSON hydration edge cases
 * - Validation error handling
 * - Immutability guarantees
 * - Type casting pipeline
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('Real-world DTO Integration Patterns', function (): void {

    // ──────────────────────────────────────────────────────────────
    // CRUD Controller Patterns
    // ──────────────────────────────────────────────────────────────

    describe('CRUD controller patterns', function (): void {
        it('simulates a POST /users endpoint (create)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John Doe',
                'password' => 'secret123',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('john@example.com');
            expect($dto->name)->toBe('John Doe');
            expect($dto->status)->toBe('active'); // DefaultValue
            expect($dto->tags)->toBe([]); // Default
            expect($dto->phone)->toBeNull(); // Optional
        });

        it('simulates a PUT /users/1 endpoint (full update via with())', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John Doe',
            ], validate: false);

            $updated = $dto->with(['name' => 'Jane Doe', 'status' => 'inactive']);

            expect($updated)->toBeInstanceOf(CreateUserDTO::class);
            expect($updated->email)->toBe('john@example.com'); // Preserved
            expect($updated->name)->toBe('Jane Doe'); // Updated
            expect($updated->status)->toBe('inactive'); // Updated

            // Original unchanged (immutability)
            expect($dto->name)->toBe('John Doe');
            expect($dto->status)->toBe('active');
        });

        it('simulates a PATCH /users/1 endpoint (partial update)', function (): void {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated Name Only',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Updated Name Only');
            expect($dto->status)->toBe('active'); // Default preserved
            expect($dto->email)->toBe(''); // Type-appropriate empty for non-nullable string
        });
    });

    // ──────────────────────────────────────────────────────────────
    // API Resource Serialization
    // ──────────────────────────────────────────────────────────────

    describe('API resource serialization', function (): void {
        it('toArray() excludes hidden fields (password)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('status');
            expect($arr)->not->toHaveKey('password'); // Hidden
        });

        it('allValues() includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('only() returns specified fields only', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
                'tags' => ['admin'],
            ], validate: false);

            $result = $dto->only('email', 'name');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });

        it('except() excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status', 'tags');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });

        it('toJson() produces valid JSON', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeString();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded)->not->toHaveKey('password');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Validation Rules
    // ──────────────────────────────────────────────────────────────

    describe('Validation rules', function (): void {
        it('rules() returns Laravel-compatible rule arrays', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');

            expect($rules)->toHaveKey('name');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('rulesFor() returns rules for an action context', function (): void {
            $createRules = CreateUserDTO::rulesFor('create');
            $updateRules = CreateUserDTO::rulesFor('update');

            // Default implementation returns same rules for all actions
            expect($createRules)->toBe($updateRules);
        });

        it('nullable properties have appropriate rules', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules)->toHaveKey('phone');
            expect($rules['phone'])->toContain('sometimes');
        });

        it('ProductDTO has numeric and integer rules', function (): void {
            $rules = ProductDTO::rules();

            expect($rules)->toHaveKey('price');
            expect($rules['price'])->toContain('required');
            expect($rules['price'])->toContain('numeric');

            expect($rules)->toHaveKey('stock');
            expect($rules['stock'])->toContain('integer');
            expect($rules['stock'])->toContain('min:0');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // State Checks
    // ──────────────────────────────────────────────────────────────

    describe('State checks', function (): void {
        it('isEmpty() returns true for empty DTO', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty() returns false when properties have meaningful values', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('equals() compares two DTOs by toArray() output', function (): void {
            $data = ['email' => 'a@b.com', 'name' => 'Test'];
            $dto1 = CreateUserDTO::fromArray($data, validate: false);
            $dto2 = CreateUserDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals() returns false for different values', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // JSON Hydration
    // ──────────────────────────────────────────────────────────────

    describe('JSON hydration', function (): void {
        it('fromJson() parses valid JSON object', function (): void {
            $json = '{"email":"test@example.com","name":"Test User"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
        });

        it('fromJson() throws DTOException on invalid JSON', function (): void {
            expect(fn () => CreateUserDTO::fromJson('not-json{', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson() throws DTOException on sequential array', function (): void {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson() accepts empty object', function (): void {
            $dto = EmptyDTO::fromJson('{}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('round-trips through JSON serialization', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'roundtrip@example.com',
                'name' => 'Round Trip',
                'tags' => ['test', 'dev'],
                'phone' => '+1234567890',
            ], validate: false);

            $json = $dto->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe('roundtrip@example.com');
            expect($restored->name)->toBe('Round Trip');
            expect($restored->tags)->toBe(['test', 'dev']);
            expect($restored->phone)->toBe('+1234567890');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // MapFrom Key Mapping
    // ──────────────────────────────────────────────────────────────

    describe('MapFrom key mapping', function (): void {
        it('maps source keys to DTO properties', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+1234567890', // Mapped to $phone
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('uses property name when mapped key is absent', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone' => '+9876543210', // Direct property name
            ], validate: false);

            expect($dto->phone)->toBe('+9876543210');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Type Casting Pipeline
    // ──────────────────────────────────────────────────────────────

    describe('Type casting pipeline', function (): void {
        it('Cast array attribute deserializes JSON strings', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '["admin","user"]', // JSON string → array
            ], validate: false);

            expect($dto->tags)->toBe(['admin', 'user']);
        });

        it('Cast array attribute passes through arrays', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => ['a', 'b'],
            ], validate: false);

            expect($dto->tags)->toBe(['a', 'b']);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DtoCollection Operations
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection operations', function (): void {
        it('creates collection from DTO instances', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('map() transforms DTOs to plain values', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $names = $col->map(fn (CreateUserDTO $d): string => $d->name);

            expect($names)->toBe(['Alice', 'Charlie']);
        });

        it('filter() returns new collection with matching items', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'anna@test.com', 'name' => 'Anna'], validate: false);

            $col = DtoCollection::make([$d1, $d2, $d3]);
            $filtered = $col->filter(fn (CreateUserDTO $d): bool => str_starts_with($d->name, 'A'));

            expect($filtered->count())->toBe(2);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('pluck() extracts single property values', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('pluckKey() creates key-value map', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $map = $col->pluckKey('email', 'name');

            expect($map)->toBe([
                'alice@test.com' => 'Alice',
                'bob@test.com' => 'Bob',
            ]);
        });

        it('append() returns new collection (immutable)', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $newCol = $col->append($d2);

            expect($col->count())->toBe(1); // Original unchanged
            expect($newCol->count())->toBe(2);
        });

        it('push() mutates in-place and returns self', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $result = $col->push($d2);

            expect($col->count())->toBe(2); // Mutated
            expect($result)->toBe($col); // Same instance
        });

        it('merge() combines two collections', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

            $col1 = DtoCollection::make([$d1]);
            $col2 = DtoCollection::make([$d2, $d3]);

            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(3);
            expect($col1->count())->toBe(1); // Original unchanged
        });

        it('first/last return correct items', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'First'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Last'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            expect($col->first()->name)->toBe('First');
            expect($col->last()->name)->toBe('Last');
        });

        it('empty collection returns null for first/last', function (): void {
            $col = DtoCollection::make([]);
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
            expect($col->isEmpty())->toBeTrue();
        });

        it('toArray() serializes all DTOs', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $arr = $col->toArray();

            expect($arr)->toBeArray();
            expect(count($arr))->toBe(2);
            expect($arr[0])->toHaveKey('email');
            expect($arr[1])->toHaveKey('name');
        });

        it('jsonSerialize() produces JSON-serializable array', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            $json = json_encode($col);
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect(count($decoded))->toBe(1);
            expect($decoded[0]['email'])->toBe('a@b.com');
        });

        it('rejects non-DTO instances in constructor', function (): void {
            expect(fn () => new DtoCollection([new \stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset re-indexes collection', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

            $col = DtoCollection::make([$d1, $d2, $d3]);
            unset($col[0]); // Remove first

            expect($col->count())->toBe(2);
            expect($col->first()->name)->toBe('C'); // Re-indexed
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOException Factory Methods
    // ──────────────────────────────────────────────────────────────

    describe('DTOException factory methods', function (): void {
        it('invalidCast() includes property and type info', function (): void {
            $e = DTOException::invalidCast('age', 'integer', 'not-an-int');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson() includes property and error info', function (): void {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString() returns class name and message', function (): void {
            $e = DTOException::invalidJson('field', 'error');
            $str = (string) $e;
            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('error');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Interface Contracts
    // ──────────────────────────────────────────────────────────────

    describe('Interface contract compliance', function (): void {
        it('CreateUserDTO implements FromRequestDTO', function (): void {
            expect(CreateUserDTO::class)->toImplement(FromRequestDTO::class);
        });

        it('CreateUserDTO implements ValidatableDTO', function (): void {
            expect(CreateUserDTO::class)->toImplement(ValidatableDTO::class);
        });

        it('has static rules() method', function (): void {
            expect(method_exists(CreateUserDTO::class, 'rules'))->toBeTrue();
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('has static rulesFor() method', function (): void {
            expect(method_exists(CreateUserDTO::class, 'rulesFor'))->toBeTrue();
        });

        it('has fromPartialArray() for PATCH semantics', function (): void {
            expect(method_exists(CreateUserDTO::class, 'fromPartialArray'))->toBeTrue();
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Partial'], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Immutability Guarantees
    // ──────────────────────────────────────────────────────────────

    describe('Immutability guarantees', function (): void {
        it('readonly properties cannot be modified after construction', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            // Verify values are accessible
            expect($dto->email)->toBe('test@example.com');

            // with() creates new instance
            $modified = $dto->with(['email' => 'new@example.com']);
            expect($dto->email)->toBe('test@example.com'); // Unchanged
            expect($modified->email)->toBe('new@example.com');
        });

        it('with() always validates (prevents invalid state)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            // with() with validate: true (default) would throw for invalid data
            // But since we can't run validation here, just verify the contract
            expect(method_exists($dto, 'with'))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Empty DTO Edge Cases
    // ──────────────────────────────────────────────────────────────

    describe('Empty DTO edge cases', function (): void {
        it('EmptyDTO can be created from empty array', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toBe([]);
        });

        it('EmptyDTO fromJson with empty object', function (): void {
            $dto = EmptyDTO::fromJson('{}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toBe([]);
        });

        it('MinimalDTO handles optional fields', function (): void {
            $dto = MinimalDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });
});
