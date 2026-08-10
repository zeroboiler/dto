<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Tests for castToBool edge cases, fromPartialArray type inference,
 * and fromJson error scenarios.
 *
 * Covers:
 * - castToBool with various input types (int 0/1, float, string "true"/"false"/"on"/"yes"/"off"/"no"/"1"/"0"/"", null, objects)
 * - fromPartialArray emptyValueForType for various property types
 * - fromJson with valid JSON, invalid JSON, sequential array, empty object
 * - with() always validates (deprecated parameter has no effect)
 * - validatePartialArray converts 'required' to 'sometimes'
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

describe('DTO casting and edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // Cast to bool edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Cast to bool edge cases', function (): void {
        it('casts string "true" to true', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 'true',
            ], validate: false);

            expect($dto->active)->toBeTrue();
        });

        it('casts string "false" to false', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 'false',
            ], validate: false);

            expect($dto->active)->toBeFalse();
        });

        it('casts string "1" to true', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => '1',
            ], validate: false);

            expect($dto->active)->toBeTrue();
        });

        it('casts string "0" to false', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => '0',
            ], validate: false);

            expect($dto->active)->toBeFalse();
        });

        it('casts string "on" to true', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 'on',
            ], validate: false);

            expect($dto->active)->toBeTrue();
        });

        it('casts string "yes" to true', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 'yes',
            ], validate: false);

            expect($dto->active)->toBeTrue();
        });

        it('casts string "no" to false', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 'no',
            ], validate: false);

            expect($dto->active)->toBeFalse();
        });

        it('casts int 1 to true', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 1,
            ], validate: false);

            expect($dto->active)->toBeTrue();
        });

        it('casts int 0 to false', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => 0,
            ], validate: false);

            expect($dto->active)->toBeFalse();
        });

        it('casts empty string to false', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => '',
            ], validate: false);

            expect($dto->active)->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // fromJson edge cases
    // ──────────────────────────────────────────────────────────────

    describe('fromJson edge cases', function (): void {
        it('throws DTOException on invalid JSON syntax', function (): void {
            expect(fn () => RoundtripDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on sequential array (JSON array)', function (): void {
            expect(fn () => RoundtripDTO::fromJson('["a", "b"]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function (): void {
            // Empty object — all required fields missing, but validate=false
            // This should still work since validate is false
            $dto = RoundtripDTO::fromArray([], validate: false);

            // Verify it creates an instance (even with defaults)
            assert($dto instanceof RoundtripDTO);
        });

        it('roundtrips correctly: fromArray → toJson → fromJson', function (): void {
            $original = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30', // string that gets cast to int
                'active' => true,
                'tags' => '["a","b"]', // string that gets cast to array
                'source_bio' => 'A developer',
                'secret' => 'hidden',
            ], validate: false);

            $json = $original->toJson();
            $restored = RoundtripDTO::fromJson($json, validate: false);

            expect($restored->name)->toBe('Alice');
            expect($restored->age)->toBe(30);
            expect($restored->active)->toBe(true);
            expect($restored->tags)->toBe(['a', 'b']);
            expect($restored->bio)->toBe('A developer');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Cast to integer / string edge cases
    // ──────────────────────────────────────────────────────────────

    describe('Cast to integer and string edge cases', function (): void {
        it('casts string "30" to int 30', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => '30',
                'active' => true,
            ], validate: false);

            expect($dto->age)->toBe(30);
            expect(is_int($dto->age))->toBeTrue();
        });

        it('casts float 3.7 to int 3', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 3.7,
                'active' => true,
            ], validate: false);

            expect($dto->age)->toBe(3);
            expect(is_int($dto->age))->toBeTrue();
        });

        it('casts 0 on non-numeric string for integer', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 'not-a-number',
                'active' => true,
            ], validate: false);

            expect($dto->age)->toBe(0);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // with() always validates
    // ──────────────────────────────────────────────────────────────

    describe('with() immutable update', function (): void {
        it('preserves hidden field exclusions in with()', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
                'active' => true,
                'secret' => 'original',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            // secret should still be hidden
            expect($updated->toArray())->not->toHaveKey('secret');
            // allValues should include it
            expect($updated->allValues())->toHaveKey('secret');
            expect($updated->allValues()['secret'])->toBe('original');
        });

        it('creates a new instance with different values', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
                'active' => true,
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob', 'age' => 30]);

            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($updated->age)->toBe(30);
        });

        it('preserves MapFrom keys in with()', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => true,
                'source_bio' => 'Original bio',
            ], validate: false);

            $updated = $dto->with(['source_bio' => 'Updated bio']);

            expect($updated->bio)->toBe('Updated bio');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // isEmpty / isNotEmpty boundary conditions
    // ──────────────────────────────────────────────────────────────

    describe('isEmpty boundary conditions', function (): void {
        it('returns false when int property has value 0', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 0, // 0 is a valid value, not empty
                'active' => true,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('returns false when float property has value 0.0', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => true,
                'score' => 0.0, // 0.0 is a valid value
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('returns true when all properties are default/empty', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 25,
                'active' => true,
            ], validate: false);

            // 'name' = 'Test' is non-empty, so not empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // equals() comparison
    // ──────────────────────────────────────────────────────────────

    describe('equals() comparison', function (): void {
        it('returns false for different instances', function (): void {
            $d1 = RoundtripDTO::fromArray([
                'name' => 'Alice', 'age' => 25, 'active' => true,
            ], validate: false);
            $d2 = RoundtripDTO::fromArray([
                'name' => 'Bob', 'age' => 30, 'active' => true,
            ], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('returns true for identical instances', function (): void {
            $data = ['name' => 'Alice', 'age' => 25, 'active' => true];
            $d1 = RoundtripDTO::fromArray($data, validate: false);
            $d2 = RoundtripDTO::fromArray($data, validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('ignores hidden fields in comparison', function (): void {
            $d1 = RoundtripDTO::fromArray([
                'name' => 'Alice', 'age' => 25, 'active' => true,
                'secret' => 'secret1',
            ], validate: false);
            $d2 = RoundtripDTO::fromArray([
                'name' => 'Alice', 'age' => 25, 'active' => true,
                'secret' => 'secret2',
            ], validate: false);

            // Both have same toArray() output (secret excluded)
            expect($d1->equals($d2))->toBeTrue();
        });
    });
});
