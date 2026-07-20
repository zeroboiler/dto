<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;

describe('invalid JSON in array cast (#1)', function (): void {
    it('throws DTOException for malformed JSON', function (): void {
        expect(fn (): ArrayCastDTO => ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '{invalid json}',
        ], validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for truncated JSON', function (): void {
        expect(fn (): ArrayCastDTO => ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '{"key":"value"',
        ], validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException when JSON decodes to a non-array (string)', function (): void {
        expect(fn (): ArrayCastDTO => ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '"just a string"',
        ], validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException when JSON decodes to a non-array (integer)', function (): void {
        expect(fn (): ArrayCastDTO => ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '42',
        ], validate: false))
            ->toThrow(DTOException::class);
    });

    it('still accepts valid JSON arrays', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '{"key": "value", "nested": [1, 2, 3]}',
        ], validate: false);

        expect($dto->tags)->toBe(['key' => 'value', 'nested' => [1, 2, 3]]);
    });

    it('still accepts empty string as empty array', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    it('still accepts native arrays', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => ['a', 'b', 'c'],
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b', 'c']);
    });

    it('includes property name in exception message', function (): void {
        try {
            ArrayCastDTO::fromArray([
                'name' => 'Test',
                'metadata' => '{broken',
            ], validate: false);
            expect(false)->toBeTrue('Should have thrown');
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('metadata');
        }
    });
});
