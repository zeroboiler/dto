<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO from empty data and edge cases', function () {
    it('fromArray with empty array on DTO with required fields throws ValidationException', function () {
        expect(fn () => CreateUserDTO::fromArray([]))->toThrow(ValidationException::class);
    });

    it('fromArray with empty array on DTO without required fields succeeds', function () {
        $dto = EmptyDTO::fromArray([]);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toBeArray();
    });

    it('fromArray skip validation produces DTO even with missing required fields', function () {
        $dto = CreateUserDTO::fromArray([], validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('');
    });

    it('fromPartialArray with empty array uses defaults and empty values', function () {
        $dto = CreateUserDTO::fromPartialArray([]);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->status)->toBe('active');
    });

    it('fromPartialArray skips validation when validatePresent is false', function () {
        $dto = CreateUserDTO::fromPartialArray(['email' => 'not-an-email'], validatePresent: false);
        expect($dto->email)->toBe('not-an-email');
    });

    it('fromJson with empty object string produces valid DTO with defaults', function () {
        $dto = EmptyDTO::fromJson('{}');
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('fromJson with empty array string throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('[]'))->toThrow(DTOException::class);
    });

    it('fromJson with invalid JSON throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid}'))->toThrow(DTOException::class);
    });

    it('fromJson with scalar JSON throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('"string"'))->toThrow(DTOException::class);
    });

    it('fromJson with numeric JSON throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('42'))->toThrow(DTOException::class);
    });

    it('fromJson with boolean JSON throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('true'))->toThrow(DTOException::class);
    });

    it('fromJson with null JSON throws DTOException', function () {
        expect(fn () => CreateUserDTO::fromJson('null'))->toThrow(DTOException::class);
    });

    it('equals returns true for same data, false for different', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Other'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty returns true when all fields are empty/default', function () {
        $dto = EmptyDTO::fromArray([]);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when at least one field has value', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('toJson returns valid JSON string', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($json)->toBeString();
        expect($json)->not->toBeEmpty();
        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('a@b.com');
        expect($decoded['name'])->toBe('Test');
    });

    it('toJson excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('email');
        expect($decoded)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $values = $dto->allValues();
        expect($values)->toHaveKey('password');
        expect($values['password'])->toBe('secret123');
    });

    it('with() creates new instance with overrides and validates', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $updated = $dto->with(['name' => 'Updated']);

        expect($dto->name)->toBe('Test'); // original unchanged
        expect($updated->name)->toBe('Updated');
        expect($updated->email)->toBe('a@b.com');
    });

    it('with() throws ValidationException on invalid merged data', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        expect(fn () => $dto->with(['email' => 'invalid-email']))
            ->toThrow(ValidationException::class);
    });

    it('only() returns specified fields only', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'status' => 'active'], validate: false);
        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });

    it('except() returns all fields except specified', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'status' => 'active'], validate: false);
        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('rules() returns array with expected keys', function () {
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor() returns same as rules() by default', function () {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
    });
});
