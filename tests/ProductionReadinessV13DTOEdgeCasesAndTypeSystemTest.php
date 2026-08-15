<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

/**
 * Production Readiness V13 — DTO Edge Cases & Type System Verification.
 *
 * Deep edge-case testing for:
 * 1. Hidden field behavior across toArray/allValues/only/except
 * 2. Immutable update with() preserves hidden behavior
 * 3. DtoCollection immutability and mutation edge cases
 * 4. equals/isEmpty/isNotEmpty with edge-case property values
 * 5. fromJson with whitespace and malformed JSON
 * 6. Partial update with DefaultValue attribute interaction
 * 7. rules/rulesFor consistency
 * 8. MapFrom attribute behavior
 * 9. Validation exception message content
 * 10. DtoCollection pluckKey/toDictionary/toArrayBy
 */
describe('Production Readiness V13 — DTO Edge Cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. Hidden field behavior across serialization methods
    // ──────────────────────────────────────────────────────────────

    describe('Hidden field behavior', function (): void {
        it('toArray excludes #[Hidden] properties', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('age');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes #[Hidden] properties', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('only() on a hidden field returns empty', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret123',
            ], validate: false);

            // only() operates on toArray() output which excludes hidden fields
            $result = $dto->only('password');
            expect($result)->toBe([]);
        });

        it('except() respects hidden attributes', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret123',
            ], validate: false);

            $result = $dto->except('email');
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('age');
            expect($result)->not->toHaveKey('email');
            expect($result)->not->toHaveKey('password'); // also hidden
        });

        it('RegistrationDTO hides ipAddress via toArray but not allValues', function (): void {
            $dto = RegistrationDTO::fromArray([
                'email' => 'test@example.com',
                'password' => 'password123',
                'termsAccepted' => true,
                'ipAddress' => '192.168.1.1',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('ipAddress');
            expect($dto->allValues())->toHaveKey('ipAddress');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 2. Immutable update with() preserves behavior
    // ──────────────────────────────────────────────────────────────

    describe('with() immutable update behavior', function (): void {
        it('creates new instance with updated value', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);
            expect($updated->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // original unchanged
        });

        it('preserves hidden fields through with()', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);
            expect($updated->toArray())->not->toHaveKey('password');
            expect($updated->allValues())->toHaveKey('password');
        });

        it('validates merged data — empty name should fail', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            // name has Min(2) — empty string should fail validation
            expect(fn () => $dto->with(['name' => '']))
                ->toThrow(ValidationException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 3. DtoCollection immutability and edge cases
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection operations', function (): void {
        it('filter returns new collection without modifying original', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $filtered = $col->filter(fn ($dto) => $dto->age > 30);

            expect($col->count())->toBe(2);
            expect($filtered->count())->toBe(1);
        });

        it('append returns new collection without modifying original', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1]);
            $appended = $col->append($d2);

            expect($col->count())->toBe(1);
            expect($appended->count())->toBe(2);
        });

        it('merge combines two collections', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'age' => 30], validate: false);

            $col1 = new DtoCollection([$d1]);
            $col2 = new DtoCollection([$d2]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('push mutates in-place and returns same instance', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'age' => 30], validate: false);

            $col = new DtoCollection([$d1]);
            $result = $col->push($d2);

            expect($col->count())->toBe(2);
            expect($result)->toBe($col); // same instance
        });

        it('offsetUnset re-indexes array', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'age' => 30], validate: false);
            $d3 = ComprehensiveDTO::fromArray(['email' => 'c@b.com', 'name' => 'C', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1, $d2, $d3]);
            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col[0]->email)->toBe('b@b.com'); // re-indexed
        });

        it('pluck extracts property values', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $names = $col->pluck('name');

            expect($names)->toBe(['Alice', 'Charlie']);
        });

        it('isEmpty/isNotEmpty work correctly', function (): void {
            $empty = new DtoCollection;
            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();

            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $nonEmpty = new DtoCollection([$d1]);
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('toArray serializes all DTOs without hidden fields', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25, 'password' => 'secret'], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'Bob', 'age' => 30], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $arr = $col->toArray();

            expect($arr)->toBeArray();
            expect($arr)->toHaveCount(2);
            expect($arr[0])->toHaveKey('email');
            expect($arr[0])->not->toHaveKey('password');
        });

        it('allValues includes hidden fields in serialized DTOs', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25, 'password' => 'secret'], validate: false);

            $col = new DtoCollection([$d1]);
            $all = $col->allValues();

            expect($all[0])->toHaveKey('password');
        });

        it('map returns plain array with callback results', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'Bob', 'age' => 30], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $names = $col->map(fn ($dto) => $dto->name);

            expect($names)->toBe(['Alice', 'Bob']);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. equals / isEmpty / isNotEmpty edge cases
    // ──────────────────────────────────────────────────────────────

    describe('DTO state checks', function (): void {
        it('equals returns true for identical DTOs', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'Bob', 'age' => 30], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('equals ignores hidden fields (compares toArray only)', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25, 'password' => 'a'], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25, 'password' => 'b'], validate: false);

            // Both serialize to same toArray() (password excluded)
            expect($d1->equals($d2))->toBeTrue();
        });

        it('isEmpty: age 0 is not empty for non-nullable int', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => '',
                'name' => '',
            ], validate: false);

            // age defaults to 0, which is non-empty for non-nullable int
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isEmpty: all strings empty and age 0 is not empty', function (): void {
            $dto = StrictValidationDTO::fromArray([
                'name' => '',
                'age' => 0,
            ], validate: false);

            // age=0 is not empty for non-nullable int; score defaults to 50 which is non-empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 5. fromJson edge cases
    // ──────────────────────────────────────────────────────────────

    describe('fromJson edge cases', function (): void {
        it('throws DTOException for invalid JSON', function (): void {
            expect(fn () => ComprehensiveDTO::fromJson('not json at all'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array JSON', function (): void {
            expect(fn () => ComprehensiveDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object JSON (fails validation, not JSON parsing)', function (): void {
            expect(fn () => ComprehensiveDTO::fromJson('{}'))
                ->toThrow(ValidationException::class);
        });

        it('accepts valid JSON object and hydrates correctly', function (): void {
            $dto = ComprehensiveDTO::fromJson('{"email":"a@b.com","name":"Alice","age":25}');
            expect($dto->email)->toBe('a@b.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->age)->toBe(25);
        });

        it('fromJson skips validation when validate: false', function (): void {
            $dto = ComprehensiveDTO::fromJson('{"email":"a@b.com","name":"Alice","age":25}', validate: false);
            expect($dto->email)->toBe('a@b.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. Partial update with DefaultValue attribute
    // ──────────────────────────────────────────────────────────────

    describe('Partial update with DefaultValue', function (): void {
        it('fromPartialArray applies DefaultValue for missing fields', function (): void {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Alice',
            ], validatePresent: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('default@example.com');
            expect($dto->role)->toBe('viewer');
            expect($dto->isActive)->toBeTrue();
            expect($dto->score)->toBe(100);
        });

        it('fromPartialArray overrides DefaultValue when field is provided', function (): void {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Alice',
                'email' => 'custom@example.com',
                'score' => 75,
            ], validatePresent: false);

            expect($dto->email)->toBe('custom@example.com');
            expect($dto->score)->toBe(75);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. MapFrom attribute behavior
    // ──────────────────────────────────────────────────────────────

    describe('MapFrom attribute', function (): void {
        it('maps source key to property name', function (): void {
            $dto = PartialDefaultValueDTO::fromArray([
                'name' => 'Alice',
                'user_role' => 'admin',
            ], validate: false);

            expect($dto->role)->toBe('admin');
        });

        it('uses property name directly when MapFrom key is absent', function (): void {
            $dto = PartialDefaultValueDTO::fromArray([
                'name' => 'Alice',
                'role' => 'editor',
            ], validate: false);

            expect($dto->role)->toBe('editor');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. rules() and rulesFor() consistency
    // ──────────────────────────────────────────────────────────────

    describe('Validation rules consistency', function (): void {
        it('rules() returns array with string keys', function (): void {
            $rules = ComprehensiveDTO::rules();

            foreach ($rules as $field => $ruleList) {
                expect($field)->toBeString();
                expect($ruleList)->toBeArray();
            }
        });

        it('rulesFor() returns same as rules() for default actions', function (): void {
            $rules = ComprehensiveDTO::rules();
            $rulesForCreate = ComprehensiveDTO::rulesFor('create');
            $rulesForUpdate = ComprehensiveDTO::rulesFor('update');
            $rulesForPatch = ComprehensiveDTO::rulesFor('patch');

            expect($rulesForCreate)->toBe($rules);
            expect($rulesForUpdate)->toBe($rules);
            expect($rulesForPatch)->toBe($rules);
        });

        it('rules() includes required and email rules for email field', function (): void {
            $rules = ComprehensiveDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('rules() does not include required for optional fields with defaults', function (): void {
            $rules = ComprehensiveDTO::rules();

            // age has default, should not have required
            expect($rules['age'])->not->toContain('required');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. DTOException factory methods
    // ──────────────────────────────────────────────────────────────

    describe('DTOException factory methods', function (): void {
        it('invalidCast creates exception with property and type info', function (): void {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidCast handles null value display', function (): void {
            $e = DTOException::invalidCast('field', 'string', null);
            expect($e->getMessage())->toContain('field');
            expect($e->getMessage())->toContain('string');
        });

        it('invalidJson creates exception with property and error', function (): void {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString includes class name', function (): void {
            $e = DTOException::invalidCast('field', 'string', null);
            expect((string) $e)->toContain(DTOException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. toJson serialization
    // ──────────────────────────────────────────────────────────────

    describe('toJson serialization', function (): void {
        it('serializes to valid JSON string', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('a@b.com');
            expect($decoded['name'])->toBe('Alice');
        });

        it('toJson excludes hidden fields', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => 25,
                'password' => 'secret',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->not->toHaveKey('password');
        });

        it('jsonSerialize matches toArray output', function (): void {
            $dto = ComprehensiveDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. DtoCollection factory and helpers
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection static factory and helpers', function (): void {
        it('make creates empty collection', function (): void {
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();
        });

        it('make creates collection from array', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $col = DtoCollection::make([$d1]);
            expect($col->count())->toBe(1);
        });

        it('rejects non-DTO items in constructor', function (): void {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('rejects non-DTO items in push', function (): void {
            $col = new DtoCollection;
            expect(fn () => $col->push('not a dto'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('first/last return correct items', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'age' => 30], validate: false);

            $col = new DtoCollection([$d1, $d2]);

            expect($col->first()->email)->toBe('a@b.com');
            expect($col->last()->email)->toBe('b@b.com');
        });

        it('first/last return null for empty collection', function (): void {
            $col = new DtoCollection;
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });

        it('items() returns raw DTO array', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            $items = $col->items();
            expect($items)->toHaveCount(1);
            expect($items[0])->toBe($d1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. DtoCollection pluckKey, toDictionary, toArrayBy
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection key-value extraction', function (): void {
        it('pluckKey creates keyed array by property', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $keyed = $col->pluckKey('email');

            expect($keyed)->toHaveKey('a@b.com');
            expect($keyed)->toHaveKey('c@d.com');
            expect($keyed['a@b.com'])->toHaveKey('email');
            expect($keyed['a@b.com']['name'])->toBe('Alice');
        });

        it('toDictionary extracts key-value pairs', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'age' => 35], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $dict = $col->toDictionary('email', 'name');

            expect($dict)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
        });

        it('toArrayBy delegates to pluckKey', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            $byEmail = $col->toArrayBy('email');
            $byPluck = $col->pluckKey('email');

            expect($byEmail)->toBe($byPluck);
        });

        it('pluckKey skips null key values', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);

            // If we create a DTO with null for the key field, it should be skipped
            // But ComprehensiveDTO email is required, so we'd need to use a different approach
            // For now test with a non-null scenario
            $col = new DtoCollection([$d1]);
            $keyed = $col->pluckKey('password'); // password is null

            // null keys are skipped
            expect($keyed)->toBe([]);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. Validation failure message content
    // ──────────────────────────────────────────────────────────────

    describe('Validation failure produces meaningful errors', function (): void {
        it('invalid email triggers validation error on email field', function (): void {
            try {
                ComprehensiveDTO::fromArray([
                    'email' => 'not-an-email',
                    'name' => 'Alice',
                ]);
                $this->fail('Expected ValidationException');
            } catch (ValidationException $e) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('email');
            }
        });

        it('missing required field triggers validation error', function (): void {
            try {
                ComprehensiveDTO::fromArray([
                    'name' => 'Alice',
                    // missing email
                ]);
                $this->fail('Expected ValidationException');
            } catch (ValidationException $e) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('email');
            }
        });

        it('Min constraint violation triggers error', function (): void {
            try {
                ComprehensiveDTO::fromArray([
                    'email' => 'a@b.com',
                    'name' => 'A', // Min(2)
                ]);
                $this->fail('Expected ValidationException');
            } catch (ValidationException $e) {
                $errors = $e->errors();
                expect($errors)->toHaveKey('name');
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. ArrayAccess interface compliance
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection ArrayAccess compliance', function (): void {
        it('offsetExists returns true for valid index', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeFalse();
        });

        it('offsetGet returns DTO at index or null', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col[0]->email)->toBe('a@b.com');
            expect($col[99])->toBeNull(); // out of bounds
        });

        it('offsetSet allows adding items by index', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'age' => 25], validate: false);
            $d2 = ComprehensiveDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'age' => 30], validate: false);

            $col = new DtoCollection;
            $col[] = $d1;
            $col[5] = $d2;

            expect($col->count())->toBe(2);
            expect($col[0]->email)->toBe('a@b.com');
            expect($col[5]->email)->toBe('b@b.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 15. DtoCollection jsonSerialize
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection JSON serialization', function (): void {
        it('jsonSerialize returns toArray output', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('json_encode produces valid JSON array', function (): void {
            $d1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 25], validate: false);
            $col = new DtoCollection([$d1]);

            $json = json_encode($col);
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded[0])->toHaveKey('email');
        });
    });
});
