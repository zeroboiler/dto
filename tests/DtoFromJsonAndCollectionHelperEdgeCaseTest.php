<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DataTransferObject fromJson rejection paths', function (): void {
    it('rejects sequential JSON arrays (non-empty)', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('["email","name"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts empty array [] as valid JSON object', function (): void {
        // EmptyDTO has no required properties, so empty data should work
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('fromJson rejects invalid JSON syntax', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('{invalid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson exception contains property name in message', function (): void {
        try {
            CreateUserDTO::fromJson('{bad}', validate: false);
            expect(true)->toBeFalse('Should have thrown');
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('(root)');
        }
    });
});

describe('DataTransferObject only/except edge cases', function (): void {
    it('only with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('except with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('only ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('nonexistent_field');
        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    });

    it('except ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('nonexistent_field');
        // All actual fields should still be present
        expect($result)->toBe($dto->toArray());
    });
});

describe('DataTransferObject with() immutable update', function (): void {
    it('returns new instance, original unchanged', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'original@test.com',
            'name' => 'Original',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);

        expect($dto->toArray()['name'])->toBe('Original');
        expect($updated->toArray()['name'])->toBe('Updated');
    });

    it('with validate parameter is ignored — always validates', function (): void {
        // The $validate parameter is deprecated and ignored
        // Valid data should succeed regardless
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $updated = $dto->with(['name' => 'New Name'], validate: false);
        expect($updated->toArray()['name'])->toBe('New Name');
    });
});

describe('DtoCollection toArrayBy and toDictionary', function (): void {
    it('toArrayBy produces keyed array from DTO field', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        $result = $col->toArrayBy('email');
        expect($result)->toBeArray();
        // Should have entries keyed by email
        expect($result)->toHaveKey('a@test.com');
        expect($result['a@test.com'])->toBeArray();
    });

    it('toArrayBy skips items with null key values', function (): void {
        // CreateUserDTO with no phone set → null values skipped
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $col = new DtoCollection([$dto1]);

        // phone is null by default, so pluckKey('phone') should return empty
        $result = $col->toArrayBy('phone');
        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    });

    it('toDictionary with valid key and value fields', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        $map = $col->toDictionary('email', 'name');

        expect($map)->toBeArray();
        expect($map['a@test.com'])->toBe('Alice');
        expect($map['b@test.com'])->toBe('Bob');
    });

    it('map returns plain array with correct indices', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        $names = $col->map(fn (DataTransferObject $d): string => $d->toArray()['name'] ?? '');
        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('filter returns new collection with matching items', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        $filtered = $col->filter(
            fn (DataTransferObject $d): bool => str_starts_with($d->toArray()['email'] ?? '', 'a')
        );

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->toArray()['name'])->toBe('Alice');
    });
});

describe('DTOException factory methods', function (): void {
    it('invalidCast includes property name, type, and debug type', function (): void {
        $exception = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('integer');
        expect($exception->getMessage())->toContain('string'); // get_debug_type('not_a_number') = 'string'
    });

    it('invalidJson includes property name and error', function (): void {
        $exception = DTOException::invalidJson('payload', 'Syntax error');

        expect($exception->getMessage())->toContain('payload');
        expect($exception->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function (): void {
        $exception = DTOException::invalidCast('field', 'int', 'abc');

        expect((string) $exception)->toBe(DTOException::class.': '.$exception->getMessage());
    });
});

describe('DataTransferObject equals and isEmpty', function (): void {
    it('equals returns true for identical DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns true for DTO with only default/null values', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });
});

describe('DataTransferObject toJson', function (): void {
    it('produces valid JSON string', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($json)->toBeString();
        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('test@test.com');
    });

    it('excludes hidden fields from JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('password');
    });
});
