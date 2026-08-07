<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;

describe('MixedAttributesDTO integration', function () {
    it('hydrates from array with all property types', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'johndoe',
            'hexCode' => 'a1b2c3',
            'user_email' => 'john@example.com',
            'age' => '30',
            'role' => 'admin',
            'token' => 'secret',
            'isActive' => true,
            'tags' => ['php', 'laravel'],
        ], validate: false);

        expect($dto->username)->toBe('johndoe');
        expect($dto->hexCode)->toBe('a1b2c3');
        expect($dto->email)->toBe('john@example.com');
        expect($dto->age)->toBe(30);
        expect($dto->role)->toBe('admin');
        expect($dto->token)->toBe('secret');
        expect($dto->isActive)->toBeTrue();
        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('applies default value when key is missing', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'alice',
            'hexCode' => 'ff00ff',
        ], validate: false);

        expect($dto->role)->toBe('user');
        expect($dto->email)->toBeNull();
        expect($dto->age)->toBe(0);
        expect($dto->isActive)->toBeFalse();
        expect($dto->tags)->toBe([]);
    });

    it('serializes with hidden field excluded', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'bob',
            'hexCode' => 'abcdef',
            'token' => 'hidden-token',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('username');
        expect($arr)->not->toHaveKey('token');
    });

    it('allValues includes hidden fields', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'bob',
            'hexCode' => 'abcdef',
            'token' => 'visible-secret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('token');
        expect($all['token'])->toBe('visible-secret');
    });

    it('MapFrom maps source key correctly', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'charlie',
            'hexCode' => '123456',
            'user_email' => 'charlie@test.com',
        ], validate: false);

        expect($dto->email)->toBe('charlie@test.com');
    });

    it('Cast converts string to integer', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'dave',
            'hexCode' => 'aabbcc',
            'age' => '25',
        ], validate: false);

        expect($dto->age)->toBe(25);
        expect($dto->age)->toBeInt();
    });

    it('only() returns specified fields', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'eve',
            'hexCode' => '112233',
            'isActive' => true,
        ], validate: false);

        $result = $dto->only('username', 'isActive');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['username', 'isActive']);
    });

    it('except() excludes specified fields', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'frank',
            'hexCode' => '445566',
        ], validate: false);

        $result = $dto->except('username');

        expect($result)->not->toHaveKey('username');
        expect($result)->toHaveKey('hexCode');
    });

    it('with() creates new instance with overrides', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'grace',
            'hexCode' => '778899',
        ], validate: false);

        $updated = $dto->with(['role' => 'superadmin']);

        expect($updated->role)->toBe('superadmin');
        expect($updated->username)->toBe('grace');
        expect($dto->role)->toBe('user'); // original unchanged
    });

    it('equals() checks value equality', function () {
        $dto1 = MixedAttributesDTO::fromArray([
            'username' => 'test',
            'hexCode' => 'aabbcc',
        ], validate: false);

        $dto2 = MixedAttributesDTO::fromArray([
            'username' => 'test',
            'hexCode' => 'aabbcc',
        ], validate: false);

        $dto3 = MixedAttributesDTO::fromArray([
            'username' => 'other',
            'hexCode' => 'aabbcc',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty() and isNotEmpty() work correctly', function () {
        $empty = MixedAttributesDTO::fromArray([
            'username' => '',
            'hexCode' => '',
        ], validate: false);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
    });

    it('generates validation rules', function () {
        $rules = MixedAttributesDTO::rules();

        expect($rules)->toHaveKey('username');
        expect($rules['username'])->toContain('required');
        expect($rules['username'])->toContain('min:3');
        expect($rules['username'])->toContain('max:100');

        expect($rules)->toHaveKey('hexCode');
        expect($rules['hexCode'])->toContain('regex:/^[a-f0-9]{6}$/');
    });

    it('roundtrips through JSON', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'hank',
            'hexCode' => 'deadbe',
            'tags' => ['dev', 'ops'],
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['username'])->toBe('hank');
        expect($decoded['tags'])->toBe(['dev', 'ops']);
    });
});

describe('DeepNestedDTO integration', function () {
    it('hydrates deeply nested DTOs from arrays', function () {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-001',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
                'country' => 'TR',
            ],
            'label' => 'Primary Address',
        ], validate: false);

        expect($dto->address)->toBeInstanceOf(AddressDTO::class);
        expect($dto->address->street)->toBe('123 Main St');
        expect($dto->address->city)->toBe('Istanbul');
    });

    it('serializes nested DTOs recursively', function () {
        $dto = DeepNestedDTO::fromArray([
            'id' => 'order-002',
            'address' => [
                'street' => '456 Oak Ave',
                'city' => 'Ankara',
                'country' => 'TR',
            ],
            'label' => 'Secondary Address',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr['address'])->toBeArray();
        expect($arr['address']['street'])->toBe('456 Oak Ave');
        expect($arr['address']['city'])->toBe('Ankara');
    });

    it('equals() works with nested DTOs', function () {
        $data = [
            'id' => 'same',
            'address' => ['street' => 'Same St', 'city' => 'Same City', 'country' => 'US'],
            'label' => 'Same Label',
        ];

        $dto1 = DeepNestedDTO::fromArray($data, validate: false);
        $dto2 = DeepNestedDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });
});
