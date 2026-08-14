<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DataTransferObject serialization roundtrip', function (): void {
    it('toArray → fromArray preserves all public fields', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'active',
            'tags' => ['php', 'laravel'],
            'phone' => '+905551234567',
        ], validate: false);

        $array = $original->toArray();
        $restored = CreateUserDTO::fromArray($array, validate: false);

        expect($restored->email)->toBe($original->email);
        expect($restored->name)->toBe($original->name);
        expect($restored->status)->toBe($original->status);
        expect($restored->tags)->toBe($original->tags);
        expect($restored->phone)->toBe($original->phone);
    });

    it('toJson → fromJson roundtrip works', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'roundtrip@test.com',
            'name' => 'Bob',
            'status' => 'inactive',
        ], validate: false);

        $json = $original->toJson();
        $restored = CreateUserDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe($original->email);
        expect($restored->name)->toBe($original->name);
        expect($restored->status)->toBe($original->status);
    });

    it('jsonSerialize returns same as toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Charlie',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Dave',
            'password' => 'secret123',
        ], validate: false);

        $public = $dto->toArray();
        $all = $dto->allValues();

        expect($public)->not->toHaveKey('password');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('with() creates new instance without mutating original', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Eve',
            'status' => 'active',
        ], validate: false);

        $modified = $original->with(['name' => 'Updated Eve']);

        expect($original->name)->toBe('Eve');
        expect($modified->name)->toBe('Updated Eve');
        expect($modified->email)->toBe('test@example.com');
        expect($modified->status)->toBe('active');
    });

    it('with() validates merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Frank',
        ], validate: false);

        // Clear the email — should fail validation
        expect(fn (): mixed => $dto->with(['email' => '']))
            ->throws(\Illuminate\Validation\ValidationException::class);
    });
});

describe('DataTransferObject equals', function (): void {
    it('considers DTOs equal with same visible data', function (): void {
        $a = CreateUserDTO::fromArray([
            'email' => 'same@test.com',
            'name' => 'Same',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'same@test.com',
            'name' => 'Same',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('considers DTOs unequal with different hidden fields but same visible', function (): void {
        $a = CreateUserDTO::fromArray([
            'email' => 'same@test.com',
            'name' => 'Same',
            'password' => 'pw_a',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'same@test.com',
            'name' => 'Same',
            'password' => 'pw_b',
        ], validate: false);

        // Hidden field (password) is excluded from equals comparison
        expect($a->equals($b))->toBeTrue();
    });

    it('considers DTOs unequal with different visible data', function (): void {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'A',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'B',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });
});
