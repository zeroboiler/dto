<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ---------------------------------------------------------------------------
// Fixture DTOs for testing with() and isEmpty() contracts
// ---------------------------------------------------------------------------

final class WithDeprecationTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Required, Min(8)]
        public readonly string $password,

        #[Min(0)]
        public readonly int $score = 0,
    ) {}
}

final class IsEmptyAllDefaultsDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
        public readonly float $rate = 0.0,
        public readonly bool $active = false,
        public readonly ?string $nickname = null,
        public readonly array $tags = [],
    ) {}
}

final class IsEmptyNonEmptyDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
    ) {}
}

final class IsEmptyWithZeroIntDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $quantity = 0,
        public readonly float $price = 0.0,
    ) {}
}

final class IsEmptyWithNullIntDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?int $score = null,
    ) {}
}

describe('DTO with() immutable update contract', function () {
    it('creates a new instance without modifying the original', function () {
        $original = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        $modified = $original->with(['name' => 'Updated User']);

        expect($modified)->not->toBe($original);
        expect($original->name)->toBe('Test User');
        expect($modified->name)->toBe('Updated User');
        expect($original->email)->toBe('test@example.com');
        expect($modified->email)->toBe('test@example.com');
    });

    it('always validates regardless of the $validate parameter value', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        // Even with validate: false passed to with(), validation always runs internally
        expect(fn () => $dto->with(['email' => 'not-an-email'], validate: false))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('preserves non-overridden fields from allValues()', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
            'score' => 42,
        ], validate: false);

        $modified = $dto->with(['score' => 100]);

        expect($modified->score)->toBe(100);
        expect($modified->name)->toBe('Test User');
        expect($modified->email)->toBe('test@example.com');
    });

    it('supports chaining multiple with() calls', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        $modified = $dto->with(['name' => 'First Update'])->with(['name' => 'Second Update']);

        expect($modified->name)->toBe('Second Update');
        expect($modified->email)->toBe('test@example.com');
    });

    it('returns toArray() output that matches equals()', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        $modified = $dto->with(['name' => 'Updated']);
        $recreated = WithDeprecationTestDTO::fromArray($modified->allValues(), validate: false);

        expect($modified->equals($recreated))->toBeTrue();
    });
});

describe('DTO isEmpty() / isNotEmpty() contract', function () {
    it('returns true when all properties have default/empty values', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('returns false when at least one property has a non-empty value', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray(['name' => 'Alice'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('returns false for a DTO with a required non-empty field', function () {
        $dto = IsEmptyNonEmptyDTO::fromArray(['name' => 'Bob'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers zero int as non-empty (valid meaningful value)', function () {
        $dto = IsEmptyWithZeroIntDTO::fromArray([
            'quantity' => 0,
            'price' => 0.0,
        ], validate: false);

        // 0 and 0.0 are valid, meaningful values — NOT empty
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('considers null nullable int as empty', function () {
        $dto = IsEmptyWithNullIntDTO::fromArray(['score' => null], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('considers non-null nullable int as non-empty even when zero', function () {
        $dto = IsEmptyWithNullIntDTO::fromArray(['score' => 0], validate: false);

        // 0 is a valid meaningful value for a nullable int — non-null means non-empty
        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers empty array as empty', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray([
            'name' => '',
            'tags' => [],
            'nickname' => null,
        ], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('considers non-empty array as non-empty', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray([
            'name' => '',
            'tags' => ['admin'],
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers false boolean as empty', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray([
            'name' => '',
            'active' => false,
        ], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('considers true boolean as non-empty', function () {
        $dto = IsEmptyAllDefaultsDTO::fromArray([
            'name' => '',
            'active' => true,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO fromJson() edge cases', function () {
    it('throws DTOException for invalid JSON', function () {
        expect(fn () => WithDeprecationTestDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential arrays', function () {
        expect(fn () => WithDeprecationTestDTO::fromJson('[1, 2, 3]'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty object {}', function () {
        // Uses a DTO with all-optional params
        $dto = IsEmptyAllDefaultsDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(IsEmptyAllDefaultsDTO::class);
    });

    it('accepts empty array [] (valid empty JSON object)', function () {
        $dto = IsEmptyAllDefaultsDTO::fromJson('[]', validate: false);

        expect($dto)->toBeInstanceOf(IsEmptyAllDefaultsDTO::class);
    });

    it('round-trips through toJson() and fromJson()', function () {
        $original = WithDeprecationTestDTO::fromArray([
            'email' => 'roundtrip@test.com',
            'name' => 'Round Trip',
            'password' => 'securepass1',
            'score' => 88,
        ], validate: false);

        $json = $original->toJson();
        $restored = WithDeprecationTestDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe('roundtrip@test.com');
        expect($restored->name)->toBe('Round Trip');
        expect($restored->password)->toBe('securepass1');
        expect($restored->score)->toBe(88);
        expect($restored->equals($original))->toBeTrue();
    });
});

describe('DTO equals() value semantics', function () {
    it('returns true for identical DTOs', function () {
        $a = IsEmptyNonEmptyDTO::fromArray(['name' => 'Alice'], validate: false);
        $b = IsEmptyNonEmptyDTO::fromArray(['name' => 'Alice'], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for DTOs with different values', function () {
        $a = IsEmptyNonEmptyDTO::fromArray(['name' => 'Alice'], validate: false);
        $b = IsEmptyNonEmptyDTO::fromArray(['name' => 'Bob'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('returns true when comparing a DTO to itself', function () {
        $dto = IsEmptyNonEmptyDTO::fromArray(['name' => 'Same'], validate: false);

        expect($dto->equals($dto))->toBeTrue();
    });
});

describe('DTO only() and except() selective output', function () {
    it('returns only the specified fields', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
            'score' => 42,
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('password');
        expect($result)->not->toHaveKey('score');
    });

    it('accepts a single string key for only()', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('excludes the specified fields', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
            'score' => 42,
        ], validate: false);

        $result = $dto->except(['password', 'score']);

        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('password');
        expect($result)->not->toHaveKey('score');
    });

    it('accepts a single string key for except()', function () {
        $dto = WithDeprecationTestDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ], validate: false);

        $result = $dto->except('password');

        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('password');
    });

    it('ignores non-existent keys silently', function () {
        $dto = IsEmptyNonEmptyDTO::fromArray(['name' => 'Alice'], validate: false);

        $result = $dto->only(['nonexistent']);

        expect($result)->toBe([]);
    });
});
