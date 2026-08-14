<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DtoCollection offsetUnset re-index and boundary tests.
 *
 * Verifies that DtoCollection correctly re-indexes after unsetting elements,
 * preventing gaps that would break last(), count(), map(), and filter() behavior.
 *
 * @covers \ZeroBoiler\DTO\DtoCollection
 */

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;

describe('DtoCollection offsetUnset re-index behavior', function (): void {
    it('re-indexes after offsetUnset', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $dto3 = ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        expect($collection->count())->toBe(3);

        // Remove middle element
        unset($collection[1]);
        expect($collection->count())->toBe(2);

        // After re-index, offset 1 should now hold the third item
        expect($collection[0]->name)->toBe('A');
        expect($collection[1]->name)->toBe('C');
    });

    it('last() works correctly after offsetUnset', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $dto3 = ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        $last = $collection->last();
        expect($last?->name)->toBe('C');
    });

    it('map() returns correct results after offsetUnset', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        unset($collection[0]);

        $names = $collection->map(fn (DataTransferObject $dto): string => $dto->name);
        expect($names)->toBe(['B']);
    });

    it('filter() returns re-indexed collection', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $dto3 = ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $collection->filter(fn (DataTransferObject $dto): bool => $dto->name !== 'B');

        expect($filtered->count())->toBe(2);
        expect($filtered[0]->name)->toBe('A');
        expect($filtered[1]->name)->toBe('C');
    });

    it('empty collection returns null for first() and last()', function (): void {
        $collection = new DtoCollection([]);

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();
    });

    it('push() returns same instance for chaining', function (): void {
        $dto = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $collection = new DtoCollection([]);
        $result = $collection->push($dto);

        expect($result)->toBe($collection);
        expect($collection->count())->toBe(1);
    });

    it('append() returns new instance without mutating original', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $newCollection = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($newCollection->count())->toBe(2);
    });

    it('merge() returns new combined instance without mutating originals', function (): void {
        $dto1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $dto2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $dto3 = ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });
});
