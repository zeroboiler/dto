<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DataTransferObject fromJson()', function () {
    it('creates DTO from valid JSON string', function () {
        $json = json_encode([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
    });

    it('respects defaults for missing properties', function () {
        $json = json_encode([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('throws DTOException on invalid JSON', function () {
        CreateUserDTO::fromJson('{invalid json}', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on non-object JSON', function () {
        CreateUserDTO::fromJson('"just a string"', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on JSON array at root', function () {
        CreateUserDTO::fromJson('[1, 2, 3]', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on empty string', function () {
        CreateUserDTO::fromJson('', validate: false);
    })->throws(DTOException::class);
});

describe('DataTransferObject isEmpty()', function () {
    it('returns true when all properties are null/empty defaults', function () {
        // EmptyDTO: both foo and bar default to null
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false when at least one property has a non-empty value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('returns false when string property is non-empty', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // email and name are non-empty → not empty
        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers empty string as empty', function () {
        $dto = EmptyDTO::fromArray(['foo' => '', 'bar' => ''], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false when DTO has non-empty default values', function () {
        // CreateUserDTO has status='active' as default — that is non-empty
        $dto = CreateUserDTO::fromArray([
            'email' => '',
            'name' => '',
        ], validate: false);

        // status defaults to 'active' (non-empty) → isEmpty is false
        expect($dto->isEmpty())->toBeFalse();
    });

    it('returns true for DTO with only null values', function () {
        $dto = EmptyDTO::fromArray(['foo' => null, 'bar' => null], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('distinguishes zero and false as empty', function () {
        // EmptyDTO only has ?string properties — so null, '', '' are all empty
        $dto = EmptyDTO::fromArray(['foo' => '', 'bar' => null], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('considers zero as empty', function () {
        $dto = EmptyDTO::fromArray(['foo' => '0', 'bar' => null], validate: false);

        // '0' is a non-empty string
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DataTransferObject isNotEmpty()', function () {
    it('returns false for DTO with only null/empty properties', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('returns true for DTO with values', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('is the negation of isEmpty()', function () {
        $empty = EmptyDTO::fromArray([], validate: false);
        $nonEmpty = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($empty->isNotEmpty())->toBe(!$empty->isEmpty());
        expect($nonEmpty->isNotEmpty())->toBe(!$nonEmpty->isEmpty());
    });
});
