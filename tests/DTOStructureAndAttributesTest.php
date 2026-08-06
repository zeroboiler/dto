<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
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
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DTO Attribute & Class Structure', function () {
    describe('All attribute classes are final', function () {
        $attributeClasses = [
            Accepted::class,
            Between::class,
            Boolean::class,
            Cast::class,
            Collection::class,
            Confirmed::class,
            Date::class,
            Declined::class,
            DefaultValue::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            EnumAttr::class,
            Hidden::class,
            In::class,
            Integer::class,
            Json::class,
            MapFrom::class,
            Max::class,
            Min::class,
            NestedArray::class,
            Nullable::class,
            Numeric::class,
            Pattern::class,
            Present::class,
            Prohibited::class,
            Required::class,
            RequiredIf::class,
            RequiredUnless::class,
            RequiredWith::class,
            RequiredWithAll::class,
            RequiredWithout::class,
            RequiredWithoutAll::class,
            Same::class,
            Size::class,
            Sometimes::class,
            StartsWith::class,
            Url::class,
            Uuid::class,
            ArrayRule::class,
        ];

        test('each attribute class is final', function () use ($attributeClasses) {
            foreach ($attributeClasses as $class) {
                $ref = new ReflectionClass($class);

                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        });

        test('validation attributes have readonly properties', function () {
            $validationAttrs = [
                Accepted::class,
                Between::class,
                Boolean::class,
                Confirmed::class,
                Date::class,
                Declined::class,
                Different::class,
                Distinct::class,
                Email::class,
                EndsWith::class,
                EnumAttr::class,
                In::class,
                Integer::class,
                Json::class,
                Max::class,
                Min::class,
                NestedArray::class,
                Nullable::class,
                Numeric::class,
                Pattern::class,
                Present::class,
                Prohibited::class,
                Required::class,
                RequiredIf::class,
                RequiredUnless::class,
                RequiredWith::class,
                RequiredWithAll::class,
                RequiredWithout::class,
                RequiredWithoutAll::class,
                Same::class,
                Size::class,
                Sometimes::class,
                StartsWith::class,
                Url::class,
                Uuid::class,
                ArrayRule::class,
                Collection::class,
            ];

            foreach ($validationAttrs as $class) {
                $ref = new ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    if (! $prop->isStatic()) {
                        expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->getName()} should be readonly");
                    }
                }
            }
        });

        test('metadata attributes have readonly properties', function () {
            $metaAttrs = [
                Cast::class,
                DefaultValue::class,
                Hidden::class,
                MapFrom::class,
            ];

            foreach ($metaAttrs as $class) {
                $ref = new ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    if (! $prop->isStatic()) {
                        expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->getName()} should be readonly");
                    }
                }
            }
        });
    });

    describe('Core classes are final', function () {
        $coreClasses = [
            DataTransferObject::class => false, // abstract, cannot be final in PHP, but base class
            DtoCollection::class => true,
            DTOException::class => true,
            \ZeroBoiler\DTO\DTOManager::class => true,
            \ZeroBoiler\DTO\DTOSServiceProvider::class => true,
            \ZeroBoiler\DTO\Support\DtoMetadataResolver::class => true,
            \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class => true,
            \ZeroBoiler\DTO\Casts\DTOCast::class => true,
            \ZeroBoiler\DTO\Facades\DTO::class => true,
        ];

        test('core service classes are final', function () use ($coreClasses) {
            foreach ($coreClasses as $class => $shouldBeFinal) {
                if ($shouldBeFinal) {
                    $ref = new ReflectionClass($class);

                    expect($ref->isFinal())->toBeTrue("{$class} should be final");
                }
            }
        });
    });

    describe('Contracts are interfaces', function () {
        $contracts = [
            \ZeroBoiler\DTO\Contracts\FromRequestDTO::class,
            \ZeroBoiler\DTO\Contracts\ValidatableDTO::class,
            \ZeroBoiler\DTO\Contracts\ValidationAttribute::class,
        ];

        test('all contracts are interfaces', function () use ($contracts) {
            foreach ($contracts as $contract) {
                $ref = new ReflectionClass($contract);

                expect($ref->isInterface())->toBeTrue("{$contract} should be an interface");
            }
        });
    });

    describe('DtoCollection interface implementations', function () {
        test('implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
            $ref = new ReflectionClass(DtoCollection::class);

            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    describe('Fixture DTO structure', function () {
        test('CreateUserDTO has all expected properties', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);
            $props = $ref->getProperties();

            $propNames = array_map(fn (\ReflectionProperty $p): string => $p->getName(), $props);

            expect($propNames)->toContain('email');
            expect($propNames)->toContain('name');
            expect($propNames)->toContain('status');
            expect($propNames)->toContain('tags');
            expect($propNames)->toContain('phone');
            expect($propNames)->toContain('password');
        });

        test('CreateUserDTO properties are public readonly', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isPublic())->toBeTrue("CreateUserDTO::\${$prop->getName()} should be public");
                expect($prop->isReadOnly())->toBeTrue("CreateUserDTO::\${$prop->getName()} should be readonly");
            }
        });

        test('EmptyDTO can be created with no data', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
        });

        test('UnionTypeDTO has union type property', function () {
            $ref = new ReflectionClass(UnionTypeDTO::class);
            $prop = $ref->getProperty('identifier');
            $type = $prop->getType();

            expect($type)->toBeInstanceOf(\ReflectionUnionType::class);
        });
    });

    describe('DataTransferObject serialization consistency', function () {
        test('toArray and allValues differ when Hidden is used', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $array = $dto->toArray();
            $all = $dto->allValues();

            expect($array)->not->toHaveKey('password');
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        test('only with single string arg', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            expect($dto->only('email'))->toBe(['email' => 'test@example.com']);
        });

        test('except with single string arg', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        test('toJson returns valid JSON string', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar', 'bar' => 'baz'], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBe(['foo' => 'bar', 'bar' => 'baz']);
        });

        test('jsonSerialize matches toArray', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('DataTransferObject immutability', function () {
        test('with returns new instance', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'original', 'bar' => 'kept'], validate: false);
            $updated = $dto->with(['foo' => 'updated']);

            expect($dto)->not->toBe($updated);
            expect($dto->foo)->toBe('original');
            expect($updated->foo)->toBe('updated');
            expect($updated->bar)->toBe('kept');
        });

        test('readonly properties prevent mutation', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $ref = new ReflectionProperty($dto, 'foo');

            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->isPublic())->toBeTrue();
        });
    });

    describe('DTOException', function () {
        test('invalidCast includes property and type info', function () {
            $ex = DTOException::invalidCast('price', 'float', 'string');

            expect($ex->getMessage())->toContain('price');
            expect($ex->getMessage())->toContain('float');
            expect($ex->getMessage())->toContain('string');
        });

        test('invalidJson includes property and error message', function () {
            $ex = DTOException::invalidJson('payload', 'Syntax error');

            expect($ex->getMessage())->toContain('payload');
            expect($ex->getMessage())->toContain('Syntax error');
        });

        test('is final', function () {
            $ref = new ReflectionClass(DTOException::class);

            expect($ref->isFinal())->toBeTrue();
        });
    });

    describe('rules() and rulesFor()', function () {
        test('rules returns array with string keys', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            foreach (array_keys($rules) as $key) {
                expect($key)->toBeString();
            }
        });

        test('rulesFor returns rules by default', function () {
            $rules = EmptyDTO::rules();
            $rulesFor = EmptyDTO::rulesFor('create');

            expect($rulesFor)->toBe($rules);
        });

        test('rulesFor update returns same as rules by default', function () {
            $rules = EmptyDTO::rules();
            $rulesFor = EmptyDTO::rulesFor('update');

            expect($rulesFor)->toBe($rules);
        });

        test('rulesFor patch returns same as rules by default', function () {
            $rules = EmptyDTO::rules();
            $rulesFor = EmptyDTO::rulesFor('patch');

            expect($rulesFor)->toBe($rules);
        });
    });

    describe('fromPartialArray edge cases', function () {
        test('empty data uses all defaults', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });

        test('partial data overrides only specified fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated Name');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });
    });

    describe('DtoCollection iteration', function () {
        test('foreach iterates over all items', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $results = [];
            foreach ($collection as $dto) {
                $results[] = $dto->foo;
            }

            expect($results)->toEqual(['a', 'b']);
        });

        test('ArrayAccess offsetExists works', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$d1]);

            expect(isset($collection[0]))->toBeTrue();
            expect(isset($collection[1]))->toBeFalse();
        });

        test('ArrayAccess offsetGet returns null for missing', function () {
            $collection = new DtoCollection;

            expect($collection[99])->toBeNull();
        });

        test('allValues includes all properties from each DTO', function () {
            $d1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);
            $collection = new DtoCollection([$d1]);

            $all = $collection->allValues();

            expect($all[0])->toHaveKey('password');
        });
    });
});
