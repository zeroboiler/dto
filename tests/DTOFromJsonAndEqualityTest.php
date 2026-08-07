<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DataTransferObject fromJson edge cases', function (): void {
    it('fromJson throws DTOException for invalid JSON', function (): void {
        expect(fn () => CreateUserDTO::fromJson('not json'))
            ->toThrow(DTOException::class)
            ->getMessage()
            ->toContain('Cannot decode JSON');
    });

    it('fromJson throws DTOException for sequential array JSON', function (): void {
        expect(fn () => CreateUserDTO::fromJson('["email","name"]'))
            ->toThrow(DTOException::class)
            ->getMessage()
            ->toContain('Expected a JSON object');
    });

    it('fromJson with validate:false skips validation', function (): void {
        $dto = CreateUserDTO::fromJson(
            '{"email": "test@example.com", "name": "Test"}',
            validate: false
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('fromJson handles empty JSON object', function (): void {
        // EmptyDTO has nullable optional fields, so empty object works
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });
});

describe('DataTransferObject equals edge cases', function (): void {
    it('same data produces equal DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('different data produces non-equal DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'A',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'B',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('equals excludes hidden fields from comparison', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'pass1',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'pass2',
        ], validate: false);

        // Password is hidden, so both toArray() produce the same result
        expect($dto1->equals($dto2))->toBeTrue();
    });
});

describe('DataTransferObject isEmpty edge cases', function (): void {
    it('DTO with only nullable defaults is empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('DTO with required fields filled is not empty', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DataTransferObject with() always validates', function (): void {
    it('with() creates new instance with merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'original@example.com',
            'name' => 'Original',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);

        expect($updated)->not->toBe($dto); // New instance
        expect($updated->name)->toBe('Updated');
        expect($updated->email)->toBe('original@example.com'); // Preserved
    });

    it('with() validate parameter has no effect', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // Even with validate: false, with() always validates internally
        $updated = $dto->with(['email' => 'new@example.com'], validate: false);

        expect($updated->email)->toBe('new@example.com');
    });
});

describe('DataTransferObject rules consistency', function (): void {
    it('rules() returns array with string keys', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        foreach (array_keys($rules) as $key) {
            expect($key)->toBeString();
        }
    });

    it('rulesFor() returns same as rules() by default', function (): void {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('delete'))->toBe(CreateUserDTO::rules());
    });
});
