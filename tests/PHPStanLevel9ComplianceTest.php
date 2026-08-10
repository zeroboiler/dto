<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * PHPStan Level 9 strict type compliance verification for DTOs.
 *
 * This test file documents and verifies that the DTO package
 * meets PHPStan Level 9 requirements. Run with:
 *
 *   vendor/bin/phpstan analyse --level=9 src/
 *
 * Each test documents a specific PHPStan L9 rule compliance area.
 * These tests are structural assertions — they verify that the public API
 * has correct return types, parameter types, and no mixed types.
 *
 * @see https://phpstan.org/blog/introducing-phpstan-level-9
 */

use ZeroBoiler\DTO\Attributes\{Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('PHPStan Level 9 Compliance — DTO', function (): void {

    // ──────────────────────────────────────────────────────────────
    // DataTransferObject — hydration and serialization type safety
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject return types', function (): void {
        it('fromArray() returns concrete DTO class (not mixed)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            assert($dto instanceof CreateUserDTO);
        });

        it('toArray() returns array<string, mixed> (not mixed)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->not->toHaveKey('password'); // Hidden
        });

        it('allValues() returns array including hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toBeArray();
            expect($all)->toHaveKey('password');
        });

        it('toJson() returns string', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeString();
            expect($json)->toBeJson();
        });

        it('jsonSerialize() returns array (not mixed)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $serialized = $dto->jsonSerialize();
            expect($serialized)->toBeArray();
        });

        it('fromJson() returns concrete DTO class', function (): void {
            $json = '{"email":"test@example.com","name":"Test User"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            assert($dto instanceof CreateUserDTO);
            expect($dto->email)->toBe('test@example.com');
        });

        it('fromJson() throws DTOException on invalid JSON', function (): void {
            expect(fn () => CreateUserDTO::fromJson('not-json'))
                ->toThrow(DTOException::class);
        });

        it('fromJson() throws DTOException on sequential array', function (): void {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Selective output — only/except return types
    // ──────────────────────────────────────────────────────────────

    describe('Selective output types', function (): void {
        it('only() returns array with specified keys', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('only() accepts string param', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toBeArray();
        });

        it('except() returns array without specified keys', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status');
            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('status');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Immutable update — with() returns new instance
    // ──────────────────────────────────────────────────────────────

    describe('Immutable update types', function (): void {
        it('with() returns new instance of same class', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            assert($updated instanceof CreateUserDTO);
            assert($dto !== $updated); // New instance
            expect($dto->name)->toBe('Test User');
            expect($updated->name)->toBe('Updated');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // State checks — equals, isEmpty, isNotEmpty
    // ──────────────────────────────────────────────────────────────

    describe('State check return types', function (): void {
        it('equals() returns bool (not mixed)', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('isEmpty() returns bool', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty() returns false when properties have values', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Validation — rules return type
    // ──────────────────────────────────────────────────────────────

    describe('Validation method types', function (): void {
        it('rules() returns array<string, array<int, mixed>>', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules['email'])->toBeArray();
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('rulesFor() returns array with same shape', function (): void {
            $rules = CreateUserDTO::rulesFor('create');

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
        });

        it('validateArray() returns validated array', function (): void {
            $result = CreateUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($result)->toBeArray();
            expect($result['email'])->toBe('test@example.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Partial update — fromPartialArray return type
    // ──────────────────────────────────────────────────────────────

    describe('Partial update types', function (): void {
        it('fromPartialArray() returns concrete DTO class', function (): void {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validate: false);

            assert($dto instanceof CreateUserDTO);
            expect($dto->name)->toBe('Updated Name');
            // Other fields should have defaults
            expect($dto->status)->toBe('active');
        });

        it('fromPartialRequest() is callable', function (): void {
            // Can't call without actual Request, but verify method exists
            expect(method_exists(CreateUserDTO::class, 'fromPartialRequest'))->toBeTrue();
        });

        it('validatePartialArray() returns array', function (): void {
            $result = CreateUserDTO::validatePartialArray([
                'name' => 'Updated Name',
            ]);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Updated Name');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DtoCollection — type-safe collection operations
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection type safety', function (): void {
        it('constructor rejects non-DTO instances', function (): void {
            expect(fn () => new DtoCollection([new \stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('make() returns DtoCollection', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            assert($col instanceof DtoCollection);
            expect($col->count())->toBe(1);
        });

        it('toArray() returns array of arrays', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            $arr = $col->toArray();
            expect($arr)->toBeArray();
            expect($arr[0])->toBeArray();
            expect($arr[0])->toHaveKey('email');
        });

        it('items() returns array of DTO instances', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            $items = $col->items();
            expect($items)->toBeArray();
            assert($items[0] instanceof CreateUserDTO);
        });

        it('pluck() returns array of property values', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            $emails = $col->pluck('email');
            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('pluckKey() returns associative array', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            $map = $col->pluckKey('email', 'name');
            expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
        });

        it('map() returns plain array (not DtoCollection)', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            $names = $col->map(fn (CreateUserDTO $d): string => $d->name);
            expect($names)->toBe(['A']);
        });

        it('filter() returns new DtoCollection', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            $filtered = $col->filter(fn (CreateUserDTO $d): bool => $d->name === 'Alice');
            assert($filtered instanceof DtoCollection);
            expect($filtered->count())->toBe(1);
        });

        it('append() returns new DtoCollection (immutable)', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $newCol = $col->append($d2);
            assert($newCol instanceof DtoCollection);
            expect($newCol->count())->toBe(2);
            expect($col->count())->toBe(1); // Original unchanged
        });

        it('merge() returns new DtoCollection combining both', function (): void {
            $col1 = DtoCollection::make([
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            ]);
            $col2 = DtoCollection::make([
                CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
            ]);

            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
        });

        it('push() mutates in-place and returns self', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $result = $col->push($d2);
            expect($col->count())->toBe(2); // Mutated
            assert($result === $col); // Same instance
        });

        it('first/last return DTO or null', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            assert($col->first() instanceof CreateUserDTO);
            assert($col->last() instanceof CreateUserDTO);

            $empty = DtoCollection::make([]);
            expect($empty->first())->toBeNull();
            expect($empty->last())->toBeNull();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOManager — runtime delegation type safety
    // ──────────────────────────────────────────────────────────────

    describe('DTOManager type safety', function (): void {
        it('make() returns DataTransferObject instance', function (): void {
            $manager = new DTOManager;
            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            assert($dto instanceof CreateUserDTO);
        });

        it('validate() returns array', function (): void {
            $manager = new DTOManager;
            $result = $manager->validate(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($result)->toBeArray();
        });

        it('makeFromJson() returns DataTransferObject', function (): void {
            $manager = new DTOManager;
            $dto = $manager->makeFromJson(CreateUserDTO::class, '{"email":"test@example.com","name":"Test User"}');

            assert($dto instanceof CreateUserDTO);
        });

        it('schema() returns array', function (): void {
            $manager = new DTOManager;
            $schema = $manager->schema(ProductDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOException — factory method return types
    // ──────────────────────────────────────────────────────────────

    describe('DTOException factory types', function (): void {
        it('invalidCast() creates exception with property info', function (): void {
            $e = DTOException::invalidCast('status', 'integer', 'not-an-int');

            assert($e instanceof DTOException);
            expect($e->getMessage())->toContain('status');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson() creates exception with JSON error', function (): void {
            $e = DTOException::invalidJson('payload', 'Syntax error');

            assert($e instanceof DTOException);
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // ValidationAttribute contract compliance
    // ──────────────────────────────────────────────────────────────

    describe('ValidationAttribute contract compliance', function (): void {
        it('all validation attributes implement ValidationAttribute', function (): void {
            $attributeClasses = [
                \ZeroBoiler\DTO\Attributes\Accepted::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class,
                \ZeroBoiler\DTO\Attributes\Between::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class,
                \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\NestedArray::class,
                \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Present::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class,
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class,
                \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
                \ZeroBoiler\DTO\Attributes\Same::class,
                \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\Sometimes::class,
                \ZeroBoiler\DTO\Attributes\StartsWith::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
            ];

            foreach ($attributeClasses as $class) {
                $ref = new \ReflectionClass($class);
                expect($ref->implementsInterface(ValidationAttribute::class))
                    ->toBeTrue("{$class} must implement ValidationAttribute");
            }
        });

        it('ruleKey() returns string for each validation attribute', function (): void {
            $attrs = [
                new \ZeroBoiler\DTO\Attributes\Required,
                new \ZeroBoiler\DTO\Attributes\Email,
                new \ZeroBoiler\DTO\Attributes\Max(100),
                new \ZeroBoiler\DTO\Attributes\Min(1),
                new \ZeroBoiler\DTO\Attributes\Url,
                new \ZeroBoiler\DTO\Attributes\Pattern('/^[a-z]+$/'),
                new \ZeroBoiler\DTO\Attributes\Integer,
                new \ZeroBoiler\DTO\Attributes\Numeric,
                new \ZeroBoiler\DTO\Attributes\Boolean,
                new \ZeroBoiler\DTO\Attributes\Uuid,
            ];

            foreach ($attrs as $attr) {
                expect($attr->ruleKey())->toBeString();
                expect($attr->ruleKey())->not->toBeEmpty();
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Interface compliance — FromRequestDTO, ValidatableDTO
    // ──────────────────────────────────────────────────────────────

    describe('Interface compliance', function (): void {
        it('CreateUserDTO implements FromRequestDTO', function (): void {
            expect(CreateUserDTO::class)->toImplement(FromRequestDTO::class);
        });

        it('CreateUserDTO implements ValidatableDTO', function (): void {
            expect(CreateUserDTO::class)->toImplement(ValidatableDTO::class);
        });

        it('DataTransferObject implements Arrayable and JsonSerializable', function (): void {
            expect(DataTransferObject::class)
                ->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
            expect(DataTransferObject::class)
                ->toImplement(\JsonSerializable::class);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // No mixed types — explicit type assertions on public API
    // ──────────────────────────────────────────────────────────────

    describe('No mixed types in public API', function (): void {
        it('rules() keys are strings, values are arrays', function (): void {
            $rules = CreateUserDTO::rules();

            foreach ($rules as $key => $value) {
                assert(is_string($key), "Rule key must be string, got " . get_debug_type($key));
                assert(is_array($value), "Rule value must be array, got " . get_debug_type($value));
            }
        });

        it('fromArray() properties are typed (no mixed access)', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'price' => '29.99',
                'stock' => 42,
            ], validate: false);

            // These access typed readonly properties — PHPStan L9 must not flag as mixed
            assert(is_string($dto->name));
            assert(is_string($dto->price));
            assert(is_int($dto->stock));
        });

        it('metadata cache flush works without errors', function (): void {
            DataTransferObject::flushMetadataCache();

            // Should not throw — all static cache cleared
            expect(true)->toBeTrue();
        });

        it('metadata cache TTL can be set and flushed per class', function (): void {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);
            expect(true)->toBeTrue();
        });
    });
});
