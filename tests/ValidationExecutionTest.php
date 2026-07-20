<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

/**
 * Issue #11: Tests for actual validation execution.
 *
 * These tests call fromArray() with validate: true (the default)
 * to ensure the Laravel validator actually runs against input data.
 */
describe('Issue #11: Validation execution — valid data', function (): void {
    it('passes validation with valid CreateUserDTO data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        expect($dto->email)->toBe('valid@example.com')
            ->and($dto->name)->toBe('Doruk');
    });

    it('passes validation with valid ProductDTO data', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 42,
        ]);

        expect($dto->name)->toBe('Widget')
            ->and($dto->price)->toBe('29.99')
            ->and($dto->stock)->toBe(42);
    });

    it('passes validation with valid ValidationTestDTO data', function (): void {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => 30,
        ]);

        expect($dto->name)->toBe('John')
            ->and($dto->age)->toBe(30);
    });
});

describe('Issue #11: Validation execution — invalid data rejected', function (): void {
    it('rejects invalid email format', function (): void {
        expect(fn () => CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => 'Doruk',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects missing required field', function (): void {
        expect(fn () => CreateUserDTO::fromArray([
            'name' => 'Doruk',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects name below min length', function (): void {
        expect(fn () => CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'x',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects name exceeding max length', function (): void {
        expect(fn () => CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => str_repeat('x', 60),
        ]))->toThrow(ValidationException::class);
    });

    it('rejects non-numeric price for ProductDTO', function (): void {
        expect(fn () => ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => 'not-a-number',
            'stock' => 5,
        ]))->toThrow(ValidationException::class);
    });

    it('rejects negative stock below min constraint', function (): void {
        expect(fn () => ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '10.00',
            'stock' => -1,
        ]))->toThrow(ValidationException::class);
    });

    it('rejects when required name is entirely missing', function (): void {
        expect(fn () => ProductDTO::fromArray([
            'price' => '10.00',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects non-integer age', function (): void {
        expect(fn () => ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => 'not-a-number',
        ]))->toThrow(ValidationException::class);
    });

    it('rejects missing required age field', function (): void {
        expect(fn () => ValidationTestDTO::fromArray([
            'name' => 'John',
        ]))->toThrow(ValidationException::class);
    });
});

describe('Issue #11: Validation boundary tests', function (): void {
    it('accepts name at exact min boundary (2 chars)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'ab',
        ]);

        expect($dto->name)->toBe('ab');
    });

    it('accepts name at exact max boundary (50 chars)', function (): void {
        $name = str_repeat('x', 50);
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => $name,
        ]);

        expect($dto->name)->toBe($name);
    });

    it('accepts stock at exact min boundary (0)', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '10.00',
            'stock' => 0,
        ]);

        expect($dto->stock)->toBe(0);
    });

    it('accepts age at between lower boundary (0)', function (): void {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => 0,
        ]);

        expect($dto->age)->toBe(0);
    });

    it('accepts age at between upper boundary (120)', function (): void {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => 120,
        ]);

        expect($dto->age)->toBe(120);
    });

    it('rejects age below between range', function (): void {
        expect(fn () => ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => -1,
        ]))->toThrow(ValidationException::class);
    });

    it('rejects age above between range', function (): void {
        expect(fn () => ValidationTestDTO::fromArray([
            'name' => 'John',
            'age' => 121,
        ]))->toThrow(ValidationException::class);
    });
});

describe('Issue #11: Validation exception contains field errors', function (): void {
    it('includes field-specific errors in ValidationException', function (): void {
        try {
            CreateUserDTO::fromArray([
                'email' => 'invalid',
                'name' => 'x',
            ]);
            expect(false)->toBeTrue('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->toArray();
            expect($errors)->toHaveKey('email')
                ->and($errors)->toHaveKey('name');
        }
    });
});
