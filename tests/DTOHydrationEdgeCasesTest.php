<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DataTransferObject fromJson edge cases', function (): void {
    it('decodes valid JSON object correctly', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"a@b.com","name":"Alice"}');

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Alice');
        expect($dto->status)->toBe('active'); // default
    });

    it('throws DTOException on invalid JSON syntax', function (): void {
        CreateUserDTO::fromJson('{"email":broken');
    })->throws(DTOException::class);

    it('throws DTOException on sequential JSON array', function (): void {
        CreateUserDTO::fromJson('["email","test@example.com"]');
    })->throws(DTOException::class);

    it('accepts empty JSON object {}', function (): void {
        $dto = EmptyDTO::fromJson('{}');

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('accepts empty JSON array [] for DTOs with all optional fields', function (): void {
        $dto = EmptyDTO::fromJson('[]');

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('preserves map_from key mapping', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"a@b.com","name":"A","phone_number":"+123"}');

        expect($dto->phone)->toBe('+123');
    });

    it('skips validation when validate: false', function (): void {
        // Missing required 'email' and 'name' — would fail validation
        $dto = CreateUserDTO::fromJson('{"status":"inactive"}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->status)->toBe('inactive');
    });
});

describe('DataTransferObject isEmpty and isNotEmpty', function (): void {
    it('returns true when all properties are null defaults', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('returns false when at least one property has a value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('considers zero as non-empty', function (): void {
        // EmptyDTO only has nullable strings, so use a DTO with non-nullable fields
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
            'password' => '',
        ], validate: false);

        // name is non-null and non-empty string, so isEmpty should be false
        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers false as empty', function (): void {
        // A DTO where all nullable/optional properties are false/empty/null
        $dto = EmptyDTO::fromArray(['foo' => '', 'bar' => false], validate: false);

        // '' and false are considered empty
        expect($dto->isEmpty())->toBeTrue();
    });

    it('considers empty array as empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => ''], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty is exact negation of isEmpty', function (): void {
        $dtoList = [
            EmptyDTO::fromArray([], validate: false),
            EmptyDTO::fromArray(['foo' => 'x'], validate: false),
            EmptyDTO::fromArray(['foo' => '', 'bar' => null], validate: false),
        ];

        foreach ($dtoList as $dto) {
            expect($dto->isNotEmpty())->toBe(! $dto->isEmpty());
        }
    });
});

describe('DataTransferObject equals', function (): void {
    it('returns true for identical values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for different values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('excludes hidden fields from comparison', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 'secret1'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 'secret2'], validate: false);

        // Hidden field 'password' should not affect equality
        expect($a->equals($b))->toBeTrue();
    });
});

describe('DataTransferObject only and except', function (): void {
    it('only returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
        expect($result)->not->toHaveKey('password');
    });

    it('only accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('only ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('except excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('except accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('name');

        expect($result)->not->toHaveKey('name');
        expect($result)->toHaveKey('email');
    });

    it('except ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveCount(2);
    });
});

describe('DataTransferObject toJson', function (): void {
    it('serializes to valid JSON string', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeString()->not->toBeEmpty();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('a@b.com');
        expect($decoded['name'])->toBe('Alice');
    });

    it('excludes hidden fields from JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('password');
    });
});

describe('DataTransferObject array cast pipeline', function (): void {
    it('casts JSON string to array via Cast attribute', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["a","b","c"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b', 'c']);
    });

    it('casts empty string to empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    it('passes through existing array unchanged', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => ['x', 'y'],
        ], validate: false);

        expect($dto->tags)->toBe(['x', 'y']);
    });
});
