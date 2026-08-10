<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection Edge Cases', function () {
    describe('isEmpty and isNotEmpty', function () {
        it('isEmpty returns true for empty collection', function () {
            $col = new DtoCollection();
            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false for non-empty collection', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $col = new DtoCollection([$dto]);
            expect($col->isEmpty())->toBeFalse();
            expect($col->isNotEmpty())->toBeTrue();
        });
    });

    describe('merge', function () {
        it('merges two non-empty collections', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);

            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
            expect($merged->first()->email)->toBe('a@b.com');
            expect($merged->last()->email)->toBe('c@d.com');
        });

        it('merges with empty collection', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $empty = new DtoCollection();

            $merged = $col->merge($empty);
            expect($merged->count())->toBe(1);
        });

        it('does not mutate original collections', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
            expect($merged)->not->toBe($col1);
            expect($merged)->not->toBe($col2);
        });
    });

    describe('append', function () {
        it('returns new collection with appended item', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });
    });

    describe('push', function () {
        it('appends in-place and returns self', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1]);
            $result = $col->push($dto2);

            expect($col->count())->toBe(2);
            expect($result)->toBe($col);
        });
    });

    describe('pluckKey', function () {
        it('builds key-value map from two fields', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $map = $col->pluckKey('email', 'name');

            expect($map)->toBe([
                'a@b.com' => 'Alice',
                'c@d.com' => 'Charlie',
            ]);
        });

        it('builds key-toArray map with single field', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $map = $col->pluckKey('email');

            expect($map)->toHaveKey('a@b.com');
            expect($map['a@b.com'])->toBeArray();
        });
    });

    describe('filter', function () {
        it('returns new collection with filtered items', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn (CreateUserDTO $d): bool => str_starts_with($d->name, 'A'));

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('returns empty collection when nothing matches', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $filtered = $col->filter(fn (CreateUserDTO $d): bool => false);

            expect($filtered->count())->toBe(0);
            expect($filtered->isEmpty())->toBeTrue();
        });
    });

    describe('map', function () {
        it('maps over items and returns plain array', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $names = $col->map(fn (CreateUserDTO $d): string => $d->name);

            expect($names)->toBe(['Alice', 'Charlie']);
        });
    });

    describe('first and last', function () {
        it('first returns null for empty collection', function () {
            $col = new DtoCollection();
            expect($col->first())->toBeNull();
        });

        it('last returns null for empty collection', function () {
            $col = new DtoCollection();
            expect($col->last())->toBeNull();
        });

        it('first and last return same item for single-item collection', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            expect($col->first()->email)->toBe($col->last()->email);
        });
    });

    describe('ArrayAccess', function () {
        it('offsetExists returns false for non-existent index', function () {
            $col = new DtoCollection();
            expect($col->offsetExists(0))->toBeFalse();
        });

        it('offsetGet returns null for non-existent index', function () {
            $col = new DtoCollection();
            expect($col->offsetGet(0))->toBeNull();
        });

        it('offsetUnset re-indexes array', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $col->offsetUnset(0);

            // After re-index, the remaining item should be at index 0
            expect($col->offsetExists(0))->toBeTrue();
            expect($col->count())->toBe(1);
            expect($col->first()->email)->toBe('c@d.com');
        });

        it('rejects non-DTO values in offsetSet', function () {
            $col = new DtoCollection();
            expect(fn () => $col[0] = 'not a dto')
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('jsonSerialize', function () {
        it('serializes to array of arrays', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $json = json_encode($col);

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded[0])->toHaveKey('email');
            expect($decoded[0])->toHaveKey('name');
        });
    });

    describe('toArray and allValues', function () {
        it('toArray excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $arr = $col->toArray();

            expect($arr[0])->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $arr = $col->allValues();

            expect($arr[0])->toHaveKey('password');
            expect($arr[0]['password'])->toBe('secret');
        });
    });

    describe('make factory', function () {
        it('creates collection from array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = DtoCollection::make([$dto]);
            expect($col->count())->toBe(1);
        });

        it('creates empty collection from empty array', function () {
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();
        });
    });

    describe('constructor validation', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
        });
    });
});
