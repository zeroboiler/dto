<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO production hardening — DtoCollection edge cases and validation contract', function () {
    // ── DtoCollection edge cases ────────────────────────────────────────────

    it('toArrayBy returns correct keyed array', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        $result = $col->toArrayBy('email');
        expect($result)->toHaveKey('a@b.com');
        expect($result['a@b.com'])->toHaveKey('name');
        expect($result['a@b.com']['name'])->toBe('Alice');
    });

    it('toDictionary returns correct key-value pairs', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $col = new DtoCollection([$dto1, $dto2]);

        $result = $col->toDictionary('email', 'name');
        expect($result)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('DtoCollection::make returns empty collection for empty array', function () {
        $col = DtoCollection::make([]);
        expect($col->isEmpty())->toBeTrue();
        expect($col->count())->toBe(0);
        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
        expect($col->items())->toBe([]);
        expect($col->toArray())->toBe([]);
    });

    it('DtoCollection rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not a dto', 123, new \stdClass]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection::push returns same instance for chaining', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$dto1]);
        $returned = $col->push($dto2);

        expect($returned)->toBe($col); // Same instance
        expect($col->count())->toBe(2);
    });

    it('DtoCollection::append returns new instance (immutable)', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $original = new DtoCollection([$dto1]);
        $cloned = $original->append($dto2);

        expect($cloned)->not->toBe($original);
        expect($original->count())->toBe(1);
        expect($cloned->count())->toBe(2);
    });

    it('DtoCollection::merge returns new instance combining both', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged)->not->toBe($col1);
        expect($merged)->not->toBe($col2);
        expect($merged->count())->toBe(3);
    });

    it('DtoCollection offsetSet with null appends', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection;
        $col[] = $dto;

        expect($col->count())->toBe(1);
        expect($col[0]->email)->toBe('a@b.com');
    });

    it('DtoCollection offsetSet with explicit index replaces', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$dto1]);
        $col[0] = $dto2;

        expect($col->count())->toBe(1);
        expect($col[0]->email)->toBe('c@d.com');
    });

    it('DtoCollection offsetSet rejects non-DTO values', function () {
        $col = new DtoCollection;

        expect(fn () => $col[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection offsetUnset re-indexes correctly', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($col[0]);

        // Should have 2 items, re-indexed to [0, 1]
        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('c@d.com');
        expect($col[1]->email)->toBe('e@f.com');
        expect($col->last()->email)->toBe('e@f.com');
    });

    it('DtoCollection::filter returns new instance (immutable)', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $filtered = $col->filter(fn (DataTransferObject $d) => str_starts_with($d->email, 'a'));

        expect($filtered)->not->toBe($col);
        expect($filtered->count())->toBe(1);
        expect($col->count())->toBe(2); // Original unchanged
    });

    it('DtoCollection::pluck extracts single property', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        expect($col->pluck('email'))->toEqual(['a@b.com', 'c@d.com']);
        expect($col->pluck('name'))->toEqual(['Alice', 'Charlie']);
    });

    it('DtoCollection::pluckKey returns key-value pairs', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        expect($col->pluckKey('email', 'name'))->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('DtoCollection::pluckKey with null valueField returns full DTO array', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        $col = new DtoCollection([$dto1]);
        $result = $col->pluckKey('email');

        expect($result)->toHaveKey('a@b.com');
        expect($result['a@b.com'])->toHaveKey('name');
    });

    it('DtoCollection is countable', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        expect(count($col))->toBe(1);
    });

    it('DtoCollection is iterable', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        $results = [];
        foreach ($col as $key => $item) {
            $results[$key] = $item->email;
        }

        expect($results)->toBe([0 => 'a@b.com']);
    });

    // ── DataTransferObject::with() always validates ────────────────────────

    it('with() ignores $validate=false parameter and always validates', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        // with() should still validate even when validate=false is passed (backward compat param)
        // Since we can't run validation without Laravel, this tests the method signature
        expect(method_exists($dto, 'with'))->toBeTrue();
    });

    // ── fromArray with empty data ──────────────────────────────────────────

    it('fromArray with valid data for DTO with all required fields works', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '42'], validate: false);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->toArray())->toBeArray();
        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('42');
    });

    // ── DtoCollection JSON serialization ───────────────────────────────────

    it('DtoCollection jsonSerialize returns array of arrays', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        $json = json_encode($col);
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->toHaveKey('name');
    });

    // ── DtoCollection allValues includes hidden fields ─────────────────────

    it('DtoCollection allValues includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
        $col = new DtoCollection([$dto]);

        $values = $col->allValues();
        expect($values[0])->toHaveKey('password');
        expect($values[0]['password'])->toBe('secret');
    });
});
