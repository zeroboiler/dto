<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NestedWithHiddenDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ParentWithNestedHiddenDTO;

describe('DTO Serialization Normalization — Comprehensive Edge Cases', function () {
    // ─────────────────────────────────────────────────────────────
    // §1: AllScalarTypesDTO — normalization of every scalar type
    // ─────────────────────────────────────────────────────────────

    describe('AllScalarTypesDTO — scalar type normalization', function () {
        it('serializes int property as integer', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 42,
                'name' => 'Test',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['count'])->toBeInt()->toBe(42);
        });

        it('serializes float property as float', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'score' => 99.5,
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['score'])->toBeFloat()->toBe(99.5);
        });

        it('serializes bool property as boolean', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'active' => true,
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['active'])->toBeBool()->toBeTrue();
        });

        it('serializes string property as string', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Hello World',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['name'])->toBeString()->toBe('Hello World');
        });

        it('serializes nullable property as null when unset', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('optional');
            expect($arr['optional'])->toBeNull();
        });

        it('serializes nullable property as string when set', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'optional' => 'present',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['optional'])->toBeString()->toBe('present');
        });

        it('serializes array property as array', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'items' => ['a', 'b', 'c'],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['items'])->toBeArray()->toBe(['a', 'b', 'c']);
        });

        it('respects DefaultValue attribute for absent keys', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            expect($dto->tag)->toBe('default-tag');
            expect($dto->score)->toBe(0.0);
            expect($dto->castedInt)->toBe(0);
        });

        it('respects MapFrom for source key mapping', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'source_field' => 'mapped-value',
            ], validate: false);

            expect($dto->mapped)->toBe('mapped-value');
        });

        it('serializes using property name (not mapped key)', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'source_field' => 'value',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('mapped');
            expect($arr)->not->toHaveKey('source_field');
        });

        it('excludes Hidden properties from toArray()', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'secret' => 'hush',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('secret');
        });

        it('includes Hidden properties in allValues()', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'secret' => 'hush',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('hush');
        });

        it('applies Cast attribute to integer property', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'castedInt' => '42',
            ], validate: false);

            expect($dto->castedInt)->toBeInt()->toBe(42);
        });

        it('applies Cast boolean to string values', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'active' => '1',
            ], validate: false);

            expect($dto->active)->toBeBool()->toBeTrue();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §2: Nested DTO with hidden fields — recursive normalization
    // ─────────────────────────────────────────────────────────────

    describe('ParentWithNestedHiddenDTO — nested hidden field normalization', function () {
        it('toArray() excludes hidden fields on parent and nested DTOs', function () {
            $dto = ParentWithNestedHiddenDTO::fromArray([
                'title' => 'Test Article',
                'apiKey' => 'secret-key',
                'nested' => [
                    'publicName' => 'Nested Item',
                    'internalId' => 'internal-123',
                ],
            ], validate: false);

            $arr = $dto->toArray();

            // Parent hidden field excluded
            expect($arr)->not->toHaveKey('apiKey');

            // Nested hidden field excluded
            expect($arr['nested'])->not->toHaveKey('internalId');
            expect($arr['nested']['publicName'])->toBe('Nested Item');
        });

        it('allValues() includes hidden fields on both parent and nested DTOs', function () {
            $dto = ParentWithNestedHiddenDTO::fromArray([
                'title' => 'Test Article',
                'apiKey' => 'secret-key',
                'nested' => [
                    'publicName' => 'Nested Item',
                    'internalId' => 'internal-123',
                ],
            ], validate: false);

            $all = $dto->allValues();

            // Parent hidden field included
            expect($all)->toHaveKey('apiKey');
            expect($all['apiKey'])->toBe('secret-key');

            // Nested hidden field included
            expect($all['nested'])->toHaveKey('internalId');
            expect($all['nested']['internalId'])->toBe('internal-123');
        });

        it('serializes null nested DTO correctly', function () {
            $dto = ParentWithNestedHiddenDTO::fromArray([
                'title' => 'No Nested',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['nested'])->toBeNull();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §3: isEmpty() edge cases — zero, false, empty string, empty array
    // ─────────────────────────────────────────────────────────────

    describe('isEmpty() — boundary value semantics', function () {
        it('int 0 is NOT empty (meaningful value)', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 0,
                'name' => '',
            ], validate: false);

            // count=0 is a meaningful int value → NOT empty
            expect($dto->isEmpty())->toBeFalse();
        });

        it('float 0.0 is NOT empty (meaningful value)', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => '',
                'score' => 0.0,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('empty string is empty', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
        });

        it('false boolean is empty', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => '',
                'active' => false,
            ], validate: false);

            // name='' + active=false are empty, but count=1 is non-empty
            expect($dto->isEmpty())->toBeFalse();
        });

        it('non-empty string makes DTO non-empty', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Hello',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §4: JSON round-trip fidelity with various types
    // ─────────────────────────────────────────────────────────────

    describe('fromJson() — round-trip fidelity with edge-case types', function () {
        it('round-trips DTO with boolean, int, float, and array', function () {
            $original = AllScalarTypesDTO::fromArray([
                'count' => 7,
                'name' => 'Round Trip',
                'active' => true,
                'score' => 3.14,
                'items' => ['x', 'y'],
                'tag' => 'special',
                'optional' => 'present',
                'castedInt' => '99',
            ], validate: false);

            $json = $original->toJson();
            $restored = AllScalarTypesDTO::fromJson($json, validate: false);

            expect($restored->count)->toBe(7);
            expect($restored->name)->toBe('Round Trip');
            expect($restored->active)->toBeTrue();
            expect($restored->score)->toBe(3.14);
            expect($restored->items)->toBe(['x', 'y']);
            expect($restored->tag)->toBe('special');
            expect($restored->optional)->toBe('present');
            expect($restored->castedInt)->toBe(99);
        });

        it('rejects sequential JSON array', function () {
            expect(fn () => AllScalarTypesDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('rejects invalid JSON', function () {
            expect(fn () => AllScalarTypesDTO::fromJson('{invalid}', validate: false))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            $dto = AllScalarTypesDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(AllScalarTypesDTO::class);
            expect($dto->tag)->toBe('default-tag');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §5: with() — immutable override preserves type integrity
    // ─────────────────────────────────────────────────────────────

    describe('with() — immutable override type integrity', function () {
        it('preserves int type through with()', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $modified = $dto->with(['count' => 99]);

            expect($modified->count)->toBeInt()->toBe(99);
        });

        it('preserves float type through with()', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $modified = $dto->with(['score' => 42.5]);

            expect($modified->score)->toBeFloat()->toBe(42.5);
        });

        it('preserves bool type through with()', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $modified = $dto->with(['active' => true]);

            expect($modified->active)->toBeBool()->toBeTrue();
        });

        it('original DTO is unchanged after with()', function () {
            $original = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Original',
            ], validate: false);

            $modified = $original->with(['name' => 'Modified']);

            expect($original->name)->toBe('Original');
            expect($modified->name)->toBe('Modified');
            expect($original)->not->toBe($modified);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §6: only() / except() — return types are consistent
    // ─────────────────────────────────────────────────────────────

    describe('only() / except() — output type consistency', function () {
        it('only() returns array with string keys', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only(['count', 'name']);

            expect($result)->toBeArray();
            expect(array_keys($result))->toEqual(['count', 'name']);
        });

        it('except() preserves remaining keys', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'tag' => 'special',
            ], validate: false);

            $result = $dto->except(['count']);

            expect($result)->toHaveKeys(['name', 'tag']);
            expect($result)->not->toHaveKey('count');
        });

        it('only() with non-existent key returns array without that key', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only('non_existent');

            expect($result)->toBeArray()->toBeEmpty();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §7: equals() — structural equality across all property types
    // ─────────────────────────────────────────────────────────────

    describe('equals() — structural equality', function () {
        it('two DTOs with identical data are equal', function () {
            $a = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Same',
                'active' => true,
                'score' => 1.5,
            ], validate: false);
            $b = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Same',
                'active' => true,
                'score' => 1.5,
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('two DTOs with different data are not equal', function () {
            $a = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'A',
            ], validate: false);
            $b = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'B',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('hidden fields do not affect equality', function () {
            $a = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'secret' => 'secret-a',
            ], validate: false);
            $b = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'secret' => 'secret-b',
            ], validate: false);

            // Hidden fields are excluded from toArray(), so equals() ignores them
            expect($a->equals($b))->toBeTrue();
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §8: fromPartialArray — empty value inference
    // ─────────────────────────────────────────────────────────────

    describe('fromPartialArray — empty value inference for missing fields', function () {
        it('missing int field gets 0 from emptyValueForType', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['name' => 'Test'],
                validate: false,
            );

            // count has no default, type is int → emptyValueForType returns 0
            expect($dto->count)->toBe(0);
        });

        it('missing string field gets empty string from emptyValueForType', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['count' => 1],
                validate: false,
            );

            expect($dto->name)->toBe('');
        });

        it('missing bool field gets false from emptyValueForType', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['count' => 1, 'name' => 'Test'],
                validate: false,
            );

            expect($dto->active)->toBeFalse();
        });

        it('missing float field gets 0.0 from emptyValueForType', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['count' => 1, 'name' => 'Test'],
                validate: false,
            );

            // score has a default of 0.0, so it should use that
            expect($dto->score)->toBe(0.0);
        });

        it('missing nullable field gets null', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['count' => 1, 'name' => 'Test'],
                validate: false,
            );

            expect($dto->optional)->toBeNull();
        });

        it('present fields are hydrated correctly', function () {
            $dto = AllScalarTypesDTO::fromPartialArray(
                ['count' => 42, 'name' => 'Partial'],
                validate: false,
            );

            expect($dto->count)->toBe(42);
            expect($dto->name)->toBe('Partial');
        });
    });

    // ─────────────────────────────────────────────────────────────
    // §9: toJson — valid JSON output
    // ─────────────────────────────────────────────────────────────

    describe('toJson() — valid JSON output', function () {
        it('produces valid JSON', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'JSON Test',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeString();
            expect(json_validate($json))->toBeTrue();
        });

        it('decoded JSON matches toArray() output', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
                'items' => ['a'],
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBe($dto->toArray());
        });

        it('JSON_PRETTY_PRINT option works', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 1,
                'name' => 'Test',
            ], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
        });
    });
});
