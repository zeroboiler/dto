<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * DTO serialization roundtrip contract tests.
 *
 * Verifies that:
 * - fromArray → toArray preserves all scalar values (string, int, float, bool)
 * - fromArray → toJson → fromJson produces equivalent DTOs
 * - with() returns a new instance (immutability)
 * - equals() compares by toArray() output
 * - only()/except() produce correct subsets
 * - Hidden fields are excluded from toArray() but present in allValues()
 * - DtoCollection serialization is consistent
 * - jsonSerialize() matches toArray()
 * - __debugInfo() matches toArray()
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO
 * @see \ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO
 */
describe('DTO serialization roundtrip contract', function (): void {
    // -----------------------------------------------------------------------
    // Scalar DTO roundtrip
    // -----------------------------------------------------------------------
    describe('Scalar DTO roundtrip', function (): void {
        it('fromArray → toArray preserves all scalar values', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['name'])->toBe('Alice');
            expect($arr['age'])->toBe(30);
            expect($arr['active'])->toBe(true);
        });

        it('fromJson → toArray produces equivalent output', function (): void {
            $json = '{"name":"Bob","age":25,"active":true}';
            $dto = RoundtripDTO::fromJson($json, validate: false);

            expect($dto->toArray()['name'])->toBe('Bob');
            expect($dto->toArray()['age'])->toBe(25);
            expect($dto->toArray()['active'])->toBe(true);
        });

        it('toJson produces valid JSON that decodes to toArray output', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Charlie',
                'age' => '35',
                'active' => 'false',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBe($dto->toArray());
        });

        it('jsonSerialize() matches toArray()', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Dave',
                'age' => '40',
                'active' => '1',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('__debugInfo() matches toArray()', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Eve',
                'age' => '28',
                'active' => '0',
            ], validate: false);

            expect($dto->__debugInfo())->toBe($dto->toArray());
        });

        it('roundtrip preserves defaults and cast results', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Frank',
                'age' => '22',
                'active' => 'true',
                'score' => '99.5',
                'tags' => 'a,b,c',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['name'])->toBe('Frank');
            expect($arr['age'])->toBe(22);
            expect($arr['active'])->toBe(true);
            expect($arr['score'])->toBe(99.5);
            expect($arr['tags'])->toBe(['a', 'b', 'c']);
            expect($arr['role'])->toBe('user'); // default value
            expect($arr)->not->toHaveKey('secret'); // Hidden field excluded
        });

        it('hidden field is excluded from toArray but present in allValues', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'HiddenTest',
                'age' => '30',
                'active' => 'true',
                'secret' => 's3cret',
            ], validate: false);

            $arr = $dto->toArray();
            $all = $dto->allValues();

            expect($arr)->not->toHaveKey('secret');
            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('s3cret');
        });

        it('MapFrom maps source key to property correctly', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'MappedTest',
                'age' => '25',
                'active' => 'true',
                'source_bio' => 'Hello World',
            ], validate: false);

            expect($dto->bio)->toBe('Hello World');
            expect($dto->toArray()['bio'])->toBe('Hello World');
        });
    });

    // -----------------------------------------------------------------------
    // Immutability
    // -----------------------------------------------------------------------
    describe('Immutability contract', function (): void {
        it('with() returns a new instance', function (): void {
            $original = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob']);

            expect($modified)->not->toBe($original);
            expect($original->name)->toBe('Alice');
            expect($modified->name)->toBe('Bob');
        });

        it('with() preserves unchanged fields', function (): void {
            $original = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob']);

            expect($modified->age)->toBe(30);
            expect($modified->active)->toBe(true);
            expect($modified->role)->toBe('user'); // default preserved
        });

        it('with() handles multiple fields at once', function (): void {
            $original = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob', 'age' => '25']);

            expect($modified->name)->toBe('Bob');
            expect($modified->age)->toBe(25);
            expect($modified->active)->toBe(true); // unchanged
        });
    });

    // -----------------------------------------------------------------------
    // Equality
    // -----------------------------------------------------------------------
    describe('Equality contract', function (): void {
        it('equals() returns true for identical data', function (): void {
            $data = ['name' => 'Alice', 'age' => '30', 'active' => 'true'];
            $a = RoundtripDTO::fromArray($data, validate: false);
            $b = RoundtripDTO::fromArray($data, validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different data', function (): void {
            $a = RoundtripDTO::fromArray(
                ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                validate: false,
            );
            $b = RoundtripDTO::fromArray(
                ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                validate: false,
            );

            expect($a->equals($b))->toBeFalse();
        });

        it('equals() returns false for different types', function (): void {
            $dto = RoundtripDTO::fromArray(
                ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                validate: false,
            );
            $minimal = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

            expect($dto->equals($minimal))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Selective output
    // -----------------------------------------------------------------------
    describe('Selective output', function (): void {
        it('only() returns specified fields', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->only('name', 'age');

            expect($result)->toHaveKeys(['name', 'age']);
            expect($result)->not->toHaveKey('active');
        });

        it('only() with single string returns one field', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->only('name');

            expect($result)->toHaveCount(1);
            expect($result['name'])->toBe('Alice');
        });

        it('except() excludes specified fields', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->except('active', 'score');

            expect($result)->toHaveKeys(['name', 'age']);
            expect($result)->not->toHaveKey('active');
        });

        it('except() with single string excludes one field', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->except('name');

            expect($result)->not->toHaveKey('name');
            expect($result)->toHaveKey('age');
            expect($result)->toHaveKey('active');
        });

        it('only() ignores non-existent keys silently', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->only('name', 'nonexistent');

            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('name');
        });

        it('except() ignores non-existent keys silently', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            $result = $dto->except('nonexistent');

            // Should have name, age, active, score, tags, bio, role (all visible fields)
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('age');
        });
    });

    // -----------------------------------------------------------------------
    // State checks
    // -----------------------------------------------------------------------
    describe('State checks', function (): void {
        it('isNotEmpty() returns true for populated DTO', function (): void {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => '30',
                'active' => 'true',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });

        it('MinimalDTO with required fields is not empty', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => 'ok'], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // DtoCollection serialization
    // -----------------------------------------------------------------------
    describe('DtoCollection serialization', function (): void {
        it('toArray() serializes all DTOs', function (): void {
            $dataList = [
                ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
            ];
            $dtoList = array_map(
                fn (array $d): RoundtripDTO => RoundtripDTO::fromArray($d, validate: false),
                $dataList,
            );

            $col = new DtoCollection($dtoList);
            $arr = $col->toArray();

            expect($arr)->toHaveCount(2);
            expect($arr[0]['name'])->toBe('Alice');
            expect($arr[1]['name'])->toBe('Bob');
        });

        it('jsonSerialize() matches toArray()', function (): void {
            $dtoList = [
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
            ];

            $col = new DtoCollection($dtoList);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('count() returns correct item count', function (): void {
            $dtoList = [];
            for ($i = 0; $i < 5; $i++) {
                $dtoList[] = RoundtripDTO::fromArray(
                    ['name' => "User{$i}", 'age' => (string) (20 + $i), 'active' => 'true'],
                    validate: false,
                );
            }

            $col = new DtoCollection($dtoList);

            expect($col->count())->toBe(5);
            expect(count($col))->toBe(5);
        });

        it('isEmpty() and isNotEmpty() work correctly', function (): void {
            $empty = new DtoCollection([]);
            $nonEmpty = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
            ]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('first() and last() return correct items', function (): void {
            $dtoList = [
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
                RoundtripDTO::fromArray(
                    ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                    validate: false,
                ),
                RoundtripDTO::fromArray(
                    ['name' => 'Charlie', 'age' => '35', 'active' => 'true'],
                    validate: false,
                ),
            ];

            $col = new DtoCollection($dtoList);

            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Charlie');
        });

        it('push() mutates in place and returns self', function (): void {
            $col = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
            ]);

            $item = RoundtripDTO::fromArray(
                ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                validate: false,
            );

            $result = $col->push($item);

            expect($col->count())->toBe(2);
            expect($col->last()->name)->toBe('Bob');
            expect($result)->toBe($col); // same instance (mutation)
        });

        it('append() returns a new collection without mutating', function (): void {
            $original = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
            ]);

            $item = RoundtripDTO::fromArray(
                ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                validate: false,
            );

            $appended = $original->append($item);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
            expect($appended->last()->name)->toBe('Bob');
        });

        it('map() returns plain array with correct types', function (): void {
            $col = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
                RoundtripDTO::fromArray(
                    ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                    validate: false,
                ),
            ]);

            $names = $col->map(fn (RoundtripDTO $dto): string => $dto->name);

            expect($names)->toEqual(['Alice', 'Bob']);
        });

        it('filter() returns new collection with matching items', function (): void {
            $col = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
                RoundtripDTO::fromArray(
                    ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                    validate: false,
                ),
            ]);

            $filtered = $col->filter(fn (RoundtripDTO $dto): bool => $dto->age >= 30);

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('pluck() extracts a single property from all DTOs', function (): void {
            $col = new DtoCollection([
                RoundtripDTO::fromArray(
                    ['name' => 'Alice', 'age' => '30', 'active' => 'true'],
                    validate: false,
                ),
                RoundtripDTO::fromArray(
                    ['name' => 'Bob', 'age' => '25', 'active' => 'false'],
                    validate: false,
                ),
            ]);

            $names = $col->pluck('name');

            expect($names)->toEqual(['Alice', 'Bob']);
        });
    });

    // -----------------------------------------------------------------------
    // Validation rules
    // -----------------------------------------------------------------------
    describe('Validation rules contract', function (): void {
        it('rules() returns an array keyed by field name', function (): void {
            $rules = RoundtripDTO::rules();

            expect($rules)->toBeArray();

            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rules() contains expected fields', function (): void {
            $rules = RoundtripDTO::rules();

            expect($rules)->toHaveKeys(['name', 'age', 'active']);
        });

        it('rulesFor() returns the same as rules() by default', function (): void {
            expect(RoundtripDTO::rulesFor('create'))->toBe(RoundtripDTO::rules());
            expect(RoundtripDTO::rulesFor('update'))->toBe(RoundtripDTO::rules());
        });
    });

    // -----------------------------------------------------------------------
    // Partial update
    // -----------------------------------------------------------------------
    describe('Partial update contract', function (): void {
        it('fromPartialArray hydrates only provided fields', function (): void {
            DataTransferObject::flushMetadataCache(RoundtripDTO::class);
            $dto = RoundtripDTO::fromPartialArray(
                ['name' => 'Updated Name'],
                validate: false,
            );

            expect($dto->name)->toBe('Updated Name');
        });

        it('fromPartialArray uses defaults for missing fields', function (): void {
            DataTransferObject::flushMetadataCache(RoundtripDTO::class);
            $dto = RoundtripDTO::fromPartialArray([], validate: false);

            $arr = $dto->allValues();

            // Should have all properties set (to defaults or null)
            foreach (RoundtripDTO::rules() as $field => $_rules) {
                expect($arr)->toHaveKey($field);
            }
        });
    });

    // -----------------------------------------------------------------------
    // MinimalDTO edge cases
    // -----------------------------------------------------------------------
    describe('MinimalDTO basic contract', function (): void {
        it('fromArray → toArray roundtrip for minimal DTO', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => 'ok'], validate: false);

            expect($dto->toArray())->toBe(['name' => 'test', 'value' => 'ok']);
        });

        it('equals() works for minimal DTO', function (): void {
            $data = ['name' => 'test', 'value' => 'ok'];
            $a = MinimalDTO::fromArray($data, validate: false);
            $b = MinimalDTO::fromArray($data, validate: false);

            expect($a->equals($b))->toBeTrue();
        });
    });
});
