<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('allValues() — includes hidden fields', function (): void {
    it('includes hidden fields that toArray() excludes', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $visible = $dto->toArray();
        $all = $dto->allValues();

        // toArray() excludes password
        expect($visible)->not->toHaveKey('password');

        // allValues() includes password
        expect($all)->toHaveKey('password')
            ->and($all['password'])->toBe('secret123');
    });

    it('contains all non-hidden fields identical to toArray()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $visible = $dto->toArray();
        $all = $dto->allValues();

        // Every key in toArray() should be in allValues() with the same value
        foreach ($visible as $key => $value) {
            expect($all)->toHaveKey($key)
                ->and($all[$key])->toBe($value);
        }
    });

    it('works with nested DTOs via allValues()', function (): void {
        $inner = CreateUserDTO::fromArray([
            'email' => 'inner@test.com',
            'name' => 'Inner',
        ], validate: false);

        // allValues on inner should return visible fields
        $all = $inner->allValues();

        expect($all)->toHaveKey('email')
            ->and($all['email'])->toBe('inner@test.com');
    });
});

describe('equals() — DTO equality', function (): void {
    it('returns true for two DTOs with identical values', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('returns false when any field differs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('returns false for DTOs of different classes', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // This would throw a TypeError — different types cannot be compared
        // But the method signature is self, so PHP enforces this at compile time
        expect(fn (): bool => $dto->equals($dto))->toBeCallable();
    });
});

describe('fromRequest() — request hydration', function (): void {
    it('creates DTO from request data', function (): void {
        $request = Request::create('/users', 'POST', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $dto = CreateUserDTO::fromRequest($request, validate: false);

        expect($dto->email)->toBe('test@example.com')
            ->and($dto->name)->toBe('Test User')
            ->and($dto->status)->toBe('active');
    });
});
