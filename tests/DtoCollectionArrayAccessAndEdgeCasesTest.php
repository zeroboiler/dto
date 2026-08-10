<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Tests for DtoCollection ArrayAccess, IteratorAggregate, and edge case operations.
 *
 * Covers:
 * - ArrayAccess: offsetExists, offsetGet, offsetSet, offsetUnset
 * - Re-indexing after offsetUnset
 * - IteratorAggregate (foreach traversal)
 * - Countable (count)
 * - jsonSerialize consistency
 * - Empty collection behavior
 * - allValues() vs toArray() for hidden fields
 *
 * @see \ZeroBoiler\DTO\DtoCollection
 */

use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection ArrayAccess and edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // ArrayAccess interface
    // ──────────────────────────────────────────────────────────────

    describe('ArrayAccess interface', function (): void {
        it('offsetExists returns true for valid indices', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeTrue();
            expect(isset($col[2]))->toBeFalse();
        });

        it('offsetGet returns DTO at valid index', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            expect($col[0])->toBe($d1);
            expect($col[0]->email)->toBe('a@b.com');
        });

        it('offsetGet returns null for invalid index', function (): void {
            $col = DtoCollection::make([]);

            expect($col[0])->toBeNull();
            expect($col[99])->toBeNull();
        });

        it('offsetSet appends when offset is null', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1]);

            $col[] = $d2;

            expect($col->count())->toBe(2);
            expect($col[1]->email)->toBe('c@d.com');
        });

        it('offsetSet replaces at specific index', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1]);

            $col[0] = $d2;

            expect($col->count())->toBe(1);
            expect($col[0]->email)->toBe('c@d.com');
        });

        it('offsetSet rejects non-DTO values', function (): void {
            $col = DtoCollection::make([]);

            expect(fn () => $col[] = new \stdClass)
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset removes item and re-indexes', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);
            $col = DtoCollection::make([$d1, $d2, $d3]);

            unset($col[0]);

            // Re-indexed: $d2 is now at index 0, $d3 at index 1
            expect($col->count())->toBe(2);
            expect($col[0]->email)->toBe('c@d.com');
            expect($col[1]->email)->toBe('e@f.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // IteratorAggregate
    // ──────────────────────────────────────────────────────────────

    describe('IteratorAggregate (foreach traversal)', function (): void {
        it('iterates over all items in order', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$d1, $d2]);

            $emails = [];
            foreach ($col as $dto) {
                $emails[] = $dto->email;
            }

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('yields empty for empty collection', function (): void {
            $col = DtoCollection::make([]);

            $count = 0;
            foreach ($col as $dto) {
                $count++;
            }

            expect($count)->toBe(0);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // jsonSerialize
    // ──────────────────────────────────────────────────────────────

    describe('jsonSerialize', function (): void {
        it('produces consistent output with toArray()', function (): void {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$d1]);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('excludes hidden fields from json output', function (): void {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'A',
                'password' => 'secret',
            ], validate: false);
            $col = DtoCollection::make([$d1]);

            $json = json_encode($col);
            expect($json)->not->toContain('secret');
            expect($json)->toContain('a@b.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // allValues vs toArray (hidden fields)
    // ──────────────────────────────────────────────────────────────

    describe('allValues vs toArray', function (): void {
        it('toArray excludes hidden fields', function (): void {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'A',
                'password' => 'secret',
            ], validate: false);
            $col = DtoCollection::make([$d1]);

            $arr = $col->toArray();
            expect($arr[0])->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function (): void {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'A',
                'password' => 'secret',
            ], validate: false);
            $col = DtoCollection::make([$d1]);

            $all = $col->allValues();
            expect($all[0])->toHaveKey('password');
            expect($all[0]['password'])->toBe('secret');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Empty collection behavior
    // ──────────────────────────────────────────────────────────────

    describe('Empty collection behavior', function (): void {
        it('isEmpty returns true for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();
        });

        it('toArray returns empty array for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->toArray())->toBe([]);
            expect($col->allValues())->toBe([]);
        });

        it('pluck returns empty array for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->pluck('email'))->toBe([]);
        });

        it('pluckKey returns empty array for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->pluckKey('email', 'name'))->toBe([]);
        });

        it('map returns empty array for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->map(fn (CreateUserDTO $d): string => $d->name))->toBe([]);
        });

        it('filter returns empty collection for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->filter(fn (CreateUserDTO $d): bool => true)->isEmpty())->toBeTrue();
        });

        it('jsonSerialize returns empty array for empty collection', function (): void {
            $col = DtoCollection::make([]);

            expect($col->jsonSerialize())->toBe([]);
        });
    });
});
