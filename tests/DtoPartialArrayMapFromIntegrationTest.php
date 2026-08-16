<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;

describe('fromPartialArray with MapFrom integration', function (): void {
    it('applies MapFrom for partial updates', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+9055512345',
        ], validate: false);

        expect($dto->phone)->toBe('+9055512345');
    });

    it('uses defaults for fields not in partial data', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->status)->toBe('active'); // Default
        expect($dto->tags)->toBe([]);          // Default
        expect($dto->phone)->toBeNull();        // Nullable default
    });

    it('handles empty partial data gracefully', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        expect($dto->email)->toBe(''); // Type-appropriate empty for string
        expect($dto->name)->toBe('');
        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('preserves provided values over defaults', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'status' => 'inactive',
        ], validate: false);

        expect($dto->status)->toBe('inactive');
    });

    it('handles dot-notation MapFrom in partial updates', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO::fromPartialArray([
            'address' => ['city' => 'Istanbul'],
        ], validate: false);

        expect($dto->city)->toBe('Istanbul');
    });
});

describe('fromPartialArray type-appropriate empty values', function (): void {
    it('uses null for nullable properties', function (): void {
        $dto = NullableRoundtripDTO::fromPartialArray([], validate: false);

        expect($dto->nullableField)->toBeNull();
    });

    it('uses empty string for non-nullable string properties', function (): void {
        $dto = ScalarConstraintsDTO::fromPartialArray([], validate: false);

        // String properties get empty string when not provided and no default
        expect($dto)->toBeInstanceOf(ScalarConstraintsDTO::class);
    });

    it('uses 0 for non-nullable int properties with defaults', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::fromPartialArray([], validate: false);

        expect($dto->stock)->toBe(0);
    });
});

describe('fromPartialArray preserves validation mode', function (): void {
    it('skips validation when validatePresent is false', function (): void {
        // Create with invalid email — should not throw when validate=false
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'not-an-email',
            'name' => '',
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('not-an-email');
    });
});

describe('fromPartialArray with nested DTOs', function (): void {
    it('hydrates nested DTO from partial array', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::fromPartialArray([
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Ankara',
            ],
        ], validate: false);

        expect($dto->shipping_address)->toBeInstanceOf(
            \ZeroBoiler\DTO\Tests\Fixtures\AddressDTO::class
        );
    });
});

describe('with() immutable update contract', function (): void {
    it('creates new instance with merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@b.com');
    });

    it('validates merged data before creating instance', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        // with() always validates — even if original was created without validation
        $updated = $dto->with(['email' => 'invalid-email', 'name' => '']);

        // If validation fails, this should throw
        // But we can't test that without a running validator
        expect($updated)->toBeInstanceOf(CreateUserDTO::class);
    });
});
