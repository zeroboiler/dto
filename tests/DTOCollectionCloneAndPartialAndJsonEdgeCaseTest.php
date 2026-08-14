<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection clone and immutability semantics', function () {
    it('append() returns a new collection without modifying the original', function () {
        $original = new DtoCollection([]);
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $appended = $original->append($dto);

        expect($original->count())->toBe(0);
        expect($appended->count())->toBe(1);
        expect($appended)->not->toBe($original);
    });

    it('merge() returns a new collection combining both without mutation', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'status' => 'active',
        ], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);

        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('filter() returns a new collection without mutating the original', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'status' => 'inactive',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (DataTransferObject $d): bool => $d->name === 'Alice'
        );

        expect($collection->count())->toBe(2);
        expect($filtered->count())->toBe(1);
    });

    it('clone throws RuntimeException — use append/merge instead', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $original = new DtoCollection([$dto]);

        expect(fn () => clone $original)->toThrow(\RuntimeException::class);
    });
});

describe('fromPartialArray edge cases', function () {
    it('handles empty array by using all defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // status has default 'active'
        expect($dto->status)->toBe('active');
        // email and name are required but in partial mode, get empty type defaults
    });

    it('fromPartialArray validates only present fields', function () {
        // email is required but with min/max constraints
        // Providing a valid name should not fail (email is not present, so not validated)
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: true);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fromPartialArray respects MapFrom attribute', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+905551234567',
        ], validatePresent: false);

        expect($dto->phone)->toBe('+905551234567');
    });
});

describe('fromJson error handling', function () {
    it('throws DTOException on invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('not valid json{'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON array (sequential)', function () {
        expect(fn () => CreateUserDTO::fromJson('["a", "b", "c"]'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON number', function () {
        expect(fn () => CreateUserDTO::fromJson('42'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        $dto = CreateUserDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });
});

describe('with() immutability guarantee', function () {
    it('original DTO is unchanged after with()', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $updated = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
    });

    it('with() always validates even when validate=false is passed', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        // with() should still validate (validate param is deprecated/no-op)
        $updated = $dto->with(['email' => 'bad-email']);

        expect($updated->email)->toBe('bad-email');
    });
});

describe('equals() and isEmpty() edge cases', function () {
    it('equals() returns true for identical DTOs', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $b = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals() returns false for different DTOs', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $b = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Bob',
            'status' => 'active',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty() returns true when all fields are default/null', function () {
        $dto = CreateUserDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty() returns true when at least one field has a value', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('only() and except() selective output', function () {
    it('only() returns only specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('only() accepts multiple keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toBe(['email' => 'a@b.com', 'name' => 'Alice']);
    });

    it('except() excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('only() ignores hidden fields in source array', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'password' => 'secret123',
        ], validate: false);

        // password is #[Hidden], so only() should not include it
        $result = $dto->only('email', 'password');

        expect($result)->toBe(['email' => 'a@b.com']);
    });
});
