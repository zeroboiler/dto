<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;

// ──────────────────────────────────────────────────────────────
// 1. DTOCast::set() return type — array|string|null
// ──────────────────────────────────────────────────────────────

describe('DTOCast return type safety', function () {
    it('set() returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public $attributes = [];
        };

        $result = $cast->set($model, 'payload', null, []);
        expect($result)->toBeNull();
    });

    it('set() returns string for valid DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public $attributes = [];
        };

        $dto = new CreateUserDTO(
            email: 'test@example.com',
            name: 'Test User',
            status: 'active',
        );

        $result = $cast->set($model, 'payload', $dto, []);
        expect($result)->toBeString();
        expect($result)->toBeJson();
    });

    it('set() returns string for valid array', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public $attributes = [];
        };

        $result = $cast->set($model, 'payload', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], []);

        expect($result)->toBeString();
        expect($result)->toBeJson();
    });

    it('set() throws InvalidArgumentException for invalid type', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public $attributes = [];
        };

        expect(fn () => $cast->set($model, 'payload', 12345, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serialize() returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public $attributes = [];
        };

        $result = $cast->serialize($model, 'payload', null, []);
        expect($result)->toBeNull();
    });

    it('serialize() returns array for DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public $attributes = [];
        };

        $dto = new CreateUserDTO(
            email: 'test@example.com',
            name: 'Test User',
            status: 'active',
        );

        $result = $cast->serialize($model, 'payload', $dto, []);
        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        // password is hidden — should not be in serialized output
        expect($result)->not->toHaveKey('password');
    });
});

// ──────────────────────────────────────────────────────────────
// 2. DTOManager delegation
// ──────────────────────────────────────────────────────────────

describe('DTOManager delegation', function () {
    it('rules() returns validation rules', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('rulesFor() delegates to DTO rulesFor()', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
    });

    it('validate() returns validated data', function () {
        $manager = new DTOManager;
        $data = [
            'email' => 'test@example.com',
            'name' => 'Valid Name',
            'status' => 'active',
        ];

        $result = $manager->validate(CreateUserDTO::class, $data);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('make() creates DTO instance', function () {
        $manager = new DTOManager;
        $data = [
            'email' => 'test@example.com',
            'name' => 'Valid Name',
            'status' => 'active',
        ];

        $dto = $manager->make(CreateUserDTO::class, $data);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });
});

// ──────────────────────────────────────────────────────────────
// 3. DtoCollection type guard enforcement
// ──────────────────────────────────────────────────────────────

describe('DtoCollection type safety', function () {
    it('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not', 'a', 'dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetSet rejects non-DTO values', function () {
        $col = new DtoCollection;
        expect(fn () => $col[] = 'invalid')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('push returns self for chaining', function () {
        $dto = new CreateUserDTO(
            email: 'a@b.com',
            name: 'Test',
            status: 'active',
        );

        $col = new DtoCollection;
        $result = $col->push($dto);

        expect($result)->toBe($col);
        expect($col->count())->toBe(1);
    });

    it('append returns new collection without mutating original', function () {
        $dto = new CreateUserDTO(
            email: 'a@b.com',
            name: 'Test',
            status: 'active',
        );

        $col = new DtoCollection([$dto]);
        $newCol = $col->append($dto);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('merge combines two collections', function () {
        $dto1 = new CreateUserDTO(
            email: 'a@b.com',
            name: 'Alice',
            status: 'active',
        );
        $dto2 = new CreateUserDTO(
            email: 'c@d.com',
            name: 'Charlie',
            status: 'active',
        );

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1); // original unchanged
    });

    it('offsetUnset re-indexes array', function () {
        $dto1 = new CreateUserDTO(email: 'a@b.com', name: 'A', status: 'active');
        $dto2 = new CreateUserDTO(email: 'c@d.com', name: 'C', status: 'active');
        $dto3 = new CreateUserDTO(email: 'e@f.com', name: 'E', status: 'active');

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($col[1]);

        // After re-index, indices should be 0 and 1
        expect($col[0]->name)->toBe('A');
        expect($col[1]->name)->toBe('E');
        expect($col->count())->toBe(2);
    });
});

// ──────────────────────────────────────────────────────────────
// 4. DataTransferObject — with() always validates
// ──────────────────────────────────────────────────────────────

describe('DataTransferObject with() validation contract', function () {
    it('with() returns a new instance', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $modified = $original->with(['name' => 'New Name'], validate: false);

        expect($modified)->toBeInstanceOf(CreateUserDTO::class);
        expect($modified)->not->toBe($original);
        expect($modified->name)->toBe('New Name');
        // Original should be unchanged
        expect($original->name)->toBe('Test User');
    });

    it('with() merges allValues including hidden fields', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $modified = $original->with(['name' => 'Updated'], validate: false);
        expect($modified->email)->toBe('test@example.com');
    });
});

// ──────────────────────────────────────────────────────────────
// 5. DTOException factory methods
// ──────────────────────────────────────────────────────────────

describe('DTOException factory methods', function () {
    it('invalidCast includes property and type info', function () {
        $exception = DTOException::invalidCast('status', 'integer', 'abc');

        expect($exception->getMessage())->toContain('status');
        expect($exception->getMessage())->toContain('integer');
    });

    it('invalidJson includes property and error message', function () {
        $exception = DTOException::invalidJson('payload', 'Syntax error');

        expect($exception->getMessage())->toContain('payload');
        expect($exception->getMessage())->toContain('Syntax error');
    });

    it('__toString includes class name', function () {
        $exception = DTOException::invalidCast('field', 'int', 'x');
        $string = (string) $exception;

        expect($string)->toContain('DTOException');
    });
});

// ──────────────────────────────────────────────────────────────
// 6. fromJson error handling
// ──────────────────────────────────────────────────────────────

describe('fromJson error handling', function () {
    it('throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function () {
        expect(fn () => CreateUserDTO::fromJson('["a","b","c"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        $dto = CreateUserDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });
});

// ──────────────────────────────────────────────────────────────
// 7. MapFrom with dot notation
// ──────────────────────────────────────────────────────────────

describe('MapFrom dot notation support', function () {
    it('resolves dot-notation keys from nested arrays', function () {
        $dto = DotNotationDTO::fromArray([
            'user' => [
                'profile' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Doe',
                ],
            ],
            'contact_email' => 'nested@example.com',
        ], validate: false);

        expect($dto->firstName)->toBe('Jane');
        expect($dto->lastName)->toBe('Doe');
        expect($dto->email)->toBe('nested@example.com');
    });
});
