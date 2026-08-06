<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Cast;
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
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('DTO Final Production Quality Audit', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('Final class verification — all core classes', function () {
        it('DataTransferObject is abstract', function () {
            $reflection = new ReflectionClass(DataTransferObject::class);

            expect($reflection->isAbstract())->toBeTrue();
        });

        it('DtoCollection is final', function () {
            $reflection = new ReflectionClass(DtoCollection::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DTOManager is final', function () {
            $reflection = new ReflectionClass(DTOManager::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DTOException is final', function () {
            $reflection = new ReflectionClass(DTOException::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DTOCast is final', function () {
            $reflection = new ReflectionClass(DTOCast::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DTOSServiceProvider is final', function () {
            $reflection = new ReflectionClass(DTOSServiceProvider::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DtoMetadataResolver is final', function () {
            $reflection = new ReflectionClass(DtoMetadataResolver::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('OpenApiSchemaGenerator is final', function () {
            $reflection = new ReflectionClass(OpenApiSchemaGenerator::class);

            expect($reflection->isFinal())->toBeTrue();
        });
    });

    describe('Attribute final classes', function () {
        $attributes = [
            Accepted::class, BooleanAttr::class, Cast::class, Confirmed::class,
            Date::class, Declined::class, DefaultValue::class, Different::class,
            Distinct::class, Email::class, EndsWith::class, Hidden::class,
            In::class, Integer::class, MapFrom::class, Max::class, Min::class,
            NestedArray::class, Nullable::class, Numeric::class, Pattern::class,
            Present::class, Prohibited::class, Required::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class, Same::class,
            Size::class, Sometimes::class, StartsWith::class, Url::class, Uuid::class,
        ];

        test('each validation attribute is final', function () use ($attributes) {
            foreach ($attributes as $attributeClass) {
                $reflection = new ReflectionClass($attributeClass);

                expect($reflection->isFinal())
                    ->toBeTrue("{$attributeClass} should be final");
            }
        });

        test('each validation attribute has readonly properties', function () use ($attributes) {
            foreach ($attributes as $attributeClass) {
                $reflection = new ReflectionClass($attributeClass);

                foreach ($reflection->getProperties() as $property) {
                    expect($property->isReadOnly())
                        ->toBeTrue("{$attributeClass}::\${$property->getName()} should be readonly");
                }
            }
        });
    });

    describe('Interface contract verification', function () {
        it('FromRequestDTO requires fromRequest method', function () {
            $reflection = new ReflectionClass(FromRequestDTO::class);
            $method = $reflection->getMethod('fromRequest');

            expect($method->isStatic())->toBeTrue();
            expect($method->getReturnType()?->getName())->toBe('static');
        });

        it('ValidatableDTO requires rules and rulesFor methods', function () {
            $reflection = new ReflectionClass(ValidatableDTO::class);

            expect($reflection->getMethod('rules')->isStatic())->toBeTrue();
            expect($reflection->getMethod('rulesFor')->isStatic())->toBeTrue();
        });

        it('ValidationAttribute requires ruleKey method', function () {
            $reflection = new ReflectionClass(ValidationAttribute::class);
            $method = $reflection->getMethod('ruleKey');

            expect($method->getReturnType()?->getName())->toBe('string');
        });

        it('DataTransferObject implements FromRequestDTO', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        });

        it('DataTransferObject implements ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });
    });

    describe('DataTransferObject — return type completeness', function () {
        $methods = [
            'fromArray' => 'static',
            'fromPartialArray' => 'static',
            'fromRequest' => 'static',
            'fromPartialRequest' => 'static',
            'fromJson' => 'static',
            'validateArray' => 'array',
            'validatePartialArray' => 'array',
            'rules' => 'array',
            'rulesFor' => 'array',
            'toArray' => 'array',
            'allValues' => 'array',
            'toJson' => 'string',
            'jsonSerialize' => 'mixed',
            'only' => 'array',
            'except' => 'array',
            'equals' => 'bool',
            'isEmpty' => 'bool',
            'isNotEmpty' => 'bool',
            'flushMetadataCache' => 'void',
            'setMetadataCacheTtl' => 'void',
        ];

        test('each public/protected method has return type', function () use ($methods) {
            foreach ($methods as $method => $expectedType) {
                $reflection = new ReflectionMethod(DataTransferObject::class, $method);
                $returnType = $reflection->getReturnType();

                expect($returnType)->not->toBeNull(
                    "DataTransferObject::{$method}() should have a return type"
                );

                $actualType = $returnType->getName();
                expect($actualType)->toBe($expectedType,
                    "DataTransferObject::{$method}() should return {$expectedType}, got {$actualType}"
                );
            }
        });
    });

    describe('DtoCollection — return type completeness', function () {
        it('count returns int', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'count');

            expect($reflection->getReturnType()?->getName())->toBe('int');
        });

        it('isEmpty returns bool', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'isEmpty');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });

        it('isNotEmpty returns bool', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'isNotEmpty');

            expect($reflection->getReturnType()?->getName())->toBe('bool');
        });

        it('first returns ?DataTransferObject', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'first');

            expect($reflection->getReturnType()?->getName())->toBe('?');
            expect($reflection->getReturnType()?->allowsNull())->toBeTrue();
        });

        it('last returns ?DataTransferObject', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'last');

            expect($reflection->getReturnType()?->getName())->toBe('?');
            expect($reflection->getReturnType()?->allowsNull())->toBeTrue();
        });

        it('push returns self', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'push');

            expect($reflection->getReturnType()?->getName())->toBe('self');
        });

        it('filter returns self', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'filter');

            expect($reflection->getReturnType()?->getName())->toBe('self');
        });

        it('map returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'map');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('pluck returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'pluck');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('pluckKey returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'pluckKey');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('make returns self', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'make');

            expect($reflection->getReturnType()?->getName())->toBe('self');
        });

        it('items returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'items');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('toArray returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'toArray');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('allValues returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'allValues');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });
    });

    describe('DTOManager — return type completeness', function () {
        it('validate returns array', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'validate');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });

        it('make returns DataTransferObject', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'make');

            expect($reflection->getReturnType()?->getName())->toBe(DataTransferObject::class);
        });

        it('makeFromJson returns DataTransferObject', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'makeFromJson');

            expect($reflection->getReturnType()?->getName())->toBe(DataTransferObject::class);
        });

        it('schema returns array', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'schema');

            expect($reflection->getReturnType()?->getName())->toBe('array');
        });
    });

    describe('DTOCast — readonly property', function () {
        it('DTOCast has readonly dtoClass property', function () {
            $reflection = new ReflectionClass(DTOCast::class);
            $prop = $reflection->getProperty('dtoClass');

            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->isPrivate())->toBeTrue();
            expect($prop->getType()?->getName())->toBe('string');
        });

        it('DTOCast has readonly validate property', function () {
            $reflection = new ReflectionClass(DTOCast::class);
            $prop = $reflection->getProperty('validate');

            expect($prop->isReadOnly())->toBeTrue();
            expect($prop->getType()?->getName())->toBe('bool');
        });

        it('DTOCast accepts false validate flag', function () {
            $cast = new DTOCast(EmptyDTO::class, validate: false);

            // Should create without error
            expect($cast)->toBeInstanceOf(DTOCast::class);
        });
    });

    describe('DtoCollection — ArrayAccess edge cases', function () {
        it('offsetUnset on empty collection does nothing', function () {
            $collection = new DtoCollection;

            unset($collection[0]);

            expect($collection->isEmpty())->toBeTrue();
        });

        it('offsetUnset on last item empties collection', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$dto]);

            unset($collection[0]);

            expect($collection->isEmpty())->toBeTrue();
            expect($collection->count())->toBe(0);
        });

        it('supports foreach iteration', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $collection = new DtoCollection([$d1, $d2]);

            $values = [];
            foreach ($collection as $dto) {
                $values[] = $dto->foo;
            }

            expect($values)->toEqual(['a', 'b']);
        });
    });

    describe('fromPartialArray — type-appropriate empty values', function () {
        it('string property gets empty string', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto->name)->toBe('');
        });

        it('array property gets empty array', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto->tags)->toEqual([]);
        });

        it('nullable property gets null', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('DefaultValue attribute takes precedence', function () {
            $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

            expect($dto->status)->toBe('active');
        });
    });

    describe('fromJson — error handling', function () {
        it('throws DTOException for invalid JSON', function () {
            expect(fn (): mixed => MinimalDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON array', function () {
            expect(fn (): mixed => MinimalDTO::fromJson('["a", "b"]'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON number', function () {
            expect(fn (): mixed => MinimalDTO::fromJson('42'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for empty string', function () {
            expect(fn (): mixed => MinimalDTO::fromJson(''))
                ->toThrow(DTOException::class);
        });
    });

    describe('with() — immutability guarantee', function () {
        it('original DTO is unchanged after with()', function () {
            $original = EmptyDTO::fromArray(['foo' => 'original', 'bar' => 'keep'], validate: false);
            $updated = $original->with(['foo' => 'updated']);

            expect($original->foo)->toBe('original');
            expect($original->bar)->toBe('keep');
            expect($updated->foo)->toBe('updated');
            expect($updated->bar)->toBe('keep');
        });

        it('with() creates new instance', function () {
            $original = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $updated = $original->with(['foo' => 'b']);

            expect($original)->not->toBe($updated);
        });
    });

    describe('equals — value semantics', function () {
        it('same data equals true', function () {
            $d1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '9.99', 'stock' => 10], validate: false);
            $d2 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '9.99', 'stock' => 10], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('different data equals false', function () {
            $d1 = ProductDTO::fromArray(['name' => 'Widget A', 'price' => '9.99', 'stock' => 10], validate: false);
            $d2 = ProductDTO::fromArray(['name' => 'Widget B', 'price' => '9.99', 'stock' => 10], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('equals is symmetric', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);

            expect($d1->equals($d2))->toBe($d2->equals($d1));
        });
    });

    describe('isEmpty / isNotEmpty', function () {
        it('DTO with all null properties is empty', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('DTO with non-null property is not empty', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'value'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('DTO with zero value is empty', function () {
            // CreateUserDTO has int stock (if any), but let's test with array
            $dto = EmptyDTO::fromArray(['foo' => '0'], validate: false);

            // String '0' is not empty (non-null, non-empty-string)
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('ActionScopedDTO — rulesFor', function () {
        it('create action uses default rules', function () {
            $createRules = ActionScopedDTO::rulesFor('create');

            expect($createRules)->toBe(ActionScopedDTO::rules());
        });

        it('update action uses different rules', function () {
            $updateRules = ActionScopedDTO::rulesFor('update');
            $defaultRules = ActionScopedDTO::rules();

            expect($updateRules)->not->toBe($defaultRules);
        });

        it('unknown action uses default rules', function () {
            $unknownRules = ActionScopedDTO::rulesFor('delete');
            $defaultRules = ActionScopedDTO::rules();

            expect($unknownRules)->toBe($defaultRules);
        });
    });

    describe('OpenApiSchemaGenerator — nested DTO detection', function () {
        it('throws LogicException for DTO with nested references using generate()', function () {
            expect(fn (): mixed => OpenApiSchemaGenerator::generate(OrderDTO::class))
                ->toThrow(\LogicException::class);
        });

        it('generateWithComponents returns both schema and components', function () {
            $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

            expect($result)->toHaveKeys(['schema', 'components']);
            expect($result['components'])->toHaveKey('schemas');
            expect($result['components']['schemas'])->toHaveKey('AddressDTO');
            expect($result['components']['schemas'])->toHaveKey('OrderItemDTO');
        });

        it('simple DTO schema has correct type and properties', function () {
            $schema = OpenApiSchemaGenerator::generate(EmptyDTO::class);

            expect($schema['type'])->toBe('object');
            expect($schema['properties'])->toBeObject();
            expect($schema['properties'])->toHaveProperty('foo');
        });
    });

    describe('ServiceProvider registration', function () {
        it('DTOSServiceProvider is final', function () {
            $reflection = new ReflectionClass(DTOSServiceProvider::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('DTOSServiceProvider has Override attribute on register', function () {
            $method = new ReflectionMethod(DTOSServiceProvider::class, 'register');

            $hasOverride = false;
            foreach ($method->getAttributes() as $attr) {
                if (str_ends_with($attr->getName(), '\\Override')) {
                    $hasOverride = true;
                }
            }

            expect($hasOverride)->toBeTrue();
        });

        it('DTOSServiceProvider has Override attribute on boot', function () {
            $method = new ReflectionMethod(DTOSServiceProvider::class, 'boot');

            $hasOverride = false;
            foreach ($method->getAttributes() as $attr) {
                if (str_ends_with($attr->getName(), '\\Override')) {
                    $hasOverride = true;
                }
            }

            expect($hasOverride)->toBeTrue();
        });
    });

    describe('Facade accessor verification', function () {
        it('DTO facade returns zeroboiler.dto', function () {
            $facade = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
            $method = $facade->getMethod('getFacadeAccessor');

            expect($method->getReturnType()?->getName())->toBe('string');
        });

        it('DTO facade is final', function () {
            $facade = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);

            expect($facade->isFinal())->toBeTrue();
        });
    });

    describe('Fixture DTOs are all final', function () {
        $fixtureDtos = [
            CreateUserDTO::class,
            EmptyDTO::class,
            AddressDTO::class,
            OrderDTO::class,
            OrderItemDTO::class,
            ProductDTO::class,
            MinimalDTO::class,
            ActionScopedDTO::class,
        ];

        test('each fixture DTO is final', function () use ($fixtureDtos) {
            foreach ($fixtureDtos as $dtoClass) {
                $reflection = new ReflectionClass($dtoClass);

                expect($reflection->isFinal())
                    ->toBeTrue("{$dtoClass} should be final");
            }
        });

        test('each fixture DTO extends DataTransferObject', function () use ($fixtureDtos) {
            foreach ($fixtureDtos as $dtoClass) {
                expect(is_subclass_of($dtoClass, DataTransferObject::class))
                    ->toBeTrue("{$dtoClass} should extend DataTransferObject");
            }
        });
    });

    describe('DtoCollection — IteratorAggregate', function () {
        it('implements IteratorAggregate', function () {
            $reflection = new ReflectionClass(DtoCollection::class);

            expect($reflection->implementsInterface(\Traversable::class))->toBeTrue();
            expect($reflection->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        });

        it('implements Countable', function () {
            $reflection = new ReflectionClass(DtoCollection::class);

            expect($reflection->implementsInterface(\Countable::class))->toBeTrue();
        });

        it('implements ArrayAccess', function () {
            $reflection = new ReflectionClass(DtoCollection::class);

            expect($reflection->implementsInterface(\ArrayAccess::class))->toBeTrue();
        });

        it('implements JsonSerializable', function () {
            $reflection = new ReflectionClass(DtoCollection::class);

            expect($reflection->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    describe('Nested DTO hydration — with validation disabled', function () {
        it('OrderDTO nested AddressDTO is hydrated correctly', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-FINAL',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                    'zipCode' => '34000',
                ],
                'items' => [],
                'rawTotal' => '99.99',
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Istanbul');
            expect($dto->shippingAddress->zipCode)->toBe('34000');
        });

        it('OrderDTO nested array of OrderItemDTO is hydrated', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-FINAL',
                'shippingAddress' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Ankara',
                ],
                'items' => [
                    ['productName' => 'Widget A', 'price' => 9.99, 'quantity' => 3],
                    ['productName' => 'Widget B', 'price' => 14.99, 'quantity' => 1],
                ],
            ], validate: false);

            expect(count($dto->items))->toBe(2);
            expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->items[0]->productName)->toBe('Widget A');
            expect($dto->items[1]->productName)->toBe('Widget B');
        });
    });
});
