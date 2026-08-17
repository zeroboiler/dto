<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('ProductDTO Hydration and Validation', function () {
    describe('fromArray with full data', function () {
        it('hydrates all properties correctly', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget Pro',
                'sku' => 'AB1234',
                'priceCents' => 999,
                'isActive' => true,
                'category' => 'electronics',
                'description' => 'A professional widget',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'vendor_code' => 'VC-001',
                'internalNotes' => 'secret',
                'stockCount' => 42,
            ]);

            expect($dto->name)->toBe('Widget Pro');
            expect($dto->sku)->toBe('AB1234');
            expect($dto->priceCents)->toBe(999);
            expect($dto->isActive)->toBeTrue();
            expect($dto->category)->toBe('electronics');
            expect($dto->description)->toBe('A professional widget');
            expect($dto->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000');
            expect($dto->vendorCode)->toBe('VC-001');
            expect($dto->internalNotes)->toBe('secret');
            expect($dto->stockCount)->toBe(42);
        });
    });

    describe('fromArray with minimal required data', function () {
        it('applies defaults for optional fields', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Basic Widget',
                'sku' => 'XY0000',
                'priceCents' => 500,
            ]);

            expect($dto->name)->toBe('Basic Widget');
            expect($dto->isActive)->toBeTrue(); // default
            expect($dto->category)->toBe('general'); // DefaultValue attribute
            expect($dto->description)->toBeNull(); // nullable default
            expect($dto->uuid)->toBeNull();
            expect($dto->vendorCode)->toBeNull();
            expect($dto->stockCount)->toBe(0); // default
        });
    });

    describe('MapFrom resolution', function () {
        it('maps vendor_code to vendorCode property', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'vendor_code' => 'VENDOR-XYZ',
            ]);

            expect($dto->vendorCode)->toBe('VENDOR-XYZ');
        });

        it('ignores vendorCode when vendor_code is absent', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            expect($dto->vendorCode)->toBeNull();
        });
    });

    describe('serialization', function () {
        it('toArray excludes hidden fields', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'internalNotes' => 'should be hidden',
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('sku');
            expect($array)->toHaveKey('priceCents');
            expect($array)->not->toHaveKey('internalNotes');
        });

        it('allValues includes hidden fields', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'internalNotes' => 'visible in allValues',
            ]);

            $all = $dto->allValues();
            expect($all)->toHaveKey('internalNotes');
            expect($all['internalNotes'])->toBe('visible in allValues');
        });

        it('only returns specified fields', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            $only = $dto->only('name', 'sku');
            expect($only)->toBe([
                'name' => 'Widget',
                'sku' => 'AB1234',
            ]);
        });

        it('except excludes specified fields', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'stockCount' => 10,
            ]);

            $except = $dto->except('priceCents', 'stockCount');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('sku');
            expect($except)->not->toHaveKey('priceCents');
            expect($except)->not->toHaveKey('stockCount');
        });
    });

    describe('equality and state', function () {
        it('equals returns true for identical DTOs', function () {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);
            $dto2 = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Widget A',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);
            $dto2 = ProductDTO::fromArray([
                'name' => 'Widget B',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns false when required fields are set', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('immutable update (with)', function () {
        it('creates new DTO with updated fields', function () {
            $original = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
            ]);

            $updated = $original->with(['priceCents' => 200, 'category' => 'premium']);

            expect($updated->name)->toBe('Widget'); // unchanged
            expect($updated->priceCents)->toBe(200); // updated
            expect($updated->category)->toBe('premium'); // updated
            expect($original->priceCents)->toBe(100); // original unchanged
        });
    });

    describe('partial update (fromPartialArray)', function () {
        it('only updates provided fields', function () {
            $dto = ProductDTO::fromPartialArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
            ]);

            expect($dto->name)->toBe('Widget');
            expect($dto->sku)->toBe('AB1234');
            expect($dto->priceCents)->toBe(0); // type-appropriate empty
            expect($dto->category)->toBe('general'); // DefaultValue
            expect($dto->isActive)->toBeFalse(); // bool empty
        });
    });

    describe('validation rules', function () {
        it('rules returns correct structure', function () {
            $rules = ProductDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('sku');
            expect($rules)->toHaveKey('priceCents');
            expect($rules)->toHaveKey('isActive');
            expect($rules)->toHaveKey('category');
            expect($rules)->toHaveKey('uuid');
            expect($rules)->toHaveKey('stockCount');
        });

        it('rulesFor returns same rules by default', function () {
            expect(ProductDTO::rulesFor('create'))->toBe(ProductDTO::rules());
            expect(ProductDTO::rulesFor('update'))->toBe(ProductDTO::rules());
        });
    });

    describe('Boolean cast edge cases', function () {
        it('casts string "false" to false', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'isActive' => 'false',
            ]);

            expect($dto->isActive)->toBeFalse();
        });

        it('casts integer 0 to false', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'isActive' => 0,
            ]);

            expect($dto->isActive)->toBeFalse();
        });

        it('casts string "1" to true', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'sku' => 'AB1234',
                'priceCents' => 100,
                'isActive' => '1',
            ]);

            expect($dto->isActive)->toBeTrue();
        });
    });
});
