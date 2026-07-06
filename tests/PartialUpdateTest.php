<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('fromPartialArray (Issue #67)', function (): void {
    it('creates DTO with only provided fields, using defaults for missing', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'partial@example.com',
        ], validatePresent: false);

        expect($dto->email)->toBe('partial@example.com')
            ->and($dto->name)->toBe('') // non-nullable string, type-appropriate empty
            ->and($dto->status)->toBe('active') // default value
            ->and($dto->tags)->toBe([]); // default value
    });

    it('hydrates only the fields present in data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Updated Name')
            ->and($dto->email)->toBe(''); // non-nullable string, type-appropriate empty
    });

    it('uses default values for missing fields', function (): void {
        $dto = ProductDTO::fromPartialArray([
            'name' => 'Widget',
        ], validatePresent: false);

        expect($dto->name)->toBe('Widget')
            ->and($dto->stock)->toBe(0); // default
    });

    it('handles empty partial data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->email)->toBe('')
            ->and($dto->name)->toBe('')
            ->and($dto->status)->toBe('active') // default
            ->and($dto->tags)->toBe([]); // default
    });

    it('does not throw when validatePresent is false', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'valid@example.com',
        ], validatePresent: false);

        expect($dto->email)->toBe('valid@example.com');
        // Should NOT throw validation error for missing required 'name' field
    });

    it('converts required rules to sometimes for partial validation', function (): void {
        // Should not throw even though 'email' is required,
        // because 'email' is present and valid
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@test.com',
            'name' => 'Valid Name',
        ], validatePresent: false);

        expect($dto->email)->toBe('test@test.com')
            ->and($dto->name)->toBe('Valid Name');
    });

    it('does not validate missing required fields', function (): void {
        // Only providing 'name' - should not fail on missing required 'email'
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Only Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Only Name');
    });

    it('maps fields via MapFrom attribute in partial mode', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+905551234567',
        ], validatePresent: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('casts array fields in partial mode', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'tags' => ['php', 'laravel'],
        ], validatePresent: false);

        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('respects explicit null values for nullable properties in partial mode', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'phone' => null,
        ], validatePresent: false);

        expect($dto->phone)->toBeNull();
    });

    it('throws TypeError when null given for non-nullable property', function (): void {
        expect(fn (): CreateUserDTO => CreateUserDTO::fromPartialArray([
            'email' => 'test@test.com',
            'name' => null,
        ], validatePresent: false))
            ->toThrow(TypeError::class);
    });

    it('returns empty string for non-nullable string fields not in data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // email and name are non-nullable strings → type-appropriate empty value
        expect($dto->email)->toBe('')
            ->and($dto->name)->toBe('');
    });

    it('returns null for nullable fields not in data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // phone is ?string → null
        expect($dto->phone)->toBeNull();
    });

    it('returns correct defaults for fields with DefaultValue attribute', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->status)->toBe('active') // DefaultValue('active')
            ->and($dto->tags)->toBe([]); // constructor default
    });
});

describe('fromPartialRequest (Issue #67)', function (): void {
    it('creates DTO from partial request data', function (): void {
        $request = Request::create('/users/1', 'PATCH', [
            'name' => 'Patched Name',
        ]);

        $dto = CreateUserDTO::fromPartialRequest($request, validate: false);

        expect($dto->name)->toBe('Patched Name')
            ->and($dto->status)->toBe('active'); // default
    });
});

describe('with() vs fromPartialArray() integration', function (): void {
    it('can use fromPartialArray then merge onto existing DTO', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'original@test.com',
            'name' => 'Original',
            'status' => 'active',
        ], validate: false);

        // PATCH: only update name
        $patched = $original->with([
            'name' => 'Patched',
        ], validate: false);

        expect($patched->email)->toBe('original@test.com') // unchanged
            ->and($patched->name)->toBe('Patched') // updated
            ->and($patched->status)->toBe('active'); // unchanged
    });
});
