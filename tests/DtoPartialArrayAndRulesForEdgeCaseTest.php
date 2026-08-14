<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DataTransferObject fromPartialArray edge cases', function (): void {
    it('hydrates only provided fields, defaults for rest', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
        ], validatePresent: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->status)->toBe('active'); // DefaultValue default
        expect($dto->tags)->toBe([]); // constructor default
    });

    it('respects explicit null values (does not override with default)', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'phone' => null,
        ], validatePresent: false);

        // Explicit null should be respected
        expect($dto->phone)->toBeNull();
    });

    it('empty data array uses all defaults', function (): void {
        // With no data at all, required fields (email, name) will fail
        // So we test with only optional fields
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // email and name are required — fromPartialArray gives type-appropriate empty values
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('');
        expect($dto->status)->toBe('active');
    });

    it('MapFrom works in partial mode', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+905559998877',
        ], validatePresent: false);

        expect($dto->phone)->toBe('+905559998877');
    });

    it('cast works in partial mode', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'tags' => '["a","b"]',
        ], validatePresent: false);

        expect($dto->tags)->toBe(['a', 'b']);
    });
});

describe('DataTransferObject rulesFor action-scoped', function (): void {
    it('rulesFor returns same rules as rules() by default', function (): void {
        $defaultRules = CreateUserDTO::rules();
        $createRules = CreateUserDTO::rulesFor('create');
        $updateRules = CreateUserDTO::rulesFor('update');

        expect($createRules)->toBe($defaultRules);
        expect($updateRules)->toBe($defaultRules);
    });
});

describe('DataTransferObject metadata cache TTL', function (): void {
    it('flushMetadataCache clears specific class', function (): void {
        // Resolve metadata to populate cache
        $rules1 = CreateUserDTO::rules();

        // Flush only this class
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // Re-resolve — should still work (cache rebuilt)
        $rules2 = CreateUserDTO::rules();

        expect($rules2)->toBe($rules1);
    });

    it('flushMetadataCache with null clears all', function (): void {
        $rules1 = CreateUserDTO::rules();
        $rules2 = MinimalDTO::rules();

        CreateUserDTO::flushMetadataCache(null);

        $rules1After = CreateUserDTO::rules();
        $rules2After = MinimalDTO::rules();

        expect($rules1After)->toBe($rules1);
        expect($rules2After)->toBe($rules2);
    });
});
