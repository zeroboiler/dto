<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);


// ─── Test Fixtures ───────────────────────────────────────────────────────────

namespace ZeroBoiler\DTO\Tests\DtoEdgeCaseFixture {

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Label
{
    public function __construct(
        public readonly string $value,
    ) {}
}

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

/**
 * Minimal DTO for testing basic hydration and serialization.
 */
class SimpleDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Nullable]
        public readonly ?string $email = null,
        #[DefaultValue('active')]
        public readonly string $status = 'active',
    ) {}
}

/**
 * DTO with all basic scalar types for cast verification.
 */
class AllTypesDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count = 0,
        #[Cast('float')]
        public readonly float $price = 0.0,
        #[Cast('string')]
        public readonly string $label = '',
        #[Cast('boolean')]
        public readonly bool $active = false,
        #[Cast('array')]
        public readonly array $tags = [],
    ) {}
}

/**
 * DTO with MapFrom for testing key aliasing.
 */
class AliasedDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        #[MapFrom('user_email')]
        #[Nullable]
        public readonly ?string $email = null,
    ) {}
}

/**
 * DTO with Hidden property.
 */
class SensitiveDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $username,
        #[Hidden]
        public readonly string $password,
        #[Hidden]
        public readonly ?string $token = null,
    ) {}
}

/**
 * DTO with nested structure using MapFrom dot notation.
 */
class ProfileDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $displayName,
        #[MapFrom('meta.age')]
        #[Nullable]
        public readonly ?int $age = null,
        #[MapFrom('meta.bio')]
        #[Nullable]
        public readonly ?string $bio = null,
    ) {}
}

/**
 * DTO with Min/Max validation attributes.
 */
class ValidatedDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Required]
        #[Min(3)]
        #[Max(100)]
        public readonly string $title,
        #[Min(1)]
        #[Max(1000)]
        public readonly int $quantity = 1,
    ) {}
}

}

