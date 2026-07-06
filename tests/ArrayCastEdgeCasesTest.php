<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;

describe('Array Cast Edge Cases (Issue #64)', function (): void {
    it('handles valid JSON string conversion to array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["laravel","php","react"]',
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php', 'react']);
    });

    it('handles empty string as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    it('handles whitespace-only string as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '   ',
        ], validate: false);

        // Whitespace-only is not valid JSON, so returns empty array
        expect($dto->tags)->toBe([]);
    });

    it('handles invalid JSON string as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => 'not-json{',
        ], validate: false);

        // Invalid JSON should silently return empty array, not throw
        expect($dto->tags)->toBe([]);
    });

    it('handles nested JSON structures', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => '{"level1":{"level2":{"key":"value"},"arr":[1,2,3]}}',
        ], validate: false);

        expect($dto->metadata)->toHaveKey('level1')
            ->and($dto->metadata['level1'])->toHaveKey('level2')
            ->and($dto->metadata['level1']['level2']['key'])->toBe('value')
            ->and($dto->metadata['level1']['arr'])->toBe([1, 2, 3]);
    });

    it('handles associative arrays from JSON', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => '{"key1":"value1","key2":"value2","nested":{"a":1}}',
        ], validate: false);

        expect($dto->metadata)->toBe([
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => ['a' => 1],
        ]);
    });

    it('handles arrays with special characters in JSON', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["hello <world>", "quote\\"test", "tab\\there"]',
        ], validate: false);

        expect($dto->tags)->toBe([
            'hello <world>',
            'quote"test',
            'tab	here',
        ]);
    });

    it('handles JSON null value in array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["a", null, "b"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', null, 'b']);
    });

    it('handles JSON numeric and boolean values', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => '{"int":42,"float":3.14,"bool":true,"null":null}',
        ], validate: false);

        expect($dto->metadata['int'])->toBe(42)
            ->and($dto->metadata['float'])->toBe(3.14)
            ->and($dto->metadata['bool'])->toBeTrue()
            ->and($dto->metadata['null'])->toBeNull();
    });

    it('handles actual array input (not JSON string)', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => ['already', 'an', 'array'],
        ], validate: false);

        expect($dto->tags)->toBe(['already', 'an', 'array']);
    });

    it('handles JSON array of objects', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => '[{"id":1},{"id":2}]',
        ], validate: false);

        expect($dto->metadata)->toBe([['id' => 1], ['id' => 2]]);
    });

    it('handles non-array JSON (scalar JSON) as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '"just-a-string"',
        ], validate: false);

        // json_decode returns a string, not an array — should return []
        expect($dto->tags)->toBe([]);
    });

    it('handles JSON number as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '12345',
        ], validate: false);

        // json_decode returns an int, not an array — should return []
        expect($dto->tags)->toBe([]);
    });

    it('handles JSON boolean as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => 'true',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    it('handles deeply nested JSON (3+ levels)', function (): void {
        $json = '{"a":{"b":{"c":{"d":{"e":"deep"}}}}}';
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => $json,
        ], validate: false);

        expect($dto->metadata['a']['b']['c']['d']['e'])->toBe('deep');
    });

    it('handles empty JSON object string as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'metadata' => '{}',
        ], validate: false);

        expect($dto->metadata)->toBe([]);
    });

    it('handles empty JSON array string', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '[]',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });
});
