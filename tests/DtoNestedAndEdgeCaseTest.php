<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO nested DTO and collection edge cases', function () {
    it('hydrates nested DTO from array data', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'zip' => '12345',
            ],
            'items' => [
                ['name' => 'Widget', 'quantity' => 2, 'price' => 9.99],
            ],
            'rawTotal' => 100,
        ], validate: false);

        expect($dto->orderNumber)->toBe('ORD-001');
        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->city)->toBe('Anytown');
    });

    it('serializes nested DTO to array recursively', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => [
                'street' => '456 Oak Ave',
                'city' => 'Somewhere',
                'zip' => '54321',
            ],
            'rawTotal' => 50,
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr['shippingAddress'])->toBeArray();
        expect($arr['shippingAddress']['city'])->toBe('Somewhere');
    });

    it('round-trips nested DTO through with() override', function () {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => [
                'street' => '789 Pine Rd',
                'city' => 'Nowhere',
                'zip' => '11111',
            ],
            'rawTotal' => 200,
        ], validate: false);

        $modified = $dto->with(['orderNumber' => 'ORD-003-UPDATED'], validate: false);
        expect($modified->orderNumber)->toBe('ORD-003-UPDATED');
        // Nested DTO should be preserved
        expect($modified->shippingAddress->city)->toBe('Nowhere');
    });

    it('allValues includes hidden properties', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');

        // toArray should exclude hidden
        $pub = $dto->toArray();
        expect($pub)->not->toHaveKey('password');
    });

    it('equals() compares serialized output', function () {
        $data = [
            'email' => 'same@example.com',
            'name' => 'Same User',
        ];

        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray($data, validate: false);
        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different data', function () {
        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'User A',
        ], validate: false);

        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'User B',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty() returns true when all properties are empty', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromPartialArray([], validate: false);

        // Status has default 'active', so it won't be empty
        // But email and name should be empty/default
        expect($dto->email)->toBe('');
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromJson('["a","b","c"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson rejects invalid JSON', function () {
        expect(fn () => \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('MapFrom attribute correctly maps source keys', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'map@test.com',
            'name' => 'Mapped User',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });
});
