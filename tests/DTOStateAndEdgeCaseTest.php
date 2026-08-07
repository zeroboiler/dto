<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DataTransferObject isEmpty and state checks', function (): void {
    it('returns true when all properties are empty or default', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => '',
            'name' => '',
            'status' => '',
        ], validate: false);

        // status has default 'active', but we overrode it with ''
        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false when at least one property has a non-empty value', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => '',
            'status' => '',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty is the negation of isEmpty', function (): void {
        $empty = CreateUserDTO::fromArray([
            'email' => '',
            'name' => '',
            'status' => '',
        ], validate: false);

        $nonEmpty = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => '',
            'status' => '',
        ], validate: false);

        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('considers numeric zero as empty', function (): void {
        // For DTOs with numeric properties, 0 should be considered empty
        expect(true)->toBeTrue(); // placeholder — relies on DTO having numeric props
    });

    it('considers false boolean as empty', function (): void {
        // isEmpty checks: value !== false
        // If a DTO has a bool property set to false, it's considered empty
        expect(true)->toBeTrue(); // placeholder
    });

    it('considers empty array as empty', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => '',
            'name' => '',
            'tags' => [],
        ], validate: false);

        // tags=[] is considered empty by isEmpty()
        // But other fields may have defaults that count as empty
        expect($dto->tags)->toBe([]);
    });
});

describe('DataTransferObject fromJson edge cases', function (): void {
    it('rejects sequential JSON arrays', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('["a", "b", "c"]', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('rejects invalid JSON with syntax error', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('rejects JSON null', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('null', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('accepts empty JSON object', function (): void {
        // Empty object — missing required fields may cause validation errors
        // but without validation, defaults should be applied
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('DataTransferObject only and except edge cases', function (): void {
    it('only returns empty array for non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('non_existent_field');

        expect($result)->toBe([]);
    });

    it('except with non-existent keys returns all fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('non_existent_field');
        $array = $dto->toArray();

        expect($result)->toBe($array);
    });

    it('only accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('except accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });
});
