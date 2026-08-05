<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DtoCollection — type guards', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        expect(fn (): DtoCollection => new DtoCollection([
            new ProductDTO(name: 'A', price: '1.00'),
            'not a dto',
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('rejects non-DTO items via offsetSet', function (): void {
        $dto = new ProductDTO(name: 'A', price: '1.00');
        $collection = new DtoCollection([$dto]);

        expect(fn () => $collection[] = 'not a dto')->toThrow(InvalidArgumentException::class);
    });

    it('rejects non-DTO items via offsetSet with explicit key', function (): void {
        $dto = new ProductDTO(name: 'A', price: '1.00');
        $collection = new DtoCollection([$dto]);

        expect(fn () => $collection[1] = 42)->toThrow(InvalidArgumentException::class);
    });

    it('accepts valid DTO items in constructor', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
    });

    it('accepts valid DTO items via offsetSet', function (): void {
        $dto1 = new ProductDTO(name: 'A', price: '1.00');
        $dto2 = new ProductDTO(name: 'B', price: '2.00');
        $collection = new DtoCollection([$dto1]);

        $collection[] = $dto2;

        expect($collection->count())->toBe(2);
        expect($collection[1])->toBe($dto2);
    });

    it('rejects null in constructor', function (): void {
        expect(fn (): DtoCollection => new DtoCollection([null]))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects arrays in constructor', function (): void {
        expect(fn (): DtoCollection => new DtoCollection([['name' => 'A']]))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects integers in constructor', function (): void {
        expect(fn (): DtoCollection => new DtoCollection([1, 2, 3]))
            ->toThrow(InvalidArgumentException::class);
    });
});
