<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('Issue #8: DTOCast::set() validation', function (): void {
    it('validates array data by default (validate=true is the default)', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'not-an-email', 'name' => 'Test'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('rejects invalid email when validation is enabled', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'invalid', 'name' => 'Test'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('rejects missing required fields when validation is enabled', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'valid@example.com'], // missing 'name'
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('serializes valid array through DTO pipeline when validation enabled', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'valid@example.com', 'name' => 'John'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('valid@example.com')
            ->and($decoded['name'])->toBe('John')
            ->and($decoded['status'])->toBe('active'); // default applied via DTO
    });

    it('serializes valid array even without validation', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'valid@example.com', 'name' => 'Jane'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('valid@example.com')
            ->and($decoded['name'])->toBe('Jane');
    });

    it('throws InvalidArgumentException for string value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: 'raw-string-data',
            attributes: [],
        ))->toThrow(InvalidArgumentException::class, 'DTOCast::set()');
    });

    it('throws InvalidArgumentException for integer value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: 42,
            attributes: [],
        ))->toThrow(InvalidArgumentException::class);
    });

    it('throws InvalidArgumentException for boolean value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: true,
            attributes: [],
        ))->toThrow(InvalidArgumentException::class);
    });

    it('still handles DTO instances correctly', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'dto@example.com',
            'name' => 'DTO',
        ], validate: false);

        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: $dto,
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('dto@example.com');
    });

    it('still handles null correctly', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('serializes through DTO pipeline to ensure consistent output even without validation', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'test@example.com', 'name' => 'Consistency'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        // DTO pipeline applies defaults, casts, and hides fields
        expect($decoded)->toHaveKey('status', 'active') // default applied
            ->and($decoded)->not->toHaveKey('password'); // hidden field excluded
    });
});
