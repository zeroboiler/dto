<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection toArrayBy', function () {
    it('re-keys collection by a property value', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toBeArray();
        expect($keyed)->toHaveCount(2);
        expect($keyed['a@test.com'])->toBe($dto1->toArray());
        expect($keyed['b@test.com'])->toBe($dto2->toArray());
    });

    it('skips items with null key values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => null, 'name' => 'NoEmail'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveCount(1);
        expect($keyed)->toHaveKey('a@test.com');
        expect($keyed)->not->toHaveKey('');
    });

    it('returns empty array for empty collection', function () {
        $collection = DtoCollection::make([]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toBe([]);
    });

    it('last-write-wins on duplicate key values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'same@test.com', 'name' => 'First'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'same@test.com', 'name' => 'Second'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveCount(1);
        expect($keyed['same@test.com']['name'])->toBe('Second');
    });
});

describe('DtoCollection toDictionary', function () {
    it('creates key-value pairs from two properties', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toBeArray();
        expect($dict['a@test.com'])->toBe('Alice');
        expect($dict['b@test.com'])->toBe('Bob');
    });

    it('skips items with null key values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => null, 'name' => 'NoEmail'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toHaveCount(1);
        expect($dict['a@test.com'])->toBe('Alice');
    });

    it('returns empty array for empty collection', function () {
        $collection = DtoCollection::make([]);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toBe([]);
    });

    it('handles integer property as key', function () {
        // Use name as key (string), email as value
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false);
        $collection = DtoCollection::make([$dto1]);

        $dict = $collection->toDictionary('name', 'email');
        expect($dict['Alice'])->toBe('a@test.com');
    });
});
