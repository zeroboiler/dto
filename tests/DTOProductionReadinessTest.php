<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO Production Readiness', function () {
    describe('ValidationAttribute contract', function () {
        it('all validation attributes implement ValidationAttribute', function () {
            $attributes = [
                new Accepted,
                new ArrayRule,
                new Between(1, 10),
                new Boolean,
                new Confirmed,
                new Date,
                new Declined,
                new Different('other'),
                new Distinct,
                new Email,
                new EndsWith('.com'),
                new EnumAttr(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class),
                new Integer,
                new In(['a', 'b']),
                new Json,
                new Max(255),
                new Min(1),
                new Nullable,
                new Numeric,
                new Pattern('/^[a-z]+$/'),
                new Present,
                new Prohibited,
                new Required,
                new RequiredIf('field', 'value'),
                new RequiredUnless('field', 'value'),
                new RequiredWith('email'),
                new RequiredWithAll(['email', 'phone']),
                new RequiredWithout('email'),
                new RequiredWithoutAll(['email', 'phone']),
                new Same('password'),
                new Size(5),
                new Sometimes,
                new StartsWith('https://'),
                new Url,
                new Uuid,
            ];

            foreach ($attributes as $attr) {
                expect($attr)->toBeInstanceOf(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
                expect(method_exists($attr, 'ruleKey'))->toBeTrue();
                expect($attr->ruleKey())->toBeString();
            }
        });

        it('ruleKey returns correct Laravel rule names', function () {
            expect((new Accepted)->ruleKey())->toBe('accepted');
            expect((new ArrayRule)->ruleKey())->toBe('array');
            expect((new Boolean)->ruleKey())->toBe('boolean');
            expect((new Email)->ruleKey())->toBe('email');
            expect((new Integer)->ruleKey())->toBe('integer');
            expect((new Max(10))->ruleKey())->toBe('max');
            expect((new Min(5))->ruleKey())->toBe('min');
            expect((new Pattern('/test/'))->ruleKey())->toBe('regex');
            expect((new Required)->ruleKey())->toBe('required');
            expect((new Url)->ruleKey())->toBe('url');
            expect((new Uuid)->ruleKey())->toBe('uuid');
            expect((new Date)->ruleKey())->toBe('date');
            expect((new Date('Y-m-d'))->ruleKey())->toBe('date');
            expect((new In(['a']))->ruleKey())->toBe('in');
            expect((new StartsWith('pre'))->ruleKey())->toBe('starts_with');
            expect((new EndsWith('suf'))->ruleKey())->toBe('ends_with');
            expect((new Prohibited)->ruleKey())->toBe('prohibited');
            expect((new Present)->ruleKey())->toBe('present');
            expect((new Declined)->ruleKey())->toBe('declined');
            expect((new Confirmed)->ruleKey())->toBe('confirmed');
            expect((new Different('x'))->ruleKey())->toBe('different');
            expect((new Same('x'))->ruleKey())->toBe('same');
            expect((new Distinct)->ruleKey())->toBe('distinct');
            expect((new Sometimes)->ruleKey())->toBe('sometimes');
            expect((new Nullable)->ruleKey())->toBe('nullable');
            expect((new Size(5))->ruleKey())->toBe('size');
            expect((new Numeric)->ruleKey())->toBe('numeric');
            expect((new Json)->ruleKey())->toBe('json');
            expect((new RequiredIf('f', 'v'))->ruleKey())->toBe('required_if');
            expect((new RequiredUnless('f', 'v'))->ruleKey())->toBe('required_unless');
            expect((new RequiredWith('f'))->ruleKey())->toBe('required_with');
            expect((new RequiredWithAll(['f']))->ruleKey())->toBe('required_with_all');
            expect((new RequiredWithout('f'))->ruleKey())->toBe('required_without');
            expect((new RequiredWithoutAll(['f']))->ruleKey())->toBe('required_without_all');
        });
    });

    describe('DtoCollection type safety', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn (): mixed => new DtoCollection([new stdClass]))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects non-DTO items via offsetSet', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $collection = new DtoCollection([$dto]);

            expect(fn (): mixed => $collection[] = 'not a dto')
                ->toThrow(InvalidArgumentException::class);
        });

        it('count returns correct number', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            expect($collection->count())->toBe(2);
            expect($collection->isEmpty())->toBeFalse();
            expect($collection->isNotEmpty())->toBeTrue();
        });

        it('first and last return correct items', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            expect($collection->first()->foo)->toBe('a');
            expect($collection->last()->foo)->toBe('b');
        });

        it('filter returns new collection', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1, $d2, $d3]);

            $filtered = $collection->filter(fn (EmptyDTO $d): bool => $d->foo === 'a');

            expect($filtered)->toBeInstanceOf(DtoCollection::class);
            expect($filtered->count())->toBe(2);
        });

        it('map returns plain array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $result = $collection->map(fn (EmptyDTO $d): string => $d->foo);

            expect($result)->toBe(['a', 'b']);
        });

        it('pluck returns array of property values', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob', 'status' => 'active'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $emails = $collection->pluck('email');

            expect($emails)->toEqual(['a@b.com', 'c@d.com']);
        });

        it('pluckKey returns associative array', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob', 'status' => 'active'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $map = $collection->pluckKey('email', 'name');

            expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Bob']);
        });

        it('offsetUnset reindexes array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);
            $collection = new DtoCollection([$d1, $d2, $d3]);

            unset($collection[0]);

            // After unsetting index 0 and re-indexing, first should be 'b'
            expect($collection->first()->foo)->toBe('b');
            expect($collection->count())->toBe(2);
        });

        it('toArray serializes all DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'x'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b', 'bar' => 'y'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $result = $collection->toArray();

            expect($result)->toEqual([
                ['foo' => 'a', 'bar' => 'x'],
                ['foo' => 'b', 'bar' => 'y'],
            ]);
        });

        it('jsonSerialize returns array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            $result = $collection->jsonSerialize();

            expect($result)->toBe([['foo' => 'a']]);
        });

        it('push returns self for fluent chaining', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1]);

            $result = $collection->push($d2);

            expect($result)->toBe($collection); // Same instance
            expect($collection->count())->toBe(2);
        });

        it('make creates from array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = DtoCollection::make([$d1]);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(1);
        });

        it('items returns raw DTO instances', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect($collection->items()[0])->toBeInstanceOf(EmptyDTO::class);
        });
    });

    describe('DTOException factory methods', function () {
        it('creates invalidCast exception', function () {
            $exception = DTOException::invalidCast('age', 'int', 'string');

            expect($exception->getMessage())->toContain('age');
            expect($exception->getMessage())->toContain('int');
            expect($exception->getMessage())->toContain('string');
        });

        it('creates invalidJson exception', function () {
            $exception = DTOException::invalidJson('metadata', 'Syntax error');

            expect($exception->getMessage())->toContain('metadata');
            expect($exception->getMessage())->toContain('Syntax error');
        });
    });

    describe('DTOManager', function () {
        it('validates data against DTO class', function () {
            $manager = new DTOManager;
            $result = $manager->validate(EmptyDTO::class, ['foo' => 'bar']);

            expect($result)->toBe(['foo' => 'bar']);
        });

        it('makes DTO from data', function () {
            $manager = new DTOManager;
            $dto = $manager->make(EmptyDTO::class, ['foo' => 'bar']);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBe('bar');
        });

        it('generates OpenAPI schema', function () {
            $manager = new DTOManager;
            $schema = $manager->schema(EmptyDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
        });
    });

    describe('DTOCast', function () {
        it('returns null for null on get', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->get(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('returns null for non-array on get', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->get(new stdClass, 'data', 'not-json-array', []);

            expect($result)->toBeNull();
        });

        it('hydrates from JSON string on get', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $json = json_encode(['foo' => 'bar']);
            $result = $cast->get(new stdClass, 'data', $json, []);

            expect($result)->toBeInstanceOf(EmptyDTO::class);
            expect($result->foo)->toBe('bar');
        });

        it('hydrates from array on get', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->get(new stdClass, 'data', ['foo' => 'bar'], []);

            expect($result)->toBeInstanceOf(EmptyDTO::class);
        });

        it('returns null for null on set', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->set(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('serializes DTO instance on set', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $result = $cast->set(new stdClass, 'data', $dto, []);

            $decoded = json_decode($result, true);
            expect($decoded)->toBe(['foo' => 'bar']);
        });

        it('hydrates and serializes array on set', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->set(new stdClass, 'data', ['foo' => 'bar'], []);

            $decoded = json_decode($result, true);
            expect($decoded)->toBe(['foo' => 'bar']);
        });

        it('throws on invalid type in set', function () {
            $cast = new DTOCast(EmptyDTO::class);

            expect(fn (): mixed => $cast->set(new stdClass, 'data', 123, []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('returns null for null on serialize', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->serialize(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('serializes DTO to array on serialize', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $result = $cast->serialize(new stdClass, 'data', $dto, []);

            expect($result)->toBe(['foo' => 'bar']);
        });
    });

    describe('DataTransferObject edge cases', function () {
        it('equals compares two identical DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('only returns specified keys', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

            expect($dto->only('foo'))->toBe(['foo' => 'a']);
            expect($dto->only('bar'))->toBe(['bar' => 'b']);
        });

        it('only accepts variadic string args', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

            expect($dto->only('foo', 'bar'))->toBe(['foo' => 'a', 'bar' => 'b']);
        });

        it('except excludes specified keys', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

            expect($dto->except('foo'))->toBe(['bar' => 'b']);
        });

        it('allValues includes all properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('toJson produces valid JSON', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

            $json = $dto->toJson();

            expect(json_decode($json))->toBe((object) ['foo' => 'bar']);
        });

        it('with creates immutable copy', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
            $updated = $dto->with(['foo' => 'updated']);

            expect($dto->foo)->toBe('a'); // Original unchanged
            expect($updated->foo)->toBe('updated');
            expect($updated->bar)->toBe('b'); // Other fields preserved
        });

        it('flushMetadataCache clears per-class cache', function () {
            // Trigger metadata resolution by creating a DTO
            EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            // Now flush the cache for this class
            DataTransferObject::flushMetadataCache(EmptyDTO::class);
            // No error — cache was cleared successfully
            expect(true)->toBeTrue();
        });

        it('flushMetadataCache with null clears all', function () {
            DataTransferObject::flushMetadataCache();

            expect(true)->toBeTrue(); // No error
        });
    });
});
