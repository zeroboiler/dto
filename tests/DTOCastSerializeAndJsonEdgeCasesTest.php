<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTOCast — serialize method', function (): void {
    it('serializes null value as null', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'payload',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('serializes DTO instance to array', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $cast->serialize(
            model: new class {},
            key: 'payload',
            value: $dto,
            attributes: [],
        );

        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
        expect($result['email'])->toBe('test@example.com');
    });
});

describe('DTOCast — get method with invalid JSON', function (): void {
    it('returns null for non-array value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            model: new class {},
            key: 'payload',
            value: 'not-json',
            attributes: [],
        );

        // json_decode of 'not-json' with true returns null
        expect($result)->toBeNull();
    });

    it('hydrates from valid JSON string', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $json = json_encode([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ]);

        $result = $cast->get(
            model: new class {},
            key: 'payload',
            value: $json,
            attributes: [],
        );

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
    });
});

describe('DTOCast — set method error cases', function (): void {
    it('throws when value is not DTO, array, or null', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $cast->set(
            model: new class {},
            key: 'payload',
            value: 42,
            attributes: [],
        );
    })->throws(\InvalidArgumentException::class, 'DTOCast::set() expects a DTO instance, array, or null');
});

describe('DataTransferObject — fromJson edge cases', function (): void {
    it('throws on invalid JSON syntax', function (): void {
        CreateUserDTO::fromJson('{invalid json}');
    })->throws(DTOException::class, 'Cannot decode JSON');

    it('throws on sequential array (JSON array, not object)', function (): void {
        CreateUserDTO::fromJson('["email","name"]');
    })->throws(DTOException::class, 'Expected a JSON object');

    it('creates DTO from valid JSON object', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"a@b.com","name":"Test"}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('a@b.com');
    });
});

describe('DataTransferObject — fromPartialArray with empty data', function (): void {
    it('returns DTO with all defaults when no data provided', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });
});

describe('DataTransferObject — isEmpty and isNotEmpty', function (): void {
    it('returns true for DTO with all null/empty properties', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false for DTO with non-empty string property', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });
});
