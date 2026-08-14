<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('fromJson strict validation', function () {
    it('rejects non-empty sequential JSON arrays', function () {
        CreateUserDTO::fromJson('["alice","alice@test.com"]');
    })->throws(DTOException::class, 'sequential array');

    it('accepts empty JSON array as empty object', function () {
        // Empty [] is both a list and a valid empty object — should not throw
        // The DTO will use defaults for all fields
        $dto = CreateUserDTO::fromJson('[]', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('rejects invalid JSON with descriptive error', function () {
        CreateUserDTO::fromJson('{invalid json}');
    })->throws(DTOException::class);

    it('accepts valid JSON object', function () {
        $dto = CreateUserDTO::fromJson(
            json_encode(['name' => 'Alice', 'email' => 'alice@test.com']),
            validate: false
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->toArray())->toHaveKey('name');
        expect($dto->toArray())->toHaveKey('email');
    });

    it('throws DTOException when JSON decodes to a scalar', function () {
        CreateUserDTO::fromJson('"just a string"');
    })->throws(DTOException::class);
});
