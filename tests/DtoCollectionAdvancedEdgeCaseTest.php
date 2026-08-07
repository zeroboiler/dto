<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection edge cases', function (): void {
    it('offsetUnset re-indexes items correctly', function (): void {
        $dtoArray = [];
        for ($i = 1; $i <= 5; $i++) {
            $dto = CreateUserDTO::fromArray([
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
            ], validate: false);
            $dtoArray[] = $dto;
        }

        $collection = new DtoCollection($dtoArray);
        expect($collection->count())->toBe(5);

        // Remove middle element
        unset($collection[2]);
        expect($collection->count())->toBe(4);

        // Verify re-indexing: keys should be 0, 1, 2, 3 (no gaps)
        $items = $collection->items();
        expect(array_keys($items))->toBe([0, 1, 2, 3]);

        // Verify third element is now the original fourth
        expect($items[2]->email)->toBe('user4@example.com');
    });

    it('offsetSet with null key appends to end', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'first@example.com',
            'name' => 'First',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'second@example.com',
            'name' => 'Second',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[] = $dto2;

        expect($collection->count())->toBe(2);
        expect($collection[1]->email)->toBe('second@example.com');
    });

    it('offsetSet with integer key replaces element', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'original@example.com',
            'name' => 'Original',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'replaced@example.com',
            'name' => 'Replaced',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect($collection[0]->email)->toBe('replaced@example.com');
    });

    it('rejects non-DTO values in constructor', function (): void {
        expect(fn (): DtoCollection => new DtoCollection(['not', 'a', 'dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO values in offsetSet', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect(fn () => $collection[] = 'invalid')
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('DtoCollection make and push', function (): void {
    it('make creates collection from array', function (): void {
        $dtoArray = [];
        for ($i = 1; $i <= 3; $i++) {
            $dtoArray[] = CreateUserDTO::fromArray([
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
            ], validate: false);
        }

        $collection = DtoCollection::make($dtoArray);
        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(3);
    });

    it('make with empty array creates empty collection', function (): void {
        $collection = DtoCollection::make([]);
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    it('push returns self for fluent chaining', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'A',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'B',
        ], validate: false);

        $result = DtoCollection::make([$dto1])->push($dto2);

        expect($result)->toBeInstanceOf(DtoCollection::class);
        expect($result->count())->toBe(2);
        expect($result[0]->email)->toBe('a@example.com');
        expect($result[1]->email)->toBe('b@example.com');
    });
});

describe('DtoCollection serialization', function (): void {
    it('jsonSerialize returns same as toArray', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret123');
    });

    it('toArray excludes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $visible = $collection->toArray();

        expect($visible[0])->not->toHaveKey('password');
    });
});
