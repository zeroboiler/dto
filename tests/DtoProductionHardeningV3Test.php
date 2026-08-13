<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO Production Hardening V3', function () {
    describe('fromArray with validation=false bypasses rules', function () {
        it('creates DTO even with invalid data when validate=false', function () {
            $dto = MinimalDTO::fromArray(['name' => '', 'value' => ''], validate: false);
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });

        it('creates DTO with empty string fields when validate=false', function () {
            $dto = MinimalDTO::fromArray(['name' => '', 'value' => ''], validate: false);
            expect($dto->toArray())->toEqual(['name' => '', 'value' => '']);
        });
    });

    describe('fromArray with validate=true enforces rules', function () {
        it('throws DTOException for missing required field with validation', function () {
            expect(fn () => MinimalDTO::fromArray([], validate: true))
                ->toThrow(DTOException::class);
        });

        it('creates DTO with valid data and validation', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Valid', 'value' => 'Value'], validate: true);
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });

    describe('toArray and serialization consistency', function () {
        it('round-trips through fromArray and toArray with RoundtripDTO', function () {
            $data = [
                'name' => 'John',
                'age' => '30',
                'active' => true,
                'tags' => '["a","b"]',
            ];
            $dto = RoundtripDTO::fromArray($data, validate: false);
            $arr = $dto->toArray();

            // Cast('integer') should cast string '30' to int 30
            expect($arr['name'])->toBe('John');
            expect($arr['age'])->toBe(30);
            expect($arr['active'])->toBe(true);
            expect($arr['tags'])->toBe(['a', 'b']);
        });

        it('excludes hidden fields from toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('applies default values in toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['status'])->toBe('active');
        });
    });

    describe('MapFrom attribute', function () {
        it('maps source key to DTO property on CreateUserDTO', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->toArray()['phone'])->toBe('+1234567890');
        });

        it('maps source_bio to bio on RoundtripDTO', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'John',
                'age' => 25,
                'active' => true,
                'source_bio' => 'Hello world',
            ], validate: false);

            expect($dto->toArray()['bio'])->toBe('Hello world');
        });
    });

    describe('JSON serialization edge cases', function () {
        it('fromJson round-trips correctly with CreateUserDTO', function () {
            $data = [
                'email' => 'john@example.com',
                'name' => 'John',
                'status' => 'active',
            ];
            $dto = CreateUserDTO::fromArray($data, validate: false);
            $json = $dto->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->toArray()['email'])->toBe('john@example.com');
            expect($restored->toArray()['name'])->toBe('John');
        });

        it('fromJson throws on invalid JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{invalid}', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws on non-object JSON', function () {
            expect(fn () => MinimalDTO::fromJson('"string"', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws on non-array JSON', function () {
            expect(fn () => MinimalDTO::fromJson('42', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws on boolean JSON', function () {
            expect(fn () => MinimalDTO::fromJson('true', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    describe('equals() value comparison', function () {
        it('returns true for same values', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Same', 'value' => 'Same'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Same', 'value' => 'Same'], validate: false);
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('returns false for different values', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'A'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => 'B'], validate: false);
            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('returns false for different DTO classes', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('only() and except() field filtering', function () {
        it('only returns selected fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
                'status' => 'active',
            ], validate: false);

            $filtered = $dto->only('name', 'email');
            expect($filtered)->toHaveKey('name');
            expect($filtered)->toHaveKey('email');
            expect($filtered)->not->toHaveKey('status');
        });

        it('except removes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
                'status' => 'active',
            ], validate: false);

            $filtered = $dto->except('email');
            expect($filtered)->toHaveKey('name');
            expect($filtered)->not->toHaveKey('email');
        });
    });

    describe('with() immutable override', function () {
        it('returns new instance with overridden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
            ], validate: false);
            $modified = $dto->with(['name' => 'Jane']);

            expect($modified)->toBeInstanceOf(CreateUserDTO::class);
            expect($modified)->not->toBe($dto);
        });

        it('preserves non-overridden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
            ], validate: false);
            $modified = $dto->with(['name' => 'Jane']);

            expect($modified->toArray()['email'])->toBe('john@example.com');
        });

        it('preserves default values when not overridden', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'john@example.com',
                'name' => 'John',
            ], validate: false);
            $modified = $dto->with(['name' => 'Jane']);

            expect($modified->toArray()['status'])->toBe('active');
        });
    });

    describe('fromPartialArray for partial updates', function () {
        it('creates DTO with only specified fields', function () {
            $dto = RoundtripDTO::fromPartialArray(['name' => 'Updated'], validatePresent: false);
            expect($dto->toArray()['name'])->toBe('Updated');
        });

        it('applies default values for missing fields', function () {
            $dto = RoundtripDTO::fromPartialArray(['name' => 'Test'], validatePresent: false);
            expect($dto->toArray()['role'])->toBe('user');
        });
    });

    describe('DTOCollection operations', function () {
        it('creates collection from DTO instances', function () {
            $items = [
                MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false),
                MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false),
            ];
            $collection = new DtoCollection($items);

            expect($collection)->toHaveCount(2);
            expect($collection[0])->toBeInstanceOf(MinimalDTO::class);
        });

        it('supports pluck on collection', function () {
            $items = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            ];
            $collection = new DtoCollection($items);

            $names = $collection->pluck('name');
            expect($names)->toEqual(['A', 'B']);
        });
    });

    describe('DTOException', function () {
        it('invalidJson exception contains field name and message', function () {
            try {
                MinimalDTO::fromJson('not-json', validate: false);
                expect(true)->toBeFalse('Should have thrown');
            } catch (DTOException $e) {
                expect($e->getMessage())->toContain('Invalid JSON');
            }
        });

        it('serializes to string via __toString', function () {
            $e = DTOException::invalidJson('field', 'parse error');
            $str = (string) $e;
            expect($str)->toContain('InvalidJsonDTOException');
            expect($str)->toContain('parse error');
        });
    });

    describe('Validation rules generation', function () {
        it('rules() returns array of rules for MinimalDTO', function () {
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('value');
        });

        it('rules contain required rules for required fields', function () {
            $rules = MinimalDTO::rules();
            $nameRules = $rules['name'];
            $hasRequired = in_array('required', $nameRules, true);
            expect($hasRequired)->toBeTrue();
        });

        it('rules for ScalarConstraintsDTO include boolean, integer, numeric', function () {
            $rules = ScalarConstraintsDTO::rules();
            expect($rules)->toHaveKey('is_admin');
            expect($rules)->toHaveKey('score');
            expect($rules)->toHaveKey('rating');
        });
    });

    describe('Metadata cache management', function () {
        it('flushMetadataCache clears all cached metadata', function () {
            MinimalDTO::rules();
            DataTransferObject::flushMetadataCache();

            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl controls caching behavior', function () {
            DataTransferObject::setMetadataCacheTtl(60.0);
            MinimalDTO::rules();

            expect(DataTransferObject::getMetadataCacheTtl())->toBe(60.0);

            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    describe('Cast attribute edge cases', function () {
        it('integer cast handles string input in RoundtripDTO', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'John',
                'age' => '42',
                'active' => true,
            ], validate: false);
            expect($dto->toArray()['age'])->toBeInt();
            expect($dto->toArray()['age'])->toBe(42);
        });

        it('array cast handles JSON string input in RoundtripDTO', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'John',
                'age' => 25,
                'active' => true,
                'tags' => '["a","b","c"]',
            ], validate: false);
            expect($dto->toArray()['tags'])->toBe(['a', 'b', 'c']);
        });

        it('array cast passes arrays through as-is', function () {
            $dto = RoundtripDTO::fromArray([
                'name' => 'John',
                'age' => 25,
                'active' => true,
                'tags' => ['x', 'y'],
            ], validate: false);
            expect($dto->toArray()['tags'])->toBe(['x', 'y']);
        });
    });

    describe('Contract compliance', function () {
        it('DTO implements JsonSerializable', function () {
            $dto = MinimalDTO::fromArray(['name' => 'John', 'value' => 'test'], validate: false);
            expect($dto)->toBeInstanceOf(\JsonSerializable::class);
        });

        it('jsonSerialize matches toArray output', function () {
            $dto = MinimalDTO::fromArray(['name' => 'John', 'value' => 'test'], validate: false);
            expect($dto->jsonSerialize())->toEqual($dto->toArray());
        });

        it('supports property access via readonly promoted properties', function () {
            $dto = MinimalDTO::fromArray(['name' => 'John', 'value' => 'test'], validate: false);
            expect($dto->name)->toBe('John');
            expect($dto->value)->toBe('test');
        });
    });

    describe('EmptyDTO edge cases', function () {
        it('creates EmptyDTO from empty array', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toEqual([]);
        });

        it('round-trips EmptyDTO through JSON', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            $json = $dto->toJson();
            $restored = EmptyDTO::fromJson($json, validate: false);
            expect($restored->toArray())->toEqual([]);
        });
    });
});
