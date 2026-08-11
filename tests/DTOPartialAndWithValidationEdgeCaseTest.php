<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DTO with() Validation and fromPartialArray DefaultValue Integration', function () {
    describe('with() validation rejection', function () {
        it('rejects invalid merged data in with()', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            // Try to set name to empty string (violates min:2)
            expect(fn () => $dto->with(['name' => '']))
                ->toThrow(ValidationException::class);
        });

        it('rejects age below min constraint in with()', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            expect(fn () => $dto->with(['age' => -1]))
                ->toThrow(ValidationException::class);
        });

        it('rejects score above max constraint in with()', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
                'score' => 50,
            ], validate: false);

            expect(fn () => $dto->with(['score' => 101]))
                ->toThrow(ValidationException::class);
        });

        it('accepts valid merged data in with()', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob', 'age' => 30]);

            expect($updated)->toBeInstanceOf(StrictValidationDTO::class);
            expect($updated->name)->toBe('Bob');
            expect($updated->age)->toBe(30);
            // Unchanged field preserved
            expect($updated->score)->toBe(50);
        });

        it('preserves original DTO instance (immutability)', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            // Original unchanged
            expect($dto->name)->toBe('Alice');
            expect($dto->age)->toBe(25);

            // New instance has updated value
            expect($updated->name)->toBe('Bob');
            expect($updated)->not->toBe($dto);
        });

        it('with() validate parameter has no effect (always validates)', function () {
            $dto = StrictValidationDTO::fromArray([
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            // Even with validate: false, with() should still validate
            // (the parameter is deprecated but kept for backward compat)
            expect(fn () => $dto->with(['name' => ''], validate: false))
                ->toThrow(ValidationException::class);
        });
    });

    describe('fromPartialArray with DefaultValue attribute', function () {
        it('applies DefaultValue for missing fields in partial update', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray(
                ['name' => 'Alice'],
                validate: false,
            );

            expect($dto->name)->toBe('Alice');
            // DefaultValue attribute should kick in for email
            expect($dto->email)->toBe('default@example.com');
            // DefaultValue attribute for role
            expect($dto->role)->toBe('viewer');
            // DefaultValue attribute for bool
            expect($dto->isActive)->toBeTrue();
            // DefaultValue attribute for int
            expect($dto->score)->toBe(100);
            // PHP constructor default for nullable without DefaultValue
            expect($dto->optionalNote)->toBeNull();
        });

        it('overrides DefaultValue when field is explicitly provided', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Alice',
                'email' => 'alice@real.com',
            ], validate: false);

            expect($dto->email)->toBe('alice@real.com');
        });

        it('respects MapFrom for partial fields', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Alice',
                'user_role' => 'admin',
            ], validate: false);

            expect($dto->role)->toBe('admin');
        });

        it('uses type-appropriate empty values for non-nullable fields without defaults', function () {
            // In a partial update, if 'name' is not provided but has no default,
            // fromPartialArray should use empty string for string type
            $dto = PartialDefaultValueDTO::fromPartialArray(
                [],
                validate: false,
            );

            // name has no DefaultValue attribute and no PHP default — gets empty string
            expect($dto->name)->toBe('');
        });

        it('preserves explicit null for nullable fields', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray(
                ['name' => 'Alice', 'optionalNote' => null],
                validate: false,
            );

            expect($dto->optionalNote)->toBeNull();
        });

        it('partial update with only defaults produces valid DTO', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray(
                ['name' => 'Bob'],
                validate: false,
            );

            expect($dto->toArray())->toBe([
                'name' => 'Bob',
                'email' => 'default@example.com',
                'role' => 'viewer',
                'isActive' => true,
                'score' => 100,
                'optionalNote' => null,
            ]);
        });
    });

    describe('fromPartialArray validation', function () {
        it('skips validation when validatePresent is false', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray(
                ['name' => ''],  // name requires min:3
                validatePresent: false,
            );

            expect($dto)->toBeInstanceOf(PartialDefaultValueDTO::class);
            expect($dto->name)->toBe('');
        });

        it('validates present fields when validatePresent is true', function () {
            expect(fn () => PartialDefaultValueDTO::fromPartialArray(
                ['name' => 'AB'],  // min:3 violation
                validatePresent: true,
            ))->toThrow(ValidationException::class);
        });

        it('accepts empty array when validatePresent is true', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray(
                [],
                validatePresent: true,
            );

            expect($dto)->toBeInstanceOf(PartialDefaultValueDTO::class);
            expect($dto->name)->toBe('');
        });
    });

    describe('Edge cases: empty and minimal DTOs with with()', function () {
        it('with() on EmptyDTO returns a new EmptyDTO', function () {
            $dto = EmptyDTO::fromArray([]);
            $updated = $dto->with([]);

            expect($updated)->toBeInstanceOf(EmptyDTO::class);
            expect($updated->toArray())->toBe([]);
        });

        it('fromPartialArray on EmptyDTO returns empty DTO', function () {
            $dto = EmptyDTO::fromPartialArray([], validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toBe([]);
        });

        it('equals() returns true for two EmptyDTOs', function () {
            $dto1 = EmptyDTO::fromArray([]);
            $dto2 = EmptyDTO::fromArray([]);

            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    describe('DtoCollection with partial-update-created DTOs', function () {
        it('can create a DtoCollection from partial-update DTOs', function () {
            $dto1 = PartialDefaultValueDTO::fromPartialArray(['name' => 'Alice'], validate: false);
            $dto2 = PartialDefaultValueDTO::fromPartialArray(['name' => 'Bob'], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->count())->toBe(2);
            expect($collection->pluck('name'))->toEqual(['Alice', 'Bob']);
        });

        it('filter() on collection returns correct subset', function () {
            $dto1 = PartialDefaultValueDTO::fromPartialArray(['name' => 'Alice', 'user_role' => 'admin'], validate: false);
            $dto2 = PartialDefaultValueDTO::fromPartialArray(['name' => 'Bob', 'user_role' => 'viewer'], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $admins = $collection->filter(fn ($dto) => $dto->role === 'admin');

            expect($admins->count())->toBe(1);
            expect($admins->first()->name)->toBe('Alice');
        });

        it('toArray() on collection serializes all DTOs', function () {
            $dto1 = PartialDefaultValueDTO::fromPartialArray(['name' => 'Alice'], validate: false);
            $collection = new DtoCollection([$dto1]);

            $arr = $collection->toArray();

            expect($arr)->toBeArray();
            expect($arr[0])->toHaveKey('name');
            expect($arr[0]['name'])->toBe('Alice');
        });
    });

    describe('UnionTypeDTO edge cases', function () {
        it('accepts string value for union type field', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'abc',
                'identifier' => 'xyz-123',
            ], validate: false);

            expect($dto->id)->toBe('abc');
            expect($dto->identifier)->toBe('xyz-123');
        });

        it('accepts int value for union type field', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'abc',
                'identifier' => 42,
            ], validate: false);

            expect($dto->identifier)->toBe(42);
        });

        it('serializes union type field correctly', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'abc',
                'identifier' => 42,
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['identifier'])->toBe(42);
        });
    });
});
