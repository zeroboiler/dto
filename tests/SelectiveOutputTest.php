<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('only() — selective field output', function (): void {
    it('returns only the specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toBe([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    });

    it('excludes hidden fields even when explicitly requested via toArray path', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        // toArray() excludes hidden, so only() also excludes them
        $result = $dto->only(['email', 'password']);

        expect($result)->toHaveKey('email')
            ->and($result)->not->toHaveKey('password');
    });

    it('ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only(['email', 'nonexistent']);

        expect($result)->toBe([
            'email' => 'test@example.com',
        ]);
    });

    it('returns empty array when no matching keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only(['nonexistent1', 'nonexistent2']);

        expect($result)->toBe([]);
    });

    it('works with a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'test@example.com']);
    });
});

describe('except() — field exclusion output', function (): void {
    it('returns all fields except the specified ones', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['status']);

        expect($result)->toHaveKey('email')
            ->and($result)->toHaveKey('name')
            ->and($result)->not->toHaveKey('status');
    });

    it('ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except(['nonexistent']);

        // All non-hidden fields should be present
        expect($result)->toHaveKey('email')
            ->and($result)->toHaveKey('name');
    });

    it('still excludes hidden fields even when not in except list', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        // password is #[Hidden] — always excluded from toArray()
        $result = $dto->except(['email']);

        expect($result)->not->toHaveKey('email')
            ->and($result)->not->toHaveKey('password');
    });

    it('works with a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email')
            ->and($result)->toHaveKey('name');
    });
});

describe('validatePartialArray() — standalone partial validation', function (): void {
    it('validates only present fields and returns data', function (): void {
        $result = CreateUserDTO::validatePartialArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        expect($result)->toBe([
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);
    });

    it('does not require missing required fields', function (): void {
        // Only email present — name is required but not present, so it should pass
        $result = CreateUserDTO::validatePartialArray([
            'email' => 'test@example.com',
        ]);

        expect($result)->toBe([
            'email' => 'test@example.com',
        ]);
    });

    it('validates present fields against their rules', function (): void {
        expect(fn (): array => CreateUserDTO::validatePartialArray([
            'email' => 'not-an-email',
        ]))->toThrow(ValidationException::class);
    });

    it('returns empty data when no fields present', function (): void {
        $result = CreateUserDTO::validatePartialArray([]);

        expect($result)->toBe([]);
    });

    it('rejects field that fails min constraint', function (): void {
        expect(fn (): array => CreateUserDTO::validatePartialArray([
            'name' => 'x',
        ]))->toThrow(ValidationException::class);
    });

    it('accepts valid field at boundary', function (): void {
        $result = CreateUserDTO::validatePartialArray([
            'name' => 'ab',
        ]);

        expect($result)->toBe(['name' => 'ab']);
    });
});

describe('only() + except() consistency', function (): void {
    it('only + except complement each other', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $only = $dto->only(['email', 'name']);
        $except = $dto->except(['status']);

        // Both should yield the same result (all visible fields minus 'status')
        expect($only)->toBe($except);
    });
});
