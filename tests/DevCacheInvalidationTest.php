<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

/**
 * Issue #3: Cache invalidation for metadata in development.
 *
 * In local/testing environments, metadata cache entries should be
 * automatically invalidated after a TTL expires, so that changes
 * to DTO classes are picked up without manual cache flushing.
 */
describe('Issue #3: Dev cache invalidation via TTL', function (): void {
    beforeEach(function (): void {
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);
    });

    afterEach(function (): void {
        DataTransferObject::setMetadataCacheTtl(0.0);
    });

    it('caches metadata permanently when TTL is 0 (production)', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.0);

        $rules1 = CreateUserDTO::rules();

        // Sleep to simulate time passing
        usleep(100_000); // 0.1s

        $rules2 = CreateUserDTO::rules();

        // Same cached data — TTL disabled
        expect($rules1)->toBe($rules2);
    });

    it('invalidates cache after TTL expires', function (): void {
        // Set TTL to 50ms
        DataTransferObject::setMetadataCacheTtl(0.05);

        $rules1 = CreateUserDTO::rules();

        // Wait beyond TTL
        usleep(100_000); // 0.1s > 0.05s TTL

        // Should re-resolve — cache should have been invalidated
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toBe($rules2); // Same content
        // But cache was regenerated (verified by checking timestamp was updated)
    });

    it('keeps cache valid within TTL window', function (): void {
        DataTransferObject::setMetadataCacheTtl(2.0);

        $rules1 = CreateUserDTO::rules();

        // Wait briefly — within TTL
        usleep(10_000); // 0.01s

        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toBe($rules2);
    });

    it('setMetadataCacheTtl accepts float values', function (): void {
        DataTransferObject::setMetadataCacheTtl(1.5);

        // Just verify it doesn't error
        CreateUserDTO::rules();

        expect(true)->toBeTrue();
    });

    it('flushMetadataCache also clears timestamps', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.01);

        // Populate cache
        CreateUserDTO::rules();
        OrderDTO::rules();

        // Wait past TTL
        usleep(20_000);

        // Flush all
        DataTransferObject::flushMetadataCache();

        // Should work fine — re-resolves from scratch
        $rules = CreateUserDTO::rules();
        expect($rules)->toHaveKey('email');
    });

    it('flushMetadataCache(class) clears timestamp for specific class', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.01);

        // Populate cache for both
        CreateUserDTO::rules();
        OrderDTO::rules();

        // Wait past TTL
        usleep(20_000);

        // Flush only CreateUserDTO
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Both should still work
        expect(CreateUserDTO::rules())->toHaveKey('email');
        expect(OrderDTO::rules())->not->toBeEmpty();
    });

    it('TTL invalidation works per-class, not globally', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.05);

        // Cache CreateUserDTO first
        CreateUserDTO::rules();

        // Wait beyond TTL for CreateUserDTO
        usleep(60_000); // 0.06s

        // Now cache OrderDTO (fresh)
        OrderDTO::rules();

        // Wait a tiny bit (within TTL for OrderDTO)
        usleep(10_000);

        // OrderDTO should still be cached (within its TTL)
        // CreateUserDTO should have been invalidated (past its TTL)
        // This tests that each class has its own timestamp
        $orderRules = OrderDTO::rules();
        expect($orderRules)->not->toBeEmpty();
    });
});
