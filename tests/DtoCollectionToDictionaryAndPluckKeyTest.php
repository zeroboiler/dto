<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection toDictionary and toArrayBy', function (): void {
    it('toArrayBy re-keys collection by specified property', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toHaveKey('a@test.com');
        expect($keyed['a@test.com']['name'])->toBe('Alice');
        expect($keyed['b@test.com']['name'])->toBe('Bob');
    });

    it('toArrayBy skips items with null key value', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $keyed = $collection->toArrayBy('phone'); // phone is null for both

        expect($keyed)->toBeArray();
        expect($keyed)->toBeEmpty();
    });

    it('toDictionary extracts key-value pairs', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $dict = $collection->toDictionary('email', 'name');

        expect($dict)->toBe([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });

    it('toDictionary skips items with null key value', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $dict = $collection->toDictionary('phone', 'name');

        expect($dict)->toBeArray();
        expect($dict)->toBeEmpty();
    });
});

describe('DtoCollection pluckKey', function (): void {
    it('pluckKey returns key-array pairs', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $result = $collection->pluckKey('email');

        expect($result)->toHaveKey('a@test.com');
        expect($result['a@test.com'])->toHaveKey('email');
        expect($result['a@test.com'])->toHaveKey('name');
    });

    it('pluckKey with valueField extracts single field', function (): void {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $result = $collection->pluckKey('email', 'name');

        expect($result)->toBe([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });
});
