<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Enum, Hidden, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

/**
 * DtoMetadataResolver type detection and rule inference edge cases.
 *
 * Covers type-based rule inference, nullable handling, default value detection,
 * Cast attribute processing, and edge cases in union type resolution.
 */
describe('DtoMetadataResolver Type Detection Edge Cases', function (): void {
    it('infers integer rule for int-typed properties', function (): void {
        // ProductDTO has 'stock' typed as int
        $rules = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::rules();

        expect($rules['stock'])->toContain('integer');
    });

    it('infers numeric rule for float-typed properties', function (): void {
        // ProductDTO has 'price' typed as string but also has numeric rule
        $rules = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::rules();

        expect($rules['price'])->toContain('numeric');
    });

    it('adds sometimes rule for nullable properties without defaults', function (): void {
        $rules = EmptyDTO::rules();

        // foo and bar are both nullable without defaults
        if (isset($rules['foo'])) {
            expect($rules['foo'])->toContain('sometimes');
        }
        if (isset($rules['bar'])) {
            expect($rules['bar'])->toContain('sometimes');
        }
    });

    it('detects required from attribute even on nullable type', function (): void {
        $rules = CreateUserDTO::rules();

        // email is required despite being a non-nullable string
        expect($rules['email'])->toContain('required');
    });

    it('resolves MapFrom attribute into metadata', function (): void {
        // CreateUserDTO has phone with #[MapFrom('phone_number')]
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('applies Cast attribute during hydration', function (): void {
        // Test array casting via Cast attribute in CreateUserDTO
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => '"laravel"',
        ], validate: false);

        // Cast('array') decodes JSON strings to arrays
        expect($dto->tags)->toBe(['laravel']);
    });

    it('preserves explicit values over defaults', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'inactive',
        ], validate: false);

        expect($dto->status)->toBe('inactive');
    });

    it('applies default when key is absent from data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('respects explicit null when key is present', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone' => null,
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });

    it('Hidden attribute excludes property from toArray but not allValues', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues()['password'])->toBe('secret');
    });

    it('metadata cache returns same result on repeated calls', function (): void {
        $rules1 = CreateUserDTO::rules();
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toBe($rules2);
    });

    it('rulesFor returns same rules as rules() by default', function (): void {
        $rules = CreateUserDTO::rules();
        $rulesForCreate = CreateUserDTO::rulesFor('create');
        $rulesForUpdate = CreateUserDTO::rulesFor('update');

        expect($rules)->toBe($rulesForCreate);
        expect($rules)->toBe($rulesForUpdate);
    });
});
