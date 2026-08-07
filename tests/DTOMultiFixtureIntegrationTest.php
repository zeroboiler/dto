<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

// ── Inline test fixtures ──────────────────────────────────────────────

final class ProductDto extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Numeric, Min(0)]
        public readonly float $price,

        #[Required, Integer, Min(0)]
        public readonly int $stock,

        #[Boolean]
        public readonly bool $isActive = true,

        #[Uuid]
        public readonly ?string $sku = null,
    ) {}
}

final class SearchFilterDto extends DataTransferObject
{
    public function __construct(
        public readonly ?string $query = null,

        #[Integer, Min(1), Max(100)]
        #[DefaultValue(20)]
        public readonly int $perPage = 20,

        #[Cast('integer')]
        #[DefaultValue(1)]
        public readonly int $page = 1,

        public readonly array $sortBy = [],

        #[MapFrom('order_direction')]
        public readonly string $orderDirection = 'asc',
    ) {}
}

final class NestedProductDto extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title,

        #[Required]
        public readonly ProductDto $product,

        #[Required]
        public readonly AddressDTO $shippingAddress,
    ) {}
}

describe('DTO Multi-Fixture Integration Tests', function () {
    describe('hydration across different DTO shapes', function () {
        it('hydrates ProductDto with all property types', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Widget',
                'price' => '29.99',
                'stock' => '100',
                'isActive' => '1',
                'sku' => '550e8400-e29b-41d4-a716-446655440000',
            ]);

            expect($dto->name)->toBe('Widget');
            expect($dto->price)->toBe(29.99);
            expect($dto->stock)->toBe(100);
            expect($dto->isActive)->toBeTrue();
            expect($dto->sku)->toBe('550e8400-e29b-41d4-a716-446655440000');
        });

        it('hydrates SearchFilterDto with MapFrom and Cast', function () {
            $dto = SearchFilterDto::fromArray([
                'query' => 'laravel',
                'order_direction' => 'desc',
            ]);

            expect($dto->query)->toBe('laravel');
            expect($dto->perPage)->toBe(20);
            expect($dto->page)->toBe(1);
            expect($dto->sortBy)->toBe([]);
            expect($dto->orderDirection)->toBe('desc');
        });

        it('hydrates SearchFilterDto with explicit values overriding defaults', function () {
            $dto = SearchFilterDto::fromArray([
                'perPage' => '50',
                'page' => '3',
                'sortBy' => ['name', 'price'],
            ]);

            expect($dto->perPage)->toBe(50);
            expect($dto->page)->toBe(3);
            expect($dto->sortBy)->toBe(['name', 'price']);
        });

        it('hydrates CreateUserDTO with all features', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'phone_number' => '+1234567890',
                'password' => 'secret123',
            ]);

            expect($dto->email)->toBe('user@example.com');
            expect($dto->name)->toBe('John Doe');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->password)->toBe('secret123');
        });
    });

    describe('serialization consistency', function () {
        it('toArray excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('status');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'user@example.com',
                'name' => 'John',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('serializes nested DTOs recursively in toArray', function () {
            $dto = NestedProductDto::fromArray([
                'title' => 'Featured Widget',
                'product' => [
                    'name' => 'Widget Pro',
                    'price' => 49.99,
                    'stock' => 25,
                ],
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['title'])->toBe('Featured Widget');
            expect($arr['product'])->toBeArray();
            expect($arr['product']['name'])->toBe('Widget Pro');
            expect($arr['product']['price'])->toBe(49.99);
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Istanbul');
        });

        it('toJson produces valid JSON', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeString();
            expect(json_decode($json, true))->toBeArray();
        });
    });

    describe('selective output', function () {
        it('only returns specified fields', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
                'sku' => 'test-sku',
            ], validate: false);

            $only = $dto->only('name', 'price');
            expect($only)->toHaveKeys(['name', 'price']);
            expect($only)->not->toHaveKey('stock');
            expect($only)->not->toHaveKey('sku');
        });

        it('only accepts single string key', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $only = $dto->only('name');
            expect($only)->toHaveCount(1);
            expect($only)->toHaveKey('name');
        });

        it('except excludes specified fields', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $except = $dto->except('stock');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('price');
            expect($except)->not->toHaveKey('stock');
        });
    });

    describe('equality and state checks', function () {
        it('equals returns true for same values', function () {
            $dto1 = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $dto2 = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different values', function () {
            $dto1 = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $dto2 = ProductDto::fromArray([
                'name' => 'Widget',
                'price' => 25.00,
                'stock' => 100,
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns true for DTO with all defaults/nulls', function () {
            $dto = SearchFilterDto::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false for DTO with non-empty fields', function () {
            $dto = SearchFilterDto::fromArray([
                'query' => 'search term',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('immutable updates', function () {
        it('with creates a new instance with overrides', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $updated = $dto->with(['stock' => 150]);
            expect($dto->stock)->toBe(200); // original unchanged
            expect($updated->stock)->toBe(150);
            expect($updated->name)->toBe('Gadget');
        });

        it('with preserves defaults for unspecified fields', function () {
            $dto = ProductDto::fromArray([
                'name' => 'Gadget',
                'price' => 15.50,
                'stock' => 200,
            ], validate: false);

            $updated = $dto->with(['price' => 20.00]);
            expect($updated->name)->toBe('Gadget');
            expect($updated->price)->toBe(20.00);
            expect($updated->stock)->toBe(200);
            expect($updated->isActive)->toBeTrue();
        });
    });

    describe('validation rules', function () {
        it('rules contain expected constraints', function () {
            $rules = ProductDto::rules();
            expect($rules['name'])->toContain('required');
            expect($rules['price'])->toContain('required');
            expect($rules['price'])->toContain('numeric');
            expect($rules['price'])->toContain('min:0');
            expect($rules['stock'])->toContain('integer');
            expect($rules['stock'])->toContain('min:1');
            expect($rules['isActive'])->toContain('boolean');
            expect($rules['sku'])->toContain('uuid');
        });

        it('rulesFor returns same as rules by default', function () {
            expect(ProductDto::rulesFor('create'))->toBe(ProductDto::rules());
            expect(ProductDto::rulesFor('update'))->toBe(ProductDto::rules());
        });

        it('SearchFilterDto has correct defaults in rules', function () {
            $rules = SearchFilterDto::rules();
            expect($rules['perPage'])->toContain('integer');
            expect($rules['perPage'])->toContain('min:1');
            expect($rules['perPage'])->toContain('max:100');
            expect($rules['page'])->toContain('integer');
        });
    });

    describe('partial update semantics', function () {
        it('fromPartialArray hydrates only present fields', function () {
            $dto = ProductDto::fromPartialArray([
                'name' => 'Updated Name',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated Name');
            expect($dto->price)->toBe(0.0);
            expect($dto->stock)->toBe(0);
        });

        it('fromPartialArray respects existing defaults', function () {
            $dto = SearchFilterDto::fromPartialArray([
                'query' => 'test',
            ], validatePresent: false);

            expect($dto->query)->toBe('test');
            expect($dto->perPage)->toBe(20);
            expect($dto->page)->toBe(1);
        });
    });

    describe('DtoCollection operations', function () {
        it('pluck extracts single field from collection', function () {
            $items = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 10], validate: false),
            ]);

            $names = $items->pluck('name');
            expect($names)->toBe(['A', 'B']);
        });

        it('pluckKey builds key-value map', function () {
            $items = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 10], validate: false),
            ]);

            $map = $items->pluckKey('name', 'price');
            expect($map)->toBe(['A' => 10.0, 'B' => 20.0]);
        });

        it('filter returns new collection with matching items', function () {
            $items = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 0], validate: false),
            ]);

            $inStock = $items->filter(fn (ProductDto $p): bool => $p->stock > 0);
            expect($inStock->count())->toBe(1);
        });

        it('map returns plain array', function () {
            $items = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 10], validate: false),
            ]);

            $names = $items->map(fn (ProductDto $p): string => $p->name);
            expect($names)->toBe(['A', 'B']);
        });

        it('push appends and returns fluent', function () {
            $collection = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
            ]);

            $result = $collection->push(
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 10], validate: false),
            );

            expect($result->count())->toBe(2);
            expect($result->last()?->name)->toBe('B');
        });

        it('first and last return correct items', function () {
            $items = DtoCollection::make([
                ProductDto::fromArray(['name' => 'A', 'price' => 10.0, 'stock' => 5], validate: false),
                ProductDto::fromArray(['name' => 'B', 'price' => 20.0, 'stock' => 10], validate: false),
            ]);

            expect($items->first()?->name)->toBe('A');
            expect($items->last()?->name)->toBe('B');
        });
    });
});
