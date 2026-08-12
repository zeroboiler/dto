<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;
use Illuminate\Validation\ValidationException;

describe('Cross-Fixture Integration Tests', function () {
    describe('RoundtripDTO serialization cycle', function () {
        it('fromArray → toArray is a perfect roundtrip', function () {
            $data = [
                'name' => 'Alice',
                'age' => 30,
                'active' => true,
                'score' => 95.5,
                'tags' => ['php', 'laravel'],
                'source_bio' => 'Software engineer',
                'secret' => 'hidden-value',
                'role' => 'admin',
            ];

            $dto = RoundtripDTO::fromArray($data);
            $result = $dto->toArray();

            // secret should be excluded (#[Hidden])
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('age');
            expect($result)->toHaveKey('active');
            expect($result)->toHaveKey('score');
            expect($result)->toHaveKey('tags');
            expect($result)->toHaveKey('bio');
            expect($result)->toHaveKey('role');
            expect($result)->not->toHaveKey('secret');

            expect($result['name'])->toBe('Alice');
            expect($result['age'])->toBe(30);
            expect($result['active'])->toBeTrue();
            expect($result['score'])->toBe(95.5);
            expect($result['tags'])->toBe(['php', 'laravel']);
            expect($result['bio'])->toBe('Software engineer');
            expect($result['role'])->toBe('admin');
        });

        it('allValues includes hidden fields', function () {
            $data = [
                'name' => 'Bob',
                'age' => 25,
                'active' => false,
                'secret' => 's3cret',
            ];

            $dto = RoundtripDTO::fromArray($data);
            $result = $dto->allValues();

            expect($result)->toHaveKey('secret');
            expect($result['secret'])->toBe('s3cret');
        });

        it('with() → toArray preserves defaults and hidden exclusions', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Charlie',
                'age' => 28,
                'active' => true,
            ]);

            $updated = $dto->with(['name' => 'David']);
            $result = $updated->toArray();

            expect($result['name'])->toBe('David');
            expect($result['age'])->toBe(28);
            expect($result['role'])->toBe('user'); // default preserved
            expect($result['tags'])->toBe([]);     // default preserved
            expect($result)->not->toHaveKey('secret');
        });

        it('equals() works correctly', function () {
            $data = ['name' => 'Test', 'age' => 20, 'active' => true];
            $dto1 = RoundtripDTO::fromArray($data);
            $dto2 = RoundtripDTO::fromArray($data);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('isEmpty/isNotEmpty boundary conditions', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 0,
                'active' => false,
                'score' => 0.0,
            ]);

            // name is non-empty, so isNotEmpty should be true
            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });

        it('MapFrom correctly maps source key to property name', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Eve',
                'age' => 35,
                'active' => true,
                'source_bio' => 'Designer',
            ]);

            expect($dto->bio)->toBe('Designer');
            expect($dto->toArray())->toHaveKey('bio');
            expect($dto->toArray())->not->toHaveKey('source_bio');
        });

        it('Cast integer correctly transforms string to int', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Frank',
                'age' => '42', // string input
                'active' => true,
            ]);

            expect($dto->age)->toBe(42);
            expect($dto->age)->toBeInt();
        });

        it('Cast array correctly transforms JSON string to array', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Grace',
                'age' => 30,
                'active' => true,
                'tags' => '["a","b"]', // JSON string input
            ]);

            expect($dto->tags)->toBe(['a', 'b']);
            expect($dto->tags)->toBeArray();
        });
    });

    describe('DotNotationDTO nested key mapping', function () {
        it('maps nested dot-notation keys correctly', function () {
            $data = [
                'user' => [
                    'profile' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                    ],
                ],
                'contact_email' => 'john@example.com',
            ];

            $dto = DotNotationDTO::fromArray($data);

            expect($dto->firstName)->toBe('John');
            expect($dto->lastName)->toBe('Doe');
            expect($dto->email)->toBe('john@example.com');
        });

        it('fails validation when required dot-notation key is missing', function () {
            expect(fn () => DotNotationDTO::fromArray([
                'user' => [
                    'profile' => [
                        'lastName' => 'Doe',
                    ],
                ],
            ]))->toThrow(ValidationException::class);
        });

        it('serializes to flat array (property names, not source keys)', function () {
            $dto = DotNotationDTO::fromArray([
                'user' => [
                    'profile' => [
                        'firstName' => 'Jane',
                        'lastName' => 'Smith',
                    ],
                ],
            ]);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('firstName');
            expect($arr)->toHaveKey('lastName');
            expect($arr)->not->toHaveKey('user');
        });
    });

    describe('PartialDefaultValueDTO interaction', function () {
        it('applies DefaultValue for missing fields in fromPartialArray', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Alice',
            ]);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('default@example.com');
            expect($dto->role)->toBe('viewer');
            expect($dto->isActive)->toBeTrue();
            expect($dto->score)->toBe(100);
            expect($dto->optionalNote)->toBeNull();
        });

        it('respects MapFrom with DefaultValue', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Bob',
                'user_role' => 'admin', // MapFrom('user_role') → $role
            ]);

            expect($dto->role)->toBe('admin');
        });

        it('overrides DefaultValue when value is explicitly provided', function () {
            $dto = PartialDefaultValueDTO::fromPartialArray([
                'name' => 'Charlie',
                'email' => 'charlie@test.com',
                'score' => 50,
            ]);

            expect($dto->email)->toBe('charlie@test.com');
            expect($dto->score)->toBe(50);
        });
    });

    describe('MixedCollectionDTO hydration', function () {
        it('hydrates NestedArray as plain array of DTOs', function () {
            $data = [
                'orderId' => 'ORD-001',
                'items' => [
                    ['street' => '123 Main', 'city' => 'Istanbul'],
                    ['street' => '456 Oak', 'city' => 'Ankara'],
                ],
                'orders' => [
                    ['name' => 'Item A', 'price' => 10],
                    ['name' => 'Item B', 'price' => 20],
                ],
            ];

            $dto = MixedCollectionDTO::fromArray($data, validate: false);

            // items should be plain array of AddressDTO
            expect($dto->items)->toBeArray();
            expect($dto->items)->toHaveCount(2);
            expect($dto->items[0])->toBeInstanceOf(AddressDTO::class);
            expect($dto->items[0]->street)->toBe('123 Main');

            // orders should be DtoCollection
            expect($dto->orders)->toBeInstanceOf(DtoCollection::class);
            expect($dto->orders->count())->toBe(2);
        });
    });

    describe('Empty and edge-case DTOs', function () {
        it('EmptyDTO with nullable fields can be instantiated from empty array', function () {
            $dto = EmptyDTO::fromArray([]);
            // foo and bar are nullable with null default — both null means isEmpty should be true
            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
            expect($dto->isEmpty())->toBeTrue();
        });

        it('NoConstructorDTO can be instantiated from empty array', function () {
            $dto = NoConstructorDTO::fromArray([]);
            expect($dto->toArray())->toBe([]);
        });

        it('MinimalDTO requires both its fields', function () {
            expect(fn () => MinimalDTO::fromArray(['name' => 'Test']))->toThrow(ValidationException::class);
            expect(fn () => MinimalDTO::fromArray([]))->toThrow(ValidationException::class);

            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'val']);
            expect($dto->name)->toBe('Test');
            expect($dto->value)->toBe('val');
        });
    });

    describe('UnionTypeDTO', function () {
        it('accepts int value for union type', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'test-1',
                'identifier' => 42,
            ], validate: false);

            expect($dto->identifier)->toBe(42);
        });

        it('accepts string value for union type', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'test-2',
                'identifier' => 'hello',
            ], validate: false);

            expect($dto->identifier)->toBe('hello');
        });
    });

    describe('DateCastDTO', function () {
        it('casts date string to Carbon instance', function () {
            $dto = DateCastDTO::fromArray([
                'event_date' => '1990-01-15',
            ], validate: false);

            expect($dto->event_date)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($dto->event_date->format('Y-m-d'))->toBe('1990-01-15');
        });

        it('serializes Carbon back to ISO string', function () {
            $dto = DateCastDTO::fromArray([
                'event_date' => '1990-01-15',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['event_date'])->toBeString();
            expect($arr['event_date'])->toBe('1990-01-15T00:00:00+00:00');
        });
    });

    describe('ArrayCastDTO', function () {
        it('casts JSON string to array', function () {
            $dto = ArrayCastDTO::fromArray([
                'name' => 'Test',
                'metadata' => '{"key":"value"}',
            ], validate: false);

            expect($dto->metadata)->toBe(['key' => 'value']);
        });

        it('serializes array back to array (not JSON string)', function () {
            $dto = ArrayCastDTO::fromArray([
                'name' => 'Test',
                'metadata' => '{"key":"value"}',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['metadata'])->toBe(['key' => 'value']);
        });
    });

    describe('Cross-fixture rules generation consistency', function () {
        it('all DTOs produce non-empty rules arrays', function () {
            $dtos = [
                CreateUserDTO::class,
                RoundtripDTO::class,
                ValidationTestDTO::class,
                DateCastDTO::class,
                MinimalDTO::class,
            ];

            foreach ($dtos as $dto) {
                $rules = $dto::rules();
                expect($rules)->toBeArray();
            }
        });

        it('rulesFor returns same as rules for default action', function () {
            $createRules = CreateUserDTO::rules();
            $defaultRules = CreateUserDTO::rulesFor('create');

            expect($defaultRules)->toBe($createRules);
        });
    });

    describe('Cross-fixture only/except consistency', function () {
        it('only returns subset of fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'active' => true,
                'source_bio' => 'Bio',
            ]);

            $result = $dto->only('name', 'age');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKeys(['name', 'age']);
        });

        it('except removes specified fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'active' => true,
            ]);

            $result = $dto->except('age');
            expect($result)->not->toHaveKey('age');
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('active');
        });
    });
});
