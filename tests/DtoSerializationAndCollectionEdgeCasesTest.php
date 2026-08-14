<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO serialization and type safety edge cases', function (): void {
    it('fromArray preserves all data types correctly', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '42'], validate: false);

        expect($dto->name)->toBeString();
        expect($dto->value)->toBeString();
    });

    it('only() returns subset of fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $only = $dto->only('name');

        expect($only)->toBeArray();
        expect($only)->toHaveKey('name');
        expect($only)->not->toHaveKey('value');
    });

    it('only() with array returns multiple fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $only = $dto->only(['name', 'value']);

        expect($only)->toHaveKey('name');
        expect($only)->toHaveKey('value');
    });

    it('except() excludes specified fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $except = $dto->except('name');

        expect($except)->not->toHaveKey('name');
        expect($except)->toHaveKey('value');
    });

    it('except() ignores non-existent keys', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $except = $dto->except('nonexistent_key');

        expect($except)->toHaveKey('name');
        expect($except)->toHaveKey('value');
    });

    it('with() creates new instance without modifying original', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = $dto1->with(['name' => 'Bob']);

        expect($dto1->name)->toBe('Alice');
        expect($dto2->name)->toBe('Bob');
        expect($dto1->value)->toBe('x');
        expect($dto2->value)->toBe('x');
    });

    it('with() validates data (always)', function (): void {
        // Even with validate=false in constructor, with() always validates
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

        // with() should create a new valid instance
        $modified = $dto->with(['name' => 'Bob', 'value' => 'y']);
        expect($modified)->toBeInstanceOf(MinimalDTO::class);
    });

    it('fromJson rejects sequential arrays', function (): void {
        DataTransferObject::fromJson('["one","two"]');
    })->throws(\ZeroBoiler\DTO\Exceptions\DTOException::class);

    it('fromJson rejects non-object JSON', function (): void {
        DataTransferObject::fromJson('"just a string"');
    })->throws(\ZeroBoiler\DTO\Exceptions\DTOException::class);

    it('fromJson accepts empty object', function (): void {
        // EmptyDTO has no constructor parameters
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO::fromJson('{}', validate: false);
        expect($dto)->toBeInstanceOf(\ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO::class);
    });

    it('fromJson rejects invalid JSON', function (): void {
        DataTransferObject::fromJson('{invalid json}');
    })->throws(\ZeroBoiler\DTO\Exceptions\DTOException::class);

    it('toArray returns consistent structure', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $arr1 = $dto->toArray();
        $arr2 = $dto->toArray();

        expect($arr1)->toBe($arr2);
    });

    it('allValues and toArray have same keys when no hidden fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

        expect(array_keys($dto->toArray()))->toBe(array_keys($dto->allValues()));
    });

    it('DtoCollection make creates empty collection', function (): void {
        $collection = \ZeroBoiler\DTO\DtoCollection::make([]);

        expect($collection)->toBeInstanceOf(\ZeroBoiler\DTO\DtoCollection::class);
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    it('DtoCollection implements ArrayAccess', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto]);

        expect($collection->offsetExists(0))->toBeTrue();
        expect($collection->offsetGet(0))->toBe($dto);
        expect($collection->offsetExists(1))->toBeFalse();
        expect($collection->offsetGet(1))->toBeNull();
    });

    it('DtoCollection offsetUnset re-indexes', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        $collection->offsetUnset(0);

        expect($collection->count())->toBe(1);
        expect($collection->offsetExists(0))->toBeTrue();
        expect($collection->first()?->name)->toBe('Bob');
    });

    it('DtoCollection push returns self for chaining', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection;

        $result = $collection->push($dto);

        expect($result)->toBe($collection);
        expect($collection->count())->toBe(1);
    });

    it('DtoCollection append returns new instance', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1]);

        $newCollection = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($newCollection->count())->toBe(2);
        expect($newCollection)->not->toBe($collection);
    });

    it('DtoCollection merge creates new combined collection', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection1 = new \ZeroBoiler\DTO\DtoCollection([$dto1]);
        $collection2 = new \ZeroBoiler\DTO\DtoCollection([$dto2]);

        $merged = $collection1->merge($collection2);

        expect($merged->count())->toBe(2);
        expect($collection1->count())->toBe(1);
        expect($collection2->count())->toBe(1);
    });

    it('DtoCollection filter returns new filtered collection', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        $filtered = $collection->filter(fn (\ZeroBoiler\DTO\DataTransferObject $d) => $d->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($collection->count())->toBe(2);
    });

    it('DtoCollection toArray serializes all DTOs', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr)->toHaveCount(2);
        expect($arr[0]['name'])->toBe('Alice');
        expect($arr[1]['name'])->toBe('Bob');
    });

    it('DtoCollection jsonSerialize returns toArray output', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });

    it('DtoCollection first and last work correctly', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        expect($collection->first()?->name)->toBe('Alice');
        expect($collection->last()?->name)->toBe('Bob');
    });

    it('DtoCollection first/last return null for empty collection', function (): void {
        $collection = new \ZeroBoiler\DTO\DtoCollection;

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('DtoCollection map returns plain array', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        $names = $collection->map(fn (DataTransferObject $d) => $d->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('DtoCollection is iterable via foreach', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto1, $dto2]);

        $names = [];
        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Bob']);
    });
});
