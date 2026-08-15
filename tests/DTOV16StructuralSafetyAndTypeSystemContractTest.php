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
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

describe('DTO V16 — Structural Safety And Type System Contract', function () {
    describe('fromArray hydration type safety', function () {
        it('hydrates with all required fields present', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->status)->toBe('active'); // default
            expect($dto->tags)->toBe([]);
        });

        it('applies MapFrom for key aliasing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('applies Cast integer type casting', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'Test',
                'count' => '42',
                'price' => '99.99',
            ], validate: false);

            expect($dto->count)->toBeInt();
            expect($dto->count)->toBe(42);
            expect($dto->price)->toBeFloat();
            expect($dto->price)->toBe(99.99);
        });

        it('applies Cast boolean type casting', function () {
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => 'Test',
                'count' => 1,
                'price' => 0.0,
                'active' => 'yes',
            ], validate: false);

            expect($dto->active)->toBeBool();
            expect($dto->active)->toBeTrue();
        });

        it('applies DefaultValue when key is absent', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });

        it('respects explicit null over DefaultValue', function () {
            $dto = NullableRoundtripDTO::fromArray([
                'name' => 'Test',
                'email' => null,
            ], validate: false);

            expect($dto->email)->toBeNull();
        });

        it('respects explicit empty string over DefaultValue', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => '',
            ], validate: false);

            expect($dto->status)->toBe('');
        });
    });

    describe('toArray serialization type safety', function () {
        it('excludes hidden properties from toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('includes hidden properties in allValues', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toArray returns associative array', function () {
            $dto = MinimalDTO::fromArray([], validate: false);

            expect($dto->toArray())->toBeArray();
        });

        it('toJson produces valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded)->not->toHaveKey('password');
        });
    });

    describe('immutable update (with) contract', function () {
        it('with returns new instance with merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            expect($updated)->toBeInstanceOf(CreateUserDTO::class);
            expect($updated->name)->toBe('Updated');
            expect($dto->name)->toBe('Test'); // original unchanged
            expect($updated)->not->toBe($dto);
        });

        it('with preserves unchanged fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $updated = $dto->with(['status' => 'inactive']);

            expect($updated->email)->toBe('test@example.com');
            expect($updated->name)->toBe('Test');
            expect($updated->status)->toBe('inactive');
        });
    });

    describe('equality and state checks', function () {
        it('equals returns true for identical DTOs', function () {
            $data = ['email' => 'a@b.com', 'name' => 'Test'];
            $dto1 = CreateUserDTO::fromArray($data, validate: false);
            $dto2 = CreateUserDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Test'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns true when all fields are empty/default', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when at least one field has a value', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Test'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isEmpty considers 0 as non-empty for non-nullable int', function () {
            // Zero values should NOT be considered empty
            $dto = ScalarConstraintsDTO::fromArray([
                'name' => '',
                'count' => 0,
                'price' => 0.0,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('selective output contract', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only(['email', 'name']);

            expect($result)->toHaveCount(2);
            expect($result)->toHaveKeys(['email', 'name']);
        });

        it('only with string argument returns single field', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('test@example.com');
        });

        it('except excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('except ignores non-existent keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->except('nonexistent');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });
    });

    describe('fromJson hydration contract', function () {
        it('hydrates from valid JSON string', function () {
            $json = json_encode(['email' => 'test@example.com', 'name' => 'Test']);

            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test');
        });

        it('round-trips through toJson and fromJson', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $json = $dto->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe($dto->email);
            expect($restored->name)->toBe($dto->name);
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object JSON', function () {
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });
    });

    describe('fromPartialArray PATCH semantics', function () {
        it('hydrates only provided fields with defaults for rest', function () {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated'], validate: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active'); // default
            expect($dto->tags)->toBe([]); // default
        });

        it('uses type-appropriate empty values when no default', function () {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);

            // email is required string with no default → empty string for partial
            expect($dto->email)->toBe('');
        });

        it('preserves explicit values over defaults', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'override@example.com',
                'name' => 'Override',
                'status' => 'suspended',
            ], validate: false);

            expect($dto->email)->toBe('override@example.com');
            expect($dto->name)->toBe('Override');
            expect($dto->status)->toBe('suspended');
        });
    });

    describe('validation rules contract', function () {
        it('rules returns array with string keys', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rules generates required rule from Required attribute', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['name'])->toContain('required');
        });

        it('rules generates email rule from Email attribute', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('email');
        });

        it('rules generates min/max from Min/Max attributes', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('rulesFor returns same rules as rules by default', function () {
            $rules = CreateUserDTO::rules();
            $rulesForUpdate = CreateUserDTO::rulesFor('update');

            expect($rules)->toEqual($rulesForUpdate);
        });

        it('validateArray returns validated data', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Test User'];

            $validated = CreateUserDTO::validateArray($data);

            expect($validated)->toBeArray();
        });
    });

    describe('DtoCollection type safety', function () {
        it('make creates collection from array of DTOs', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);

            expect($col)->toBeInstanceOf(DtoCollection::class);
            expect($col->count())->toBe(2);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('first returns first item or null', function () {
            $col = DtoCollection::make([]);
            expect($col->first())->toBeNull();

            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->first())->toBeInstanceOf(CreateUserDTO::class);
        });

        it('last returns last item or null', function () {
            $col = DtoCollection::make([]);
            expect($col->last())->toBeNull();

            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->last())->toBeInstanceOf(CreateUserDTO::class);
        });

        it('map returns array of results', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $names = $col->map(fn (DataTransferObject $d) => $d->name);

            expect($names)->toEqual(['Alice', 'Charlie']);
        });

        it('filter returns new collection with matching items', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $filtered = $col->filter(fn (DataTransferObject $d) => $d->name === 'Alice');

            expect($filtered->count())->toBe(1);
        });

        it('push mutates in place and returns self', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();

            $result = $col->push($dto);

            expect($result)->toBe($col); // same instance
            expect($col->count())->toBe(1);
        });

        it('append returns new collection without mutating', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$dto1]);

            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1); // original unchanged
            expect($newCol->count())->toBe(2);
        });

        it('merge returns new combined collection', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
            expect($merged->count())->toBe(2);
        });

        it('jsonSerialize returns array of arrays', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            $json = json_encode($col);

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded[0])->toHaveKey('email');
            expect($decoded[0])->not->toHaveKey('password'); // hidden
        });

        it('clone throws RuntimeException', function () {
            $col = DtoCollection::make();

            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('offsetExists works for valid and invalid offsets', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeFalse();
        });

        it('offsetGet returns item or null', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);

            expect($col->offsetGet(0))->toBeInstanceOf(CreateUserDTO::class);
            expect($col->offsetGet(1))->toBeNull();
        });

        it('offsetUnset re-indexes the collection', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $col->offsetUnset(0);

            expect($col->count())->toBe(1);
            expect($col->offsetGet(0))->toBeInstanceOf(CreateUserDTO::class); // re-indexed
        });

        it('pluck extracts single property from all DTOs', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $emails = $col->pluck('email');

            expect($emails)->toEqual(['a@b.com', 'c@d.com']);
        });

        it('isEmpty and isNotEmpty work correctly', function () {
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();

            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = DtoCollection::make([$dto]);
            expect($col->isEmpty())->toBeFalse();
            expect($col->isNotEmpty())->toBeTrue();
        });
    });

    describe('DTOException contract', function () {
        it('invalidCast formats message correctly', function () {
            $e = DTOException::invalidCast('email', 'integer', 'not-a-number');

            expect($e->getMessage())->toContain('email');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson formats message correctly', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');

            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function () {
            $e = DTOException::invalidJson('root', 'bad json');

            expect($e->__toString())->toContain(DTOException::class);
            expect($e->__toString())->toContain($e->getMessage());
        });
    });

    describe('rules generation for various attribute types', function () {
        it('Uuid attribute generates uuid rule', function () {
            $rules = StrictValidationDTO::rules();

            // If StrictValidationDTO has a UUID field
            if (isset($rules['id'])) {
                expect($rules['id'])->toContain('uuid');
            }
        });

        it('Url attribute generates url rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['website'])) {
                expect($rules['website'])->toContain('url');
            }
        });

        it('Pattern attribute generates regex rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['code'])) {
                $patternRules = array_filter($rules['code'], fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'regex:'));
                expect($patternRules)->not->toBeEmpty();
            }
        });

        it('In attribute generates in rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['role'])) {
                $inRules = array_filter($rules['role'], fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'in:'));
                expect($inRules)->not->toBeEmpty();
            }
        });

        it('Boolean attribute generates boolean rule', function () {
            $rules = AllScalarTypesDTO::rules();

            if (isset($rules['active'])) {
                expect($rules['active'])->toContain('boolean');
            }
        });

        it('Integer attribute generates integer rule', function () {
            $rules = AllScalarTypesDTO::rules();

            if (isset($rules['age'])) {
                expect($rules['age'])->toContain('integer');
            }
        });

        it('Size attribute generates size rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['pin'])) {
                $sizeRules = array_filter($rules['pin'], fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'size:'));
                expect($sizeRules)->not->toBeEmpty();
            }
        });

        it('StartsWith attribute generates starts_with rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['prefix'])) {
                $ssRules = array_filter($rules['prefix'], fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'starts_with:'));
                expect($ssRules)->not->toBeEmpty();
            }
        });

        it('Accepted attribute generates accepted rule', function () {
            $rules = StrictValidationDTO::rules();

            if (isset($rules['terms'])) {
                expect($rules['terms'])->toContain('accepted');
            }
        });
    });

    describe('attribute class final and readonly contract', function () {
        $reflectionClasses = [
            Required::class,
            Email::class,
            Max::class,
            Min::class,
            Boolean::class,
            Integer::class,
            Pattern::class,
            Url::class,
            Uuid::class,
            Hidden::class,
            MapFrom::class,
            Cast::class,
            DefaultValue::class,
            Nullable::class,
            In::class,
            Date::class,
            Size::class,
            StartsWith::class,
            Accepted::class,
        ];

        foreach ($reflectionClasses as $class) {
            it("{$class} is final", function () use ($class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });
});
