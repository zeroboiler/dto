<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('Metadata Cache', function (): void {
    it('caches metadata after first resolution', function (): void {
        // Flush to ensure clean state
        DataTransferObject::flushMetadataCache();

        // First resolution — populates cache
        $rules1 = CreateUserDTO::rules();

        // Second resolution — should return from cache (same data)
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toBe($rules2);
    });

    it('flushMetadataCache clears all cached metadata', function (): void {
        // Populate cache for multiple DTOs
        CreateUserDTO::rules();
        OrderDTO::rules();

        // Flush all
        DataTransferObject::flushMetadataCache();

        // Verify metadata is re-resolved (not stale)
        $rules = CreateUserDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('flushMetadataCache clears a specific class only', function (): void {
        // Populate cache
        CreateUserDTO::rules();
        OrderDTO::rules();

        // Flush only CreateUserDTO
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Both should still work — re-resolution for CreateUserDTO
        $userRules = CreateUserDTO::rules();
        $orderRules = OrderDTO::rules();

        expect($userRules)->toHaveKey('email');
        expect($orderRules)->not->toBeEmpty();
    });

    it('handles multiple DTO classes without interference', function (): void {
        DataTransferObject::flushMetadataCache();

        $userRules = CreateUserDTO::rules();
        $productRules = ProductDTO::rules();

        // Each class should have its own distinct rules
        expect($userRules)->toHaveKey('email');
        expect($userRules)->not->toHaveKey('price');

        expect($productRules)->toHaveKey('price');
        expect($productRules)->not->toHaveKey('email');
    });

    it('flushMetadataCache with non-existent class does not error', function (): void {
        expect(fn () => DataTransferObject::flushMetadataCache('NonExistentClass'))->not->toThrow(Exception::class);
    });
});
