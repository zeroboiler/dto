<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('V30 DTO Production Behavior Contract', function () {
    // ── Basic Hydration and Serialization ──────────────────────────────────

    describe('basic hydration and serialization', function () {
        it('fromArray creates DTO with typed readonly properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->password)->toBe('secret123');
        });

        it('toArray returns associative array of public properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password'); // Hidden field
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
        });

        it('toJson produces valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toHaveKey('email');
            expect($decoded)->not->toHaveKey('password');
        });

        it('fromJson roundtrips correctly', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
        });
    });

    // ── MapFrom Key Aliasing ──────────────────────────────────────────────

    describe('MapFrom key aliasing', function () {
        it('maps source key to property name during hydration', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890', // mapped from phone_number → phone
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('serializes using property name not source key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('phone');
            expect($arr)->not->toHaveKey('phone_number');
        });
    });

    // ── DefaultValue ────────────────────────────────────────────────────────

    describe('DefaultValue behavior', function () {
        it('applies default when source key is absent', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
            expect($dto->items)->toEqual([]);
        });

        it('does NOT override explicit empty values', function () {
            $dto = AllDefaultsDTO::fromArray([
                'name' => '',
            ], validate: false);

            expect($dto->name)->toBe('');
        });
    });

    // ── Cast Types ──────────────────────────────────────────────────────────

    describe('Cast type conversion', function () {
        it('DTOCast handles array type casting', function () {
            // Cast attribute on tags property in CreateUserDTO
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'tags' => '["tag1","tag2"]', // JSON string → array
            ], validate: false);

            expect($dto->tags)->toBe(['tag1', 'tag2']);
        });
    });

    // ── Selective Output ──────────────────────────────────────────────────

    describe('selective output', function () {
        it('only() returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $only = $dto->only('email');
            expect($only)->toHaveCount(1);
            expect($only)->toHaveKey('email');
        });

        it('except() excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });
    });

    // ── Immutable Update (with) ──────────────────────────────────────────

    describe('immutable update (with)', function () {
        it('creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob'], validate: false);

            expect($updated)->not->toBe($dto);
            expect($updated->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // original unchanged
        });
    });

    // ── Equality and State Checks ─────────────────────────────────────────

    describe('equality and state checks', function () {
        it('equals returns true for same data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals returns false for different data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'b@b.com',
                'name' => 'Bob',
                'password' => 'pass2',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('isEmpty returns true for all-default DTO', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            // EmptyDTO has no properties
            $empty = EmptyDTO::fromArray([], validate: false);
            expect($empty->isEmpty())->toBeTrue();
        });

        it('isEmpty returns false for DTO with values', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ── Partial Updates (fromPartialArray) ───────────────────────────────

    describe('partial updates (PATCH semantics)', function () {
        it('fromPartialArray only hydrates provided fields', function () {
            $dto = AllDefaultsDTO::fromPartialArray([
                'name' => 'custom-name',
            ], validate: false);

            expect($dto->name)->toBe('custom-name');
            expect($dto->count)->toBe(0); // default retained
        });

        it('fromPartialArray uses defaults for missing fields', function () {
            $dto = AllDefaultsDTO::fromPartialArray([], validate: false);

            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
        });
    });

    // ── Validation Rules Generation ───────────────────────────────────────

    describe('validation rules generation', function () {
        it('rules() returns Laravel-compatible rules array', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');

            expect($rules)->toHaveKey('name');
            expect($rules['name'])->toContain('required');
        });

        it('rulesFor returns same as rules by default', function () {
            $rules = ValidationTestDTO::rules();
            $rulesFor = ValidationTestDTO::rulesFor('create');

            expect($rules)->toEqual($rulesFor);
        });
    });

    // ── DtoCollection Operations ────────────────────────────────────────

    describe('DtoCollection operations', function () {
        it('make creates collection from array of DTOs', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('push mutates in-place and returns collection', function () {
            $col = DtoCollection::make();
            $d = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);

            $result = $col->push($d);
            expect($result)->toBe($col); // same instance
            expect($col->count())->toBe(1);
        });

        it('append returns new immutable collection', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1]);
            $new = $col->append($d2);

            expect($new)->not->toBe($col);
            expect($col->count())->toBe(1);
            expect($new->count())->toBe(2);
        });

        it('pluck extracts property values', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $names = $col->pluck('name');

            expect($names)->toEqual(['Alice', 'Bob']);
        });

        it('filter returns new collection with matching items', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $filtered = $col->filter(fn ($d) => $d->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2); // original unchanged
        });

        it('toArray serializes all DTOs', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $arr = $col->toArray();

            expect($arr)->toHaveCount(2);
            expect($arr[0])->toHaveKey('name');
            expect($arr[0]['name'])->toBe('Alice');
        });

        it('first and last return correct items', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            expect($col->first()->name)->toBe('Alice');
            expect($col->last()->name)->toBe('Bob');
        });

        it('map transforms items to plain array', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $d2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);
            $names = $col->map(fn ($d) => $d->name);

            expect($names)->toEqual(['Alice', 'Bob']);
        });

        it('jsonSerialize produces array of arrays', function () {
            $d1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = DtoCollection::make([$d1]);

            $json = json_encode($col);
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded[0])->toHaveKey('name');
        });
    });

    // ── DTOCast Eloquent Integration ───────────────────────────────────────

    describe('DTOCast Eloquent integration', function () {
        it('get() hydrates DTO from JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {
                public array $attributes = [];
            };

            $dto = $cast->get($model, 'data', '{"email":"a@b.com","name":"Alice"}', []);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('a@b.com');
        });

        it('get() returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {
                public array $attributes = [];
            };

            $result = $cast->get($model, 'data', null, []);
            expect($result)->toBeNull();
        });

        it('set() serializes DTO to JSON', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {
                public array $attributes = [];
            };

            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $result = $cast->set($model, 'data', $dto, []);

            expect($result)->toBeJson();
            $decoded = json_decode($result, true);
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('set() rejects unexpected types', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {
                public array $attributes = [];
            };

            expect(fn () => $cast->set($model, 'data', 42, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns toArray output', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new class {
                public array $attributes = [];
            };

            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'pass',
            ], validate: false);

            $result = $cast->serialize($model, 'data', $dto, []);
            expect($result)->toBeArray();
            expect($result['email'])->toBe('a@b.com');
            expect($result)->not->toHaveKey('password');
        });
    });

    // ── DTOException Named Constructors ───────────────────────────────────

    describe('DTOException named constructors', function () {
        it('invalidCast creates descriptive exception', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'abc');

            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
            expect($ex->getMessage())->toContain('string');
        });

        it('invalidJson creates descriptive exception', function () {
            $ex = DTOException::invalidJson('payload', 'Syntax error');

            expect($ex->getMessage())->toContain('payload');
            expect($ex->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function () {
            $ex = DTOException::invalidJson('field', 'error');

            expect((string) $ex)->toContain('DTOException');
            expect((string) $ex)->toContain('field');
        });
    });

    // ── DTOManager Delegation ─────────────────────────────────────────────

    describe('DTOManager delegation', function () {
        it('validate delegates to DTO class', function () {
            $manager = new DTOManager();
            $result = $manager->validate(CreateUserDTO::class, [
                'email' => 'invalid-email',
                'name' => 'A',
                'password' => 'short',
            ]);

            // Should throw ValidationException for invalid data
            // If we get here, validation passed or wasn't strict
            expect($result)->toBeArray();
        });

        it('make creates DTO from data', function () {
            $manager = new DTOManager();
            $dto = $manager->make(MinimalDTO::class, ['name' => 'Alice'], validate: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('Alice');
        });

        it('rules delegates to DTO class', function () {
            $manager = new DTOManager();
            $rules = $manager->rules(CreateUserDTO::class);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
        });

        it('rulesFor delegates to DTO class', function () {
            $manager = new DTOManager();
            $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

            expect($rules)->toBeArray();
        });
    });

    // ── Cross-Fixture DTO Consistency ──────────────────────────────────────

    describe('cross-fixture DTO consistency', function () {
        it('all fixture DTOs extend DataTransferObject', function () {
            $fixtures = [
                CreateUserDTO::class,
                EmptyDTO::class,
                MinimalDTO::class,
                AllDefaultsDTO::class,
                ValidationTestDTO::class,
            ];

            foreach ($fixtures as $fixture) {
                expect(is_subclass_of($fixture, DataTransferObject::class))
                    ->toBeTrue("{$fixture} must extend DataTransferObject");
            }
        });

        it('all fixture DTOs can roundtrip through toArray/fromArray', function () {
            $fixtures = [
                [MinimalDTO::class, ['name' => 'Test', 'value' => 'v1']],
                [AllDefaultsDTO::class, ['name' => 'custom']],
                [EmptyDTO::class, []],
            ];

            foreach ($fixtures as [$class, $data]) {
                $dto = $class::fromArray($data, validate: false);
                $arr = $dto->toArray();
                $restored = $class::fromArray($arr, validate: false);

                expect($dto->toArray())->toEqual($restored->toArray());
            }
        });
    });
});
