<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DataTransferObject — lifecycle and edge cases', function (): void {
    it('fromArray with empty data uses all defaults', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('fromArray passes through provided values', function (): void {
        $dto = EmptyDTO::fromArray([
            'foo' => 'hello',
            'bar' => 'world',
        ], validate: false);

        expect($dto->foo)->toBe('hello');
        expect($dto->bar)->toBe('world');
    });

    it('fromArray uses default for missing fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('fromArray with explicit null overrides default', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => null,
        ], validate: false);

        expect($dto->status)->toBeNull();
    });

    it('fromPartialArray fills missing fields with defaults', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Bob',
        ], validatePresent: false);

        expect($dto->name)->toBe('Bob');
        expect($dto->status)->toBe('active'); // Default
        expect($dto->tags)->toBe([]); // Default
    });

    it('fromPartialArray with empty array uses all defaults', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('toArray includes all non-hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
            'status' => 'active',
            'tags' => ['php'],
            'phone_number' => '+123',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->toHaveKeys(['email', 'name', 'status', 'tags', 'phone']);
        expect($array)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });

    it('only() returns specified fields only', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $only = $dto->only('email');

        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
    });

    it('only() accepts array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $only = $dto->only(['email', 'status']);

        expect($only)->toHaveCount(2);
        expect($only)->toHaveKeys(['email', 'status']);
    });

    it('except() excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $except = $dto->except('email');

        expect($except)->not->toHaveKey('email');
        expect($except)->toHaveKey('name');
    });

    it('toJson produces valid JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('a@b.com');
    });

    it('equals compares toArray output', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Bob'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty detects DTO with all empty/null values', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty detects DTO with non-empty value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty considers 0 as non-empty for non-nullable numeric properties', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => '',
            'price' => '0',
            'stock' => 0,
        ], validate: false);

        // stock = 0 (non-nullable int) → not empty
        expect($dto->isEmpty())->toBeFalse();
    });

    it('with() creates new instance with merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $updated = $dto->with(['status' => 'inactive']);

        expect($dto->status)->toBe('active'); // Original unchanged
        expect($updated->status)->toBe('inactive');
        expect($updated->email)->toBe('a@b.com'); // Preserved
    });

    it('rules() returns rules from attributes', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('rulesFor() defaults to rules()', function (): void {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        expect($rules)->toBe($rulesFor);
    });

    it('jsonSerialize returns toArray output', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('flushMetadataCache clears all cached metadata', function (): void {
        // Access to populate cache
        CreateUserDTO::rules();

        DataTransferObject::flushMetadataCache();

        // After flush, metadata should be re-resolved on next access
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('flushMetadataCache with specific class only clears that class', function (): void {
        CreateUserDTO::rules();
        ProductDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // ProductDTO rules should still be cached (no error)
        $productRules = ProductDTO::rules();
        expect($productRules)->toBeArray();
    });
});
