<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
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
use ZeroBoiler\DTO\Attributes\Enum;
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
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Rules\DtoRule;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('V29 Full Type Safety and Docblock Audit', function () {
    // ── Attribute Contract Compliance ─────────────────────────────────────

    describe('attribute contract compliance', function () {
        it('all validation attributes implement ValidationAttribute', function () {
            $validationAttrs = [
                Accepted::class, Boolean::class, Cast::class, Collection::class,
                Confirmed::class, Date::class, Declined::class, Different::class,
                Distinct::class, Email::class, EndsWith::class, Enum::class,
                Hidden::class, In::class, Integer::class, Json::class, Max::class,
                Min::class, NestedArray::class, Nullable::class, Numeric::class,
                Pattern::class, Present::class, Prohibited::class, Required::class,
                RequiredIf::class, RequiredUnless::class, RequiredWith::class,
                RequiredWithout::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($validationAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
                expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue(
                    "{$attrClass} must implement ValidationAttribute"
                );

                // ruleKey() must return string
                $ruleKeyMethod = $ref->getMethod('ruleKey');
                expect($ruleKeyMethod->getReturnType()->getName())->toBe('string');
            }
        });

        it('all validation attributes have TARGET_PROPERTY flag', function () {
            $validationAttrs = [
                Accepted::class, Boolean::class, Cast::class, Collection::class,
                Confirmed::class, Date::class, Declined::class, Different::class,
                Distinct::class, Email::class, EndsWith::class, Enum::class,
                Hidden::class, In::class, Integer::class, Json::class, Max::class,
                Min::class, NestedArray::class, Nullable::class, Numeric::class,
                Pattern::class, Present::class, Prohibited::class, Required::class,
                RequiredIf::class, RequiredUnless::class, RequiredWith::class,
                RequiredWithout::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($validationAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $attrs = $ref->getAttributes();
                expect($attrs)->not->toBeEmpty("{$attrClass} must have #[Attribute]");

                $newInstance = $attrs[0]->newInstance();
                $flags = $newInstance->getFlags();
                expect($flags & Attribute::TARGET_PROPERTY)->toBeGreaterThan(0,
                    "{$attrClass} must target TARGET_PROPERTY");
            }
        });

        it('all validation attributes have readonly promoted constructor properties', function () {
            $validationAttrs = [
                Accepted::class, Boolean::class, Cast::class, Collection::class,
                Confirmed::class, Date::class, Declined::class, Different::class,
                Distinct::class, Email::class, EndsWith::class, Enum::class,
                In::class, Integer::class, Json::class, Max::class,
                Min::class, NestedArray::class, Nullable::class, Numeric::class,
                Pattern::class, Present::class, Prohibited::class, Required::class,
                RequiredIf::class, RequiredUnless::class, RequiredWith::class,
                RequiredWithout::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($validationAttrs as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                $constructor = $ref->getConstructor();

                if ($constructor === null) {
                    continue; // Hidden has no constructor
                }

                foreach ($constructor->getParameters() as $param) {
                    expect($param->isPromoted())->toBeTrue(
                        "{$attrClass}::\${$param->name} must be promoted"
                    );
                }

                foreach ($ref->getProperties() as $prop) {
                    expect($prop->isReadOnly())->toBeTrue(
                        "{$attrClass}::\${$prop->name} must be readonly"
                    );
                }
            }
        });

        it('Hidden is a metadata-only attribute (no ValidationAttribute)', function () {
            $ref = new ReflectionClass(Hidden::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse();
            expect($ref->getConstructor())->toBeNull();
        });

        it('MapFrom is a metadata-only attribute', function () {
            $ref = new ReflectionClass(MapFrom::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse();
        });

        it('DefaultValue is a metadata-only attribute', function () {
            $ref = new ReflectionClass(DefaultValue::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse();
        });

        it('Collection has class-string<DataTransferObject> typed property', function () {
            $ref = new ReflectionClass(Collection::class);
            $prop = $ref->getProperty('dtoClass');

            expect($prop->hasType())->toBeTrue();
            expect($prop->isReadOnly())->toBeTrue();
        });

        it('NestedArray has class-string<DataTransferObject> typed property', function () {
            $ref = new ReflectionClass(NestedArray::class);
            $prop = $ref->getProperty('dtoClass');

            expect($prop->hasType())->toBeTrue();
            expect($prop->isReadOnly())->toBeTrue();
        });

        it('Enum attribute has class-string<BackedEnum> typed property', function () {
            $ref = new ReflectionClass(Enum::class);
            $prop = $ref->getProperty('enumClass');

            expect($prop->hasType())->toBeTrue();
            expect($prop->isReadOnly())->toBeTrue();
        });
    });

    // ── Service Class Structure ──────────────────────────────────────────

    describe('service class structure', function () {
        it('DataTransferObject is abstract and implements all 5 contracts', function () {
            $ref = new ReflectionClass(DataTransferObject::class);

            expect($ref->isAbstract())->toBeTrue();
            expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
            expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
            // Additional interfaces via ArrayAccess, Countable, etc.
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        });

        it('DtoCollection is final and implements multiple interfaces', function () {
            $ref = new ReflectionClass(DtoCollection::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        });

        it('DTOManager is final readonly', function () {
            $ref = new ReflectionClass(DTOManager::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('DTOException is final with named constructors', function () {
            $ref = new ReflectionClass(DTOException::class);

            expect($ref->isFinal())->toBeTrue();

            // Must have named constructors
            expect($ref->hasMethod('missingRequired'))->toBeTrue();
            expect($ref->hasMethod('invalidJson'))->toBeTrue();

            $missingRequired = $ref->getMethod('missingRequired');
            expect($missingRequired->isStatic())->toBeTrue();
            expect($missingRequired->getReturnType()->getName())->toBe('self');

            $invalidJson = $ref->getMethod('invalidJson');
            expect($invalidJson->isStatic())->toBeTrue();
            expect($invalidJson->getReturnType()->getName())->toBe('self');
        });

        it('DTOCast is final', function () {
            $ref = new ReflectionClass(DTOCast::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('DTOSServiceProvider is final', function () {
            $ref = new ReflectionClass(DTOSServiceProvider::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('DTO facade is final', function () {
            $ref = new ReflectionClass(DTO::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });

        it('DtoMetadataResolver is final', function () {
            $ref = new ReflectionClass(DtoMetadataResolver::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('DtoRule is final readonly', function () {
            $ref = new ReflectionClass(DtoRule::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });
    });

    // ── Contract Interface Completeness ──────────────────────────────────

    describe('contract interface completeness', function () {
        it('ValidatableDTO defines rules(), validateArray(), validatePartialArray()', function () {
            $ref = new ReflectionClass(ValidatableDTO::class);

            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('rules'))->toBeTrue();
            expect($ref->hasMethod('validateArray'))->toBeTrue();
            expect($ref->hasMethod('validatePartialArray'))->toBeTrue();
            expect($ref->hasMethod('rulesFor'))->toBeTrue();

            $rulesMethod = $ref->getMethod('rules');
            expect($rulesMethod->getReturnType()->getName())->toBe('array');
        });

        it('FromRequestDTO defines fromRequest() and fromPartialRequest()', function () {
            $ref = new ReflectionClass(FromRequestDTO::class);

            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('fromRequest'))->toBeTrue();
            expect($ref->hasMethod('fromPartialRequest'))->toBeTrue();
        });

        it('ValidationAttribute defines ruleKey()', function () {
            $ref = new ReflectionClass(ValidationAttribute::class);

            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('ruleKey'))->toBeTrue();

            $ruleKeyMethod = $ref->getMethod('ruleKey');
            expect($ruleKeyMethod->getReturnType()->getName())->toBe('string');
        });
    });

    // ── Return Type Completeness ─────────────────────────────────────────

    describe('return type completeness', function () {
        it('DataTransferObject key methods have explicit return types', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            $methods = ['toArray', 'allValues', 'toJson', 'only', 'except',
                'with', 'equals', 'isEmpty', 'rules', 'rulesFor',
                'validateArray', 'validatePartialArray', 'fromArray',
                'fromJson', 'fromRequest', 'fromPartialArray', 'fromPartialRequest',
                'keys', 'count'];

            foreach ($methods as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "DataTransferObject::{$methodName}() must have a return type"
                );
            }
        });

        it('DtoCollection key methods have explicit return types', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            $methods = ['pluck', 'pluckKey', 'map', 'filter', 'items',
                'count', 'isEmpty', 'isNotEmpty', 'first', 'last',
                'push', 'append', 'merge', 'toArrayBy', 'toDictionary',
                'make'];

            foreach ($methods as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "DtoCollection::{$methodName}() must have a return type"
                );
            }
        });

        it('DTOManager methods have explicit return types', function () {
            $ref = new ReflectionClass(DTOManager::class);

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $returnType = $method->getReturnType();

                expect($returnType)->not->toBeNull(
                    "DTOManager::{$method->name}() must have a return type"
                );
            }
        });
    });

    // ── Fixture DTO Structure ──────────────────────────────────────────

    describe('fixture DTO structure', function () {
        it('CreateUserDTO is final and extends DataTransferObject', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(DataTransferObject::class))->toBeTrue();
        });

        it('AddressDTO is final and extends DataTransferObject', function () {
            $ref = new ReflectionClass(AddressDTO::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(DataTransferObject::class))->toBeTrue();
        });

        it('OrderDTO has nested DTO and array of DTOs', function () {
            $ref = new ReflectionClass(OrderDTO::class);
            $constructor = $ref->getConstructor();

            $shippingAddressParam = null;
            $itemsParam = null;

            foreach ($constructor->getParameters() as $param) {
                if ($param->getName() === 'shippingAddress') {
                    $shippingAddressParam = $param;
                }
                if ($param->getName() === 'items') {
                    $itemsParam = $param;
                }
            }

            expect($shippingAddressParam)->not->toBeNull();
            expect($itemsParam)->not->toBeNull();
        });

        it('all fixture DTO properties are public readonly', function () {
            $fixtures = [CreateUserDTO::class, AddressDTO::class, OrderDTO::class];

            foreach ($fixtures as $dtoClass) {
                $ref = new ReflectionClass($dtoClass);

                foreach ($ref->getProperties() as $prop) {
                    expect($prop->isPublic())->toBeTrue(
                        "{$dtoClass}::\${$prop->name} must be public"
                    );
                    expect($prop->isReadOnly())->toBeTrue(
                        "{$dtoClass}::\${$prop->name} must be readonly"
                    );
                }
            }
        });
    });

    // ── DtoCollection Contract Tests ──────────────────────────────────────

    describe('DtoCollection contract tests', function () {
        it('make() factory creates instance', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'Bob',
                'status' => 'active',
            ]);

            $collection = DtoCollection::make([$dto1, $dto2]);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('implements ArrayAccess, Countable, IteratorAggregate', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            $collection = new DtoCollection([$dto]);

            // ArrayAccess
            expect(isset($collection[0]))->toBeTrue();
            expect($collection[0])->toBeInstanceOf(CreateUserDTO::class);

            // Countable
            expect(count($collection))->toBe(1);

            // IteratorAggregate
            expect($collection->getIterator())->toBeInstanceOf(\ArrayIterator::class);
        });

        it('push mutates in-place and returns same instance', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'A', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'B', 'status' => 'active',
            ]);

            $collection = new DtoCollection([$dto1]);
            $result = $collection->push($dto2);

            expect($result)->toBe($collection); // same instance
            expect($collection->count())->toBe(2);
        });

        it('append returns new instance (immutable)', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'A', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'B', 'status' => 'active',
            ]);

            $original = new DtoCollection([$dto1]);
            $new = $original->append($dto2);

            expect($new)->not->toBe($original);
            expect($original->count())->toBe(1);
            expect($new->count())->toBe(2);
        });

        it('pluck extracts single field', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'Alice', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'Bob', 'status' => 'active',
            ]);

            $collection = new DtoCollection([$dto1, $dto2]);
            $emails = $collection->pluck('email');

            expect($emails)->toBe(['a@example.com', 'b@example.com']);
        });

        it('pluckKey builds key/value map', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'Alice', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'Bob', 'status' => 'active',
            ]);

            $collection = new DtoCollection([$dto1, $dto2]);
            $map = $collection->pluckKey('email', 'name');

            expect($map)->toBe([
                'a@example.com' => 'Alice',
                'b@example.com' => 'Bob',
            ]);
        });

        it('filter returns new DtoCollection', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'Alice', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'Bob', 'status' => 'inactive',
            ]);

            $collection = new DtoCollection([$dto1, $dto2]);
            $active = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->status === 'active');

            expect($active)->toBeInstanceOf(DtoCollection::class);
            expect($active->count())->toBe(1);
        });

        it('merge combines two collections', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com', 'name' => 'A', 'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com', 'name' => 'B', 'status' => 'active',
            ]);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($merged)->not->toBe($col1);
        });
    });

    // ── DTOException Named Constructors ─────────────────────────────────

    describe('DTOException named constructors', function () {
        it('missingRequired creates with field and class', function () {
            $e = DTOException::missingRequired('email', CreateUserDTO::class);

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('email');
        });

        it('invalidJson creates with message', function () {
            $e = DTOException::invalidJson('(root)', 'Syntax error');

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function () {
            $e = DTOException::missingRequired('email', CreateUserDTO::class);

            $str = (string) $e;
            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('email');
        });
    });

    // ── Strict Types Enforcement ──────────────────────────────────────

    describe('strict types enforcement', function () {
        it('all source files declare strict_types=1', function () {
            $srcDir = dirname((new ReflectionClass(DataTransferObject::class))->getFileName());
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $checked = 0;
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();
                expect($contents)->toContain('declare(strict_types=1)',
                    "{$file->getFilename()} must declare strict_types=1");
                $checked++;
            }

            expect($checked)->toBeGreaterThan(0);
            expect($checked)->toBeGreaterThanOrEqual(55,
                "Expected at least 55 source files, found {$checked}");
        });
    });

    // ── Validation Rule Generation ──────────────────────────────────────

    describe('validation rule generation', function () {
        it('CreateUserDTO generates expected rules', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');

            // Email field must have 'email' rule
            expect($rules['email'])->toContain('email');

            // Name field must have min and max
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('rules returns consistent structure', function () {
            $rules = CreateUserDTO::rules();

            foreach ($rules as $field => $fieldRules) {
                expect($fieldRules)->toBeArray();
                foreach ($fieldRules as $rule) {
                    expect($rule)->toBeString();
                }
            }
        });
    });

    // ── Serialization Exclusion ─────────────────────────────────────────

    describe('serialization exclusion', function () {
        it('toArray excludes #[Hidden] properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'password' => 'secret123',
            ]);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('password');
        });

        it('allValues includes #[Hidden] properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'password' => 'secret123',
            ]);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });
    });

    // ── Selective Output ────────────────────────────────────────────────

    describe('selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            $result = $dto->only('email');
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('except removes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            $result = $dto->except('status');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('status');
        });
    });

    // ── MapFrom Key Alias ────────────────────────────────────────────────

    describe('MapFrom key alias', function () {
        it('maps source key to property name', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'phone_number' => '+905551234567',
            ]);

            expect($dto->phone)->toBe('+905551234567');
        });

        it('toArray uses property name not source key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'phone_number' => '+905551234567',
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('phone');
            expect($array)->not->toHaveKey('phone_number');
        });
    });

    // ── Cast Type Conversion ─────────────────────────────────────────────

    describe('Cast type conversion', function () {
        it('Cast attribute applies type conversion', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
                'tags' => 'php,laravel',
            ]);

            // Cast('array') should convert string to array
            expect($dto->tags)->toBeArray();
        });
    });

    // ── DefaultValue ──────────────────────────────────────────────────

    describe('DefaultValue', function () {
        it('applies default when not provided', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
            ]);

            expect($dto->status)->toBe('active');
        });
    });

    // ── DTO State Checks ────────────────────────────────────────────────

    describe('DTO state checks', function () {
        it('equals returns true for identical DTOs', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'Bob',
                'status' => 'active',
            ]);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns false for non-empty DTO', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            expect($dto->isEmpty())->toBeFalse();
        });
    });
});
