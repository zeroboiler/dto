<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection pluck and pluckKey detailed tests', function (): void {
    it('pluck extracts a single field from all DTOs', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie'], validate: false),
        ]);

        $emails = $collection->pluck('email');

        expect($emails)->toBe(['a@test.com', 'b@test.com', 'c@test.com']);
    });

    it('pluck returns empty array for empty collection', function (): void {
        $collection = DtoCollection::make([]);

        expect($collection->pluck('email'))->toBe([]);
    });

    it('pluckKey creates key/value map from two fields', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ]);

        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });

    it('pluckKey without valueField returns full toArray as value', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
        ]);

        $map = $collection->pluckKey('email');

        expect($map)->toHaveKey('a@test.com');
        expect($map['a@test.com'])->toBeArray();
        expect($map['a@test.com'])->toHaveKey('email');
        expect($map['a@test.com'])->toHaveKey('name');
    });

    it('push returns same collection instance (fluent)', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
        ]);

        $result = $collection->push(
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false)
        );

        expect($result)->toBe($collection); // same instance (fluent)
        expect($collection->count())->toBe(2);
    });

    it('filter returns new collection with matching items', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice', 'status' => 'active'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob', 'status' => 'inactive'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie', 'status' => 'active'], validate: false),
        ]);

        $active = $collection->filter(
            fn (CreateUserDTO $dto): bool => $dto->status === 'active'
        );

        expect($active)->not->toBe($collection); // new instance
        expect($active->count())->toBe(2);
        expect($active->first()?->email)->toBe('a@test.com');
    });

    it('map returns plain array of transformed values', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ]);

        $upperNames = $collection->map(fn (CreateUserDTO $dto): string => strtoupper($dto->name));

        expect($upperNames)->toBe(['ALICE', 'BOB']);
    });

    it('offsetUnset re-indexes array to prevent gaps', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'Charlie'], validate: false),
        ]);

        // Remove the middle item
        unset($collection[1]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->email)->toBe('a@test.com');
        expect($collection[1]->email)->toBe('c@test.com'); // Re-indexed — no gap
    });

    it('jsonSerialize returns array of arrays', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
        ]);

        $serialized = $collection->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized[0])->toBeArray();
        expect($serialized[0])->toHaveKey('email');
        expect($serialized[0])->not->toHaveKey('password'); // hidden
    });

    it('allValues includes hidden fields in each DTO', function (): void {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false),
        ]);

        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret123');
    });
});
