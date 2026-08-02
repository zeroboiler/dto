<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection — construction & basic access', function (): void {
    it('creates an empty collection with no arguments', function (): void {
        $collection = new DtoCollection;

        expect($collection->count())->toBe(0)
            ->and($collection->isEmpty())->toBeTrue()
            ->and($collection->isNotEmpty())->toBeFalse();
    });

    it('creates a collection from an array of DTOs', function (): void {
        $dto1 = new CreateUserDTO(email: 'a@test.com', name: 'Alice', status: 'active');
        $dto2 = new CreateUserDTO(email: 'b@test.com', name: 'Bob', status: 'active');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->count())->toBe(2)
            ->and($collection->isEmpty())->toBeFalse()
            ->and($collection->isNotEmpty())->toBeTrue();
    });

    it('makes a collection via static make() factory', function (): void {
        $dto = new ProductDTO(name: 'Widget', price: '9.99');

        $collection = DtoCollection::make([$dto]);

        expect($collection->count())->toBe(1)
            ->and($collection->first())->toBe($dto);
    });

    it('items() returns the raw DTO array', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->items())->toBe([$dto1, $dto2]);
    });
});

describe('DtoCollection — first() & last()', function (): void {
    it('first() returns null for empty collection', function (): void {
        expect(new DtoCollection()->first())->toBeNull();
    });

    it('first() returns the first DTO', function (): void {
        $dto1 = new ProductDTO(name: 'First', price: '1.00');
        $dto2 = new ProductDTO(name: 'Second', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first())->toBe($dto1);
    });

    it('last() returns null for empty collection', function (): void {
        expect(new DtoCollection()->last())->toBeNull();
    });

    it('last() returns the last DTO', function (): void {
        $dto1 = new ProductDTO(name: 'First', price: '1.00');
        $dto2 = new ProductDTO(name: 'Second', price: '2.00');
        $dto3 = new ProductDTO(name: 'Third', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        expect($collection->last())->toBe($dto3);
    });

    it('first() and last() return the same item for single-element collection', function (): void {
        $dto = new ProductDTO(name: 'Only', price: '5.00');

        $collection = new DtoCollection([$dto]);

        expect($collection->first())->toBe($dto)
            ->and($collection->last())->toBe($dto);
    });
});

describe('DtoCollection — push()', function (): void {
    it('appends a DTO and returns self for chaining', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = DtoCollection::make()
            ->push($dto1)
            ->push($dto2);

        expect($collection->count())->toBe(2)
            ->and($collection->last())->toBe($dto2);
    });

    it('push() on empty collection works', function (): void {
        $dto = new ProductDTO(name: 'Solo', price: '0.99');

        $collection = DtoCollection::make()->push($dto);

        expect($collection->count())->toBe(1)
            ->and($collection->first())->toBe($dto);
    });
});

describe('DtoCollection — filter()', function (): void {
    it('filters items by callback and returns a new collection', function (): void {
        $cheap = new ProductDTO(name: 'Cheap', price: '1.00', stock: 5);
        $expensive = new ProductDTO(name: 'Expensive', price: '100.00', stock: 0);
        $mid = new ProductDTO(name: 'Mid', price: '50.00', stock: 10);

        $collection = new DtoCollection([$cheap, $expensive, $mid]);

        $filtered = $collection->filter(fn (ProductDTO $p): bool => $p->stock > 0);

        expect($filtered)->not->toBe($collection)
            ->and($filtered->count())->toBe(2)
            ->and($filtered->items())->toBe([$cheap, $mid]);
    });

    it('filter() with always-false callback returns empty collection', function (): void {
        $dto = new ProductDTO(name: 'X', price: '1.00');

        $collection = new DtoCollection([$dto]);

        $filtered = $collection->filter(fn (): bool => false);

        expect($filtered->isEmpty())->toBeTrue();
    });

    it('filter() preserves original collection', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        $collection->filter(fn (): bool => false);

        expect($collection->count())->toBe(2);
    });
});

describe('DtoCollection — map()', function (): void {
    it('maps over DTOs and returns a plain array', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        $names = $collection->map(fn (ProductDTO $p): string => $p->name);

        expect($names)->toBe(['A', 'B']);
    });

    it('map() receives index as second argument', function (): void {
        $dtoList = [
            new ProductDTO(name: 'X', price: '1.00'),
            new ProductDTO(name: 'Y', price: '2.00'),
            new ProductDTO(name: 'Z', price: '3.00'),
        ];

        $collection = new DtoCollection($dtoList);

        $indexed = $collection->map(fn (ProductDTO $p, int $i): string => "{$i}:{$p->name}");

        expect($indexed)->toBe(['0:X', '1:Y', '2:Z']);
    });

    it('map() on empty collection returns empty array', function (): void {
        $collection = new DtoCollection;

        $result = $collection->map(fn (ProductDTO $p): string => $p->name);

        expect($result)->toBe([]);
    });
});

