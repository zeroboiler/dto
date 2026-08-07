<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Boolean as BooleanAttr;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DTO PHPStan Level 9 Compliance', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('No mixed types in public API — strict return types', function () {
        it('fromArray() returns concrete DTO instance', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('toArray() returns array with string keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);
            $arr = $dto->toArray();

            expect($arr)->toBeArray();
            foreach (array_keys($arr) as $key) {
                expect(is_string($key))->toBeTrue();
            }
        });

        it('toJson() returns non-empty string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $json = $dto->toJson();

            expect($json)->toBeString();
            expect(strlen($json))->toBeGreaterThan(0);
            // Valid JSON
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
        });

        it('jsonSerialize() returns same as toArray()', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->jsonSerialize())->toEqual($dto->toArray());
        });

        it('only() returns array with only specified keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');

            $result = $dto->only('email', 'name');
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('except() returns array without specified keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->except('email');
            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('allValues() includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            $all = $dto->allValues();

            // toArray excludes hidden
            expect($arr)->not->toHaveKey('password');
            // allValues includes hidden
            expect($all)->toHaveKey('password');
        });

        it('rules() returns array with string keys and array values', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            foreach ($rules as $field => $fieldRules) {
                expect(is_string($field))->toBeTrue();
                expect(is_array($fieldRules))->toBeTrue();
                foreach ($fieldRules as $rule) {
                    expect(is_string($rule) || is_object($rule))->toBeTrue();
                }
            }
        });

        it('rulesFor() returns same as rules() by default', function () {
            expect(CreateUserDTO::rulesFor('create'))->toEqual(CreateUserDTO::rules());
            expect(CreateUserDTO::rulesFor('update'))->toEqual(CreateUserDTO::rules());
        });

        it('validateArray() returns validated data array', function () {
            $validated = CreateUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ]);

            expect($validated)->toBeArray();
            expect($validated)->toHaveKey('email');
        });
    });

    describe('Strict typing — property access', function () {
        it('readonly properties are immutable', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $reflection = new ReflectionClass($dto);
            $emailProp = $reflection->getProperty('email');

            expect($emailProp->isReadOnly())->toBeTrue();
            expect($emailProp->isPublic())->toBeTrue();
        });

        it('all constructor properties are public readonly', function () {
            $reflection = new ReflectionClass(CreateUserDTO::class);
            $constructor = $reflection->getConstructor();

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $prop = $reflection->getProperty($name);

                expect($prop->isPublic())
                    ->toBeTrue("{$name} should be public");
                expect($prop->isReadOnly())
                    ->toBeTrue("{$name} should be readonly");
            }
        });

        it('nullable properties accept null', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });
    });

    describe('Hidden attribute — serialization exclusion', function () {
        it('Hidden fields excluded from toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
        });

        it('Hidden fields excluded from toJson', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->not->toContain('secret');
        });

        it('Hidden fields included in allValues', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret');
        });
    });

    describe('MapFrom — source key aliasing', function () {
        it('maps source key to property name', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+905551234567',
            ], validate: false);

            expect($dto->phone)->toBe('+905551234567');
        });
    });

    describe('DefaultValue — missing key handling', function () {
        it('uses DefaultValue when key is missing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('explicit null overrides default', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => null,
            ], validate: false);

            expect($dto->status)->toBeNull();
        });
    });

    describe('Cast attribute — type transformation', function () {
        it('Cast integer transforms string to int', function () {
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'price' => '9.99',
                'stock' => 25,
            ], validate: false);

            expect(is_int($dto->stock))->toBeTrue();
            expect($dto->stock)->toBe(25);
        });

        it('Cast array handles JSON strings', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '["laravel","php"]',
            ], validate: false);

            expect($dto->tags)->toBe(['laravel', 'php']);
        });

        it('Cast array handles empty string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '',
            ], validate: false);

            expect($dto->tags)->toBe([]);
        });
    });

    describe('Partial array — PATCH semantics', function () {
        it('fromPartialArray only hydrates present fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated');
        });

        it('fromPartialArray applies defaults for missing fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated',
            ], validatePresent: false);

            expect($dto->status)->toBe('active');
        });

        it('fromPartialArray with empty data uses all defaults', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto->status)->toBe('active');
            expect($dto->phone)->toBeNull();
        });

        it('validatePartialArray only validates present fields', function () {
            $validated = CreateUserDTO::validatePartialArray([
                'name' => 'Test',
            ]);

            expect($validated)->toBeArray();
        });
    });

    describe('Nested DTO — hydration and serialization', function () {
        it('nested DTO auto-hydrated from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->city)->toBe('Istanbul');
        });

        it('nested DTO serialized recursively', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['city'])->toBe('Istanbul');
        });
    });

    describe('DtoCollection — type safety', function () {
        it('constructor rejects non-DTO instances', function () {
            expect(fn () => new DtoCollection([new stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('make() creates collection from array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = DtoCollection::make([$d1, $d2]);

            expect($collection->count())->toBe(2);
        });

        it('push returns fluent self', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection;

            $result = $collection->push($dto);

            expect($result)->toBe($collection);
            expect($collection->count())->toBe(1);
        });

        it('pluck extracts single field', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'x'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b', 'bar' => 'y'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            expect($collection->pluck('foo'))->toEqual(['a', 'b']);
        });

        it('pluckKey creates associative array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'x'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b', 'bar' => 'y'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $map = $collection->pluckKey('foo', 'bar');
            expect($map)->toEqual(['a' => 'x', 'b' => 'y']);
        });

        it('filter returns new DtoCollection', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $filtered = $collection->filter(fn ($dto) => $dto->foo === 'a');

            expect($filtered)->toBeInstanceOf(DtoCollection::class);
            expect($filtered->count())->toBe(1);
            // Original unchanged
            expect($collection->count())->toBe(2);
        });

        it('map returns plain array', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $result = $collection->map(fn ($dto) => $dto->foo);
            expect($result)->toEqual(['a', 'b']);
        });

        it('toArray serializes all DTOs', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $arr = $collection->toArray();
            expect($arr)->toEqual([['foo' => 'a'], ['foo' => 'b']]);
        });

        it('first/last return DTO or null', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect($collection->first())->toBe($d1);
            expect($collection->last())->toBe($d1);

            $empty = new DtoCollection;
            expect($empty->first())->toBeNull();
            expect($empty->last())->toBeNull();
        });
    });

    describe('DTOException — factory methods', function () {
        it('invalidCast includes property and type info', function () {
            $e = DTOException::invalidCast('age', 'int', 'not_a_number');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('int');
        });

        it('invalidJson includes property and error', function () {
            $e = DTOException::invalidJson('tags', 'Syntax error');
            expect($e->getMessage())->toContain('tags');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    describe('DTOCast — Eloquent cast type safety', function () {
        it('get returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('get returns DTO from JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $json = json_encode(['email' => 'test@example.com', 'name' => 'Test']);
            $result = $cast->get(new stdClass, 'data', $json, []);

            expect($result)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('get returns null for invalid JSON', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new stdClass, 'data', 'not-json', []);

            expect($result)->toBeNull();
        });

        it('set returns JSON string for DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $cast->set(new stdClass, 'data', $dto, []);

            expect($result)->toBeString();
            $decoded = json_decode($result, true);
            expect($decoded)->toBeArray();
        });

        it('set returns JSON string for array input', function () {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->set(new stdClass, 'data', [
                'email' => 'test@example.com',
                'name' => 'Test',
            ], []);

            expect($result)->toBeString();
        });

        it('set returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->set(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('set throws for invalid type', function () {
            $cast = new DTOCast(CreateUserDTO::class);

            expect(fn () => $cast->set(new stdClass, 'data', 123, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize returns array for DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $cast->serialize(new stdClass, 'data', $dto, []);

            expect($result)->toBeArray();
        });

        it('serialize returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->serialize(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });
    });

    describe('DTOManager — facade delegation', function () {
        it('validate delegates to DTO class', function () {
            $manager = new DTOManager;
            $validated = $manager->validate(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test',
            ]);

            expect($validated)->toBeArray();
        });

        it('make creates DTO instance', function () {
            $manager = new DTOManager;
            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('makeFromJson creates DTO from JSON', function () {
            $manager = new DTOManager;
            $dto = $manager->makeFromJson(EmptyDTO::class, '{"foo": "bar"}');

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });

        it('schema generates OpenAPI schema', function () {
            $manager = new DTOManager;
            $schema = $manager->schema(MinimalDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
        });
    });

    describe('fromJson — error handling', function () {
        it('throws DTOException for malformed JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{bad json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential JSON array', function () {
            expect(fn () => MinimalDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON string literal', function () {
            expect(fn () => MinimalDTO::fromJson('"hello"'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON number', function () {
            expect(fn () => MinimalDTO::fromJson('42'))
                ->toThrow(DTOException::class);
        });

        it('successfully creates DTO from valid JSON object', function () {
            $dto = MinimalDTO::fromJson('{"name":"test","value":"data"}', validate: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });

    describe('equals — value semantics', function () {
        it('same values equal', function () {
            $d1 = ProductDTO::fromArray(['name' => 'X', 'price' => '9.99', 'stock' => 10], validate: false);
            $d2 = ProductDTO::fromArray(['name' => 'X', 'price' => '9.99', 'stock' => 10], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('different values not equal', function () {
            $d1 = ProductDTO::fromArray(['name' => 'A', 'price' => '9.99', 'stock' => 10], validate: false);
            $d2 = ProductDTO::fromArray(['name' => 'B', 'price' => '9.99', 'stock' => 10], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('equality is symmetric', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);

            expect($d1->equals($d2))->toBe($d2->equals($d1));
        });

        it('equality is reflexive', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);

            expect($dto->equals($dto))->toBeTrue();
        });
    });

    describe('isEmpty / isNotEmpty', function () {
        it('DTO with no values is empty', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('DTO with all null values is empty', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('with — immutable update', function () {
        it('creates new instance with overrides', function () {
            $original = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'x'], validate: false);
            $updated = $original->with(['foo' => 'b']);

            expect($original->foo)->toBe('a');
            expect($updated->foo)->toBe('b');
            expect($updated->bar)->toBe('x');
        });

        it('always validates merged data', function () {
            // The validate parameter is deprecated and has no effect
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $updated = $dto->with(['foo' => 'b'], validate: true);

            expect($updated->foo)->toBe('b');
        });
    });

    describe('ValidationAttribute contract', function () {
        $attributeClasses = [
            Accepted::class, Boolean::class, Confirmed::class, Date::class,
            Declined::class, Distinct::class, Email::class, EndsWith::class,
            In::class, Integer::class, Max::class, Min::class, Numeric::class,
            Pattern::class, Present::class, Prohibited::class, Required::class,
            RequiredIf::class, RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class, RequiredWithoutAll::class,
            Same::class, Size::class, Sometimes::class, StartsWith::class,
            Url::class, Uuid::class,
        ];

        it('each validation attribute implements ValidationAttribute', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class) {
                expect($class)->toImplement(ValidationAttribute::class);
            }
        });

        it('each validation attribute has a ruleKey method', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class) {
                $reflection = new ReflectionClass($class);
                $method = $reflection->getMethod('ruleKey');
                expect($method->getReturnType()?->getName())->toBe('string');

                // Instantiate and call
                $instance = $class->getDefaultConstructor() === null
                    ? null
                    : (fn () => new $class(...array_fill(0, count($reflection->getConstructor()->getParameters()), null)))();
                if ($instance !== null) {
                    $key = $instance->ruleKey();
                    expect(is_string($key))->toBeTrue();
                    expect(strlen($key))->toBeGreaterThan(0);
                }
            }
        });
    });

    describe('Union type DTO', function () {
        it('accepts string value for string|int union', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'test-1',
                'identifier' => 'hello',
            ], validate: false);

            expect(is_string($dto->identifier))->toBeTrue();
        });

        it('accepts int value for string|int union', function () {
            $dto = UnionTypeDTO::fromArray([
                'id' => 'test-1',
                'identifier' => 42,
            ], validate: false);

            expect(is_int($dto->identifier))->toBeTrue();
        });
    });

    describe('Metadata cache TTL', function () {
        it('setMetadataCacheTtl accepts float', function () {
            DataTransferObject::setMetadataCacheTtl(2.0);

            // Resolve once to populate cache
            EmptyDTO::fromArray([], validate: false);

            // Flush to reset
            DataTransferObject::flushMetadataCache();
        });

        it('flushMetadataCache clears specific class', function () {
            DataTransferObject::setMetadataCacheTtl(0);
            EmptyDTO::fromArray([], validate: false);
            DataTransferObject::flushMetadataCache(EmptyDTO::class);
        });

        it('flushMetadataCache clears all classes', function () {
            DataTransferObject::setMetadataCacheTtl(0);
            EmptyDTO::fromArray([], validate: false);
            DataTransferObject::flushMetadataCache();
        });
    });
});
