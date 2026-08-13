<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * Runtime contract verification — ensures DTO API contracts hold
 * across all DTO features: hydration, serialization, validation,
 * collections, casting, and edge cases.
 *
 * This test verifies the actual public API behavior — not structural/code quality —
 * making it complementary to static analysis (PHPStan) and architecture audit tests.
 */
describe('DTO Runtime Contract Verification', function (): void {
    // ──────────────────────────────────────────────────────────────
    // fromArray + toArray basic roundtrip
    // ──────────────────────────────────────────────────────────────
    describe('fromArray/toArray roundtrip', function (): void {
        it('roundtrips CreateUserDTO preserving public fields', function (): void {
            $data = [
                'email' => 'user@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'tags' => ['php', 'laravel'],
                'phone' => '+1234567890',
                'password' => 'secret123',
            ];

            $dto = CreateUserDTO::fromArray($data, validate: false);

            expect($dto->email)->toBe('user@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe(['php', 'laravel']);
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->password)->toBe('secret123');

            // toArray excludes hidden fields
            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
            expect($arr['email'])->toBe('user@example.com');
            expect($arr['name'])->toBe('Alice');

            // allValues includes hidden fields
            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('uses default values for missing keys', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'minimal@example.com',
                'name' => 'Min',
            ], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('respects MapFrom for key aliasing', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'map@example.com',
                'name' => 'Mapped',
                'phone_number' => '+9999999999', // maps to $phone
            ], validate: false);

            expect($dto->phone)->toBe('+9999999999');
        });

        it('applies Cast for type coercion on RoundtripDTO', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Cast Test',
                'age' => '30',            // string '30' → int 30 via Cast('integer')
                'active' => '1',         // string '1' → bool true
                'tags' => '["a","b"]',    // JSON string → array via Cast('array')
            ], validate: false);

            expect($dto->age)->toBe(30);
            expect($dto->active)->toBeTrue();
            expect($dto->tags)->toBe(['a', 'b']);
            expect($dto->score)->toBe(0.0); // default
        });

        it('applies MapFrom on RoundtripDTO', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Mapped',
                'age' => 25,
                'active' => true,
                'source_bio' => 'This is the bio', // maps to $bio
            ], validate: false);

            expect($dto->bio)->toBe('This is the bio');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // fromJson edge cases
    // ──────────────────────────────────────────────────────────────
    describe('fromJson edge cases', function (): void {
        it('parses valid JSON object', function (): void {
            $dto = MinimalDTO::fromJson(
                '{"name": "Alice", "value": "hello"}',
                validate: false,
            );

            expect($dto->name)->toBe('Alice');
            expect($dto->value)->toBe('hello');
        });

        it('throws DTOException for invalid JSON syntax', function (): void {
            expect(fn () => MinimalDTO::fromJson('{invalid json}', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential JSON array', function (): void {
            expect(fn () => MinimalDTO::fromJson('["a", "b"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('allows empty JSON object {}', function (): void {
            // EmptyDTO has no required fields, so {} should work
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('allows empty array []', function (): void {
            $dto = EmptyDTO::fromJson('[]', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // with() immutable update — always validates
    // ──────────────────────────────────────────────────────────────
    describe('with() immutable update', function (): void {
        it('creates new instance with overrides', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'original@example.com',
                'name' => 'Original',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            expect($dto->name)->toBe('Original');     // original unchanged
            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('original@example.com'); // preserved
        });

        it('preserves hidden fields in with() via allValues merge', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'keep@example.com',
                'name' => 'Keep',
                'password' => 'secret',
            ], validate: false);

            $updated = $dto->with(['name' => 'New Name']);

            expect($updated->password)->toBe('secret');
        });

        it('validate parameter has no effect (always validates)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'valid@example.com',
                'name' => 'Valid',
            ], validate: false);

            // with() always validates — even passing validate: false doesn't skip it
            // The $validate param is deprecated and ignored
            $updated = $dto->with(['name' => 'New'], validate: false);

            expect($updated)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // equals / isEmpty / isNotEmpty
    // ──────────────────────────────────────────────────────────────
    describe('state checks', function (): void {
        it('equals returns true for same values', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'same@example.com',
                'name' => 'Same',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'same@example.com',
                'name' => 'Same',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different values', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('equals ignores hidden fields', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'eq@example.com',
                'name' => 'Eq',
                'password' => 'pass1',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'eq@example.com',
                'name' => 'Eq',
                'password' => 'pass2',
            ], validate: false);

            // equals() uses toArray() which excludes hidden fields
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('isEmpty detects default-only DTOs', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => '',
                'name' => '',
                'password' => null,
            ], validate: false);

            // email and name are '' (empty string), password is null
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isNotEmpty detects non-empty fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'real@example.com',
                'name' => 'Real Name',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('considers 0 as non-empty for non-nullable int', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Not Empty',
                'age' => 0,
                'active' => false,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // only / except selective output
    // ──────────────────────────────────────────────────────────────
    describe('selective output', function (): void {
        it('only returns specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'sel@example.com',
                'name' => 'Selective',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
            expect($result)->not->toHaveKey('password');
        });

        it('except excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'exc@example.com',
                'name' => 'Exclude',
            ], validate: false);

            $result = $dto->except('email', 'status', 'tags', 'phone');

            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('email');
        });

        it('only accepts string or array', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'multi@example.com',
                'name' => 'Multi',
            ], validate: false);

            // Single string key
            $single = $dto->only('email');
            expect($single)->toHaveCount(1);

            // Array of keys
            $multi = $dto->only(['email', 'name']);
            expect($multi)->toHaveCount(2);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // fromPartialArray (PATCH semantics)
    // ──────────────────────────────────────────────────────────────
    describe('fromPartialArray PATCH semantics', function (): void {
        it('hydrates only present fields, defaults rest', function (): void {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Patched'], validate: false);

            expect($dto->name)->toBe('Patched');
            expect($dto->status)->toBe('active'); // default
            expect($dto->tags)->toBe([]);         // default
            expect($dto->phone)->toBeNull();      // default
        });

        it('empty partial data uses all defaults', function (): void {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);

            expect($dto->status)->toBe('active');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // rules / rulesFor / validateArray
    // ──────────────────────────────────────────────────────────────
    describe('validation rules', function (): void {
        it('rules() returns array with expected keys', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rules() contains expected rule strings', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('rulesFor() returns action-specific rules', function (): void {
            $createRules = ActionScopedDTO::rulesFor('create');
            $updateRules = ActionScopedDTO::rulesFor('update');

            // Both have email key
            expect($createRules)->toHaveKey('email');
            expect($updateRules)->toHaveKey('email');

            // Update rules should have 'sometimes' for password
            expect($updateRules['password'])->toContain('sometimes');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DtoCollection
    // ──────────────────────────────────────────────────────────────
    describe('DtoCollection', function (): void {
        it('creates from array of DTOs', function (): void {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
            ];

            $col = new DtoCollection($dtos);

            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('toArray serializes each DTO', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'col@example.com',
                'name' => 'Collection',
                'password' => 'hidden',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $arr = $col->toArray();

            expect($arr)->toHaveCount(1);
            expect($arr[0])->not->toHaveKey('password'); // hidden excluded
        });

        it('supports push (mutating) and append (immutable)', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => '1@b.com', 'name' => 'One'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => '2@b.com', 'name' => 'Two'], validate: false);

            $col = new DtoCollection([$dto1]);
            $col->push($dto2); // mutates

            expect($col->count())->toBe(2);

            $dto3 = CreateUserDTO::fromArray(['email' => '3@b.com', 'name' => 'Three'], validate: false);
            $newCol = $col->append($dto3); // immutable

            expect($newCol->count())->toBe(3);
            expect($col->count())->toBe(2); // original unchanged
        });

        it('rejects non-DTO items', function (): void {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('first/last return correct items', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'first@b.com', 'name' => 'First'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'last@b.com', 'name' => 'Last'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);

            expect($col->first()->email)->toBe('first@b.com');
            expect($col->last()->email)->toBe('last@b.com');
        });

        it('filter returns new collection', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'keep@b.com', 'name' => 'Keep'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'drop@b.com', 'name' => 'Drop'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'Keep');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Keep');
        });

        it('map returns plain array', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $emails = $col->map(fn (DataTransferObject $d): string => $d->email);

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('pluck extracts single field values', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'p1@b.com', 'name' => 'P1'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'p2@b.com', 'name' => 'P2'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['p1@b.com', 'p2@b.com']);
        });

        it('pluckKey creates associative array', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'k1@b.com', 'name' => 'Key1'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'k2@b.com', 'name' => 'Key2'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $keyed = $col->pluckKey('name', 'email');

            expect($keyed)->toBe([
                'Key1' => 'k1@b.com',
                'Key2' => 'k2@b.com',
            ]);
        });

        it('merge combines two collections immutably', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2, $dto3]);

            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
            expect($col1->count())->toBe(1); // unchanged
        });

        it('supports ArrayAccess and foreach', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'acc@b.com', 'name' => 'Access'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'foreach@b.com', 'name' => 'Foreach'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);

            expect($col[0]->name)->toBe('Access');
            expect(isset($col[1]))->toBeTrue();
            expect(isset($col[2]))->toBeFalse();

            $names = [];
            foreach ($col as $dto) {
                $names[] = $dto->name;
            }
            expect($names)->toBe(['Access', 'Foreach']);
        });

        it('jsonSerialize produces array of arrays', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'j@b.com', 'name' => 'Json'], validate: false);
            $col = new DtoCollection([$dto]);

            $result = $col->jsonSerialize();

            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOCast — Eloquent casting contract
    // ──────────────────────────────────────────────────────────────
    describe('DTOCast eloquent casting', function (): void {
        it('get() hydrates from JSON string', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $json = json_encode([
                'email' => 'cast@eloquent.com',
                'name' => 'Eloquent User',
            ]);

            $result = $cast->get(
                model: new class {},
                key: 'payload',
                value: $json,
                attributes: [],
            );

            expect($result)->toBeInstanceOf(CreateUserDTO::class);
            expect($result->email)->toBe('cast@eloquent.com');
        });

        it('get() hydrates from array value', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->get(
                model: new class {},
                key: 'payload',
                value: ['email' => 'arr@eloquent.com', 'name' => 'Array'],
                attributes: [],
            );

            expect($result)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('get() returns null for null', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->get(
                model: new class {},
                key: 'payload',
                value: null,
                attributes: [],
            );

            expect($result)->toBeNull();
        });

        it('set() stores DTO as JSON', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'set@eloquent.com',
                'name' => 'Set User',
            ], validate: false);

            $result = $cast->set(
                model: new class {},
                key: 'payload',
                value: $dto,
                attributes: [],
            );

            $decoded = json_decode($result, true);
            expect($decoded['email'])->toBe('set@eloquent.com');
        });

        it('set() hydrates and stores array as JSON', function (): void {
            $cast = new DTOCast(CreateUserDTO::class, validate: false);

            $result = $cast->set(
                model: new class {},
                key: 'payload',
                value: ['email' => 'arrset@eloquent.com', 'name' => 'ArraySet'],
                attributes: [],
            );

            $decoded = json_decode($result, true);
            expect($decoded['email'])->toBe('arrset@eloquent.com');
        });

        it('set() rejects unexpected types', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            expect(fn () => $cast->set(
                model: new class {},
                key: 'payload',
                value: 42,
                attributes: [],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns toArray output', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'ser@eloquent.com',
                'name' => 'Serialize',
                'password' => 'hidden123',
            ], validate: false);

            $result = $cast->serialize(
                model: new class {},
                key: 'payload',
                value: $dto,
                attributes: [],
            );

            expect($result)->toBeArray();
            expect($result)->not->toHaveKey('password');
            expect($result['email'])->toBe('ser@eloquent.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOException — factory methods
    // ──────────────────────────────────────────────────────────────
    describe('DTOException', function (): void {
        it('creates invalidCast exception with debug type', function (): void {
            $e = DTOException::invalidCast('count', 'integer', 'not_a_number');

            expect($e->getMessage())->toContain('count');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('not_a_number');
        });

        it('creates invalidJson exception with error detail', function (): void {
            $e = DTOException::invalidJson('data', 'Syntax error');

            expect($e->getMessage())->toContain('data');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function (): void {
            $e = DTOException::invalidCast('x', 'y', 'z');

            $str = (string) $e;

            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('x');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Nested DTO hydration
    // ──────────────────────────────────────────────────────────────
    describe('nested DTO hydration', function (): void {
        it('hydrates nested DTO from array', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Parent',
                'address' => [
                    'city' => 'Istanbul',
                    'country' => 'Turkey',
                ],
            ], validate: false);

            expect($dto->address)->toBeInstanceOf(AddressDTO::class);
            expect($dto->address->city)->toBe('Istanbul');
        });

        it('serializes nested DTO recursively', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Nested',
                'address' => [
                    'city' => 'Ankara',
                    'country' => 'Turkey',
                ],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['address'])->toBeArray();
            expect($arr['address']['city'])->toBe('Ankara');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Metadata cache lifecycle
    // ──────────────────────────────────────────────────────────────
    describe('metadata cache', function (): void {
        it('flushMetadataCache clears all classes', function (): void {
            // Resolve metadata to populate cache
            CreateUserDTO::rules();

            // Flush everything
            DataTransferObject::flushMetadataCache();

            // Re-resolve should work without error
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray()->not->toBeEmpty();
        });

        it('flushMetadataCache clears specific class only', function (): void {
            CreateUserDTO::rules();

            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Other classes should still be resolvable
            $minimalRules = MinimalDTO::rules();
            expect($minimalRules)->toBeArray();
        });
    });
});
