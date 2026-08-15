<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;

describe('DTO V17 — Hydration Pipeline And Type System Contract', function () {
    describe('fromArray hydration correctness', function () {
        it('respects explicit null values for nullable properties', function () {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Test',
                'nickname' => null,   // explicitly null — should be preserved as null
                'email' => null,      // explicitly null — should be preserved as null
            ], validate: false);

            expect($dto->name)->toBe('Test');
            expect($dto->nickname)->toBeNull();
            expect($dto->email)->toBeNull();
        });

        it('applies DefaultValue when source key is absent', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 30,
                'active' => true,
                // score, tags, bio absent → defaults applied
            ], validate: false);

            expect($dto->score)->toBe(0.0);
            expect($dto->tags)->toBe([]);
            expect($dto->role)->toBe('user');
        });

        it('applies MapFrom with dot notation', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('applies Cast integer to string input', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => '25',  // string that should be cast to int
                'active' => true,
            ], validate: false);

            expect($dto->age)->toBeInt();
            expect($dto->age)->toBe(25);
        });

        it('applies Cast array from JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '["a","b"]',
            ], validate: false);

            expect($dto->tags)->toBeArray();
            expect($dto->tags)->toBe(['a', 'b']);
        });

        it('applies Cast boolean from string "true"', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'Test',
                'is_admin' => 'true',
            ], validate: false);

            expect($dto->is_admin)->toBeTrue();
        });

        it('applies Cast boolean from int 0', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'Test',
                'is_admin' => 0,
            ], validate: false);

            expect($dto->is_admin)->toBeFalse();
        });
    });

    describe('fromPartialArray PATCH semantics', function () {
        it('only hydrates provided fields, defaults for rest', function () {
            $dto = RoundtripDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validate: false);

            expect($dto->name)->toBe('Updated Name');
            // Missing fields get defaults or type-appropriate empties
            expect($dto->role)->toBe('user'); // from DefaultValue
        });

        it('preserves existing values when merging via with()', function () {
            $dto1 = RoundtripDTO::fromArray([
                'name' => 'Original',
                'age' => 25,
                'active' => true,
            ], validate: false);

            $dto2 = $dto1->with(['name' => 'Modified']);

            expect($dto2->name)->toBe('Modified');
            expect($dto2->age)->toBe(25);
            expect($dto2->active)->toBeTrue();
            expect($dto2)->not->toBe($dto1); // immutability
        });
    });

    describe('serialization contract', function () {
        it('toArray excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson produces valid JSON', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 30,
                'active' => true,
                'score' => 95.5,
                'tags' => ['a', 'b'],
                'bio' => 'Hello',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['name'])->toBe('Test');
            expect($decoded['age'])->toBe(30);
        });

        it('only() returns subset of fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 30,
                'active' => true,
            ], validate: false);

            $subset = $dto->only(['name', 'age']);

            expect($subset)->toHaveKey('name');
            expect($subset)->toHaveKey('age');
            expect($subset)->not->toHaveKey('active');
        });

        it('except() excludes specified fields', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'Test',
                'age' => 30,
                'active' => true,
            ], validate: false);

            $subset = $dto->except(['age']);

            expect($subset)->toHaveKey('name');
            expect($subset)->not->toHaveKey('age');
        });
    });

    describe('equality and state checks', function () {
        it('equals returns true for identical DTOs', function () {
            $data = ['name' => 'Test', 'age' => 30, 'active' => true];
            $dto1 = RoundtripDTO::fromArray($data, validate: false);
            $dto2 = RoundtripDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 30, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => true], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty detects DTO with all default/empty values', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            // AllDefaultsDTO: name='default-name', count=0, active=false, items=[]
            // name is 'default-name' (non-empty string) → not empty
            // count is 0 → not empty (0 is a valid meaningful value)
            // So isEmpty should be false for this DTO since name and count are non-empty
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('fromJson edge cases', function () {
        it('rejects sequential arrays', function () {
            expect(fn () => RoundtripDTO::fromJson('["a","b","c"]', validate: false))
                ->toThrow(DTOException::class, 'Expected a JSON object');
        });

        it('accepts empty object', function () {
            // EmptyDTO has all optional/defaulted properties
            if (class_exists(EmptyDTO::class)) {
                $dto = EmptyDTO::fromJson('{}', validate: false);
                expect($dto)->toBeInstanceOf(EmptyDTO::class);
            }
        });

        it('rejects invalid JSON', function () {
            expect(fn () => RoundtripDTO::fromJson('not-json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('rejects JSON that decodes to a scalar', function () {
            expect(fn () => RoundtripDTO::fromJson('"just-a-string"', validate: false))
                ->toThrow(DTOException::class, 'Expected a JSON object');
        });
    });

    describe('rules and rulesFor contract', function () {
        it('rules returns array of arrays', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toBeArray();
        });

        it('rulesFor defaults to same as rules', function () {
            $rules = RoundtripDTO::rules();
            $rulesForCreate = RoundtripDTO::rulesFor('create');
            $rulesForUpdate = RoundtripDTO::rulesFor('update');

            expect($rulesForCreate)->toBe($rules);
            expect($rulesForUpdate)->toBe($rules);
        });

        it('Contains expected validation rules for attributes', function () {
            $rules = ScalarConstraintsDTO::rules();

            expect($rules['is_admin'])->toContain('boolean');
            expect($rules['score'])->toContain('integer');
            expect($rules['uuid'])->toContain('uuid');
            expect($rules['terms'])->toContain('accepted');
            expect($rules['secret'])->toContain('prohibited');
            expect($rules['code'])->toContain('size:3');
        });
    });

    describe('DtoCollection immutability and operations', function () {
        it('append returns new collection without mutating original', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => false], validate: false);

            $original = new DtoCollection([$dto1]);
            $appended = $original->append($dto2);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
            expect($appended[1]->name)->toBe('B');
        });

        it('merge combines two collections', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => false], validate: false);
            $dto3 = RoundtripDTO::fromArray(['name' => 'C', 'age' => 35, 'active' => true], validate: false);

            $collection = new DtoCollection([$dto1]);
            $other = new DtoCollection([$dto2, $dto3]);
            $merged = $collection->merge($other);

            expect($merged->count())->toBe(3);
            expect($collection->count())->toBe(1); // original unchanged
        });

        it('filter returns new collection with matching items', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $active = $collection->filter(fn (DataTransferObject $dto): bool => $dto->active);

            expect($active->count())->toBe(1);
            expect($active[0]->name)->toBe('A');
        });

        it('pluck extracts single property values', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => 30, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $names = $collection->pluck('name');

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('map transforms each item', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 30, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $ages = $collection->map(fn (DataTransferObject $dto, int $i): int => $dto->age);

            expect($ages)->toBe([25, 30]);
        });

        it('first and last return correct items', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'First', 'age' => 1, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Last', 'age' => 2, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->first()->name)->toBe('First');
            expect($collection->last()->name)->toBe('Last');
        });

        it('first and last return null for empty collection', function () {
            $collection = new DtoCollection;

            expect($collection->first())->toBeNull();
            expect($collection->last())->toBeNull();
        });

        it('offsetUnset re-indexes the collection', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => 1, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => 2, 'active' => false], validate: false);
            $dto3 = RoundtripDTO::fromArray(['name' => 'C', 'age' => 3, 'active' => true], validate: false);

            $collection = new DtoCollection([$dto1, $dto2, $dto3]);
            unset($collection[0]);

            // After re-indexing, what was index 1 should now be index 0
            expect($collection[0]->name)->toBe('B');
            expect($collection[1]->name)->toBe('C');
            expect($collection->count())->toBe(2);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not-a-dto']))
                ->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
        });

        it('jsonSerialize returns array of arrays', function () {
            $dto = RoundtripDTO::fromArray(['name' => 'Test', 'age' => 25, 'active' => true], validate: false);
            $collection = new DtoCollection([$dto]);

            $serialized = $collection->jsonSerialize();

            expect($serialized)->toBeArray();
            expect($serialized[0])->toBeArray();
            expect($serialized[0]['name'])->toBe('Test');
        });

        it('toArrayBy re-keys by property value', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => 30, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $keyed = $collection->toArrayBy('name');

            expect($keyed)->toHaveKey('Alice');
            expect($keyed)->toHaveKey('Bob');
        });

        it('toDictionary extracts key-value pairs', function () {
            $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'age' => 25, 'active' => true], validate: false);
            $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'age' => 30, 'active' => false], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $dict = $collection->toDictionary('name', 'age');

            expect($dict['Alice'])->toBe(25);
            expect($dict['Bob'])->toBe(30);
        });
    });

    describe('DTOManager facade delegation', function () {
        it('make creates DTO from array', function () {
            $manager = new DTOManager;
            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
        });

        it('rules delegates to DTO class', function () {
            $manager = new DTOManager;
            $rules = $manager->rules(CreateUserDTO::class);

            expect($rules)->toBe(CreateUserDTO::rules());
        });

        it('rulesFor delegates with action context', function () {
            $manager = new DTOManager;
            $rules = $manager->rulesFor(CreateUserDTO::class, 'update');

            expect($rules)->toBe(CreateUserDTO::rulesFor('update'));
        });

        it('makeFromJson creates DTO from JSON string', function () {
            $manager = new DTOManager;
            $dto = $manager->makeFromJson(
                RoundtripDTO::class,
                json_encode(['name' => 'Test', 'age' => 30, 'active' => true])
            );

            expect($dto)->toBeInstanceOf(RoundtripDTO::class);
            expect($dto->name)->toBe('Test');
        });

        it('makeFromJson throws DTOException for invalid JSON', function () {
            $manager = new DTOManager;

            expect(fn () => $manager->makeFromJson(RoundtripDTO::class, 'invalid'))
                ->toThrow(DTOException::class);
        });

        it('fromPartialArray delegates correctly', function () {
            $manager = new DTOManager;
            $dto = $manager->fromPartialArray(RoundtripDTO::class, ['name' => 'Partial']);

            expect($dto)->toBeInstanceOf(RoundtripDTO::class);
            expect($dto->name)->toBe('Partial');
        });
    });

    describe('metadata cache TTL behavior', function () {
        it('flushMetadataCache clears all entries', function () {
            // Force metadata resolution to populate cache
            RoundtripDTO::rules();
            CreateUserDTO::rules();

            // Flush
            DataTransferObject::flushMetadataCache();

            // Re-resolve — should work without error
            expect(RoundtripDTO::rules())->toBeArray();
            expect(CreateUserDTO::rules())->toBeArray();
        });

        it('flushMetadataCache with specific class only clears that class', function () {
            RoundtripDTO::rules();
            CreateUserDTO::rules();

            // Flush only one
            DataTransferObject::flushMetadataCache(RoundtripDTO::class);

            // The other should still be cached and resolve correctly
            expect(CreateUserDTO::rules())->toBeArray();
        });
    });
});
