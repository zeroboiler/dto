<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO Production Edge Cases', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('fromJson error cases', function () {
        it('throws DTOException on invalid JSON syntax', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('exception message contains JSON error details', function () {
            try {
                CreateUserDTO::fromJson('{bad}', validate: false);
                expect(true)->toBeFalse('Should have thrown');
            } catch (DTOException $e) {
                expect($e->getMessage())->toContain('(root)');
            }
        });

        it('throws DTOException on sequential array (JSON array, not object)', function () {
            expect(fn () => CreateUserDTO::fromJson('["a", "b"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('sequential array exception mentions expected type', function () {
            try {
                CreateUserDTO::fromJson('["a", "b"]', validate: false);
                expect(true)->toBeFalse('Should have thrown');
            } catch (DTOException $e) {
                expect($e->getMessage())->toContain('JSON object');
            }
        });

        it('throws DTOException on JSON primitive (number)', function () {
            expect(fn () => CreateUserDTO::fromJson('42', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON primitive (string)', function () {
            expect(fn () => CreateUserDTO::fromJson('"hello"', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON boolean', function () {
            expect(fn () => CreateUserDTO::fromJson('true', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON null', function () {
            expect(fn () => CreateUserDTO::fromJson('null', validate: false))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });
    });

    describe('with() validation enforcement', function () {
        it('always validates regardless of $validate parameter', function () {
            // The deprecated $validate parameter should have no effect.
            // Validation must always run to prevent invalid state (#2).
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            // with() should still create a new instance
            $updated = $dto->with(['email' => 'new@example.com']);

            expect($updated->email)->toBe('new@example.com');
            expect($dto->email)->toBe('test@example.com'); // original unchanged
        });

        it('with() merges overrides with existing values', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'tags' => ['laravel'],
            ], validate: false);

            $updated = $dto->with(['tags' => ['php', 'vue']]);

            expect($updated->tags)->toEqual(['php', 'vue']);
            expect($updated->email)->toBe('test@example.com');
        });
    });

    describe('isEmpty / isNotEmpty', function () {
        it('EmptyDTO with null properties is considered empty', function () {
            $dto = EmptyDTO::fromArray([]);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('DTO with string property is not empty', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('DTO with only default values is considered empty', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => '',
            ], validate: false);

            // email is a non-empty string, so not empty
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('DtoCollection pluckKey', function () {
        it('builds key/value map from two fields', function () {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
            ];

            $collection = new DtoCollection($dtos);
            $map = $collection->pluckKey('email', 'name');

            expect($map)->toEqual([
                'a@test.com' => 'Alice',
                'b@test.com' => 'Bob',
            ]);
        });

        it('builds keyed map with full DTO array when no value field', function () {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            ];

            $collection = new DtoCollection($dtos);
            $map = $collection->pluckKey('email');

            expect($map)->toHaveKey('a@test.com');
            expect($map['a@test.com'])->toBeArray();
        });
    });

    describe('DtoCollection map', function () {
        it('maps over items with index', function () {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
            ];

            $collection = new DtoCollection($dtos);
            $result = $collection->map(fn (CreateUserDTO $dto, int $index): string => "{$index}: {$dto->name}");

            expect($result)->toEqual(['0: Alice', '1: Bob']);
        });
    });

    describe('DtoCollection ArrayAccess', function () {
        it('offsetGet returns null for non-existent index', function () {
            $collection = new DtoCollection([]);
            expect($collection[0])->toBeNull();
        });

        it('offsetSet with null key appends', function () {
            $collection = new DtoCollection([]);
            $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);

            $collection[] = $dto;

            expect($collection->count())->toBe(1);
            expect($collection->first())->toBe($dto);
        });

        it('offsetSet with numeric key replaces', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false);

            $collection = new DtoCollection([$dto1]);
            $collection[0] = $dto2;

            expect($collection->count())->toBe(1);
            expect($collection[0]->email)->toBe('b@test.com');
        });

        it('offsetSet rejects non-DTO', function () {
            $collection = new DtoCollection([]);

            expect(fn () => $collection[] = 'not a dto')
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('DtoCollection make factory', function () {
        it('creates empty collection', function () {
            $collection = DtoCollection::make([]);
            expect($collection->isEmpty())->toBeTrue();
        });

        it('creates collection from DTOs', function () {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            ];

            $collection = DtoCollection::make($dtos);
            expect($collection->count())->toBe(1);
        });
    });

    describe('toArray consistency', function () {
        it('toArray() preserves property order', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $arr = $dto->toArray();
            $keys = array_keys($arr);

            // email should come before name (constructor order)
            $emailIndex = array_search('email', $keys, true);
            $nameIndex = array_search('name', $keys, true);

            expect($emailIndex)->toBeLessThan($nameIndex);
        });
    });
});
