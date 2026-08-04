<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection — offsetUnset() index integrity', function (): void {
    it('re-indexes after unset so no gaps remain', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[1]);

        // After unset, items should be re-indexed: [0 => dto1, 1 => dto3]
        expect($collection->count())->toBe(2)
            ->and($collection[0])->toBe($dto1)
            ->and($collection[1])->toBe($dto3)
            ->and(isset($collection[2]))->toBeFalse();
    });

    it('last() returns correct item after offsetUnset', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[1]); // Remove 'B'

        // last() must return dto3 ('C'), not null
        expect($collection->last())->toBe($dto3)
            ->and($collection->first())->toBe($dto1);
    });

    it('last() returns correct item after unsetting the last element', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[2]); // Remove last element

        // After re-index: [0 => dto1, 1 => dto2]
        expect($collection->count())->toBe(2)
            ->and($collection->last())->toBe($dto2);
    });

    it('last() returns correct item after unsetting the first element', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[0]); // Remove first element

        // After re-index: [0 => dto2, 1 => dto3]
        expect($collection->count())->toBe(2)
            ->and($collection->first())->toBe($dto2)
            ->and($collection->last())->toBe($dto3);
    });

    it('count() is consistent after multiple unsets', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');
        $dto4 = new ProductDTO(name: 'D', price: '4.00');
        $dto5 = new ProductDTO(name: 'E', price: '5.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3, $dto4, $dto5]);

        unset($collection[1]);
        unset($collection[2]);

        // Should have 3 items: dto1, dto4, dto5
        expect($collection->count())->toBe(3)
            ->and($collection->first())->toBe($dto1)
            ->and($collection->last())->toBe($dto5);
    });

    it('iteration works correctly after unset', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[0]);

        $iterated = [];
        foreach ($collection as $key => $item) {
            $iterated[$key] = $item;
        }

        // Keys should be sequential: 0, 1 (not 1, 2)
        expect($iterated)->toHaveCount(2)
            ->and(array_keys($iterated))->toBe([0, 1])
            ->and($iterated[0])->toBe($dto2)
            ->and($iterated[1])->toBe($dto3);
    });

    it('map() produces sequential keys after unset', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $dto3 = new ProductDTO(name: 'C', price: '3.00');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($collection[1]);

        $names = $collection->map(fn (ProductDTO $p): string => $p->name);

        expect($names)->toBe(['A', 'C']);
    });

    it('unset on empty collection does nothing', function (): void {
        $collection = new DtoCollection;

        unset($collection[0]);

        expect($collection->count())->toBe(0)
            ->and($collection->isEmpty())->toBeTrue();
    });

    it('unset on non-existent index does nothing', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        unset($collection[99]);

        expect($collection->count())->toBe(2)
            ->and($collection->first())->toBe($dto1)
            ->and($collection->last())->toBe($dto2);
    });

    it('all items unset results in empty collection', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        unset($collection[0]);
        unset($collection[0]); // Was re-indexed, so former dto2 is now at 0

        expect($collection->count())->toBe(0)
            ->and($collection->isEmpty())->toBeTrue()
            ->and($collection->first())->toBeNull()
            ->and($collection->last())->toBeNull();
    });
});
