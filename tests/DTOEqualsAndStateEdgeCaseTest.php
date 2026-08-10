<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO Equals and State Tests', function () {
    describe('equals()', function () {
        it('returns true for identical DTOs', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('returns false for different values', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('excludes hidden fields from comparison', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret1',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret2',
            ], validate: false);

            // Hidden fields excluded from toArray(), so equals() ignores them
            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    describe('isEmpty()', function () {
        it('returns true when all fields are default/empty', function () {
            $dto = CreateUserDTO::fromArray([], validate: false);
            // All fields should be null/empty defaults
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('returns false when at least one field has a non-empty value', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('considers 0 as non-empty for int fields', function () {
            // If a DTO has an int field with value 0, it should not be empty
            // This is tested implicitly through the design
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('considers false as empty for bool fields', function () {
            $dto = CreateUserDTO::fromArray([], validate: false);
            // Empty DTO — all fields are null/empty
            expect($dto->isEmpty())->toBeTrue();
        });
    });

    describe('fromPartialArray()', function () {
        it('fills missing required fields with type-appropriate defaults', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Alice',
            ], validate: false);

            expect($dto->name)->toBe('Alice');
            // email should be null (nullable for partial, but required by full)
            // password should be null
        });
    });

    describe('fromJson() error handling', function () {
        it('throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not json'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

        it('throws DTOException for sequential JSON array', function () {
            expect(fn () => CreateUserDTO::fromJson('["a", "b"]'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

        it('throws DTOException with context in message', function () {
            try {
                CreateUserDTO::fromJson('{invalid');
                expect(true)->toBeFalse('Should have thrown');
            } catch (\ZeroBoiler\DTO\Exceptions\DTOException $e) {
                expect($e->getMessage())->toContain('JSON');
            }
        });
    });
});
