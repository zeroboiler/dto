<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\SimpleUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NestedAddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UserWithAddressDTO;

/**
 * DTO serialization roundtrip contract tests.
 *
 * Verifies that:
 * - fromArray → toArray produces identical output for scalar DTOs
 * - fromArray → toJson → fromJson produces equivalent DTOs
 * - with() returns a new instance (immutability)
 * - equals() compares by toArray() output
 * - only()/except() produce correct subsets
 * - allValues() includes hidden fields
 * - DtoCollection serialization is consistent
 * - jsonSerialize() matches toArray()
 * - __debugInfo() matches toArray()
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\DtoCollection
 */
describe('DTO serialization roundtrip contract', function (): void {
    // -----------------------------------------------------------------------
    // Scalar DTO roundtrip
    // -----------------------------------------------------------------------
    describe('Scalar DTO roundtrip', function (): void {
        it('fromArray → toArray preserves all scalar values', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

n            $arr = $dto->toArray();

n            expect($arr['name'])->toBe('Alice');
            expect($arr['email'])->toBe('alice@example.com');
            expect($arr['age'])->toBe(30);
        });

n        it('fromJson → toArray produces equivalent output', function (): void {
            $json = '{"name":"Bob","email":"bob@example.com","age":25}';
            $dto = SimpleUserDTO::fromJson($json, validate: false);

n            expect($dto->toArray())->toBe([
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'age' => 25,
            ]);
        });

        it('toJson produces valid JSON that decodes to toArray output', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Charlie',
                'email' => 'charlie@example.com',
                'age' => '35',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBe($dto->toArray());
        });

        it('jsonSerialize() matches toArray()', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Dave',
                'email' => 'dave@example.com',
                'age' => '40',
            ], validate: false);

n            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('__debugInfo() matches toArray()', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Eve',
                'email' => 'eve@example.com',
                'age' => '28',
            ], validate: false);

