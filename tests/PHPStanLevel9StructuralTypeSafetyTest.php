<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('PHPStan Level 9 — Structural Type Safety Contract', function () {
    // ─────────────────────────────────────────────────────────────
    // §1: DataTransferObject — all public methods return declared types
    // ─────────────────────────────────────────────────────────────

    describe('fromArray() — type-safe construction', function () {
        it('returns correct DTO instance type', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('accepts optional nullable fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'phone' => '+1234567890',
            ]);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('respects DefaultValue attribute', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($dto->status)->toBe('active');
        });

        it('respects MapFrom attribute for key mapping', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'phone_number' => '555-1234',
            ]);

            expect($dto->phone)->toBe('555-1234');
        });
    });

    describe('toArray() — consistent output types', function () {
        it('returns associative array with string keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toBeArray();
            expect($arr)->toHaveKeys(['email', 'name', 'status', 'tags']);
            expect($arr['email'])->toBeString();
            expect($arr['tags'])->toBeArray();
        });

        it('excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('password');
        });

        it('uses property name (not mapped key) for serialization', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'phone_number' => '555-1234',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('phone');
            expect($arr)->not->toHaveKey('phone_number');
        });
    });

    describe('toJson() — valid JSON string', function () {
        it('returns valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeString();
            expect(json_validate($json))->toBeTrue();

            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('accepts JSON encoding options', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
        });
    });

    describe('fromJson() — round-trip fidelity', function () {
        it('restores DTO from its own JSON', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'tags' => ['php', 'laravel'],
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored)->toBeInstanceOf(CreateUserDTO::class);
            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
            expect($restored->tags)->toBe($original->tags);
        });
    });

    describe('equals() — value-based comparison', function () {
        it('returns true for identical data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);
            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for different data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $b = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('only() / except() — array filtering', function () {
        it('only() returns selected keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('only() accepts array of keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only(['email', 'name']);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['email', 'name']);
        });

        it('except() excludes specified keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->toBeArray();
            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('except() accepts array of keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->except(['email', 'status']);

            expect($result)->toBeArray();
            expect($result)->not->toHaveKey('email');
            expect($result)->not->toHaveKey('status');
            expect($result)->toHaveKey('name');
        });
    });

    describe('with() — immutable override', function () {
        it('returns new instance with overridden field', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $modified = $original->with(['name' => 'New Name']);

            expect($modified)->toBeInstanceOf(CreateUserDTO::class);
            expect($modified)->not->toBe($original);
            expect($modified->name)->toBe('New Name');
            expect($original->name)->toBe('Test User');
        });

        it('accepts validate flag for re-validation', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            // with validate=false should not throw for valid data
            $modified = $original->with(['name' => 'Updated'], validate: false);

            expect($modified->name)->toBe('Updated');
        });
    });

    describe('isEmpty() — all-nullable-or-empty detection', function () {
        it('returns true for DTO with only default values', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
        });

        it('returns false for DTO with non-default values', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('fromPartialArray() — partial update hydration', function () {
        it('creates DTO with subset of fields', function () {
            $dto = CreateUserDTO::fromPartialArray(
                ['name' => 'Updated Name'],
                validate: false,
            );

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Updated Name');
        });

        it('nullable fields default to null when omitted', function () {
            $dto = CreateUserDTO::fromPartialArray(
                ['email' => 'test@example.com'],
                validate: false,
            );

            expect($dto->phone)->toBeNull();
        });
    });

    describe('allValues() — includes hidden properties', function () {
        it('returns all values including Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();

            // toArray() excludes hidden, allValues() includes them
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §2: Validation attribute types — ruleKey() returns string
    // ─────────────────────────────────────────────────────────────

    describe('ValidationAttribute contract — ruleKey() returns string', function () {
        it('Required implements ValidationAttribute', function () {
            $attr = new \ZeroBoiler\DTO\Attributes\Required;

            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
            expect($attr->ruleKey())->toBe('required');
        });

        it('ruleKey() always returns non-empty string for validation attributes', function () {
            $validationAttrs = [
                new \ZeroBoiler\DTO\Attributes\Required,
                new \ZeroBoiler\DTO\Attributes\Email,
                new \ZeroBoiler\DTO\Attributes\Integer,
                new \ZeroBoiler\DTO\Attributes\Numeric,
                new \ZeroBoiler\DTO\Attributes\Boolean,
                new \ZeroBoiler\DTO\Attributes\Url,
                new \ZeroBoiler\DTO\Attributes\Uuid,
                new \ZeroBoiler\DTO\Attributes\Min(1),
                new \ZeroBoiler\DTO\Attributes\Max(100),
                new \ZeroBoiler\DTO\Attributes\Between(1, 100),
                new \ZeroBoiler\DTO\Attributes\Pattern('/^[a-z]+$/'),
                new \ZeroBoiler\DTO\Attributes\StartsWith('foo'),
                new \ZeroBoiler\DTO\Attributes\EndsWith('bar'),
                new \ZeroBoiler\DTO\Attributes\In(['a', 'b', 'c']),
                new \ZeroBoiler\DTO\Attributes\Accepted,
                new \ZeroBoiler\DTO\Attributes\Confirmed,
                new \ZeroBoiler\DTO\Attributes\Declined,
                new \ZeroBoiler\DTO\Attributes\Different('other_field'),
                new \ZeroBoiler\DTO\Attributes\Distinct,
                new \ZeroBoiler\DTO\Attributes\Json,
                new \ZeroBoiler\DTO\Attributes\Nullable,
                new \ZeroBoiler\DTO\Attributes\Present,
                new \ZeroBoiler\DTO\Attributes\Prohibited,
                new \ZeroBoiler\DTO\Attributes\RequiredIf('status', 'active'),
                new \ZeroBoiler\DTO\Attributes\RequiredUnless('status', 'active'),
                new \ZeroBoiler\DTO\Attributes\RequiredWith('field'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithAll('field'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithout('field'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithoutAll('field'),
                new \ZeroBoiler\DTO\Attributes\Same('other_field'),
                new \ZeroBoiler\DTO\Attributes\Size(10),
                new \ZeroBoiler\DTO\Attributes\Sometimes,
                new \ZeroBoiler\DTO\Attributes\Date,
                new \ZeroBoiler\DTO\Attributes\ArrayRule,
                new \ZeroBoiler\DTO\Attributes\NestedArray(EmptyDTO::class),
                new \ZeroBoiler\DTO\Attributes\Collection(EmptyDTO::class),
                new \ZeroBoiler\DTO\Attributes\Enum(\stdClass::class),
            ];

            foreach ($validationAttrs as $attr) {
                expect($attr)->toBeInstanceOf(ValidationAttribute::class);
                expect($attr->ruleKey())->toBeString();
                expect(strlen($attr->ruleKey()))->toBeGreaterThan(0);
            }
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §3: Metadata-only attributes — do NOT implement ValidationAttribute
    // ─────────────────────────────────────────────────────────────

    describe('Metadata-only attributes — no ruleKey()', function () {
        it('Hidden is not a ValidationAttribute', function () {
            $attr = new Hidden;

            expect($attr)->not->toBeInstanceOf(ValidationAttribute::class);
        });

        it('Cast is not a ValidationAttribute', function () {
            $attr = new Cast('integer');

            expect($attr)->not->toBeInstanceOf(ValidationAttribute::class);
        });

        it('DefaultValue is not a ValidationAttribute', function () {
            $attr = new \ZeroBoiler\DTO\Attributes\DefaultValue('active');

            expect($attr)->not->toBeInstanceOf(ValidationAttribute::class);
        });

        it('MapFrom is not a ValidationAttribute', function () {
            $attr = new \ZeroBoiler\DTO\Attributes\MapFrom('source_key');

            expect($attr)->not->toBeInstanceOf(ValidationAttribute::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §4: DTOException — factory methods return self
    // ─────────────────────────────────────────────────────────────

    describe('DTOException — named constructors', function () {
        it('invalidCast() creates with property, type, value info', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not_a_number');

            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidJson() creates with property and error info', function () {
            $ex = DTOException::invalidJson('metadata', 'Syntax error');

            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('metadata');
            expect($ex->getMessage())->toContain('Syntax error');
        });

        it('__toString() returns class name + message', function () {
            $ex = DTOException::invalidCast('field', 'int', 'abc');

            $str = (string) $ex;

            expect($str)->toBeString();
            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('Cannot cast');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §5: rules() — return type is array<string, list<string|Rule>>
    // ─────────────────────────────────────────────────────────────

    describe('rules() — validation rule generation', function () {
        it('generates rules matching property types', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKeys(['email', 'name', 'status', 'tags', 'phone']);
        });

        it('email field has required + email rules', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('nullable fields have sometimes rule', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['phone'])->toContain('sometimes');
        });

        it('Hidden fields are not in rules', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->not->toHaveKey('password');
        });

        it('rulesFor() returns action-specific rules', function () {
            $createRules = CreateUserDTO::rulesFor('create');
            $updateRules = CreateUserDTO::rulesFor('update');

            expect($createRules)->toBeArray();
            expect($updateRules)->toBeArray();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §6: DtoCollection — type-safe iteration
    // ─────────────────────────────────────────────────────────────

    describe('DtoCollection — collection type safety', function () {
        it('wraps array of DTOs', function () {
            $items = [
                AddressDTO::fromArray(['city' => 'Istanbul', 'country' => 'TR'], validate: false),
                AddressDTO::fromArray(['city' => 'Ankara', 'country' => 'TR'], validate: false),
            ];

            $collection = new DtoCollection($items);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('first() returns DTO or null', function () {
            $collection = new DtoCollection([
                AddressDTO::fromArray(['city' => 'Istanbul', 'country' => 'TR'], validate: false),
            ]);

            expect($collection->first())->toBeInstanceOf(AddressDTO::class);
        });

        it('first() returns null for empty collection', function () {
            $collection = new DtoCollection([]);

            expect($collection->first())->toBeNull();
        });

        it('map() returns array via callable', function () {
            $collection = new DtoCollection([
                AddressDTO::fromArray(['city' => 'Istanbul', 'country' => 'TR'], validate: false),
                AddressDTO::fromArray(['city' => 'Ankara', 'country' => 'TR'], validate: false),
            ]);

            $cities = $collection->map(fn (DataTransferObject $dto): string => $dto->city);

            expect($cities)->toBe(['Istanbul', 'Ankara']);
        });

        it('toArray() returns plain array representation', function () {
            $collection = new DtoCollection([
                AddressDTO::fromArray(['city' => 'Istanbul', 'country' => 'TR'], validate: false),
            ]);

            $arr = $collection->toArray();

            expect($arr)->toBeArray();
            expect($arr[0])->toHaveKey('city');
        });

        it('isEmpty() works correctly', function () {
            expect((new DtoCollection([]))->isEmpty())->toBeTrue();
            expect(
                (new DtoCollection([
                    AddressDTO::fromArray(['city' => 'X', 'country' => 'Y'], validate: false),
                ]))->isEmpty()
            )->toBeFalse();
        });

        it('toArrayBy() groups by specified field', function () {
            $collection = new DtoCollection([
                AddressDTO::fromArray(['city' => 'Istanbul', 'country' => 'TR'], validate: false),
                AddressDTO::fromArray(['city' => 'Ankara', 'country' => 'TR'], validate: false),
            ]);

            $grouped = $collection->toArrayBy('country');

            expect($grouped)->toBeArray();
            expect($grouped)->toHaveKey('TR');
            expect(count($grouped['TR']))->toBe(2);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §7: Nested DTO hydration — type preservation
    // ─────────────────────────────────────────────────────────────

    describe('Nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'city' => 'Istanbul',
                    'country' => 'TR',
                ],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->city)->toBe('Istanbul');
        });

        it('hydrates NestedArray of DTOs from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-002',
                'shippingAddress' => ['city' => 'Ankara', 'country' => 'TR'],
                'items' => [
                    ['name' => 'Widget A', 'quantity' => 3],
                    ['name' => 'Widget B', 'quantity' => 1],
                ],
            ], validate: false);

            expect($dto->items)->toBeArray();
            expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->items[0]->name)->toBe('Widget A');
        });

        it('serializes nested DTO to array recursively', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-003',
                'shippingAddress' => ['city' => 'Izmir', 'country' => 'TR'],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Izmir');
        });

        it('nested DTO round-trips through JSON', function () {
            $original = OrderDTO::fromArray([
                'orderNumber' => 'ORD-004',
                'shippingAddress' => ['city' => 'Bursa', 'country' => 'TR'],
                'items' => [
                    ['name' => 'Part X', 'quantity' => 5],
                ],
            ], validate: false);

            $json = $original->toJson();
            $restored = OrderDTO::fromJson($json, validate: false);

            expect($restored->shippingAddress->city)->toBe('Bursa');
            expect($restored->items[0]->name)->toBe('Part X');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §8: Union type handling — strict preservation
    // ─────────────────────────────────────────────────────────────

    describe('Union type DTO', function () {
        it('accepts int value for int|string union', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'u1',
                'identifier' => 42,
            ], validate: false);

            expect($dto->identifier)->toBe(42);
            expect(is_int($dto->identifier))->toBeTrue();
        });

        it('accepts string value for int|string union', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'u1',
                'identifier' => 'abc',
            ], validate: false);

            expect($dto->identifier)->toBe('abc');
            expect(is_string($dto->identifier))->toBeTrue();
        });

        it('round-trips union type through JSON', function () {
            $original = UnionTypeDTO::fromArray([
                'id' => 'u1',
                'identifier' => 42,
            ], validate: false);

            $json = $original->toJson();
            $restored = UnionTypeDTO::fromJson($json, validate: false);

            expect($restored->identifier)->toBe(42);
        });

        it('preserves string type through JSON round-trip', function () {
            $original = UnionTypeDTO::fromArray([
                'id' => 'u1',
                'identifier' => 'abc',
            ], validate: false);

            $json = $original->toJson();
            $restored = UnionTypeDTO::fromJson($json, validate: false);

            expect($restored->identifier)->toBe('abc');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §9: Date cast — Carbon preservation
    // ─────────────────────────────────────────────────────────────

    describe('Date cast type safety', function () {
        it('casts string to Carbon instance', function () {
            $dto = DateCastDTO::fromArray([
                'event_date' => '2024-06-15 10:30:00',
            ], validate: false);

            expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('passes Carbon through as-is', function () {
            $carbon = \Carbon\Carbon::parse('2024-06-15');
            $dto = DateCastDTO::fromArray([
                'event_date' => $carbon,
            ], validate: false);

            expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('serializes Carbon to string in toArray()', function () {
            $dto = DateCastDTO::fromArray([
                'event_date' => '2024-06-15 10:30:00',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['event_date'])->toBeString();
            expect($arr['event_date'])->toContain('2024');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §10: __toString() returns class name
    // ─────────────────────────────────────────────────────────────

    describe('DTO __toString()', function () {
        it('returns fully qualified class name', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $str = (string) $dto;

            expect($str)->toBe(CreateUserDTO::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §11: Metadata cache — static lifecycle
    // ─────────────────────────────────────────────────────────────

    describe('Metadata cache lifecycle', function () {
        it('flushMetadataCache() clears static cache', function () {
            DataTransferObject::fromArray([
                'email' => 'before@example.com',
                'name' => 'Before',
            ], validate: false);

            DataTransferObject::flushMetadataCache();

            // After flush, a new fromArray call still works
            $dto = DataTransferObject::fromArray([
                'email' => 'after@example.com',
                'name' => 'After',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('setMetadataCacheTtl() changes TTL', function () {
            DataTransferObject::setMetadataCacheTtl(0.0);
            DataTransferObject::flushMetadataCache();

            // TTL=0 means no caching — fromArray still works
            $dto = CreateUserDTO::fromArray([
                'email' => 'ttl@test.com',
                'name' => 'TTL Test',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);

            // Restore default
            DataTransferObject::setMetadataCacheTtl(300.0);
        });

        it('flushMetadataCache with class parameter clears only that class', function () {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            $dto = CreateUserDTO::fromArray([
                'email' => 'class@test.com',
                'name' => 'Class Test',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §12: Strict type casting — Cast attribute types
    // ─────────────────────────────────────────────────────────────

    describe('Cast attribute type contracts', function () {
        it('creates with string type', function () {
            $attr = new Cast('integer');

            expect($attr->type)->toBe('integer');
            expect($attr->type)->toBeString();
        });

        it('MapFrom creates with string key', function () {
            $attr = new \ZeroBoiler\DTO\Attributes\MapFrom('phone_number');

            expect($attr->key)->toBe('phone_number');
            expect($attr->key)->toBeString();
        });

        it('DefaultValue preserves mixed value types', function () {
            $str = new \ZeroBoiler\DTO\Attributes\DefaultValue('active');
            $int = new \ZeroBoiler\DTO\Attributes\DefaultValue(42);
            $arr = new \ZeroBoiler\DTO\Attributes\DefaultValue([]);
            $null = new \ZeroBoiler\DTO\Attributes\DefaultValue(null);

            expect($str->value)->toBe('active');
            expect($int->value)->toBe(42);
            expect($arr->value)->toBe([]);
            expect($null->value)->toBeNull();
        });
    });
});
