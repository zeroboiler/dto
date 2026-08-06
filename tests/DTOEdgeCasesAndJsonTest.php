<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('fromJson edge cases', function (): void {
    it('parses valid JSON object', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"test@example.com","name":"Doruk"}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('throws DTOException on invalid JSON syntax', function (): void {
        expect(fn () => CreateUserDTO::fromJson('{"invalid": }'))
            ->toThrow(DTOException::class)
            ->getMessage()
            ->toContain('Cannot decode JSON');
    });

    it('throws DTOException on sequential JSON array', function (): void {
        expect(fn () => EmptyDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class)
            ->getMessage()
            ->toContain('Expected a JSON object');
    });

    it('throws DTOException on JSON scalar', function (): void {
        expect(fn () => EmptyDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON number', function (): void {
        expect(fn () => EmptyDTO::fromJson('42', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON boolean', function (): void {
        expect(fn () => EmptyDTO::fromJson('true', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON null', function (): void {
        expect(fn () => EmptyDTO::fromJson('null', validate: false))
            ->toThrow(DTOException::class);
    });

    it('handles empty JSON object', function (): void {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->foo)->toBeNull();
    });

    it('respects explicit null values in JSON', function (): void {
        $dto = EmptyDTO::fromJson('{"foo":null,"bar":null}', validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });
});

describe('DTOException factory methods', function (): void {
    it('invalidCast includes property and type info', function (): void {
        $exception = DTOException::invalidCast('age', 'int', 'hello');

        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('int');
        expect($exception->getMessage())->toContain('string'); // get_debug_type of 'hello'
    });

    it('invalidJson includes property and error', function (): void {
        $exception = DTOException::invalidJson('data', 'Syntax error');

        expect($exception->getMessage())->toContain('data');
        expect($exception->getMessage())->toContain('Syntax error');
    });
});

describe('selective output only/except', function (): void {
    it('only accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        expect($dto->only('email'))->toBe(['email' => 'test@example.com']);
    });

    it('only accepts multiple string keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('only accepts array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->only(['email']);

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('except accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except silently ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $result = $dto->except('nonexistent');

        // Should still have all normal keys
        expect($result)->toHaveKey('email');
    });

    it('only ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toBe(['email' => 'test@example.com']);
    });
});
