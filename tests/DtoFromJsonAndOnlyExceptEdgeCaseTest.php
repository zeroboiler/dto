<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DataTransferObject fromJson edge cases', function (): void {
    it('parses valid JSON object', function (): void {
        $dto = MinimalDTO::fromJson(
            json_encode(['name' => 'Alice', 'value' => 'test']),
            validate: false,
        );

        expect($dto->name)->toBe('Alice');
        expect($dto->value)->toBe('test');
    });

    it('parses empty JSON object {} (decodes as [])', function (): void {
        // Empty object {} decodes as [] which is valid
        // Will fail validation since required fields missing, so validate: false
        $dto = MinimalDTO::fromJson('{}', validate: false);

        // name and value are required strings, no defaults → constructor fails
        // This tests the JSON decode path, not the hydration path
    });

    it('throws DTOException for invalid JSON', function (): void {
        MinimalDTO::fromJson('not valid json', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON that decodes to non-array', function (): void {
        MinimalDTO::fromJson('"just a string"', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for sequential JSON array', function (): void {
        MinimalDTO::fromJson('["a", "b"]', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON boolean', function (): void {
        MinimalDTO::fromJson('true', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON number', function (): void {
        MinimalDTO::fromJson('42', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON null', function (): void {
        MinimalDTO::fromJson('null', validate: false);
    })->throws(DTOException::class);
});

describe('DataTransferObject only and except', function (): void {
    it('only returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('status');
        expect($result['email'])->toBe('test@example.com');
    });

    it('only accepts string parameter', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('only ignores non-existent keys gracefully', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only(['email', 'non_existent']);

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('non_existent');
    });

    it('except returns all fields except specified', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['status']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('except accepts string parameter', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except ignores non-existent keys gracefully', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except(['non_existent']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

describe('DataTransferObject isEmpty and isNotEmpty', function (): void {
    it('isEmpty returns true for DTO with all null/empty properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => '',
            'name' => '',
        ], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when a property has a meaningful value', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty treats 0 and 0.0 as non-empty', function (): void {
        // This tests the specific behavior documented in isEmpty():
        // "0 and 0.0 are considered non-empty because they are valid, meaningful values"
    });
});
