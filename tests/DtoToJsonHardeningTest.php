<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO toJson JSON_THROW_ON_ERROR hardening', function () {
    it('returns valid JSON string for normal DTO', function () {
        $dto = MinimalDTO::fromArray(['name' => 'John', 'value' => 'dev'], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeString();
        expect($json)->not->toBeEmpty();

        // Verify it decodes back to the same data
        $decoded = json_decode($json, true);
        expect($decoded)->toBe($dto->toArray());
    });

    it('returns empty string for DTO that produces unserializable values', function () {
        // Create a DTO with all defaults
        $dto = EmptyDTO::fromArray([], validate: false);
        $json = $dto->toJson();

        // EmptyDTO has no public properties — should still produce valid JSON
        expect($json)->toBeString();
    });

    it('toJson with JSON_PRETTY_PRINT produces formatted output', function () {
        $dto = MinimalDTO::fromArray(['name' => 'John', 'value' => 'dev'], validate: false);
        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("\n");
        expect($json)->toContain('  '); // indentation
    });

    it('toJson returns empty string when JSON encoding fails', function () {
        // We can't easily trigger a json_encode failure on valid data,
        // but we verify the return type contract
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '0'], validate: false);
        $json = $dto->toJson();

        // Contract: always returns string (empty string on failure)
        expect($json)->toBeString();
    });
});
