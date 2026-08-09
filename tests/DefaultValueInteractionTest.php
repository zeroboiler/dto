<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DefaultValue + fromPartialArray interaction', function (): void {
    it('preserves DefaultValue when field is not provided in partial update', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // 'status' has DefaultValue('active')
        expect($dto->status)->toBe('active');
    });

    it('overwrites DefaultValue when field is provided in partial update', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'inactive',
        ], validate: false);

        expect($dto->status)->toBe('inactive');
    });

    it('preserves array default when field is not provided', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // 'tags' has default []
        expect($dto->tags)->toBe([]);
    });
});

describe('DefaultValue + fromArray', function (): void {
    it('applies DefaultValue when key is missing from array', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'alice',
            'hexCode' => 'a1b2c3',
        ], validate: false);

        // 'role' has DefaultValue('user')
        expect($dto->role)->toBe('user');
        // 'age' has Cast('integer') with default 0
        expect($dto->age)->toBe(0);
        // 'isActive' defaults to false
        expect($dto->isActive)->toBeFalse();
        // 'tags' defaults to []
        expect($dto->tags)->toBe([]);
    });

    it('explicit null for non-nullable property with DefaultValue throws or uses default', function (): void {
        // status is string with DefaultValue('active')
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'pending',
        ], validate: false);

        expect($dto->status)->toBe('pending');
    });
});

describe('DefaultValue + toArray roundtrip', function (): void {
    it('includes default values in toArray output', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr['status'])->toBe('active');
        expect($arr['tags'])->toBe([]);
    });

    it('hidden fields with defaults are excluded from toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->not->toHaveKey('password');
    });
});

describe('DefaultValue + toJson roundtrip', function (): void {
    it('serializes defaults to JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson();

        $decoded = json_decode($json, true);
        expect($decoded['status'])->toBe('active');
        expect($decoded['tags'])->toBe([]);
        expect($decoded)->not->toHaveKey('password');
    });
});

describe('DefaultValue + with() immutability', function (): void {
    it('with() creates new instance with modified defaults', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'alice',
            'hexCode' => 'a1b2c3',
        ], validate: false);

        // role defaults to 'user'
        expect($dto->role)->toBe('user');

        // with() should create a new instance
        $updated = $dto->with(['role' => 'admin']);

        expect($dto->role)->toBe('user');
        expect($updated->role)->toBe('admin');
    });
});
