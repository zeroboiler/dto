<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DataTransferObject fromJson edge cases', function (): void {
    it('throws DTOException on invalid JSON', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON that decodes to sequential array', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on JSON that decodes to scalar', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts valid JSON object', function (): void {
        $json = json_encode([
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('creates DTO from empty JSON object with defaults', function (): void {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->foo)->toBeNull();
    });
});

describe('DataTransferObject only and except with multiple keys', function (): void {
    it('only returns multiple specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
        expect($result)->not->toHaveKey('phone');
    });

    it('only with string key returns single field', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('only ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only(['email', 'nonexistent']);

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('nonexistent');
    });

    it('except removes multiple specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['email', 'status']);

        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('status');
        expect($result)->toHaveKey('name');
    });

    it('except with string key removes single field', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

describe('DataTransferObject allValues includes hidden', function (): void {
    it('allValues includes hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        $public = $dto->toArray();

        expect($all)->toHaveKey('password');
        expect($public)->not->toHaveKey('password');
    });
});

describe('DataTransferObject toJson', function (): void {
    it('produces valid JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('test@example.com');
    });

    it('supports JSON_PRETTY_PRINT option', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("\n");
        expect($json)->toBeJson();
    });
});

describe('DataTransferObject isEmpty and isNotEmpty', function (): void {
    it('isEmpty returns true for DTO with all null/empty properties', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false for DTO with non-empty string', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty treats zero int as non-empty', function (): void {
        $dto = OrderItemDTO::fromArray([
            'productName' => 'Test',
            'price' => 0.0,
            'quantity' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DataTransferObject equals', function (): void {
    it('returns true for DTOs with identical visible values', function (): void {
        $data = ['email' => 'test@example.com', 'name' => 'Test'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('returns false for DTOs with different values', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@example.com', 'name' => 'B'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('ignores hidden properties in equality check', function (): void {
        $data1 = ['email' => 'test@example.com', 'name' => 'Test', 'password' => 'secret1'];
        $data2 = ['email' => 'test@example.com', 'name' => 'Test', 'password' => 'secret2'];

        $dto1 = CreateUserDTO::fromArray($data1, validate: false);
        $dto2 = CreateUserDTO::fromArray($data2, validate: false);

        // password is hidden, so equals should only compare visible properties
        expect($dto1->equals($dto2))->toBeTrue();
    });
});

describe('DataTransferObject rulesFor action scoping', function (): void {
    it('returns same rules as rules() by default', function (): void {
        $rules = CreateUserDTO::rules();
        $createRules = CreateUserDTO::rulesFor('create');

        expect($rules)->toBe($createRules);
    });
});