describe('DtoCollection — toArray() & allValues()', function (): void {
    it('toArray() serializes each DTO via toArray()', function (): void {
        $dto1 = new CreateUserDTO(email: 'a@test.com', name: 'Alice', status: 'active', password: 'secret');
        $dto2 = new CreateUserDTO(email: 'b@test.com', name: 'Bob', status: 'active');

        $collection = new DtoCollection([$dto1, $dto2]);

        $result = $collection->toArray();

        expect($result)->toHaveCount(2)
            ->and($result[0])->toMatchArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
                'status' => 'active',
                'tags' => [],
                'phone' => null,
            ])
            ->and($result[0])->not->toHaveKey('password')
            ->and($result[1])->toMatchArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ]);
    });

    it('allValues() serializes each DTO including hidden properties', function (): void {
        $dto = new CreateUserDTO(email: 'a@test.com', name: 'Alice', status: 'active', password: 'secret');

        $collection = new DtoCollection([$dto]);

        $result = $collection->allValues();

        expect($result[0])->toHaveKey('password')
            ->and($result[0]['password'])->toBe('secret');
    });

    it('toArray() on empty collection returns empty array', function (): void {
        expect(new DtoCollection()->toArray())->toBe([]);
    });
});

describe('DtoCollection — ArrayAccess', function (): void {
    it('offsetExists returns true for valid index', function (): void {
        $dto = new ProductDTO(name: 'A', price: '1.00');

        $collection = new DtoCollection([$dto]);

        expect(isset($collection[0]))->toBeTrue()
            ->and(isset($collection[1]))->toBeFalse();
    });

    it('offsetGet returns DTO at index', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection[0])->toBe($dto1)
            ->and($collection[1])->toBe($dto2);
    });

    it('offsetGet returns null for out-of-bounds index', function (): void {
        $collection = new DtoCollection([new ProductDTO(name: 'A', price: '1.00')]);

        expect($collection[99])->toBeNull();
    });

    it('offsetSet appends with null offset', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection;
        $collection[] = $dto1;
        $collection[] = $dto2;

        expect($collection->count())->toBe(2)
            ->and($collection[1])->toBe($dto2);
    });

    it('offsetSet replaces at specific index', function (): void {
        $dto1 = new ProductDTO(name: 'Original', price: '1.00');
        $replacement = new ProductDTO(name: 'Replacement', price: '2.00');

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $replacement;

        expect($collection[0])->toBe($replacement);
    });

    it('offsetUnset removes item at index', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        unset($collection[0]);

        expect(isset($collection[0]))->toBeFalse()
            ->and($collection->count())->toBe(1);
    });
});

describe('DtoCollection — IteratorAggregate', function (): void {
    it('iterates over all DTOs', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        $iterated = [];
        foreach ($collection as $key => $item) {
            $iterated[$key] = $item;
        }

        expect($iterated)->toBe([0 => $dto1, 1 => $dto2]);
    });

    it('empty collection yields no items', function (): void {
        $count = 0;
        foreach (new DtoCollection as $item) {
            $count++;
        }

        expect($count)->toBe(0);
    });
});

describe('DtoCollection — JsonSerializable', function (): void {
    it('jsonSerialize returns toArray() result', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });

    it('json_encode produces valid JSON array', function (): void {
        $dto = new ProductDTO(name: 'Widget', price: '9.99', stock: 5);

        $collection = new DtoCollection([$dto]);

        $json = json_encode($collection);

        expect($json)->toBeString()
            ->and(json_decode($json, true))->toBe([
                [
                    'name' => 'Widget',
                    'price' => '9.99',
                    'stock' => 5,
                ],
            ]);
    });

    it('json_encode of empty collection produces empty array', function (): void {
        $collection = new DtoCollection;

        expect(json_encode($collection))->toBe('[]');
    });
});

describe('DtoCollection — mixed DTO types', function (): void {
    it('can hold different DTO types in the same collection', function (): void {
        $user = new CreateUserDTO(email: 'test@test.com', name: 'Test', status: 'active');
        $product = new ProductDTO(name: 'Item', price: '5.00');

        $collection = new DtoCollection([$user, $product]);

        expect($collection->count())->toBe(2)
            ->and($collection[0])->toBe($user)
            ->and($collection[1])->toBe($product);
    });
});
