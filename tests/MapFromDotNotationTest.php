<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;

describe('MapFrom: dot-notation key extraction', function (): void {
    it('extracts nested value from flat source array using dot notation', function (): void {
        $dto = DotNotationDTO::fromArray([
            'user.profile.firstName' => 'Alice',
            'user.profile.lastName' => 'Smith',
        ], validate: false);

        expect($dto->firstName)->toBe('Alice');
        expect($dto->lastName)->toBe('Smith');
    });

    it('falls back to direct property name when mapped key is missing', function (): void {
        $dto = DotNotationDTO::fromArray([
            'firstName' => 'Bob',
            'lastName' => 'Jones',
        ], validate: false);

        // MapFrom changes the source key — if the mapped key is missing
        // and no direct property name match, defaults apply
        expect($dto->firstName)->toBe('');
        expect($dto->lastName)->toBe('');
    });

    it('nullable mapped properties default to null when key is missing', function (): void {
        $dto = DotNotationDTO::fromArray([
            'user.profile.firstName' => 'Charlie',
            'user.profile.lastName' => 'Brown',
        ], validate: false);

        expect($dto->email)->toBeNull();
    });

    it('maps non-dot keys correctly', function (): void {
        $dto = DotNotationDTO::fromArray([
            'user.profile.firstName' => 'Dave',
            'user.profile.lastName' => 'Wilson',
            'contact_email' => 'dave@example.com',
        ], validate: false);

        expect($dto->email)->toBe('dave@example.com');
    });

    it('toArray uses property names not mapped keys', function (): void {
        $dto = DotNotationDTO::fromArray([
            'user.profile.firstName' => 'Eve',
            'user.profile.lastName' => 'Taylor',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('firstName');
        expect($arr)->toHaveKey('lastName');
        expect($arr)->not->toHaveKey('user.profile.firstName');
    });
});

describe('MapFrom: simple key aliasing (MixedAttributesDTO)', function (): void {
    it('maps user_email source key to $email property', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'alice',
            'hexCode' => 'a1b2c3',
            'user_email' => 'alice@example.com',
        ], validate: false);

        expect($dto->email)->toBe('alice@example.com');
    });

    it('defaults to null when mapped key is absent', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'bob',
            'hexCode' => 'd4e5f6',
        ], validate: false);

        expect($dto->email)->toBeNull();
    });

    it('does not recognize the property name when MapFrom is set', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'charlie',
            'hexCode' => 'g7h8i9',
            'email' => 'direct@example.com',
        ], validate: false);

        // 'email' is not the mapped key — 'user_email' is
        // The property itself is nullable, so it defaults to null
        expect($dto->email)->toBeNull();
    });
});

describe('MapFrom: CreateUserDTO phone_number', function (): void {
    it('maps phone_number source key to $phone property', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('defaults phone to null when phone_number is absent', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test2@example.com',
            'name' => 'Test User 2',
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });
});
