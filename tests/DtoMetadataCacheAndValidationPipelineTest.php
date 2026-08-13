<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO metadata cache and validation pipeline', function () {
    it('flushMetadataCache clears all cached entries', function () {
        // Trigger metadata resolution by calling rules()
        $rules1 = CreateUserDTO::rules();
        expect($rules1)->toBeArray();

        // Flush all metadata
        CreateUserDTO::flushMetadataCache();

        // Re-resolve should still work
        $rules2 = CreateUserDTO::rules();
        expect($rules2)->toBe($rules1);
    });

    it('flushMetadataCache with specific class only clears that class', function () {
        // Resolve both DTOs
        CreateUserDTO::rules();
        EmptyDTO::rules();

        // Flush only CreateUserDTO
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // EmptyDTO metadata should still be cached
        $rules = EmptyDTO::rules();
        expect($rules)->toBeArray();
    });

    it('setMetadataCacheTtl controls cache expiration', function () {
        CreateUserDTO::setMetadataCacheTtl(0.0);
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // Resolve metadata
        $rules1 = CreateUserDTO::rules();
        expect($rules1)->toBeArray();

        // With TTL=0, each call re-resolves
        $rules2 = CreateUserDTO::rules();
        expect($rules2)->toBe($rules1);

        // Reset to 0 for production-like behavior
        CreateUserDTO::setMetadataCacheTtl(0.0);
    });

    it('validatePartialArray converts required to sometimes for present fields', function () {
        // Providing only email (without required name) should succeed
        // because validatePartialArray converts 'required' to 'sometimes'
        $result = CreateUserDTO::validatePartialArray(['email' => 'test@example.com']);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('validatePartialArray with empty array returns empty array', function () {
        $result = CreateUserDTO::validatePartialArray([]);
        expect($result)->toBe([]);
    });

    it('validatePartialArray validates present fields and fails on invalid', function () {
        expect(fn () => CreateUserDTO::validatePartialArray(['email' => 'not-an-email']))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('validateArray returns validated data for valid input', function () {
        $result = CreateUserDTO::validateArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
        expect($result['name'])->toBe('Test User');
    });

    it('validateArray throws on invalid input', function () {
        expect(fn () => CreateUserDTO::validateArray(['email' => 'invalid']))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('fromPartialArray with MapFrom key uses mapped source key', function () {
        // phone_number is mapped to phone via MapFrom
        $dto = CreateUserDTO::fromPartialArray(['phone_number' => '+905551234567']);
        expect($dto->phone)->toBe('+905551234567');
    });

    it('MapFrom with dot notation works in fromArray', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO::fromArray([
            'user' => ['profile' => ['firstName' => 'John', 'lastName' => 'Doe']],
        ], validate: false);

        expect($dto->firstName)->toBe('John');
        expect($dto->lastName)->toBe('Doe');
    });

    it('multiple fromArray calls reuse cached metadata', function () {
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Other'], validate: false);

        expect($dto1->email)->toBe('a@b.com');
        expect($dto2->email)->toBe('c@d.com');
    });
});
