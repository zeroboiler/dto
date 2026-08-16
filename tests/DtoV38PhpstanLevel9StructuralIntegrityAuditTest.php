<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('V38 DtoCollection structural integrity', function () {
    it('empty collection returns zero count', function () {
        $col = new DtoCollection;

        expect($col->count())->toBe(0);
        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('push() mutates in-place and returns same instance', function () {
        $col = new DtoCollection;
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        $result = $col->push($dto);

        expect($result)->toBe($col); // same instance
        expect($col->count())->toBe(1);
        expect($col->first()->name)->toBe('Alice');
    });

    it('append() returns a new instance without mutating original', function () {
        $col = new DtoCollection;
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false);

        $newCol = $col->append($dto1);
        $newerCol = $newCol->append($dto2);

        expect($col->count())->toBe(0); // original unchanged
        expect($newCol->count())->toBe(1);
        expect($newerCol->count())->toBe(2);
    });

    it('merge() combines two collections without mutation', function () {
        $a = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
        ]);
        $b = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $merged = $a->merge($b);

        expect($a->count())->toBe(1);
        expect($b->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('offsetUnset re-indexes to prevent gaps', function () {
        $dtoArray = [
            MinimalDTO::fromArray(['name' => 'A', 'value' => 'a'], validate: false),
            MinimalDTO::fromArray(['name' => 'B', 'value' => 'b'], validate: false),
            MinimalDTO::fromArray(['name' => 'C', 'value' => 'c'], validate: false),
        ];
        $col = new DtoCollection($dtoArray);

        unset($col[0]);

        expect($col[0]->name)->toBe('B');
        expect($col[1]->name)->toBe('C');
        expect($col->count())->toBe(2);
    });

    it('filter() returns a new collection with matching items only', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $filtered = $col->filter(fn ($dto) => $dto->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('pluck() extracts a single property from all DTOs', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $names = $col->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('map() returns a plain array of mapped values', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $upper = $col->map(fn ($dto, $i) => strtoupper($dto->name).':'.$i);

        expect($upper)->toBe(['ALICE:0', 'BOB:1']);
    });

    it('pluckKey() builds associative array with property as key', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $keyed = $col->pluckKey('name');

        expect($keyed)->toBe([
            'Alice' => ['name' => 'Alice', 'value' => 'v1'],
            'Bob' => ['name' => 'Bob', 'value' => 'v2'],
        ]);
    });

    it('toDictionary() builds property-to-property map', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $dict = $col->toDictionary('name', 'name');

        expect($dict)->toBe(['Alice' => 'Alice', 'Bob' => 'Bob']);
    });

    it('toArrayBy() is an alias for pluckKey()', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
        ]);

        $byName = $col->toArrayBy('name');

        expect($byName)->toBe(['Alice' => ['name' => 'Alice', 'value' => 'v1']]);
    });

    it('toArray() serializes all items', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
            MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false),
        ]);

        $arr = $col->toArray();

        expect($arr)->toBe([
            ['name' => 'Alice', 'value' => 'v1'],
            ['name' => 'Bob', 'value' => 'v2'],
        ]);
    });

    it('jsonSerialize() returns toArray() output', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
        ]);

        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('__debugInfo shows count and truncated items', function () {
        $col = new DtoCollection([
            MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false),
        ]);

        $debug = $col->__debugInfo();

        expect($debug)->toHaveKey('count');
        expect($debug['count'])->toBe(1);
    });

    it('rejects non-DTO items via offsetSet', function () {
        $col = new DtoCollection;

        expect(fn () => $col[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('items() returns the raw DTO instances array', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $col = new DtoCollection([$dto]);

        expect($col->items())->toBe([$dto]);
    });

    it('clone() throws RuntimeException', function () {
        $col = new DtoCollection;

        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });
});

describe('V38 DTO hydration edge cases', function () {
    it('fromArray with empty data on DTO with all-defaults still works', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toBe(['foo' => null, 'bar' => null]);
    });

    it('fromArray with extra keys not matching any property is ignored', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1', 'extra' => 'ignored'], validate: false);

        expect($dto->name)->toBe('Alice');
    });

    it('fromPartialArray with empty data returns defaults for all fields', function () {
        $dto = EmptyDTO::fromPartialArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('toArray excludes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
    });

    it('allValues() includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('equals() returns true for same data, false for different', function () {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $b = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $c = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'v2'], validate: false);

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
    });

    it('only() returns only specified fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->only('name'))->toBe(['name' => 'Alice']);
    });

    it('except() excludes specified fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->except('name'))->toBe(['value' => 'v1']);
    });

    it('with() creates new instance with override', function () {
        $original = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $modified = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice');
        expect($modified->name)->toBe('Bob');
    });

    it('isEmpty() returns true when all properties are empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => EmptyDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('fromJson rejects non-object JSON', function () {
        expect(fn () => EmptyDTO::fromJson('"just a string"', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('toJson() returns valid JSON', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->toJson())->toBeJson();
    });

    it('jsonSerialize() returns same as toArray()', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('__debugInfo returns toArray output', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->__debugInfo())->toBe($dto->toArray());
    });

    it('rules() returns array with required and email rules for CreateUserDTO', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor() defaults to same as rules()', function () {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
    });
});
