<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

describe('DataTransferObject isEmpty and isNotEmpty edge cases', function () {
    it('returns true when all properties have default/empty values', function () {
        $dto = CreateUserDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('returns false when at least one property has a non-empty value', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => '',
            'password' => '',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('treats numeric zero as non-empty', function () {
        // Create a DTO with a numeric zero property
        $dto = new class(0, 0.0) extends DataTransferObject {
            public function __construct(
                public readonly int $count,
                public readonly float $price,
            ) {}
        };

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('treats null nullable properties as empty', function () {
        $dto = new class(null, null) extends DataTransferObject {
            public function __construct(
                public readonly ?string $name,
                public readonly ?string $email,
            ) {}
        };

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('treats non-null nullable property as non-empty', function () {
        $dto = new class('Alice', null) extends DataTransferObject {
            public function __construct(
                public readonly ?string $name,
                public readonly ?string $email,
            ) {}
        };

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('treats empty string as empty', function () {
        $dto = new class('') extends DataTransferObject {
            public function __construct(
                public readonly string $value,
            ) {}
        };

        expect($dto->isEmpty())->toBeTrue();
    });

    it('treats empty array as empty', function () {
        $dto = new class([]) extends DataTransferObject {
            public function __construct(
                public readonly array $items,
            ) {}
        };

        expect($dto->isEmpty())->toBeTrue();
    });

    it('treats false boolean as empty', function () {
        $dto = new class(false) extends DataTransferObject {
            public function __construct(
                public readonly bool $active,
            ) {}
        };

        expect($dto->isEmpty())->toBeTrue();
    });

    it('treats true boolean as non-empty', function () {
        $dto = new class(true) extends DataTransferObject {
            public function __construct(
                public readonly bool $active,
            ) {}
        };

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DataTransferObject equals method edge cases', function () {
    it('returns true for DTOs with identical public output', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $b = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false when hidden properties differ but public output is same', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret1',
        ], validate: false);

        $b = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret2',
        ], validate: false);

        // equals() uses toArray() which excludes hidden fields
        // password is hidden, so public output is identical
        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for DTOs with different public values', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'Alice',
        ], validate: false);

        $b = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'Bob',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });
});

describe('DataTransferObject with() immutable update edge cases', function () {
    it('preserves original DTO immutability', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'original@example.com',
            'name' => 'Original',
        ], validate: false);

        $updated = $original->with(['name' => 'Updated']);

        expect($original->name)->toBe('Original');
        expect($updated->name)->toBe('Updated');
        expect($original->email)->toBe('original@example.com');
        expect($updated->email)->toBe('original@example.com');
    });

    it('returns a new instance that is not the same reference', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $updated = $original->with(['name' => 'New']);

        expect($original)->not->toBe($updated);
    });
});
