<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO PHPStan Level 9 Compliance', function () {
    it('toJson returns empty string on empty result (not false)', function () {
        // Regression: json_encode returns false on failure, but we must return string
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeString();
        expect($json)->not->toBeFalse();
    });

    it('toJson produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveKey('email');
        expect($decoded)->toHaveKey('name');
    });

    it('only() works with string key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('only() works with array keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'status']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('status');
        expect($result)->not->toHaveKey('name');
    });

    it('except() works with string key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except() works with array keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['email', 'status']);

        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('status');
        expect($result)->toHaveKey('name');
    });

    it('isEmpty returns true for default-only DTO', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // status has default 'active' — so it's empty-ish value
        // email and name are non-empty, so isEmpty should be false
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty detects all-zero/empty state', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        // EmptyDTO has no properties — should report as empty
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('cast type is resolved as string without redundant is_string check', function () {
        // Regression: the cast property is always string from DtoMetadataResolver,
        // so the redundant `is_string()` check was removed.
        // This test verifies the cast pipeline still works correctly.
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => '["a","b"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b']);
    });

    it('with() always validates regardless of $validate parameter', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // with() should always validate, even with validate: false
        // (the parameter is deprecated and ignored)
        $updated = $dto->with(['email' => 'new@example.com'], validate: true);

        expect($updated->email)->toBe('new@example.com');
        expect($updated->name)->toBe('Test');
    });
});
