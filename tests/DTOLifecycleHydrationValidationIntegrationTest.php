<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO lifecycle — hydration, serialization, partial update, and validation integration', function (): void {
    describe('full hydration pipeline — ProductDTO', function (): void {
        it('hydrates from full array with all fields', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget Pro',
                'sku' => 'AB1234',
                'priceCents' => 1999,
                'isActive' => true,
                'category' => 'electronics',
                'description' => 'A premium widget',
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'vendor_code' => 'VENDOR-001',
                'internalNotes' => 'Internal only',
                'stockCount' => 150,
            ]);

            expect($dto->name)->toBe('Widget Pro');
            expect($dto->sku)->toBe('AB1234');
            expect($dto->priceCents)->toBe(1999);
            expect($dto->isActive)->toBeTrue();
            expect($dto->category)->toBe('electronics');
            expect($dto->description)->toBe('A premium widget');
            expect($dto->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000');
            expect($dto->vendorCode)->toBe('VENDOR-001');
            expect($dto->internalNotes)->toBe('Internal only');
            expect($dto->stockCount)->toBe(150);
        });

        it('hydrates with defaults for missing optional fields', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Simple Widget',
                'sku' => 'XY9876',
                'priceCents' => 500,
            ]);

            expect($dto->name)->toBe('Simple Widget');
            expect($dto->sku)->toBe('XY9876');
            expect($dto->priceCents)->toBe(500);
            expect($dto->isActive)->toBeTrue();          // default
            expect($dto->category)->toBe('general');     // DefaultValue
            expect($dto->description)->toBeNull();       // Nullable default
            expect($dto->uuid)->toBeNull();              // Nullable default
            expect($dto->vendorCode)->toBeNull();        // Nullable default
            expect($dto->internalNotes)->toBeNull();     // Nullable default
            expect($dto->stockCount)->toBe(0);           // default
        });

        it('maps source key via MapFrom attribute', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Mapped Widget',
                'sku' => 'CD5678',
                'priceCents' => 800,
                'vendor_code' => 'VENDOR-XYZ',  // MapFrom('vendor_code') → vendorCode
            ]);

            expect($dto->vendorCode)->toBe('VENDOR-XYZ');
        });

        it('rejects invalid pattern for sku', function (): void {
            expect(fn (): mixed => ProductDTO::fromArray([
                'name' => 'Bad SKU',
                'sku' => 'invalid-sku',
                'priceCents' => 100,
            ]))->toThrow(ValidationException::class);
        });

        it('rejects missing required fields', function (): void {
            expect(fn (): mixed => ProductDTO::fromArray([
                'name' => 'Missing SKU',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('serialization — ProductDTO', function (): void {
        it('toArray excludes hidden fields', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Serialize Test',
                'sku' => 'EF9012',
                'priceCents' => 100,
                'internalNotes' => 'Should not appear',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('sku');
            expect($arr)->toHaveKey('priceCents');
            expect($arr)->not->toHaveKey('internalNotes');
        });

        it('allValues includes hidden fields', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'All Values Test',
                'sku' => 'GH3456',
                'priceCents' => 200,
                'internalNotes' => 'Included in allValues',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('internalNotes');
            expect($all['internalNotes'])->toBe('Included in allValues');
        });

        it('toJson produces valid JSON string', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'JSON Test',
                'sku' => 'IJ7890',
                'priceCents' => 300,
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded['name'])->toBe('JSON Test');
            expect($decoded['sku'])->toBe('IJ7890');
        });

        it('only() returns specified fields only', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Only Test',
                'sku' => 'KL1234',
                'priceCents' => 400,
            ], validate: false);

            $only = $dto->only('name', 'sku');

            expect($only)->toHaveCount(2);
            expect($only)->toHaveKeys(['name', 'sku']);
            expect($only)->not->toHaveKey('priceCents');
        });

        it('except() returns all except specified fields', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Except Test',
                'sku' => 'MN5678',
                'priceCents' => 500,
            ], validate: false);

            $except = $dto->except('sku');

            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('priceCents');
            expect($except)->not->toHaveKey('sku');
        });
    });

    describe('partial update — fromPartialArray', function (): void {
        it('updates only provided fields and keeps defaults', function (): void {
            $dto = ProductDTO::fromPartialArray([
                'name' => 'Partial Update',
                'sku' => 'OP9012',
                'priceCents' => 999,
            ]);

            expect($dto->name)->toBe('Partial Update');
            expect($dto->sku)->toBe('OP9012');
            expect($dto->priceCents)->toBe(999);
            expect($dto->isActive)->toBeTrue();          // default preserved
            expect($dto->category)->toBe('general');     // default preserved
            expect($dto->stockCount)->toBe(0);           // default preserved
        });

        it('handles partial update with MapFrom', function (): void {
            $dto = ProductDTO::fromPartialArray([
                'vendor_code' => 'NEW-VENDOR',
            ]);

            expect($dto->vendorCode)->toBe('NEW-VENDOR');
        });

        it('fromPartialArray with no data returns DTO with all defaults', function (): void {
            $dto = ProductDTO::fromPartialArray([]);

            // Required fields get empty type-appropriate values
            expect($dto->name)->toBe('');
            expect($dto->sku)->toBe('');
            expect($dto->priceCents)->toBe(0);
            expect($dto->isActive)->toBeTrue();
            expect($dto->category)->toBe('general');
            expect($dto->stockCount)->toBe(0);
        });
    });

    describe('immutable update — with()', function (): void {
        it('creates new instance with overrides', function (): void {
            $original = ProductDTO::fromArray([
                'name' => 'Original',
                'sku' => 'QR3456',
                'priceCents' => 100,
            ], validate: false);

            $updated = $original->with(['priceCents' => 200, 'category' => 'sale']);

            expect($updated)->not->toBe($original);  // Different instance
            expect($updated->priceCents)->toBe(200);
            expect($updated->category)->toBe('sale');
            expect($updated->name)->toBe('Original'); // Unchanged
            expect($updated->sku)->toBe('QR3456');     // Unchanged
            expect($original->priceCents)->toBe(100);  // Original unchanged
        });

        it('with() always validates regardless of $validate parameter', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Valid',
                'sku' => 'ST7890',
                'priceCents' => 100,
            ], validate: false);

            // Attempt to set invalid data — should throw even with $validate = false
            expect(fn (): mixed => $dto->with(['sku' => 'INVALID'], validate: false))
                ->toThrow(ValidationException::class);
        });
    });

    describe('equality and state', function (): void {
        it('equals returns true for identical DTOs', function (): void {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Same',
                'sku' => 'UV1234',
                'priceCents' => 100,
            ], validate: false);

            $dto2 = ProductDTO::fromArray([
                'name' => 'Same',
                'sku' => 'UV1234',
                'priceCents' => 100,
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function (): void {
            $dto1 = ProductDTO::fromArray([
                'name' => 'First',
                'sku' => 'WX5678',
                'priceCents' => 100,
            ], validate: false);

            $dto2 = ProductDTO::fromArray([
                'name' => 'Second',
                'sku' => 'WX5678',
                'priceCents' => 100,
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty detects all-default DTO', function (): void {
            $dto = ProductDTO::fromPartialArray([]);

            // name='', sku='', priceCents=0 — 0 is NOT empty per isEmpty rules
            // So isEmpty should be false because priceCents=0 is non-empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('fromJson edge cases', function (): void {
        it('hydrates from valid JSON string', function (): void {
            $json = json_encode([
                'name' => 'From JSON',
                'sku' => 'YZ9012',
                'priceCents' => 777,
            ]);

            $dto = ProductDTO::fromJson($json);

            expect($dto->name)->toBe('From JSON');
            expect($dto->sku)->toBe('YZ9012');
            expect($dto->priceCents)->toBe(777);
        });

        it('rejects sequential arrays in fromJson', function (): void {
            expect(fn (): mixed => ProductDTO::fromJson('["a", "b", "c"]'))
                ->toThrow(DTOException::class);
        });

        it('rejects invalid JSON', function (): void {
            expect(fn (): mixed => ProductDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object from JSON', function (): void {
            // Empty object is valid — partial semantics
            $dto = ProductDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(ProductDTO::class);
        });
    });

    describe('validation rules — rules() and rulesFor()', function (): void {
        it('rules() returns expected rules for ProductDTO', function (): void {
            $rules = ProductDTO::rules();

            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('sku');
            expect($rules)->toHaveKey('priceCents');
            expect($rules)->toHaveKey('isActive');
            expect($rules)->toHaveKey('category');
            expect($rules)->toHaveKey('description');
            expect($rules)->toHaveKey('uuid');
            expect($rules)->toHaveKey('vendorCode');
            expect($rules)->toHaveKey('stockCount');

            // name should have required, min:3, max:200
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:3');
            expect($rules['name'])->toContain('max:200');
        });

        it('rulesFor() returns same rules by default', function (): void {
            expect(ProductDTO::rulesFor('create'))->toBe(ProductDTO::rules());
            expect(ProductDTO::rulesFor('update'))->toBe(ProductDTO::rules());
        });

        it('validateArray rejects invalid data', function (): void {
            expect(fn (): mixed => ProductDTO::validateArray([
                'name' => 'OK',
                'sku' => 'INVALID',
            ]))->toThrow(ValidationException::class);
        });

        it('validateArray returns validated data for valid input', function (): void {
            $result = ProductDTO::validateArray([
                'name' => 'Valid Product',
                'sku' => 'AB1234',
                'priceCents' => 500,
            ]);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Valid Product');
        });
    });

    describe('DtoCollection with ProductDTO', function (): void {
        it('creates collection from array of DTOs', function (): void {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Product 1', 'sku' => 'AA1111', 'priceCents' => 100,
            ], validate: false);

            $dto2 = ProductDTO::fromArray([
                'name' => 'Product 2', 'sku' => 'BB2222', 'priceCents' => 200,
            ], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);

            expect($collection->count())->toBe(2);
            expect($collection->isEmpty())->toBeFalse();
            expect($collection->isNotEmpty())->toBeTrue();
        });

        it('pluck extracts property values', function (): void {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Alpha', 'sku' => 'AA1111', 'priceCents' => 100,
            ], validate: false);

            $dto2 = ProductDTO::fromArray([
                'name' => 'Beta', 'sku' => 'BB2222', 'priceCents' => 200,
            ], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);

            expect($collection->pluck('name'))->toBe(['Alpha', 'Beta']);
            expect($collection->pluck('priceCents'))->toBe([100, 200]);
        });

        it('toArray serializes all DTOs', function (): void {
            $dto = ProductDTO::fromArray([
                'name' => 'Serialize', 'sku' => 'CC3333', 'priceCents' => 300,
                'internalNotes' => 'Hidden',
            ], validate: false);

            $collection = DtoCollection::make([$dto]);
            $arr = $collection->toArray();

            expect($arr)->toHaveCount(1);
            expect($arr[0])->toHaveKey('name');
            expect($arr[0])->not->toHaveKey('internalNotes');
        });

        it('filter returns new collection', function (): void {
            $dto1 = ProductDTO::fromArray([
                'name' => 'Cheap', 'sku' => 'DD4444', 'priceCents' => 50,
            ], validate: false);

            $dto2 = ProductDTO::fromArray([
                'name' => 'Expensive', 'sku' => 'EE5555', 'priceCents' => 500,
            ], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $expensive = $collection->filter(fn (ProductDTO $d): bool => $d->priceCents > 100);

            expect($expensive->count())->toBe(1);
            expect($expensive->first()->name)->toBe('Expensive');
        });
    });

    describe('CreateUserDTO integration', function (): void {
        it('roundtrips through fromArray → toArray → fromArray', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
                'tags' => ['php', 'laravel'],
                'phone_number' => '+1234567890',
                'password' => 'secret123',
            ]);

            $arr = $original->allValues(); // Include hidden (password)
            $restored = CreateUserDTO::fromArray($arr);

            expect($restored->email)->toBe('test@example.com');
            expect($restored->name)->toBe('Test User');
            expect($restored->status)->toBe('active');
            expect($restored->tags)->toBe(['php', 'laravel']);
            expect($restored->phone)->toBe('+1234567890');
            expect($restored->password)->toBe('secret123');
        });

        it('phone maps from phone_number via MapFrom', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'map@test.com',
                'name' => 'Map Test',
                'phone_number' => '555-CALL',
            ]);

            expect($dto->phone)->toBe('555-CALL');
        });

        it('excludes password from toArray but includes in allValues', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'hidden@test.com',
                'name' => 'Hidden Test',
                'password' => 'supersecret',
            ]);

            expect($dto->toArray())->not->toHaveKey('password');
            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('supersecret');
        });
    });

    describe('MinimalDTO edge cases', function (): void {
        it('rejects empty input for required fields', function (): void {
            expect(fn (): mixed => MinimalDTO::fromArray([]))
                ->toThrow(ValidationException::class);
        });

        it('validates required fields', function (): void {
            $dto = MinimalDTO::fromArray([
                'name' => 'Test',
                'value' => 'Value',
            ]);

            expect($dto->name)->toBe('Test');
            expect($dto->value)->toBe('Value');
        });

        it('partial update on minimal DTO provides empty defaults', function (): void {
            $dto = MinimalDTO::fromPartialArray([]);

            expect($dto->name)->toBe('');
            expect($dto->value)->toBe('');
        });
    });

    describe('metadata cache flush', function (): void {
        it('flushMetadataCache clears per-class cache', function (): void {
            // Trigger metadata resolution
            ProductDTO::rules();

            // Flush specific class
            ProductDTO::flushMetadataCache(ProductDTO::class);

            // Re-resolve should work fine
            $rules = ProductDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
        });

        it('flushMetadataCache(null) clears all cache', function (): void {
            ProductDTO::rules();
            MinimalDTO::rules();

            ProductDTO::flushMetadataCache(null);

            // Both should re-resolve fine
            expect(ProductDTO::rules())->toBeArray();
            expect(MinimalDTO::rules())->toBeArray();
        });
    });
});
