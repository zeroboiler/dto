<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTOCast validation', function (): void {
    it('validates arrays by default when validate is not specified', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'not-an-email', 'name' => 'Test'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('accepts valid arrays with validation enabled', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'valid@example.com', 'name' => 'Valid Name'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('valid@example.com')
            ->and($decoded['name'])->toBe('Valid Name');
    });

    it('rejects invalid email when validating', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'bad', 'name' => 'Test'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('rejects missing required fields when validating', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'ok@example.com'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('rejects name below min length when validating', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'ok@example.com', 'name' => 'a'],
            attributes: [],
        ))->toThrow(ValidationException::class);
    });

    it('allows opt-out of validation via validate: false', function (): void {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'anything', 'name' => 'a'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('anything')
            ->and($decoded['name'])->toBe('a');
    });
});

describe('DTOCast type safety', function (): void {
    it('throws InvalidArgumentException for string value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        expect(fn () => $cast->set(
            model: new class {},
            key: 'payload',
            value: 'raw-string-data',
            attributes: [],
        ))->toThrow(InvalidArgumentException::class);
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

    it('throws with descriptive error message including key and expected class', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        try {
            $cast->set(
                model: new class {},
                key: 'payload',
                value: 'bad-value',
                attributes: [],
            );
            expect(false)->toBeTrue('Should have thrown');
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage())
                ->toContain('payload')
                ->toContain(CreateUserDTO::class)
                ->toContain('string');
        }
    });

    it('still accepts null', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('still accepts DTO instances', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'dto@example.com',
            'name' => 'DTO Instance',
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
});
