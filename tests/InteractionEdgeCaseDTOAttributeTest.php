<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Tests\Fixtures\InteractionEdgeCaseDTO;

describe('InteractionEdgeCaseDTO — Prohibited + Hidden + DefaultValue interactions', function (): void {
    it('creates with required fields only', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice_dev',
        ], validate: false);

        expect($dto->username)->toBe('alice');
        expect($dto->handle)->toBe('@alice_dev');
        expect($dto->source)->toBe('internal');
        expect($dto->limit)->toBe(100);
        expect($dto->adminOverride)->toBeNull();
    });

    it('respects DefaultValue for source', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'bob',
            'handle' => '@bob',
        ], validate: false);

        expect($dto->source)->toBe('internal');
    });

    it('respects DefaultValue for limit', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'charlie',
            'handle' => '@charlie',
        ], validate: false);

        expect($dto->limit)->toBe(100);
    });

    it('excludes hidden source from toArray', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->not->toHaveKey('source');
        expect($array)->toHaveKey('username');
        expect($array)->toHaveKey('handle');
        expect($array)->toHaveKey('limit');
    });

    it('includes hidden source in allValues', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('source');
        expect($all['source'])->toBe('internal');
    });

    it('serializes to JSON without hidden fields', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->not->toHaveKey('source');
    });

    it('creates immutable copy with overrides', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        $updated = $dto->with(['username' => 'bob', 'limit' => 50]);

        expect($dto->username)->toBe('alice');
        expect($dto->limit)->toBe(100);
        expect($updated->username)->toBe('bob');
        expect($updated->limit)->toBe(50);
        expect($updated->handle)->toBe('@alice');
    });

    it('checks equality correctly', function (): void {
        $dto1 = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        $dto2 = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('isEmpty returns false when required fields are set', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'alice',
            'handle' => '@alice',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('rules contain Prohibited for adminOverride', function (): void {
        $rules = InteractionEdgeCaseDTO::rules();

        expect($rules['adminOverride'])->toContain('prohibited');
    });

    it('rules contain StartsWith for handle', function (): void {
        $rules = InteractionEdgeCaseDTO::rules();

        expect($rules['handle'])->toContain('starts_with:@');
    });

    it('rules contain Required for username and handle', function (): void {
        $rules = InteractionEdgeCaseDTO::rules();

        expect($rules['username'])->toContain('required');
        expect($rules['handle'])->toContain('required');
    });
});
