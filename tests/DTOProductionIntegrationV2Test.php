<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\Attributes\{ArrayRule, Boolean, Between, Confirmed, Date, Enum, In, Integer, NestedArray};
use ZeroBoiler\DTO\Attributes\{Nullable, Numeric, Pattern, Prohibited, Size, Url, Uuid};
use ZeroBoiler\DTO\Attributes\{StartsWith, EndsWith, Same, Different, Accepted, Declined, Present, Sometimes};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\{CreateUserDTO, AddressDTO, OrderDTO, MinimalDTO, RoundtripDTO};

describe('DTO Hydration Pipeline Integration', function () {
    it('hydrates from array with all property types', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'tags' => ['php', 'laravel'],
            'phone_number' => '+905551234567',
            'password' => 'secret123',
        ]);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
        expect($dto->tags)->toBe(['php', 'laravel']);
        expect($dto->phone)->toBe('+905551234567');
        expect($dto->password)->toBe('secret123');
    });

    it('applies default values when keys are absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Bob',
        ]);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
        expect($dto->password)->toBeNull();
    });

    it('respects explicit null values over defaults', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Charlie',
            'phone_number' => null,
        ]);

        expect($dto->phone)->toBeNull();
    });

    it('MapFrom resolves dot notation keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Dave',
            'phone_number' => '+901234567890',
        ]);

        expect($dto->phone)->toBe('+901234567890');
    });
});

describe('DTO Serialization Roundtrip', function () {
    it('toArray excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Eve',
            'password' => 'pass123',
        ]);

        $array = $dto->toArray();
        expect($array)->toHaveKey('email');
        expect($array)->toHaveKey('name');
        expect($array)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Eve',
            'password' => 'pass123',
        ]);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('pass123');
    });

    it('toJson produces valid JSON without hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Frank',
        ]);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('test@example.com');
        expect($decoded['name'])->toBe('Frank');
    });

    it('fromArray + toArray is idempotent for non-hidden fields', function () {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Grace',
            'tags' => ['a', 'b'],
            'phone_number' => '+905559999999',
        ];

        $dto = CreateUserDTO::fromArray($data);
        $result = $dto->toArray();

        expect($result['email'])->toBe($data['email']);
        expect($result['name'])->toBe($data['name']);
        expect($result['tags'])->toBe($data['tags']);
        expect($result['phone'])->toBe($data['phone_number']);
    });
});

describe('DTO Selective Output Integration', function () {
    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Hank',
            'phone_number' => '+90555',
        ]);

        $only = $dto->only('email');
        expect($only)->toHaveCount(1);
        expect($only)->toHaveKey('email');

        $onlyMulti = $dto->only(['email', 'name']);
        expect($onlyMulti)->toHaveCount(2);
        expect($onlyMulti)->toHaveKeys(['email', 'name']);
    });

    it('except excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Ivy',
        ]);

        $except = $dto->except('email');
        expect($except)->not->toHaveKey('email');
        expect($except)->toHaveKey('name');
    });
});

describe('DTO Immutable Update Integration', function () {
    it('with returns new instance with overrides', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'old@example.com',
            'name' => 'Jack',
        ]);

        $updated = $original->with(['name' => 'Jack Updated']);

        expect($original->name)->toBe('Jack');
        expect($updated->name)->toBe('Jack Updated');
        expect($updated->email)->toBe('old@example.com');
    });

    it('with validates the merged data', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Kate',
        ]);

        // Empty email should fail validation
        expect(fn () => $dto->with(['email' => '']))->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('equals compares toArray output', function () {
        $a = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Leo']);
        $b = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Leo']);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different data', function () {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Mia']);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Mia']);

        expect($a->equals($b))->toBeFalse();
    });
});

describe('DTO Partial Update Integration', function () {
    it('fromPartialArray hydrates only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated Name']);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Nate']);

        expect($dto->status)->toBe('active');
    });

    it('fromPartialArray does not require fields', function () {
        // Should not throw even without required fields
        $dto = CreateUserDTO::fromPartialArray(['email' => 'partial@example.com']);

        expect($dto->email)->toBe('partial@example.com');
    });
});

describe('DTO State Checks Integration', function () {
    it('isEmpty returns true when all properties are default/empty', function () {
        // CreateUserDTO: name has no default and is required, so partial update
        // with no data should give empty values
        $dto = MinimalDTO::fromPartialArray([]);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true when at least one property has value', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Oscar']);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DTO Rules Generation Integration', function () {
    it('rules returns expected structure for CreateUserDTO', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('rulesFor returns same as rules by default', function () {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
    });

    it('validateArray returns validated data', function () {
        $data = CreateUserDTO::validateArray([
            'email' => 'valid@example.com',
            'name' => 'Pam',
        ]);

        expect($data)->toBeArray();
        expect($data['email'])->toBe('valid@example.com');
        expect($data['name'])->toBe('Pam');
    });

    it('validateArray throws on invalid data', function () {
        expect(fn () => CreateUserDTO::validateArray(['email' => 'not-an-email', 'name' => '']))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('DTO fromJson Integration', function () {
    it('creates DTO from valid JSON string', function () {
        $json = json_encode(['email' => 'json@example.com', 'name' => 'Quinn']);
        $dto = CreateUserDTO::fromJson($json);

        expect($dto->email)->toBe('json@example.com');
        expect($dto->name)->toBe('Quinn');
    });

    it('throws DTOException on invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('not-json'))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('throws DTOException on sequential array JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('[1,2,3]'))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('accepts empty object JSON', function () {
        // MinimalDTO has all optional fields
        $dto = MinimalDTO::fromJson('{}');

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
    });
});
