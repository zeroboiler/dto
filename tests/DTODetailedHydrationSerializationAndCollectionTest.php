<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTO hydration, serialization, and edge cases', function () {
    describe('CreateUserDTO: fromArray', function () {
        it('hydrates with all fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John Doe',
                'phone_number' => '+1234567890',
                'password' => 'secret123',
                'tags' => ['admin', 'user'],
            ]);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('John Doe');
            expect($dto->status)->toBe('active');
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->password)->toBe('secret123');
            expect($dto->tags)->toBe(['admin', 'user']);
        });

        it('uses default values for missing optional fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ]);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('respects MapFrom for phone_number', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'phone_number' => '555-1234',
            ]);

            expect($dto->phone)->toBe('555-1234');
        });

        it('validates required fields', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'name' => 'John',
            ]))->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('validates email format', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'not-an-email',
                'name' => 'John',
            ]))->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('validates min/max constraints on name', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'A',
            ]))->toThrow(\Illuminate\Validation\ValidationException::class);

            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => str_repeat('X', 51),
            ]))->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('can skip validation', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'not-valid-email',
                'name' => 'X',
            ], validate: false);

            expect($dto->email)->toBe('not-valid-email');
            expect($dto->name)->toBe('X');
        });
    });

    describe('toArray / allValues / Hidden', function () {
        it('toArray excludes hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'password' => 'secret',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('status');
            expect($arr)->toHaveKey('phone');
            expect($arr)->toHaveKey('tags');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });
    });

    describe('toJson / jsonSerialize', function () {
        it('serializes to valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $json = $dto->toJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('test@example.com');
            expect($decoded['name'])->toBe('John');
        });

        it('jsonSerialize returns array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($dto->jsonSerialize())->toBeArray();
            expect($dto->jsonSerialize())->not->toHaveKey('password');
        });
    });

    describe('equals / isEmpty / isNotEmpty', function () {
        it('equals returns true for same data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals returns false for different data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'John',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'John',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('isNotEmpty returns true when data present', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('only / except', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only(['email', 'name']);

            expect($result)->toHaveCount(2);
            expect($result['email'])->toBe('test@example.com');
            expect($result['name'])->toBe('John');
        });

        it('only works with single string key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveCount(1);
        });

        it('except excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except(['email', 'phone']);

            expect($result)->not->toHaveKey('email');
            expect($result)->not->toHaveKey('phone');
            expect($result)->toHaveKey('name');
        });
    });

    describe('with (immutable update)', function () {
        it('creates new instance with overrides', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            $updated = $original->with(['name' => 'Jane']);

            expect($original->name)->toBe('John');
            expect($updated->name)->toBe('Jane');
            expect($updated->email)->toBe('test@example.com');
        });

        it('validates merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John',
            ], validate: false);

            expect(fn () => $dto->with(['email' => 'invalid']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('fromJson', function () {
        it('creates DTO from valid JSON', function () {
            $json = json_encode([
                'email' => 'test@example.com',
                'name' => 'John',
            ]);

            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
        });

        it('throws for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid}'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

        it('throws for sequential array JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('["a","b"]'))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });

        it('accepts empty array JSON', function () {
            $dto = CreateUserDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });
    });

    describe('rules / rulesFor / validateArray', function () {
        it('rules returns array with email and name rules', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect(in_array('required', $rules['email'], true))->toBeTrue();
            expect(in_array('email', $rules['email'], true))->toBeTrue();
            expect(in_array('min:2', $rules['name'], true))->toBeTrue();
        });

        it('rulesFor returns same rules by default', function () {
            expect(CreateUserDTO::rulesFor('create'))
                ->toBe(CreateUserDTO::rules());
        });

        it('validateArray returns validated data', function () {
            $result = CreateUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'John Doe',
            ]);

            expect($result)->toBeArray();
            expect($result['email'])->toBe('test@example.com');
        });

        it('validateArray throws for invalid data', function () {
            expect(fn () => CreateUserDTO::validateArray(['name' => 'John']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });
});

describe('DtoCollection edge cases', function () {
    it('create from DTO instances', function () {
        $dtoList = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $collection = new DtoCollection($dtoList);

        expect($collection->count())->toBe(2);
        expect($collection->isEmpty())->toBeFalse();
    });

    it('map returns transformed array', function () {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $emails = $collection->map(fn (CreateUserDTO $dto, int $i): string => $dto->email);

        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('filter returns new filtered collection', function () {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtos);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => str_starts_with($dto->name, 'A')
        );

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('push mutates and returns self', function () {
        $collection = new DtoCollection;
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);

        $result = $collection->push($dto);

        expect($result)->toBe($collection); // Same instance
        expect($collection->count())->toBe(1);
    });

    it('append returns new collection', function () {
        $collection = new DtoCollection;
        $dto = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false);

        $newCollection = $collection->append($dto);

        expect($collection->count())->toBe(0);
        expect($newCollection->count())->toBe(1);
    });

    it('merge combines two collections', function () {
        $a = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);
        $b = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ]);

        $merged = $a->merge($b);

        expect($merged->count())->toBe(2);
    });

    it('first / last return correct items', function () {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $collection = new DtoCollection($dtos);

        expect($collection->first()->name)->toBe('A');
        expect($collection->last()->name)->toBe('B');
    });

    it('first / last return null for empty collection', function () {
        $collection = new DtoCollection;

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('offsetGet / offsetExists / offsetUnset', function () {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $collection = new DtoCollection($dtos);

        expect($collection->offsetExists(0))->toBeTrue();
        expect($collection->offsetExists(5))->toBeFalse();
        expect($collection[0]->name)->toBe('A');
        expect($collection[1]->name)->toBe('B');

        unset($collection[0]);
        expect($collection->count())->toBe(1);
        // After unset + reindex, the remaining item should be at index 0
        expect($collection[0]->name)->toBe('B');
    });

    it('make factory creates collection', function () {
        $collection = DtoCollection::make([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);

        expect($collection->count())->toBe(1);
    });

    it('jsonSerialize returns array of arrays', function () {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ];

        $collection = new DtoCollection($dtos);

        $result = $collection->jsonSerialize();

        expect($result)->toBeArray();
        expect($result[0])->toHaveKey('email');
    });

    it('rejects non-DTO instances', function () {
        expect(fn () => new DtoCollection([new \stdClass]))
            ->toThrow(\InvalidArgumentException::class);
    });
});
