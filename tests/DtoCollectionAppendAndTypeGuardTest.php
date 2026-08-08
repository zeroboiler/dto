<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection — offsetSet with null offset (append)', function (): void {
    it('appends DTO when offset is null', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1]);
        $col->offsetSet(null, $dto2);

        expect($col->count())->toBe(2);
        expect($col->last()->email)->toBe('b@test.com');
    });

    it('replaces DTO at existing offset', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1]);
        $col->offsetSet(0, $dto2);

        expect($col->count())->toBe(1);
        expect($col->first()->email)->toBe('b@test.com');
    });
});

describe('DtoCollection — offsetSet type guard', function (): void {
    it('throws when setting non-DTO value', function (): void {
        $col = new DtoCollection;

        $col->offsetSet(null, 'not a dto');
    })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');

    it('throws when setting integer value', function (): void {
        $col = new DtoCollection;

        $col->offsetSet(0, 42);
    })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');

    it('throws when constructing with non-DTO items', function (): void {
        new DtoCollection([new \stdClass]);
    })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
});

describe('DtoCollection — offsetUnset re-indexing', function (): void {
    it('re-indexes after offsetUnset', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        expect($col->count())->toBe(3);

        $col->offsetUnset(1);
        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('a@test.com');
        expect($col[1]->email)->toBe('c@test.com');
    });
});

describe('DtoCollection — pluck and pluckKey with reflection', function (): void {
    it('plucks email field from collection', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('builds key/value map via pluckKey', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });

    it('builds key/toArray map when valueField is null', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $col = new DtoCollection([$dto1]);
        $map = $col->pluckKey('email');

        expect($map)->toHaveKey('a@test.com');
        expect($map['a@test.com'])->toBeArray();
    });
});
