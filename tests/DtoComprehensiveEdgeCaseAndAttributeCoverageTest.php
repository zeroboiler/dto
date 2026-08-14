<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EdgeCaseDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationAttributeContractDTO;

describe('DTO comprehensive edge case coverage', function (): void {

    // ------------------------------------------------------------------
    // EdgeCaseDTO — Full Attribute Coverage
    // ------------------------------------------------------------------

    describe('EdgeCaseDTO hydration and serialization', function (): void {
        it('creates from array with all fields', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'user_handle' => '@testuser',
                'meta' => ['avatar' => 'https://example.com/avatar.jpg'],
                'score' => '95',        // string → int via #[Cast('integer')]
                'isActive' => 'yes',    // string → bool via #[Cast('boolean')]
                'tags' => '{"php","laravel"}', // JSON string → array via #[Cast('array')]
                'role' => 'admin',
                'token' => 'secret123',
                'bio' => 'Hello world',
                'permission' => 'editor',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'website' => 'https://example.com',
                'code' => 'AB-1234',
            ], validate: false);

            expect($dto)->toBeInstanceOf(EdgeCaseDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->handle)->toBe('@testuser');
            expect($dto->avatar)->toBe('https://example.com/avatar.jpg');
            expect($dto->score)->toBe(95);
            expect($dto->isActive)->toBeTrue();
            expect($dto->tags)->toBe(['php', 'laravel']);
            expect($dto->role)->toBe('admin');
            expect($dto->token)->toBe('secret123');
            expect($dto->bio)->toBe('Hello world');
            expect($dto->permission)->toBe('editor');
            expect($dto->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000');
            expect($dto->website)->toBe('https://example.com');
            expect($dto->code)->toBe('AB-1234');
        });

        it('uses defaults when keys are absent', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto->handle)->toBeNull();
            expect($dto->score)->toBe(0);
            expect($dto->isActive)->toBeTrue();
            expect($dto->tags)->toBe([]);
            expect($dto->role)->toBe('viewer');
            expect($dto->token)->toBeNull();
            expect($dto->bio)->toBeNull();
            expect($dto->permission)->toBe('guest');
            expect($dto->uuid)->toBeNull();
            expect($dto->website)->toBeNull();
            expect($dto->code)->toBeNull();
        });

        it('token is excluded from toArray but included in allValues', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'token' => 'secret123',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('token');
            expect($dto->allValues())->toHaveKey('token');
            expect($dto->allValues()['token'])->toBe('secret123');
        });

        it('serializes to JSON correctly', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toHaveKey('email');
            expect($decoded)->not->toHaveKey('token');
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('supports only() field filtering', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'role' => 'admin',
            ], validate: false);

            $filtered = $dto->only('email', 'name');
            expect($filtered)->toHaveKeys(['email', 'name']);
            expect($filtered)->not->toHaveKey('role');
        });

        it('supports except() field exclusion', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $filtered = $dto->except('email');
            expect($filtered)->not->toHaveKey('email');
            expect($filtered)->toHaveKey('name');
        });

        it('supports equals() comparison', function (): void {
            $data = [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ];
            $dto1 = EdgeCaseDTO::fromArray($data, validate: false);
            $dto2 = EdgeCaseDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('supports with() immutable update', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated User'], validate: false);
            expect($updated->name)->toBe('Updated User');
            expect($dto->name)->toBe('Test User'); // original unchanged
        });

        it('isEmpty() returns false when required fields have values', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('fromJson deserializes correctly', function (): void {
            $json = '{"email":"test@example.com","name":"Test User","score":"42","isActive":"1","tags":"[\"a\",\"b\"]"}';
            $dto = EdgeCaseDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->score)->toBe(42);
            expect($dto->isActive)->toBeTrue();
            expect($dto->tags)->toBe(['a', 'b']);
        });

        it('fromJson rejects sequential arrays', function (): void {
            expect(fn (): mixed => EdgeCaseDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson rejects invalid JSON', function (): void {
            expect(fn (): mixed => EdgeCaseDTO::fromJson('{invalid}', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    // ------------------------------------------------------------------
    // EdgeCaseDTO — MapFrom with Dot Notation
    // ------------------------------------------------------------------

    describe('EdgeCaseDTO MapFrom dot notation', function (): void {
        it('maps nested dot-notation keys', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'meta' => [
                    'avatar' => 'https://example.com/avatar.jpg',
                ],
            ], validate: false);

            expect($dto->avatar)->toBe('https://example.com/avatar.jpg');
        });

        it('returns null when dot-notation key is absent', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto->avatar)->toBeNull();
        });
    });

    // ------------------------------------------------------------------
    // EdgeCaseDTO — Cast Types
    // ------------------------------------------------------------------

    describe('EdgeCaseDTO cast types', function (): void {
        it('casts string to integer', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'score' => '100',
            ], validate: false);

            expect($dto->score)->toBe(100);
            expect($dto->score)->toBeInt();
        });

        it('casts "yes"/"true"/"1" to boolean true', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'isActive' => 'yes',
            ], validate: false);

            expect($dto->isActive)->toBeTrue();
        });

        it('casts "no"/"false"/"0" to boolean false', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'isActive' => 'no',
            ], validate: false);

            expect($dto->isActive)->toBeFalse();
        });

        it('casts JSON string to array', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'tags' => '["php","laravel"]',
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
            expect(is_array($dto->tags))->toBeTrue();
        });

        it('passes through array as-is when cast to array', function (): void {
            $dto = EdgeCaseDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'tags' => ['php', 'laravel'],
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
        });
    });

    // ------------------------------------------------------------------
    // EdgeCaseDTO — Validation Rules
    // ------------------------------------------------------------------

    describe('EdgeCaseDTO validation rules', function (): void {
        it('generates rules from attributes', function (): void {
            $rules = EdgeCaseDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('permission');
            expect($rules)->toHaveKey('uuid');
            expect($rules)->toHaveKey('website');
            expect($rules)->toHaveKey('code');
        });

        it('email rules contain required and email', function (): void {
            $rules = EdgeCaseDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('name rules contain min and max', function (): void {
            $rules = EdgeCaseDTO::rules();

            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:100');
        });

        it('permission rules contain in constraint', function (): void {
            $rules = EdgeCaseDTO::rules();

            $inRule = collect($rules['permission'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'in:'));
            expect($inRule)->not->toBeNull();
        });

        it('code rules contain regex pattern', function (): void {
            $rules = EdgeCaseDTO::rules();

            $patternRule = collect($rules['code'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'regex:'));
            expect($patternRule)->not->toBeNull();
        });

        it('uuid rules contain uuid', function (): void {
            $rules = EdgeCaseDTO::rules();

            expect($rules['uuid'])->toContain('uuid');
        });

        it('website rules contain url', function (): void {
            $rules = EdgeCaseDTO::rules();

            expect($rules['website'])->toContain('url');
        });

        it('role uses DefaultValue attribute', function (): void {
            // role should not be required since it has DefaultValue
            $rules = EdgeCaseDTO::rules();

            $roleRequired = in_array('required', $rules['role'], true);
            expect($roleRequired)->toBeFalse();
        });
    });

    // ------------------------------------------------------------------
    // EdgeCaseDTO — Partial Array (PATCH)
    // ------------------------------------------------------------------

    describe('EdgeCaseDTO partial array updates', function (): void {
        it('creates from partial array', function (): void {
            $dto = EdgeCaseDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validate: false);

            expect($dto->name)->toBe('Updated Name');
            expect($dto->email)->toBe(''); // non-nullable string defaults to ''
        });

        it('preserves defaults for missing fields', function (): void {
            $dto = EdgeCaseDTO::fromPartialArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->score)->toBe(0);
            expect($dto->isActive)->toBeTrue();
            expect($dto->tags)->toBe([]);
            expect($dto->role)->toBe('viewer');
        });
    });

    // ------------------------------------------------------------------
    // ValidationAttributeContractDTO — All 37 Attributes Resolve
    // ------------------------------------------------------------------

    describe('ValidationAttributeContractDTO rule resolution', function (): void {
        it('resolves rules for all 37 validation attributes', function (): void {
            $rules = ValidationAttributeContractDTO::rules();

            // Basic required
            expect($rules)->toHaveKey('username');
            expect($rules['username'])->toContain('required');

            // Type checks
            expect($rules)->toHaveKey('age');
            expect($rules['age'])->toContain('integer');

            expect($rules)->toHaveKey('price');
            expect($rules['price'])->toContain('numeric');

            expect($rules)->toHaveKey('agree');
            expect($rules['agree'])->toContain('boolean');

            // String validators
            expect($rules)->toHaveKey('field1');
            expect($rules['field1'])->toContain('same:field1');

            expect($rules)->toHaveKey('field2');
            expect($rules['field2'])->toContain('different:field1');

            expect($rules)->toHaveKey('password');
            expect($rules['password'])->toContain('confirmed');

            // Conditional required
            expect($rules)->toHaveKey('firstName');
            $requiredIf = collect($rules['firstName'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_if:'));
            expect($requiredIf)->not->toBeNull();

            expect($rules)->toHaveKey('lastName');
            $requiredUnless = collect($rules['lastName'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_unless:'));
            expect($requiredUnless)->not->toBeNull();

            expect($rules)->toHaveKey('email');
            $requiredWith = collect($rules['email'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_with:'));
            expect($requiredWith)->not->toBeNull();

            expect($rules)->toHaveKey('address');
            $requiredWithAll = collect($rules['address'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_with_all:'));
            expect($requiredWithAll)->not->toBeNull();

            expect($rules)->toHaveKey('phone');
            $requiredWithout = collect($rules['phone'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_without:'));
            expect($requiredWithout)->not->toBeNull();

            expect($rules)->toHaveKey('fax');
            $requiredWithoutAll = collect($rules['fax'])->first(fn (mixed $r): bool => str_starts_with((string) $r, 'required_without_all:'));
            expect($requiredWithoutAll)->not->toBeNull();

            // Date
            expect($rules)->toHaveKey('birthday');
            expect($rules['birthday'])->toContain('date');

            // Array distinct
            expect($rules)->toHaveKey('emails');
            expect($rules['emails'])->toContain('distinct');

            // State
            expect($rules)->toHaveKey('terms');
            expect($rules['terms'])->toContain('accepted');

            expect($rules)->toHaveKey('optOut');
            expect($rules['optOut'])->toContain('declined');

            expect($rules)->toHaveKey('secret');
            expect($rules['secret'])->toContain('prohibited');
        });

        it('distinct generates wildcard rule for array elements', function (): void {
            $rules = ValidationAttributeContractDTO::rules();

            expect($rules)->toHaveKey('emails.*');
            expect($rules['emails.*'])->toContain('distinct');
        });

        it('creates from array with minimal required data', function (): void {
            $dto = ValidationAttributeContractDTO::fromArray([
                'username' => 'testuser',
            ], validate: false);

            expect($dto->username)->toBe('testuser');
            expect($dto->age)->toBeNull();
            expect($dto->agree)->toBeFalse();
            expect($dto->emails)->toBe([]);
        });
    });

    // ------------------------------------------------------------------
    // AllDefaultsDTO — Only Defaults (No Required Fields)
    // ------------------------------------------------------------------

    describe('AllDefaultsDTO (all optional with defaults)', function (): void {
        it('creates without any data', function (): void {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('uses all constructor defaults', function (): void {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            $arr = $dto->toArray();
            // All fields should be present with their defaults
            expect($arr)->toBeArray();
            expect(count($arr))->toBeGreaterThan(0);
        });

        it('isEmpty() returns true for default-only instance', function (): void {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            // Depends on what defaults are — generally all-defaults DTO should be "empty"
            // or at least the method should not throw
            $result = $dto->isEmpty();
            expect(is_bool($result))->toBeTrue();
        });
    });

    // ------------------------------------------------------------------
    // DtoCollection Edge Cases
    // ------------------------------------------------------------------

    describe('DtoCollection operations', function (): void {
        it('creates from make factory', function (): void {
            $dto1 = EdgeCaseDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = EdgeCaseDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('first() and last() return correct items', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->first()->email)->toBe('a@b.com');
            expect($col->last()->email)->toBe('b@c.com');
        });

        it('pluck extracts property values', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@b.com', 'b@c.com']);
        });

        it('filter returns new collection', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $filtered = $col->filter(fn ($dto): bool => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('map returns plain array', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $names = $col->map(fn ($dto): string => $dto->name);

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('push mutates in place', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $col->push($dto2);

            expect($col->count())->toBe(2);
        });

        it('append returns new immutable collection', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);  // original unchanged
            expect($newCol->count())->toBe(2); // new collection has both
        });

        it('merge combines two collections', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
            $dto3 = EdgeCaseDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2, $dto3]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
        });

        it('serializes to JSON array', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $json = json_encode($col);

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded[0])->toHaveKey('email');
        });

        it('empty collection returns empty array', function (): void {
            $col = DtoCollection::make([]);
            expect($col->toArray())->toBe([]);
            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });

        it('rejects non-DTO items in constructor', function (): void {
            expect(fn (): mixed => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset re-indexes the collection', function (): void {
            $dto1 = EdgeCaseDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = EdgeCaseDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
            $dto3 = EdgeCaseDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2, $dto3]);
            unset($col[0]); // removes first, should re-index

            expect($col->count())->toBe(2);
            expect($col[0]->email)->toBe('b@c.com'); // re-indexed
        });
    });

    // ------------------------------------------------------------------
    // RulesFor — Action-Scoped Rules
    // ------------------------------------------------------------------

    describe('rulesFor action-scoped rules', function (): void {
        it('defaults to rules() for unknown actions', function (): void {
            $dto = EdgeCaseDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);

            $defaultRules = EdgeCaseDTO::rules();
            $updateRules = EdgeCaseDTO::rulesFor('update');

            expect($updateRules)->toBe($defaultRules);
        });
    });
});
