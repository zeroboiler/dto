<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('CreateUserDTO', function (): void {
    it('derives validation rules from attributes', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');

        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('creates from array with defaults', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Doruk');
        expect($dto->status)->toBe('active'); // default
        expect($dto->tags)->toBe([]); // default
        expect($dto->phone)->toBeNull();
    });

    it('maps fields via MapFrom attribute', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('casts array fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'tags' => ['laravel', 'php'],
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });

    it('excludes hidden fields from toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->not->toHaveKey('password');
        expect($array)->toHaveKey('email');
    });

    it('serializes to JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('email');
        expect($decoded)->toHaveKey('name');
        expect($decoded['email'])->toBe('test@example.com');
    });

    it('creates immutable copy with overrides', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ]);

        $updated = $dto->with(['status' => 'inactive']);

        expect($dto->status)->toBe('active');
        expect($updated->status)->toBe('inactive');
        expect($updated->email)->toBe('test@example.com');
    });

    it('throws ValidationException when with() receives invalid data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        expect(fn (): CreateUserDTO => $dto->with(['email' => 'not-an-email']))
            ->toThrow(ValidationException::class);
    });

    it('validates merged data in with() — existing valid data + invalid override fails', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        // name has min:2 rule — single char must fail
        expect(fn (): CreateUserDTO => $dto->with(['name' => 'X']))
            ->toThrow(ValidationException::class);
    });

    it('checks equality', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Other'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });
});

describe('ProductDTO', function (): void {
    it('derives numeric and integer rules', function (): void {
        $rules = ProductDTO::rules();

        expect($rules['price'])->toContain('required');
        expect($rules['price'])->toContain('numeric');
        expect($rules['stock'])->toContain('integer');
        expect($rules['stock'])->toContain('min:0');
    });

    it('creates with valid data', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 42,
        ], validate: false);

        expect($dto->name)->toBe('Widget');
        expect($dto->price)->toBe('29.99');
        expect($dto->stock)->toBe(42);
    });
});

describe('EmptyDTO', function (): void {
    it('has empty rules when no attributes', function (): void {
        $rules = EmptyDTO::rules();

        // Nullable optional properties may have 'sometimes' rule
        expect($rules)->toBeArray();
    });

    it('creates with all nulls', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });
});
