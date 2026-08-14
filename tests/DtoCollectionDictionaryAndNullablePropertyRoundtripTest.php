<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;

describe('DtoCollection dictionary helpers and nullable property roundtrip', function (): void {
    // ── toDictionary tests ───────────────────────────────────────────────

    describe('toDictionary() — key/value extraction', function (): void {
        it('maps one property to another', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'Apple'),
                new ItemDTO(id: 2, name: 'Banana'),
                new ItemDTO(id: 3, name: 'Cherry'),
            ]);

            $dict = $items->toDictionary('id', 'name');

            expect($dict)->toBe([
                1 => 'Apple',
                2 => 'Banana',
                3 => 'Cherry',
            ]);
        });

        it('last value wins for duplicate keys', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'Apple', category: 'fruit'),
                new ItemDTO(id: 2, name: 'Banana', category: 'fruit'),
                new ItemDTO(id: 3, name: 'Cherry', category: 'veggie'),
            ]);

            $dict = $items->toDictionary('category', 'name');

            // 'fruit' appears twice — last wins
            expect($dict)->toBe([
                'fruit' => 'Banana',
                'veggie' => 'Cherry',
            ]);
        });

        it('skips items with null key values', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'A', category: 'fruit'),
                new ItemDTO(id: 2, name: 'B', category: null),
                new ItemDTO(id: 3, name: 'C', category: 'veggie'),
            ]);

            $dict = $items->toDictionary('category', 'name');

            expect($dict)->toBe([
                'fruit' => 'A',
                'veggie' => 'C',
            ]);
        });
    });

    // ── toArrayBy tests ─────────────────────────────────────────────────

    describe('toArrayBy() — re-keyed array output', function (): void {
        it('returns full DTO array keyed by specified property', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 42, name: 'Test Item'),
            ]);

            $keyed = $items->toArrayBy('id');

            expect($keyed)->toHaveKey('42');
            expect($keyed['42'])->toBe(['id' => 42, 'name' => 'Test Item', 'category' => null]);
        });

        it('returns empty collection for empty input', function (): void {
            $items = new DtoCollection([]);
            $keyed = $items->toArrayBy('id');

            expect($keyed)->toBe([]);
        });

        it('multiple items keyed correctly', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 10, name: 'Item 10'),
                new ItemDTO(id: 20, name: 'Item 20'),
            ]);

            $keyed = $items->toArrayBy('id');

            expect($keyed)->toHaveCount(2);
            expect($keyed[10]['name'])->toBe('Item 10');
            expect($keyed[20]['name'])->toBe('Item 20');
        });
    });

    // ── Nullable property roundtrip ─────────────────────────────────────

    describe('Nullable property roundtrip — fromArray → toArray preserves nulls', function (): void {
        it('preserves explicit null for nullable property', function (): void {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Alice',
                'nickname' => null,
                'email' => null,
            ]);

            expect($dto->name)->toBe('Alice');
            expect($dto->nickname)->toBeNull();
            expect($dto->email)->toBeNull();

            // toArray should include null values (not hidden)
            $arr = $dto->toArray();
            expect($arr)->toHaveKey('nickname');
            expect($arr['nickname'])->toBeNull();
            expect($arr)->toHaveKey('email');
            expect($arr['email'])->toBeNull();

            // secret should be hidden
            expect($arr)->not->toHaveKey('secret');
        });

        it('preserves string values for nullable property', function (): void {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Bob',
                'nickname' => 'Bobby',
                'email' => 'bob@test.com',
            ]);

            $arr = $dto->toArray();
            expect($arr['nickname'])->toBe('Bobby');
            expect($arr['email'])->toBe('bob@test.com');
        });

        it('allValues() includes hidden fields', function (): void {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Charlie',
                'nickname' => 'Chuck',
                'secret' => 's3cret',
            ]);

            $all = $dto->allValues();
            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('s3cret');
        });

        it('with() roundtrip preserves nullable values', function (): void {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Dave',
                'nickname' => null,
            ]);

            $updated = $dto->with(['email' => 'dave@test.com']);

            expect($updated->name)->toBe('Dave');
            expect($updated->nickname)->toBeNull();
            expect($updated->email)->toBe('dave@test.com');
            expect($dto->email)->toBeNull(); // original unchanged (immutable)
        });

        it('with() can override nullable null with a value', function (): void {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Eve',
                'nickname' => null,
            ]);

            $updated = $dto->with(['nickname' => 'Evie']);

            expect($updated->nickname)->toBe('Evie');
        });
    });

    // ── DtoCollection with pluck/pluckKey ──────────────────────────────

    describe('DtoCollection pluck/pluckKey with mixed values', function (): void {
        it('pluck extracts nullable property values including nulls', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'A', category: 'fruit'),
                new ItemDTO(id: 2, name: 'B', category: null),
                new ItemDTO(id: 3, name: 'C', category: 'veggie'),
            ]);

            $categories = $items->pluck('category');
            expect($categories)->toBe(['fruit', null, 'veggie']);
        });

        it('pluckKey with nullable key skips null keys', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'A', category: 'fruit'),
                new ItemDTO(id: 2, name: 'B', category: null),
                new ItemDTO(id: 3, name: 'C', category: 'veggie'),
            ]);

            $map = $items->pluckKey('category', 'name');
            expect($map)->toBe([
                'fruit' => 'A',
                'veggie' => 'C',
            ]);
        });
    });

    // ── DtoCollection pluck/pluckKey with non-nullable int keys ──────────

    describe('DtoCollection pluck/pluckKey with non-nullable keys', function (): void {
        it('pluckKey uses int key values correctly', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'First'),
                new ItemDTO(id: 2, name: 'Second'),
            ]);

            $map = $items->pluckKey('id', 'name');
            expect($map)->toBe([1 => 'First', 2 => 'Second']);
        });

        it('pluckKey with null valueField returns full DTO array', function (): void {
            $items = new DtoCollection([
                new ItemDTO(id: 1, name: 'First'),
            ]);

            $map = $items->pluckKey('id');
            expect($map)->toHaveKey(1);
            expect($map[1])->toBe(['id' => 1, 'name' => 'First', 'category' => null]);
        });
    });

    // ── CreateUserDTO (real fixture) roundtrip ────────────────────────

    describe('CreateUserDTO fixture — full roundtrip with validation', function (): void {
        it('fromArray validates and hydrates correctly', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ]);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->password)->toBe('secret123');
        });

        it('toArray preserves all public non-hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ]);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });
    });
});
