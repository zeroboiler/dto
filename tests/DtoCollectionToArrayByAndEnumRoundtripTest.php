<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection toArrayBy and toDictionary', function (): void {
    it('toArrayBy re-keys collection by a property value', function (): void {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoList);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveCount(2);
        expect($keyed)->toHaveKey('alice@test.com');
        expect($keyed['alice@test.com']['name'])->toBe('Alice');
        expect($keyed['bob@test.com']['name'])->toBe('Bob');
    });

    it('toArrayBy skips items with null key values', function (): void {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false),
        ];

        // Both have email — use a different key scenario:
        // toDictionary with email→name produces a flat map
        $collection = new DtoCollection($dtoList);
        $map = $collection->toDictionary('email', 'name');

        expect($map)->toHaveCount(2);
        expect($map['alice@test.com'])->toBe('Alice');
        expect($map['bob@test.com'])->toBe('Bob');
    });

    it('toDictionary re-keys by one property and extracts another as value', function (): void {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoList);
        $map = $collection->toDictionary('email', 'name');

        expect($map)->toBe([
            'alice@test.com' => 'Alice',
            'bob@test.com' => 'Bob',
        ]);
    });

    it('toArrayBy and toDictionary are aliases for pluckKey', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'X'], validate: false);
        $collection = new DtoCollection([$dto]);

        $byToArray = $collection->toArrayBy('email');
        $byPluckKey = $collection->pluckKey('email');

        expect($byToArray)->toBe($byPluckKey);

        $dictByToDict = $collection->toDictionary('email', 'name');
        $dictByPluckKey = $collection->pluckKey('email', 'name');

        expect($dictByToDict)->toBe($dictByPluckKey);
    });

    it('toArrayBy with duplicate keys keeps last occurrence', function (): void {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'same@test.com', 'name' => 'First'], validate: false),
            CreateUserDTO::fromArray(['email' => 'same@test.com', 'name' => 'Second'], validate: false),
        ];

        $collection = new DtoCollection($dtoList);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveCount(1);
        expect($keyed['same@test.com']['name'])->toBe('Second');
    });
});

describe('DtoCollection mutation and immutability', function (): void {
    it('push mutates in-place and returns self', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$a]);
        $result = $collection->push($b);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection); // same instance
    });

    it('append returns a new collection', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$a]);
        $newCollection = $collection->append($b);

        expect($collection->count())->toBe(1);
        expect($newCollection->count())->toBe(2);
    });

    it('merge returns a new combined collection', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $c = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

        $col1 = new DtoCollection([$a]);
        $col2 = new DtoCollection([$b, $c]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter returns a new filtered collection', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $filtered = $collection->filter(
            fn (DataTransferObject $dto): bool => $dto->name === 'Alice'
        );

        expect($collection->count())->toBe(2);
        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('offsetUnset re-indexes collection', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        unset($collection[0]);

        expect($collection->count())->toBe(1);
        expect($collection[0]->name)->toBe('C');
    });
});

describe('DtoCollection serialization', function (): void {
    it('jsonSerialize produces array of arrays', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $json = json_encode($collection);

        expect($json)->not->toBe(false);

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveCount(2);
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->not->toHaveKey('password'); // hidden
        expect($decoded[0]['name'])->toBe('Alice');
    });

    it('allValues includes hidden fields', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);

        $collection = new DtoCollection([$a]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });

    it('toArray excludes hidden fields', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);

        $collection = new DtoCollection([$a]);
        $arr = $collection->toArray();

        expect($arr[0])->not->toHaveKey('password');
    });

    it('jsonSerialize preserves order', function (): void {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'first@test.com', 'name' => 'First'], validate: false),
            CreateUserDTO::fromArray(['email' => 'second@test.com', 'name' => 'Second'], validate: false),
            CreateUserDTO::fromArray(['email' => 'third@test.com', 'name' => 'Third'], validate: false),
        ];

        $collection = new DtoCollection($dtoList);
        $decoded = json_decode(json_encode($collection), true);

        expect($decoded[0]['name'])->toBe('First');
        expect($decoded[1]['name'])->toBe('Second');
        expect($decoded[2]['name'])->toBe('Third');
    });
});

describe('DtoCollection edge cases', function (): void {
    it('empty collection operations', function (): void {
        $collection = new DtoCollection;

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();
        expect($collection->count())->toBe(0);
        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
        expect($collection->toArray())->toBe([]);
        expect($collection->pluck('email'))->toBe([]);
        expect($collection->map(fn (DataTransferObject $dto): string => ''))->toBe([]);
    });

    it('rejects non-DTO items in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection(['not', 'dtos']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('map returns plain array with correct indices', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $names = $collection->map(fn (DataTransferObject $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('items returns raw DTO instances', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$a]);

        $items = $collection->items();
        expect($items[0])->toBe($a);
        expect($items[0])->toBeInstanceOf(CreateUserDTO::class);
    });

    it('static make creates instance', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = DtoCollection::make([$a]);

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(1);
    });

    it('pluck extracts single property values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    it('countable interface works', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$a, $b]);
        expect(count($collection))->toBe(2);
        expect(sizeof($collection))->toBe(2);
    });
});

describe('DTO with() immutable update', function (): void {
    it('creates new instance with overrides', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'alice@test.com',
            'name' => 'Alice',
            'password' => 'oldpass',
        ], validate: false);

        $updated = $original->with(['name' => 'Alice Updated']);

        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Alice Updated');
        expect($updated->email)->toBe('alice@test.com');
    });

    it('with() hidden fields are excluded from toArray but present in allValues', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
            'password' => 'secret',
        ], validate: false);

        $updated = $dto->with(['name' => 'B']);

        expect($updated->toArray())->not->toHaveKey('password');
        expect($updated->allValues())->toHaveKey('password');
    });

    it('with() multiple overrides', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'old@test.com',
            'name' => 'Old',
        ], validate: false);

        $updated = $dto->with(['email' => 'new@test.com', 'name' => 'New']);

        expect($updated->email)->toBe('new@test.com');
        expect($updated->name)->toBe('New');
    });

    it('with() preserves untouched properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'keep@test.com',
            'name' => 'Keep',
            'password' => 'pw123',
        ], validate: false);

        $updated = $dto->with(['name' => 'Changed']);

        expect($updated->email)->toBe('keep@test.com');
        expect($updated->password)->toBe('pw123');
    });
});

describe('DTO equals', function (): void {
    it('equals returns true for same values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('equals is symmetric', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        expect($a->equals($b))->toBe($b->equals($a));
    });

    it('equals is reflexive', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        expect($a->equals($a))->toBeTrue();
    });
});
