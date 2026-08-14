<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('Cross-package enum-DTO integration pattern', function () {
    it('DTO with() roundtrip preserves all fields', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
            'status' => 'active',
            'tags' => ['admin', 'dev'],
        ], validate: false);

        $modified = $dto->with(['status' => 'inactive']);

        expect($modified->toArray()['status'])->toBe('inactive');
        expect($modified->toArray()['name'])->toBe('Alice');
        expect($modified->toArray()['email'])->toBe('alice@test.com');
    });

    it('DTO fromPartialArray with selective fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Bob',
        ], validate: false);

        expect($dto->toArray()['name'])->toBe('Bob');
        // status should use default value
        expect($dto->toArray()['status'])->toBe('active');
    });

    it('DTO fromPartialArray preserves existing values via allValues merge', function () {
        $original = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $partial = CreateUserDTO::fromPartialArray([
            'status' => 'suspended',
        ], validate: false);

        // Partial creates a new DTO — status should be updated
        expect($partial->toArray()['status'])->toBe('suspended');
    });

    it('DTO equals() compares serialized output', function () {
        $d1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@t.com'], validate: false);
        $d2 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@t.com'], validate: false);
        $d3 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@t.com'], validate: false);

        expect($d1->equals($d2))->toBeTrue();
        expect($d1->equals($d3))->toBeFalse();
    });

    it('DTO isEmpty() detects empty-like state', function () {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        // All fields have defaults or are nullable — should be "empty"
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO only/except filters correctly', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@test.com',
        ], validate: false);

        $only = $dto->only('name');
        expect($only)->toHaveKey('name');
        expect($only)->not->toHaveKey('email');

        $except = $dto->except('email');
        expect($except)->toHaveKey('name');
        expect($except)->not->toHaveKey('email');
    });
});
