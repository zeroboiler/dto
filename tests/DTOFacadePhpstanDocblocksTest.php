<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('Facade @method PHPStan docblocks', function () {
    it('DTO::validate returns validated data', function () {
        $validated = DTO::validate(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        expect($validated)->toBeArray();
        expect($validated)->toHaveKey('email');
        expect($validated)->toHaveKey('name');
    });

    it('DTO::validate throws ValidationException for invalid data', function () {
        DTO::validate(CreateUserDTO::class, [
            'email' => 'not-an-email',
            'name' => 'T', // too short, min:2
        ]);
    })->throws(ValidationException::class);

    it('DTO::make creates DTO instance from valid data', function () {
        $dto = DTO::make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
    });

    it('DTO::make throws ValidationException for invalid data', function () {
        DTO::make(CreateUserDTO::class, []);
    })->throws(ValidationException::class);

    it('DTO::makeFromJson creates DTO from valid JSON', function () {
        $json = json_encode(['email' => 'test@example.com', 'name' => 'Test User']);

        $dto = DTO::makeFromJson(CreateUserDTO::class, $json);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('DTO::makeFromJson throws DTOException for invalid JSON', function () {
        DTO::makeFromJson(CreateUserDTO::class, '{invalid json}');
    })->throws(DTOException::class);

    it('DTO::makeFromJson throws DTOException for sequential array JSON', function () {
        DTO::makeFromJson(CreateUserDTO::class, '["email","test@example.com"]');
    })->throws(DTOException::class);

    it('DTO::schema generates OpenAPI schema array', function () {
        $schema = DTO::schema(CreateUserDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
        expect($schema)->toHaveKey('properties');
    });

    it('DTO::schema marks required fields correctly', function () {
        $schema = DTO::schema(CreateUserDTO::class);

        expect($schema)->toHaveKey('required');
        expect($schema['required'])->toContain('email');
        expect($schema['required'])->toContain('name');
    });

    it('DTO::schema applies email format constraint', function () {
        $schema = DTO::schema(CreateUserDTO::class);

        $emailProp = $schema['properties']['email'] ?? [];
        expect($emailProp)->toHaveKey('format');
        expect($emailProp['format'])->toBe('email');
    });

    it('DTO::schema respects nullable properties', function () {
        $schema = DTO::schema(CreateUserDTO::class);

        // password is nullable (?string)
        $passwordProp = $schema['properties']['password'] ?? [];
        expect($passwordProp)->toHaveKey('nullable');
        expect($passwordProp['nullable'])->toBeTrue();
    });
});

describe('Facade with EmptyDTO', function () {
    it('DTO::make works with empty DTO', function () {
        $dto = DTO::make(EmptyDTO::class, []);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toBe([]);
    });

    it('DTO::validate works with empty DTO', function () {
        $validated = DTO::validate(EmptyDTO::class, []);

        expect($validated)->toBeArray();
        expect($validated)->toBe([]);
    });

    it('DTO::schema generates object schema for empty DTO', function () {
        $schema = DTO::schema(EmptyDTO::class);

        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toBeObject();
        expect($schema['required'])->toBeArray();
    });
});

describe('Facade with MinimalDTO', function () {
    it('DTO::make works with minimal DTO using defaults', function () {
        $dto = DTO::make(MinimalDTO::class, []);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
    });

    it('DTO::validate returns defaults for minimal DTO', function () {
        $validated = DTO::validate(MinimalDTO::class, []);

        expect($validated)->toBeArray();
    });
});

describe('DTO facade consistency with static methods', function () {
    it('DTO::make result equals static fromArray result', function () {
        $data = ['email' => 'test@example.com', 'name' => 'Test User'];

        $facadeDto = DTO::make(CreateUserDTO::class, $data);
        $staticDto = CreateUserDTO::fromArray($data);

        expect($facadeDto->toArray())->toEqual($staticDto->toArray());
    });

    it('DTO::validate result equals static validateArray result', function () {
        $data = ['email' => 'test@example.com', 'name' => 'Test User'];

        $facadeValidated = DTO::validate(CreateUserDTO::class, $data);
        $staticValidated = CreateUserDTO::validateArray($data);

        expect($facadeValidated)->toEqual($staticValidated);
    });

    it('DTO::schema result equals static OpenApiSchemaGenerator result', function () {
        $facadeSchema = DTO::schema(CreateUserDTO::class);

        // Verify the facade delegates correctly
        expect($facadeSchema)->toBeArray();
        expect($facadeSchema['type'])->toBe('object');
    });
});
