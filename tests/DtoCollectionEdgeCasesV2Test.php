<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DtoCollection edge cases V2', function () {
    it('push() is mutable — returns same instance', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray([], validate: false);
        $result = $col->push($dto);

        expect(spl_object_id($result))->toBe(spl_object_id($col));
        expect($col->count())->toBe(1);
    });

    it('append() is immutable — returns new instance', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray([], validate: false);
        $result = $col->append($dto);

        expect(spl_object_id($result))->not->toBe(spl_object_id($col));
        expect($col->count())->toBe(0);
        expect($result->count())->toBe(1);
    });

    it('merge() returns new collection with all items', function () {
        $a = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $c = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $col1 = new DtoCollection([$a]);
        $col2 = new DtoCollection([$b, $c]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1); // original unchanged
    });

    it('chunk() splits correctly', function () {
        $items = [];
        for ($i = 0; $i < 7; $i++) {
            $items[] = EmptyDTO::fromArray(['foo' => "item{$i}"], validate: false);
        }
        $col = new DtoCollection($items);
        $chunks = $col->chunk(3);

        expect($chunks)->toHaveCount(3);
        expect($chunks[0]->count())->toBe(3);
        expect($chunks[1]->count())->toBe(3);
        expect($chunks[2]->count())->toBe(1);
    });

    it('take() returns at most N items', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = EmptyDTO::fromArray([], validate: false);
        }
        $col = new DtoCollection($items);

        expect($col->take(3)->count())->toBe(3);
        expect($col->take(10)->count())->toBe(5); // more than available
    });

    it('skip() skips first N items', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = EmptyDTO::fromArray(['foo' => (string) $i], validate: false);
        }
        $col = new DtoCollection($items);

        $rest = $col->skip(3);
        expect($rest->count())->toBe(2);
        expect($col->count())->toBe(5); // original unchanged
    });

    it('unique() removes duplicates by toArray()', function () {
        $a = EmptyDTO::fromArray(['foo' => 'same'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'same'], validate: false);
        $c = EmptyDTO::fromArray(['foo' => 'different'], validate: false);

        $col = new DtoCollection([$a, $b, $c]);
        $unique = $col->unique();

        expect($unique->count())->toBe(2);
    });

    it('contains() returns true for matching item', function () {
        $a = EmptyDTO::fromArray(['foo' => 'target'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'other'], validate: false);

        $col = new DtoCollection([$a, $b]);
        expect($col->contains(fn ($d) => $d->foo === 'target'))->toBeTrue();
        expect($col->contains(fn ($d) => $d->foo === 'nonexistent'))->toBeFalse();
    });

    it('search() returns first matching DTO', function () {
        $a = EmptyDTO::fromArray(['foo' => 'first'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'second'], validate: false);

        $col = new DtoCollection([$a, $b]);
        $found = $col->search(fn ($d) => $d->foo === 'second');

        expect($found)->toBeInstanceOf(EmptyDTO::class);
        expect($found->foo)->toBe('second');
    });

    it('search() returns null when no match', function () {
        $col = DtoCollection::make();
        expect($col->search(fn ($d) => $d->foo === 'x'))->toBeNull();
    });

    it('toDictionary() returns key-value pairs', function () {
        $a = EmptyDTO::fromArray(['foo' => 'key1', 'bar' => 'val1'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'key2', 'bar' => 'val2'], validate: false);

        $col = new DtoCollection([$a, $b]);
        $dict = $col->toDictionary('foo', 'bar');

        expect($dict)->toBe(['key1' => 'val1', 'key2' => 'val2']);
    });

    it('toArrayBy() returns full arrays keyed by property', function () {
        $a = EmptyDTO::fromArray(['foo' => 'k1', 'bar' => 'v1'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'k2', 'bar' => 'v2'], validate: false);

        $col = new DtoCollection([$a, $b]);
        $mapped = $col->toArrayBy('foo');

        expect($mapped)->toBe([
            'k1' => ['foo' => 'k1', 'bar' => 'v1'],
            'k2' => ['foo' => 'k2', 'bar' => 'v2'],
        ]);
    });

    it('sortBy() with property name sorts correctly', function () {
        $a = EmptyDTO::fromArray(['foo' => 'c'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $c = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $col = new DtoCollection([$a, $b, $c]);
        $sorted = $col->sortBy('foo');

        $labels = $sorted->map(fn ($d) => $d->foo);
        expect($labels)->toBe(['a', 'b', 'c']);
    });

    it('jsonSerialize returns array of arrays', function () {
        $a = EmptyDTO::fromArray(['foo' => 'x'], validate: false);
        $col = new DtoCollection([$a]);

        $json = json_encode($col);
        $decoded = json_decode($json, true);
        expect($decoded)->toBe([['foo' => 'x']]);
    });

    it('offsetGet returns null for out-of-bounds', function () {
        $col = DtoCollection::make();
        expect($col[0])->toBeNull();
        expect($col[999])->toBeNull();
    });

    it('offsetSet appends when offset is null', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray([], validate: false);
        $col[] = $dto;
        expect($col->count())->toBe(1);
    });

    it('offsetSet sets at specific offset', function () {
        $a = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $col = new DtoCollection([$a]);

        $col[0] = $b;
        expect($col->count())->toBe(1);
        expect($col[0]->foo)->toBe('b');
    });

    it('offsetUnset re-indexes after removal', function () {
        $a = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $c = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $col = new DtoCollection([$a, $b, $c]);
        unset($col[0]); // remove first

        expect($col->count())->toBe(2);
        // After re-indexing, keys should be 0, 1
        expect($col[0]->foo)->toBe('b');
        expect($col[1]->foo)->toBe('c');
    });

    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class, 'DataTransferObject');
    });

    it('rejects non-DTO items in offsetSet', function () {
        $col = DtoCollection::make();
        expect(fn () => $col[0] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class, 'DataTransferObject');
    });

    it('__clone always throws', function () {
        $col = DtoCollection::make();
        expect(fn () => clone $col)
            ->toThrow(\RuntimeException::class, 'immutable');
    });
});