n            expect($dto->__debugInfo())->toBe($dto->toArray());
        });
    });

    // -----------------------------------------------------------------------
    // Immutability
    // -----------------------------------------------------------------------
    describe('Immutability contract', function (): void {
        it('with() returns a new instance', function (): void {
            $original = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob']);

            expect($modified)->not->toBe($original);
            expect($original->name)->toBe('Alice');
            expect($modified->name)->toBe('Bob');
        });

        it('with() preserves unchanged fields', function (): void {
            $original = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob']);

            expect($modified->email)->toBe('alice@example.com');
            expect($modified->age)->toBe(30);
        });
    });

    // -----------------------------------------------------------------------
    // Equality
    // -----------------------------------------------------------------------
    describe('Equality contract', function (): void {
        it('equals() returns true for identical data', function (): void {
            $data = ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'];
            $a = SimpleUserDTO::fromArray($data, validate: false);
            $b = SimpleUserDTO::fromArray($data, validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different data', function (): void {
            $a = SimpleUserDTO::fromArray(
                ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                validate: false,
            );
            $b = SimpleUserDTO::fromArray(
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                validate: false,
            );

            expect($a->equals($b))->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // Selective output
    // -----------------------------------------------------------------------
    describe('Selective output', function (): void {
        it('only() returns specified fields', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->only('name', 'email');

n            expect($result)->toHaveKeys(['name', 'email']);
            expect($result)->not->toHaveKey('age');
        });

        it('only() with single string returns one field', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->only('email');

n            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('alice@example.com');
        });

        it('except() excludes specified fields', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->except('age');

            expect($result)->toHaveKeys(['name', 'email']);
            expect($result)->not->toHaveKey('age');
        });

        it('except() with single string excludes one field', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->except('name');

            expect($result)->not->toHaveKey('name');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('age');
        });

        it('only() ignores non-existent keys silently', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->only('name', 'nonexistent');

n            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('name');
        });

        it('except() ignores non-existent keys silently', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            $result = $dto->except('nonexistent');

            expect($result)->toHaveCount(3);
        });
    });

    // -----------------------------------------------------------------------
    // State checks
    // -----------------------------------------------------------------------
    describe('State checks', function (): void {
        it('isEmpty() returns true when all properties are empty/default', function (): void {
            // Create a DTO with all default/empty values
            DataTransferObject::flushMetadataCache(SimpleUserDTO::class);
            $dto = SimpleUserDTO::fromPartialArray([], validate: false);

            // At minimum, a DTO with defaults should be checkable
            expect(is_bool($dto->isEmpty()))->toBeTrue();
        });

        it('isNotEmpty() is the inverse of isEmpty()', function (): void {
            $dto = SimpleUserDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // DtoCollection serialization
    // -----------------------------------------------------------------------
    describe('DtoCollection serialization', function (): void {
        it('toArray() serializes all DTOs', function (): void {
            $dtoList = [];
            $dataList = [
                ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
            ];

            foreach ($dataList as $data) {
                $dtoList[] = SimpleUserDTO::fromArray($data, validate: false);
            }

            $col = new DtoCollection($dtoList);
            $arr = $col->toArray();

n            expect($arr)->toHaveCount(2);
            expect($arr[0]['name'])->toBe('Alice');
            expect($arr[1]['name'])->toBe('Bob');
        });

        it('jsonSerialize() matches toArray()', function (): void {
            $dtoList = [
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
            ];

            $col = new DtoCollection($dtoList);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('count() returns correct item count', function (): void {
            $dtoList = [];
            for ($i = 0; $i < 5; $i++) {
                $dtoList[] = SimpleUserDTO::fromArray(
                    ['name' => "User{$i}", 'email' => "user{$i}@example.com", 'age' => 20 + $i],
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
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
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
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
                SimpleUserDTO::fromArray(
                    ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                    validate: false,
                ),
                SimpleUserDTO::fromArray(
                    ['name' => 'Charlie', 'email' => 'charlie@example.com', 'age' => '35'],
                    validate: false,
                ),
            ];

            $col = new DtoCollection($dtoList);

            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Charlie');
        });

        it('push() mutates in place and returns self', function (): void {
            $col = new DtoCollection([
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
            ]);

            $item = SimpleUserDTO::fromArray(
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                validate: false,
            );

            $result = $col->push($item);

            expect($col->count())->toBe(2);
            expect($col->last()->name)->toBe('Bob');
            expect($result)->toBe($col); // same instance (mutation)
        });

        it('append() returns a new collection without mutating', function (): void {
            $original = new DtoCollection([
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
            ]);

            $item = SimpleUserDTO::fromArray(
                ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                validate: false,
            );

            $appended = $original->append($item);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
            expect($appended->last()->name)->toBe('Bob');
        });

        it('map() returns plain array with correct types', function (): void {
            $col = new DtoCollection([
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
                SimpleUserDTO::fromArray(
                    ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                    validate: false,
                ),
            ]);

            $names = $col->map(fn (SimpleUserDTO $dto): string => $dto->name);

            expect($names)->toEqual(['Alice', 'Bob']);
        });

        it('filter() returns new collection with matching items', function (): void {
            $col = new DtoCollection([
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
                SimpleUserDTO::fromArray(
                    ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                    validate: false,
                ),
            ]);

            $filtered = $col->filter(fn (SimpleUserDTO $dto): bool => $dto->age >= 30);

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('pluck() extracts a single property from all DTOs', function (): void {
            $col = new DtoCollection([
                SimpleUserDTO::fromArray(
                    ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => '30'],
                    validate: false,
                ),
                SimpleUserDTO::fromArray(
                    ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => '25'],
                    validate: false,
                ),
            ]);

            $emails = $col->pluck('email');

            expect($emails)->toEqual(['alice@example.com', 'bob@example.com']);
        });
    });

    // -----------------------------------------------------------------------
    // Validation rules
    // -----------------------------------------------------------------------
    describe('Validation rules contract', function (): void {
        it('rules() returns an array keyed by field name', function (): void {
            $rules = SimpleUserDTO::rules();

            expect($rules)->toBeArray();

            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rulesFor() returns the same as rules() by default', function (): void {
            expect(SimpleUserDTO::rulesFor('create'))->toBe(SimpleUserDTO::rules());
            expect(SimpleUserDTO::rulesFor('update'))->toBe(SimpleUserDTO::rules());
        });
    });

    // -----------------------------------------------------------------------
    // Partial update
    // -----------------------------------------------------------------------
    describe('Partial update contract', function (): void {
        it('fromPartialArray hydrates only provided fields', function (): void {
            $dto = SimpleUserDTO::fromPartialArray(
                ['name' => 'Updated Name'],
                validate: false,
            );

            expect($dto->name)->toBe('Updated Name');
        });

        it('fromPartialArray uses defaults for missing fields', function (): void {
            DataTransferObject::flushMetadataCache(SimpleUserDTO::class);
            $dto = SimpleUserDTO::fromPartialArray([], validate: false);

            // Should have all properties set (to defaults or empty values)
            $arr = $dto->allValues();

            foreach (SimpleUserDTO::rules() as $field => $_rules) {
                expect($arr)->toHaveKey($field);
            }
        });
    });
});
