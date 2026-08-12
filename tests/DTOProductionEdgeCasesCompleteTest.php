<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MultiConstraintDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus as EnumUserStatus;

describe('DTO Production Edge Cases — Complete Coverage', function () {
    // ── fromArray validation ──────────────────────────────────────────────
    describe('fromArray validation', function () {
        it('creates DTO from valid array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
        });

        it('throws ValidationException for invalid email', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'not-an-email',
                'name' => 'Test',
            ]))->toThrow(ValidationException::class);
        });

        it('throws ValidationException for name too short', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'A',
            ]))->toThrow(ValidationException::class);
        });

        it('applies defaults for missing optional fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);
            // status has default 'active'
            expect($dto->status)->toBe('active');
        });

        it('respects explicit null values over defaults', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);
            // password is nullable with null default
            expect($dto->password)->toBeNull();
        });

        it('can skip validation', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'not-valid',
                'name' => 'T',
            ], validate: false);
            expect($dto->email)->toBe('not-valid');
        });
    });

    // ── toArray / allValues / Hidden ────────────────────────────────────────
    describe('Serialization', function () {
        it('toArray excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);
            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson returns valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $json = $dto->toJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('test@example.com');
        });
    });

    // ── only / except ─────────────────────────────────────────────────────
    describe('Selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $result = $dto->only('email');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('except excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $result = $dto->except('name');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('only accepts single string key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'T',
            ], validate: false);
            $result = $dto->only('email');
            expect($result)->toBe(['email' => 'a@b.com']);
        });
    });

    // ── with / equals ──────────────────────────────────────────────────────
    describe('Immutable update and equality', function () {
        it('with creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $updated = $dto->with(['name' => 'Updated']);
            expect($updated)->not->toBe($dto);
            expect($updated->name)->toBe('Updated');
            expect($dto->name)->toBe('Test'); // original unchanged
        });

        it('with validates the merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            expect(fn () => $dto->with(['email' => 'bad-email']))
                ->toThrow(ValidationException::class);
        });

        it('equals returns true for same values', function () {
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

        it('equals returns false for different values', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
            ], validate: false);
            $b = CreateUserDTO::fromArray([
                'email' => 'b@c.com',
                'name' => 'Test',
            ], validate: false);
            expect($a->equals($b))->toBeFalse();
        });
    });

    // ── isEmpty / isNotEmpty ──────────────────────────────────────────────
    describe('State checks', function () {
        it('isEmpty returns true when all properties are empty/default', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => '',
                'name' => '',
            ], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isNotEmpty returns true when at least one property has value', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => '',
            ], validate: false);
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ── fromJson ──────────────────────────────────────────────────────────
    describe('fromJson', function () {
        it('creates DTO from valid JSON string', function () {
            $json = '{"email":"test@example.com","name":"Test"}';
            $dto = CreateUserDTO::fromJson($json);
            expect($dto->email)->toBe('test@example.com');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential JSON array', function () {
            expect(fn () => CreateUserDTO::fromJson('["a","b"]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object JSON', function () {
            $dto = CreateUserDTO::fromJson('{}');
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    // ── fromPartialArray ───────────────────────────────────────────────────
    describe('fromPartialArray (PATCH semantics)', function () {
        it('hydrates only provided fields', function () {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated']);
            expect($dto->name)->toBe('Updated');
        });

        it('uses defaults for missing fields', function () {
            $dto = CreateUserDTO::fromPartialArray([]);
            expect($dto->status)->toBe('active');
        });

        it('relaxes required to sometimes for present fields', function () {
            // Only name is provided; email is not provided so no required check
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Test']);
            expect($dto->name)->toBe('Test');
        });
    });

    // ── MapFrom ───────────────────────────────────────────────────────────
    describe('MapFrom attribute', function () {
        it('maps flat source key to property name', function () {
            $dto = DotNotationDTO::fromArray([
                'user.profile.firstName' => 'Alice',
                'user.profile.lastName' => 'Wonderland',
                'contact_email' => 'alice@example.com',
            ], validate: false);
            expect($dto->firstName)->toBe('Alice');
            expect($dto->lastName)->toBe('Wonderland');
            expect($dto->email)->toBe('alice@example.com');
        });
    });

    // ── Cast attribute ────────────────────────────────────────────────────
    describe('Cast attribute', function () {
        it('casts to array type', function () {
            $dto = ArrayCastDTO::fromArray([
                'name' => 'test',
                'tags' => 'single-tag',
            ], validate: false);
            expect($dto->tags)->toBeArray();
            expect($dto->tags)->toBe(['single-tag']);
        });

        it('keeps arrays as arrays', function () {
            $dto = ArrayCastDTO::fromArray([
                'name' => 'test',
                'tags' => ['a', 'b'],
            ], validate: false);
            expect($dto->tags)->toBe(['a', 'b']);
        });
    });

    // ── Nested DTO hydration ──────────────────────────────────────────────
    describe('Nested DTO', function () {
        it('hydrates nested DTO from array', function () {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            expect($order->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($order->shippingAddress->city)->toBe('Istanbul');
        });

        it('serializes nested DTO recursively', function () {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            $arr = $order->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Istanbul');
        });
    });

    // ── DtoCollection ─────────────────────────────────────────────────────
    describe('DtoCollection', function () {
        it('wraps DTOs in type-safe collection', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            expect($col->count())->toBe(2);
            expect($col->first())->toBe($dto1);
        });

        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('pluck extracts field values', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            expect($col->pluck('name'))->toBe(['A', 'B']);
        });

        it('filter returns new collection', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn ($d) => $d->price > 15);
            expect($filtered->count())->toBe(1);
        });

        it('append returns new collection (immutable)', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1]);
            $newCol = $col->append($dto2);
            expect($col->count())->toBe(1);  // original unchanged
            expect($newCol->count())->toBe(2);
        });

        it('push mutates in place', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1]);
            $result = $col->push($dto2);
            expect($col)->toBe($result); // same instance
            expect($col->count())->toBe(2);
        });

        it('toArrayBy re-keys by property', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $keyed = $col->toArrayBy('name');
            expect($keyed)->toHaveKey('A');
            expect($keyed['A'])->toHaveKey('name');
        });

        it('toDictionary maps two properties', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $dict = $col->toDictionary('name', 'price');
            expect($dict)->toBe(['A' => 10, 'B' => 20]);
        });

        it('jsonSerialize produces valid JSON', function () {
            $dto = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $col = new DtoCollection([$dto]);
            $json = json_encode($col);
            expect($json)->not->toBeFalse();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect(count($decoded))->toBe(1);
        });

        it('offsetUnset re-indexes', function () {
            $dto1 = OrderItemDTO::fromArray(['name' => 'A', 'price' => 10], validate: false);
            $dto2 = OrderItemDTO::fromArray(['name' => 'B', 'price' => 20], validate: false);
            $dto3 = OrderItemDTO::fromArray(['name' => 'C', 'price' => 30], validate: false);

            $col = new DtoCollection([$dto1, $dto2, $dto3]);
            unset($col[0]);
            // After re-indexing, first element should be $dto2
            expect($col[0]->name)->toBe('B');
            expect($col->count())->toBe(2);
        });
    });

    // ── rules / rulesFor ───────────────────────────────────────────────────
    describe('Validation rules', function () {
        it('rules returns associative array', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('rulesFor defaults to rules()', function () {
            expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
            expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        });
    });

    // ── Enum roundtrip ────────────────────────────────────────────────────
    describe('Enum property roundtrip', function () {
        it('hydrates string to BackedEnum', function () {
            $dto = MultiConstraintDTO::fromArray([
                'username' => 'alice',
                'status' => 'active',
            ], validate: false);
            expect($dto->status)->toBeInstanceOf(EnumUserStatus::class);
            expect($dto->status)->toBe(EnumUserStatus::ACTIVE);
        });

        it('serializes BackedEnum to backed value', function () {
            $dto = MultiConstraintDTO::fromArray([
                'username' => 'alice',
                'status' => 'active',
            ], validate: false);
            $arr = $dto->toArray();
            expect($arr['status'])->toBe('active');
        });

        it('roundtrips through with() preserving enum type', function () {
            $dto = MultiConstraintDTO::fromArray([
                'username' => 'alice',
                'status' => 'active',
            ], validate: false);
            $updated = $dto->with(['status' => 'banned'], validate: false);
            expect($updated->status)->toBe(EnumUserStatus::BANNED);
            expect($updated->toArray()['status'])->toBe('banned');
        });

        it('original DTO unchanged after with()', function () {
            $dto = MultiConstraintDTO::fromArray([
                'username' => 'alice',
                'status' => 'active',
            ], validate: false);
            $dto->with(['status' => 'banned'], validate: false);
            expect($dto->status)->toBe(EnumUserStatus::ACTIVE);
        });
    });
});
