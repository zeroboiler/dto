<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO — comprehensive edge case coverage', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('DTOException factory methods', function () {
        it('invalidCast creates correct message with type info', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('string');
        });

        it('invalidCast handles object value', function () {
            $e = DTOException::invalidCast('data', 'array', new stdClass);

            expect($e->getMessage())->toContain('stdClass');
        });

        it('invalidCast handles array value', function () {
            $e = DTOException::invalidCast('count', 'integer', [1, 2, 3]);

            expect($e->getMessage())->toContain('array');
        });

        it('invalidJson creates correct message', function () {
            $e = DTOException::invalidJson('metadata', 'Syntax error');

            expect($e->getMessage())->toContain('metadata');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    describe('fromJson — edge cases', function () {
        it('rejects sequential JSON arrays', function () {
            expect(fn () => EmptyDTO::fromJson('["a", "b", "c"]'))
                ->toThrow(DTOException::class, 'sequential array');
        });

        it('rejects non-object JSON', function () {
            expect(fn () => EmptyDTO::fromJson('"just a string"'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            $dto = EmptyDTO::fromJson('{}');

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBeNull();
        });

        it('hydrates fields from JSON object', function () {
            $dto = EmptyDTO::fromJson('{"foo": "hello", "bar": "world"}');

            expect($dto->foo)->toBe('hello');
            expect($dto->bar)->toBe('world');
        });
    });

    describe('fromPartialArray — edge cases', function () {
        it('applies defaults for missing fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'test@example.com',
            ], validate: false);

            expect($dto->email)->toBe('test@example.com');
            // status has DefaultValue('active')
            expect($dto->status)->toBe('active');
            // name: no default, type is string → empty string
            expect($dto->name)->toBe('');
        });

        it('empty data creates DTO with all defaults', function () {
            $dto = EmptyDTO::fromPartialArray([]);

            expect($dto->foo)->toBeNull(); // nullable with default null
            expect($dto->bar)->toBeNull();
        });

        it('overrides defaults when data provided', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'override@example.com',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->email)->toBe('override@example.com');
            expect($dto->status)->toBe('inactive');
        });

        it('preserves explicit null values', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'test@example.com',
                'phone' => null,
            ], validate: false);

            expect($dto->phone)->toBeNull();
        });
    });

    describe('equals — strict comparison', function () {
        it('same data produces equal DTOs', function () {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
            ], validate: false);
            $d2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
            ], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('different data produces non-equal DTOs', function () {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
            ], validate: false);
            $d2 = CreateUserDTO::fromArray([
                'email' => 'x@y.com',
                'name' => 'Test',
            ], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });
    });

    describe('allValues — includes hidden properties', function () {
        it('includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toArray excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
        });
    });

    describe('DtoCollection — pluck and pluckKey', function () {
        it('pluck extracts single property from all DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'alice', 'bar' => '1'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'bob', 'bar' => '2'], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $foos = $collection->pluck('foo');

            expect($foos)->toBe(['alice', 'bob']);
        });

        it('pluckKey returns key-value pairs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'key1', 'bar' => 'val1'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'key2', 'bar' => 'val2'], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $map = $collection->pluckKey('foo', 'bar');

            expect($map['key1'])->toBe('val1');
            expect($map['key2'])->toBe('val2');
        });

        it('pluckKey without valueField returns full arrays', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'key1', 'bar' => 'val1'], validate: false);

            $collection = new DtoCollection([$d1]);
            $map = $collection->pluckKey('foo');

            expect($map['key1'])->toBe(['foo' => 'key1', 'bar' => 'val1']);
        });

        it('map transforms each DTO', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $result = $collection->map(fn (DataTransferObject $dto, int $i): string => $dto->foo . '-' . $i);

            expect($result)->toBe(['a-0', 'b-1']);
        });

        it('filter returns new collection with matching items', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'keep'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'drop'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => 'keep'], validate: false);

            $collection = new DtoCollection([$d1, $d2, $d3]);
            $filtered = $collection->filter(fn (DataTransferObject $dto): bool => $dto->foo === 'keep');

            expect($filtered->count())->toBe(2);
            expect($filtered->first()->foo)->toBe('keep');
        });

        it('items returns raw DTO instances', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect($collection->items()[0])->toBe($d1);
        });
    });

    describe('DtoCollection — make factory', function () {
        it('make creates collection from array of DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $collection = DtoCollection::make([$d1, $d2]);

            expect($collection->count())->toBe(2);
        });

        it('make rejects non-DTO instances', function () {
            expect(fn () => DtoCollection::make([new stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('DTOCast — validation toggle', function () {
        it('validate: false in constructor skips validation on set', function () {
            $cast = new DTOCast(EmptyDTO::class, validate: false);

            // EmptyDTO has no required fields, but the point is that validation
            // would normally run — with validate: false it's skipped
            $result = $cast->set(new stdClass, 'data', ['foo' => 'x'], []);

            expect(is_string($result))->toBeTrue();
        });

        it('set rejects unexpected types', function () {
            $cast = new DTOCast(EmptyDTO::class);

            expect(fn () => $cast->set(new stdClass, 'data', 123, []))
                ->toThrow(\InvalidArgumentException::class, 'expects a DTO instance, array, or null');
        });

        it('get returns null for null value', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->get(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('get returns null for non-array non-string value', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->get(new stdClass, 'data', 123, []);

            expect($result)->toBeNull();
        });

        it('serialize returns null for null value', function () {
            $cast = new DTOCast(EmptyDTO::class);
            $result = $cast->serialize(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });
    });

    describe('DTOManager — makeFromJson', function () {
        it('creates DTO from JSON string', function () {
            $manager = new DTOManager;
            $dto = $manager->makeFromJson(EmptyDTO::class, '{"foo": "hello", "bar": "world"}');

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBe('hello');
        });

        it('throws DTOException for invalid JSON', function () {
            $manager = new DTOManager;

            expect(fn () => $manager->makeFromJson(EmptyDTO::class, 'not json'))
                ->toThrow(DTOException::class);
        });
    });

    describe('DataTransferObject — rulesFor action scoping', function () {
        it('rulesFor returns same rules as rules() by default', function () {
            $rules = EmptyDTO::rules();
            $createRules = EmptyDTO::rulesFor('create');
            $updateRules = EmptyDTO::rulesFor('update');

            expect($createRules)->toBe($rules);
            expect($updateRules)->toBe($rules);
        });
    });

    describe('DataTransferObject — Cast attribute pipeline', function () {
        it('Cast("array") decodes JSON string to array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '["php", "laravel"]',
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
        });

        it('Cast("array") passes through actual arrays', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => ['a', 'b'],
            ], validate: false);

            expect($dto->tags)->toBe(['a', 'b']);
        });

        it('Cast("array") returns empty array for empty string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '',
            ], validate: false);

            expect($dto->tags)->toBe([]);
        });
    });

    describe('DataTransferObject — DefaultValue attribute', function () {
        it('DefaultValue is applied when key is missing from data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('DefaultValue is overridden by explicit data value', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'suspended',
            ], validate: false);

            expect($dto->status)->toBe('suspended');
        });

        it('DefaultValue is not used when key is present with null', function () {
            // status is non-nullable string, so null may cause validation error
            // but with validate: false it should pass through
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => null,
            ], validate: false);

            expect($dto->status)->toBeNull();
        });
    });

    describe('DataTransferObject — metadata cache flushing', function () {
        it('flushMetadataCache with specific class only clears that class', function () {
            // Populate cache for both DTOs
            CreateUserDTO::rules();
            EmptyDTO::rules();

            // Flush only EmptyDTO
            DataTransferObject::flushMetadataCache(EmptyDTO::class);

            // CreateUserDTO rules should still be cached (no error)
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache with null clears all', function () {
            CreateUserDTO::rules();
            EmptyDTO::rules();

            DataTransferObject::flushMetadataCache(null);

            // Both should rebuild without error
            expect(CreateUserDTO::rules())->toBeArray();
            expect(EmptyDTO::rules())->toBeArray();
        });
    });

    describe('DataTransferObject — jsonSerialize', function () {
        it('jsonSerialize returns same as toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
            expect($dto->jsonSerialize())->not->toHaveKey('password');
        });
    });

    describe('Nested DTO hydration — edge cases', function () {
        it('accepts already-hydrated DTO instance', function () {
            $address = AddressDTO::fromArray([
                'street' => '123 Main',
                'city' => 'Istanbul',
            ], validate: false);

            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-100',
                'shippingAddress' => $address,
                'items' => [],
            ], validate: false);

            expect($dto->shippingAddress)->toBe($address);
        });

        it('throws for invalid nested data type', function () {
            expect(fn () => OrderDTO::fromArray([
                'orderNumber' => 'ORD-100',
                'shippingAddress' => 'not_an_array',
                'items' => [],
            ], validate: false))->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('OpenApiSchemaGenerator — with components', function () {
        it('generates schema with component refs for nested DTOs', function () {
            $result = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

            expect($result)->toHaveKey('schema');
            expect($result)->toHaveKey('components');
            expect($result['components'])->toHaveKey('schemas');
            expect($result['components']['schemas'])->toHaveKey('AddressDTO');
            expect($result['components']['schemas'])->toHaveKey('OrderItemDTO');
            expect($result['schema']['type'])->toBe('object');
        });

        it('generate throws LogicException for nested DTOs', function () {
            expect(fn () => \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(OrderDTO::class))
                ->toThrow(\LogicException::class, 'generateWithComponents');
        });

        it('generate works fine for DTOs without nesting', function () {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(AddressDTO::class);

            expect($schema['type'])->toBe('object');
            expect($schema['required'])->toContain('street');
            expect($schema['required'])->toContain('city');
        });
    });
});
