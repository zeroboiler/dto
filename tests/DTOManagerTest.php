<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTOManager', function (): void {
    beforeEach(function (): void {
        $this->manager = new DTOManager;
    });

    describe('make()', function (): void {
        it('creates a DTO instance from array data', function (): void {
            $dto = $this->manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class)
                ->and($dto->email)->toBe('test@example.com')
                ->and($dto->name)->toBe('Doruk')
                ->and($dto->status)->toBe('active');
        });

        it('creates a DTO with all fields populated', function (): void {
            $dto = $this->manager->make(ProductDTO::class, [
                'name' => 'Widget',
                'price' => '9.99',
                'stock' => 42,
            ]);

            expect($dto)->toBeInstanceOf(ProductDTO::class)
                ->and($dto->name)->toBe('Widget')
                ->and($dto->price)->toBe('9.99')
                ->and($dto->stock)->toBe(42);
        });
    });

    describe('validate()', function (): void {
        it('validates data and returns validated array', function (): void {
            $validated = $this->manager->validate(CreateUserDTO::class, [
                'email' => 'valid@example.com',
                'name' => 'Doruk',
            ]);

            expect($validated)->toBeArray()
                ->and($validated)->toHaveKey('email', 'valid@example.com')
                ->and($validated)->toHaveKey('name', 'Doruk');
        });

        it('throws ValidationException for invalid data', function (): void {
            expect(fn (): CreateUserDTO => $this->manager->validate(CreateUserDTO::class, [
                'email' => 'not-an-email',
                'name' => 'Doruk',
            ]))->toThrow(ValidationException::class);
        });

        it('throws for missing required fields', function (): void {
            expect(fn (): array => $this->manager->validate(CreateUserDTO::class, [
                'name' => 'Doruk',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('schema()', function (): void {
        it('generates OpenAPI schema for a DTO class', function (): void {
            $schema = $this->manager->schema(CreateUserDTO::class);

            expect($schema)->toBeArray()
                ->and($schema)->toHaveKey('properties')
                ->and($schema['properties'])->toHaveKey('email')
                ->and($schema['properties'])->toHaveKey('name');
        });

        it('schema excludes hidden properties', function (): void {
            $schema = $this->manager->schema(CreateUserDTO::class);

            expect($schema['properties'])->not->toHaveKey('password');
        });

        it('schema includes required fields', function (): void {
            $schema = $this->manager->schema(CreateUserDTO::class);

            expect($schema)->toHaveKey('required')
                ->and($schema['required'])->toContain('email')
                ->and($schema['required'])->toContain('name');
        });
    });

    describe('DTOManager is final', function (): void {
        it('cannot be extended', function (): void {
            $reflection = new ReflectionClass(DTOManager::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('is a readonly class internally (no mutable state)', function (): void {
            $reflection = new ReflectionClass(DTOManager::class);
            $props = $reflection->getProperties();

            // DTOManager has no properties at all — stateless
            expect($props)->toBeEmpty();
        });
    });
});
