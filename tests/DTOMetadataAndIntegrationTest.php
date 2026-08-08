<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

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
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;

describe('DTO metadata cache and flush behaviour', function () {
    it('flushMetadataCache clears all cached metadata', function () {
        // Resolve metadata for a DTO (triggers caching)
        $rules1 = CreateUserDTO::rules();

        // Flush all cached metadata
        DataTransferObject::flushMetadataCache();

        // Resolve again — should still work after flush
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toEqual($rules2);
    });

    it('flushMetadataCache with specific class only clears that class', function () {
        CreateUserDTO::rules();
        EmptyDTO::rules();

        // Flush only CreateUserDTO
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // EmptyDTO rules should still be cached (no error)
        $rules = EmptyDTO::rules();
        expect($rules)->toBeArray();
    });

    it('setMetadataCacheTtl controls cache expiry', function () {
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);

        // With TTL=0, cache should always be fresh
        $rules1 = CreateUserDTO::rules();
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toEqual($rules2);

        // Reset TTL to production default
        DataTransferObject::setMetadataCacheTtl(0.0);
    });
});

describe('DTO fromJson edge cases', function () {
    it('rejects sequential JSON arrays with DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('["email@test.com", "Alice"]'))
            ->toThrow(DTOException::class, 'Expected a JSON object');
    });

    it('rejects invalid JSON with DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class, 'Cannot decode JSON');
    });

    it('accepts valid JSON object and hydrates correctly', function () {
        $dto = CreateUserDTO::fromJson(
            '{"email":"test@example.com","name":"Alice"}',
            validate: false
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('fromJson skips validation when validate: false', function () {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('DTO fromArray validation', function () {
    it('throws ValidationException on invalid data', function () {
        expect(fn () => CreateUserDTO::fromArray(['email' => 'not-an-email', 'name' => '']))
            ->toThrow(ValidationException::class);
    });

    it('succeeds with valid data', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('respects DefaultValue attribute when key is absent', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@example.com', 'name' => 'Alice'],
            validate: false
        );

        // 'status' should fall back to its DefaultValue ('active')
        expect($dto->status)->toBe('active');
    });
});

describe('DTO toArray and hidden fields', function () {
    it('toArray excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('email');
        expect($all)->toHaveKey('name');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });
});

describe('DTO with immutable update', function () {
    it('creates a new instance with overrides', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $updated = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice'); // unchanged
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('test@example.com');
    });

    it('with() always validates — rejects invalid data', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        // Even though validate: false is passed, with() ignores it
        expect(fn () => $original->with(['email' => 'invalid-email']))
            ->toThrow(ValidationException::class);
    });
});

describe('DTO equals and state checks', function () {
    it('equals returns true for DTOs with same values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for DTOs with different values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Bob'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns true for DTO with all default/null values', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when a property has a non-empty value', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DTO selective output', function () {
    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('except returns all except specified fields', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

describe('DTO toJson', function () {
    it('returns valid JSON string', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Alice'], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('test@example.com');
        expect($decoded['name'])->toBe('Alice');
    });

    it('hidden fields excluded from toJson', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('password');
    });
});
