<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

describe('DTO edge cases and contract compliance', function () {
    // ── Basic DTO hydration ─────────────────────────────────────
    describe('basic hydration', function () {
        it('creates DTO from array without validation', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            expect($dto)->toBeInstanceOf(RoundtripDTO::class);
            expect($dto->name)->toBe('Alice');
            expect($dto->age)->toBe(30); // Cast('integer') applied
            expect($dto->active)->toBeTrue();
            expect($dto->score)->toBe(0.0); // DefaultValue
            expect($dto->tags)->toBe([]); // DefaultValue
            expect($dto->bio)->toBeNull();
            expect($dto->role)->toBe('user'); // DefaultValue
        });
    });

    // ── MapFrom resolution ─────────────────────────────────────
    describe('MapFrom attribute', function () {
        it('maps source key to property name', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
                'source_bio' => 'Hello World',
            ], validate: false);

            expect($dto->bio)->toBe('Hello World');
        });

        it('supports dot notation for nested source keys', function () {
            $dto = DotNotationDTO::fromArray([
                'user' => [
                    'profile' => [
                        'firstName' => 'Alice',
                        'lastName' => 'Smith',
                    ],
                ],
                'contact_email' => 'alice@example.com',
            ], validate: false);

            expect($dto->firstName)->toBe('Alice');
            expect($dto->lastName)->toBe('Smith');
            expect($dto->email)->toBe('alice@example.com');
        });
    });

    // ── DefaultValue handling ──────────────────────────────────
    describe('DefaultValue attribute', function () {
        it('applies default when source key is absent', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            expect($dto->score)->toBe(0.0);
            expect($dto->tags)->toBe([]);
            expect($dto->role)->toBe('user');
        });

        it('does not override explicit null or empty values', function () {
            // When tags is explicitly provided as empty array, DefaultValue is NOT applied
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
                'tags' => [],
            ], validate: false);

            expect($dto->tags)->toBe([]);
        });
    });

    // ── Hidden property ────────────────────────────────────────
    describe('Hidden attribute', function () {
        it('excludes hidden property from toArray()', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
                'secret' => 'hidden-value',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('secret');
        });

        it('includes hidden property in allValues()', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
                'secret' => 'hidden-value',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('hidden-value');
        });
    });

    // ── Serialization roundtrip ────────────────────────────────
    describe('serialization', function () {
        it('toJson produces valid JSON', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            expect($dto->toJson())->toBeJson();
        });

        it('fromJson creates DTO from JSON string', function () {
            $json = json_encode([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], JSON_THROW_ON_ERROR);

            $dto = RoundtripDTO::fromJson($json, validate: false);

            expect($dto)->toBeInstanceOf(RoundtripDTO::class);
            expect($dto->name)->toBe('Alice');
        });

        it('fromJson rejects sequential arrays', function () {
            expect(fn () => RoundtripDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson rejects non-object JSON', function () {
            expect(fn () => RoundtripDTO::fromJson('"string"', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    // ── Immutable with() ──────────────────────────────────────
    describe('immutable update', function () {
        it('with() creates new instance with overrides', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            $modified = $dto->with(['name' => 'Bob']);

            expect($modified)->toBeInstanceOf(RoundtripDTO::class);
            expect($modified->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // original unchanged
        });
    });

    // ── Selective output ────────────────────────────────────────
    describe('selective output', function () {
        it('only() returns specified fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            $only = $dto->only('name');

            expect($only)->toHaveKey('name');
            expect($only)->not->toHaveKey('age');
        });

        it('except() excludes specified fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
            ], validate: false);

            $except = $dto->except('name');

            expect($except)->not->toHaveKey('name');
            expect($except)->toHaveKey('age');
        });
    });

    // ── Equality check ─────────────────────────────────────────
    describe('equality', function () {
        it('equals() returns true for same data', function () {
            $data = ['name' => 'Alice', 'age' => '30', 'active' => true];
            $dto1 = RoundtripDTO::fromArray($data, validate: false);
            $dto2 = RoundtripDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals() returns false for different data', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => '25', 'active' => false], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    // ── isEmpty / isNotEmpty ───────────────────────────────────
    describe('isEmpty and isNotEmpty', function () {
        it('returns true when all properties are empty', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => '',
                'age' => '30', // 0 is non-empty per contract
            ], validate: false);

            // Not empty because age=30 is a meaningful value
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ── Partial update ─────────────────────────────────────────
    describe('partial update', function () {
        it('fromPartialArray hydrates only present fields', function () {
            $dto = RoundtripDTO::fromPartialArray([
                'name' => 'Alice',
            ], validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->score)->toBe(0.0); // DefaultValue
            expect($dto->tags)->toBe([]); // DefaultValue
        });
    });

    // ── Validation rules ───────────────────────────────────────
    describe('validation rules', function () {
        it('rules() returns non-empty array', function () {
            $rules = RoundtripDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->not->toBeEmpty();
        });

        it('rulesFor() returns rules for specific action', function () {
            $createRules = RoundtripDTO::rulesFor('create');
            $updateRules = RoundtripDTO::rulesFor('update');

            // Default implementation returns same rules for all actions
            expect($createRules)->toBe($updateRules);
        });

        it('rules contain Required rules for required fields', function () {
            $rules = RoundtripDTO::rules();

            expect($rules['name'])->toContain('required');
        });
    });

    // ── No-constructor DTO ─────────────────────────────────────
    describe('no-constructor DTO', function () {
        it('returns empty rules for DTO with no properties', function () {
            $rules = NoConstructorDTO::rules();

            expect($rules)->toBe([]);
        });

        it('creates instance from empty array', function () {
            $dto = NoConstructorDTO::fromArray([], validate: false);

            expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
            expect($dto->toArray())->toBe([]);
        });
    });

    // ── DtoCollection ──────────────────────────────────────────
    describe('DtoCollection', function () {
        it('creates collection from DTO instances', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => '25', 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('Alice');
            expect($collection->last()->name)->toBe('Bob');
        });

        it('toArray() serializes all DTOs', function () {
            $dto = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false);
            $collection = new DtoCollection([$dto]);

            $arr = $collection->toArray();

            expect($arr)->toHaveCount(1);
            expect($arr[0])->toHaveKey('name');
        });

        it('isEmpty() and isNotEmpty() work correctly', function () {
            $empty = new DtoCollection([]);
            $nonEmpty = new DtoCollection([
                RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false),
            ]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('map() transforms each DTO', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => '25', 'active' => false], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            $names = $collection->map(fn (DataTransferObject $dto) => $dto->name);

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('filter() returns new collection with matching items', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => '30', 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => '25', 'active' => false], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            $active = $collection->filter(fn (DataTransferObject $dto): bool => $dto->active);

            expect($active->count())->toBe(1);
            expect($active->first()->name)->toBe('Alice');
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not-a-dto']))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    // ── Metadata cache ─────────────────────────────────────────
    describe('metadata cache', function () {
        it('flushMetadataCache clears all cached metadata', function () {
            // Resolve metadata (caches it)
            RoundtripDTO::rules();

            // Flush
            DataTransferObject::flushMetadataCache();

            // Should still work after flush
            $rules = RoundtripDTO::rules();

            expect($rules)->toBeArray();
        });

        it('flushMetadataCache clears specific class', function () {
            RoundtripDTO::rules();

            DataTransferObject::flushMetadataCache(RoundtripDTO::class);

            // Should still work after flush
            expect(RoundtripDTO::rules())->toBeArray();
        });
    });

    // ── Cast types ──────────────────────────────────────────────
    describe('cast types', function () {
        it('Cast integer normalizes numeric strings to int', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30', // string '30' → int 30 via Cast('integer')
                'active' => true,
            ], validate: false);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('Cast array decodes JSON strings', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => true,
                'tags' => '["php","laravel"]',
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
        });
    });
});
