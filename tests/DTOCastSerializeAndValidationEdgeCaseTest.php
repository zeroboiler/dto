<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTOCast serialize edge cases', function () {
    it('serialize returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->serialize($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('serialize returns toArray for DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $result = $cast->serialize($model, 'data', $dto, []);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('name');
        expect($result['name'])->toBe('Alice');
    });

    it('serialize returns null for non-DTO value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->serialize($model, 'data', 'not-a-dto', []);

        // null?->toArray() returns null when value is not a DTO
        expect($result)->toBeNull();
    });
});

describe('DTOCast set validation', function () {
    it('set rejects unexpected types with descriptive error', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $cast->set($model, 'data', 42, []);
    })->throws(\InvalidArgumentException::class, 'expects a DTO instance, array, or null');

    it('set passes null through', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->set($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('set serializes DTO instance to JSON', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $result = $cast->set($model, 'data', $dto, []);

        expect($result)->toBeString();
        $decoded = json_decode($result, true);
        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('Alice');
    });

    it('set hydrates and serializes raw array', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->set($model, 'data', ['name' => 'Bob', 'email' => 'bob@test.com'], []);

        expect($result)->toBeString();
        $decoded = json_decode($result, true);
        expect($decoded['name'])->toBe('Bob');
    });
});

describe('DTOCast get edge cases', function () {
    it('get returns null for null database value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('get returns null for invalid JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', 'not-valid-json{', []);

        expect($result)->toBeNull();
    });

    it('get returns null for non-array value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', 'just-a-string', []);

        expect($result)->toBeNull();
    });

    it('get hydrates from valid JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $json = json_encode(['name' => 'Alice', 'email' => 'alice@test.com']);
        $result = $cast->get($model, 'data', $json, []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('get hydrates from array value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __construct(public array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', ['name' => 'Bob', 'email' => 'bob@test.com'], []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
    });
});