namespace ZeroBoiler\DTO\Tests {
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;

// ─── Test Suite ───────────────────────────────────────────────────────────────

describe('DTO Edge Cases — Hydration & Serialization', function () {
    describe('SimpleDTO basic hydration', function () {
        it('creates from array with all fields', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->name)->toBe('Jane Doe');
            expect($dto->email)->toBe('jane@example.com');
            expect($dto->status)->toBe('inactive');
        });

        it('applies default value when field is absent', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'John',
            ], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->email)->toBeNull();
        });

        it('respects explicit null for nullable field', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'John',
                'email' => null,
            ], validate: false);

            expect($dto->email)->toBeNull();
        });

        it('respects explicit empty string (does not override with default)', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'John',
                'status' => '',
            ], validate: false);

            expect($dto->status)->toBe('');
        });
    });

    describe('toArray and serialization', function () {
        it('toArray excludes hidden fields', function () {
            $dto = SensitiveDTO::fromArray([
                'username' => 'admin',
                'password' => 'secret123',
                'token' => 'tok_abc',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('username');
            expect($arr)->not->toHaveKey('password');
            expect($arr)->not->toHaveKey('token');
        });

        it('allValues includes hidden fields', function () {
            $dto = SensitiveDTO::fromArray([
                'username' => 'admin',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('username');
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson produces valid JSON', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Test',
                'email' => 'test@example.com',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded['name'])->toBe('Test');
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('jsonSerialize matches toArray', function () {
            $dto = SensitiveDTO::fromArray([
                'username' => 'admin',
                'password' => 'secret',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('MapFrom key aliasing', function () {
        it('maps user_name to name property', function () {
            $dto = AliasedDTO::fromArray([
                'user_name' => 'Alice',
                'user_email' => 'alice@example.com',
            ], validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('serializes using property names, not source keys', function () {
            $dto = AliasedDTO::fromArray([
                'user_name' => 'Bob',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('user_name');
        });

        it('supports dot notation for nested keys', function () {
            $dto = ProfileDTO::fromArray([
                'displayName' => 'Charlie',
                'meta' => [
                    'age' => 30,
                    'bio' => 'Hello world',
                ],
            ], validate: false);

            expect($dto->displayName)->toBe('Charlie');
            expect($dto->age)->toBe(30);
            expect($dto->bio)->toBe('Hello world');
        });
    });

    describe('equals and comparison', function () {
        it('equals returns true for identical DTOs', function () {
            $a = SimpleDTO::fromArray(['name' => 'Test'], validate: false);
            $b = SimpleDTO::fromArray(['name' => 'Test'], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $a = SimpleDTO::fromArray(['name' => 'A'], validate: false);
            $b = SimpleDTO::fromArray(['name' => 'B'], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('equals ignores hidden fields', function () {
            $a = SensitiveDTO::fromArray([
                'username' => 'admin',
                'password' => 'pw1',
            ], validate: false);
            $b = SensitiveDTO::fromArray([
                'username' => 'admin',
                'password' => 'pw2',
            ], validate: false);

            // toArray excludes hidden, so both serialize to same array
            expect($a->equals($b))->toBeTrue();
        });
    });

    describe('isEmpty and isNotEmpty', function () {
        it('returns true when all properties are empty/null/false', function () {
            $dto = SimpleDTO::fromArray([
                'name' => '',
                'email' => null,
                'status' => '',
            ], validate: false);

            // 'status' is non-nullable with empty string → empty
            expect($dto->isEmpty())->toBeTrue();
        });

        it('returns false when a non-empty value exists', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Alice',
                'email' => null,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('treats 0 as non-empty for numeric properties', function () {
            $dto = AllTypesDTO::fromArray([
                'count' => 0,
                'price' => 0.0,
                'label' => '',
                'active' => false,
                'tags' => [],
            ], validate: false);

            // count=0 and price=0.0 are non-empty (valid numeric values)
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('with immutable update', function () {
        it('creates new instance with overrides', function () {
            $original = SimpleDTO::fromArray(['name' => 'Original'], validate: false);
            $modified = $original->with(['name' => 'Updated']);

            expect($modified->name)->toBe('Updated');
            expect($original->name)->toBe('Original');
            expect($modified)->not->toBe($original);
        });

        it('preserves non-overridden fields', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Test',
                'email' => 'test@example.com',
            ], validate: false);

            $modified = $dto->with(['name' => 'New']);
            expect($modified->email)->toBe('test@example.com');
        });
    });

    describe('only and except field filtering', function () {
        it('only returns specified fields', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Test',
                'email' => 't@t.com',
                'status' => 'active',
            ], validate: false);

            $filtered = $dto->only(['name', 'status']);
            expect($filtered)->toHaveKey('name');
            expect($filtered)->toHaveKey('status');
            expect($filtered)->not->toHaveKey('email');
        });

        it('only works with single string key', function () {
            $dto = SimpleDTO::fromArray(['name' => 'Test'], validate: false);
            $filtered = $dto->only('name');

            expect($filtered)->toHaveKey('name');
        });

        it('except excludes specified fields', function () {
            $dto = SimpleDTO::fromArray([
                'name' => 'Test',
                'email' => 't@t.com',
            ], validate: false);

            $filtered = $dto->except('email');
            expect($filtered)->toHaveKey('name');
            expect($filtered)->not->toHaveKey('email');
        });
    });

    describe('fromJson roundtrip', function () {
        it('deserializes from JSON string', function () {
            $json = json_encode([
                'name' => 'From JSON',
                'email' => 'json@test.com',
            ], JSON_THROW_ON_ERROR);

            $dto = SimpleDTO::fromJson($json, validate: false);

            expect($dto->name)->toBe('From JSON');
            expect($dto->email)->toBe('json@test.com');
        });

        it('full roundtrip: toArray → json → fromJson → toArray', function () {
            $original = SimpleDTO::fromArray([
                'name' => 'Roundtrip',
                'email' => 'rt@test.com',
                'status' => 'active',
            ], validate: false);

            $json = $original->toJson();
            $restored = SimpleDTO::fromJson($json, validate: false);

            expect($restored->toArray())->toBe($original->toArray());
        });
    });

    describe('fromPartialArray', function () {
        it('hydrates only provided fields, uses defaults for rest', function () {
            $dto = SimpleDTO::fromPartialArray(['name' => 'Partial'], validate: false);

            expect($dto->name)->toBe('Partial');
            expect($dto->email)->toBeNull();
            expect($dto->status)->toBe('active');
        });

        it('does not require required fields in partial mode', function () {
            // SimpleDTO has #[Required] on $name, but fromPartialArray should not throw
            $dto = SimpleDTO::fromPartialArray(['email' => 'partial@test.com'], validate: false);

            expect($dto->email)->toBe('partial@test.com');
        });
    });

    describe('rules resolution', function () {
        it('returns validation rules from attributes', function () {
            $rules = ValidatedDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('title');
            expect($rules)->toHaveKey('quantity');
        });

        it('rulesFor returns same as rules by default', function () {
            expect(ValidatedDTO::rulesFor('create'))->toBe(ValidatedDTO::rules());
        });
    });
});
}
