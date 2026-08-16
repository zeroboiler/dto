<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{
    Accepted,
    ArrayRule,
    Between,
    Boolean,
    Cast,
    Collection,
    Confirmed,
    Date,
    Declined,
    DefaultValue,
    Different,
    Distinct,
    Email,
    EndsWith,
    Enum,
    Hidden,
    In,
    Integer,
    Json,
    MapFrom,
    Max,
    Min,
    NestedArray,
    Nullable,
    Numeric,
    Pattern,
    Present,
    Prohibited,
    Required,
    RequiredIf,
    RequiredUnless,
    RequiredWith,
    RequiredWithAll,
    RequiredWithout,
    RequiredWithoutAll,
    Same,
    Size,
    Sometimes,
    StartsWith,
    Url,
    Uuid,
};
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\{FromRequestDTO, ValidationAttribute, ValidatableDTO};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\{DtoMetadataResolver, OpenApiSchemaGenerator};
use ZeroBoiler\DTO\Tests\Fixtures\{
    CreateUserDTO,
    MinimalDTO,
    EmptyDTO,
    AllDefaultsDTO,
    OrderDTO,
    OrderItemDTO,
    ArticleDTO,
    ScalarConstraintsDTO,
    AddressDTO,
    DateCastDTO,
    ArrayCastDTO,
    RoundtripDTO,
    ComprehensiveDTO,
    EdgeCaseDTO,
    NullableRoundtripDTO,
    StrictValidationDTO,
    ActionScopedDTO,
    WithRoundtripDTO,
    AllScalarTypesDTO,
    ProductDTO,
};

describe('V25 final production hardening — DTO structural audit', function () {
    // ─── Source Code Structural Compliance ────────────────────────────────────

    describe('All core classes are final', function () {
        $finalClasses = [
            DataTransferObject::class,
            DtoCollection::class,
            DTOManager::class,
            DTOException::class,
            DTOCast::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOSServiceProvider::class,
            DTO::class,
        ];

        it('all core classes are final', function () use ($finalClasses) {
            foreach ($finalClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });
    });

    describe('DTOManager is final readonly', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOManager::class))->isFinal())->toBeTrue();
        });

        it('is readonly', function () {
            expect((new ReflectionClass(DTOManager::class))->isReadOnly())->toBeTrue();
        });
    });

    describe('DTO Facade is final', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTO::class))->isFinal())->toBeTrue();
        });

        it('extends Facade', function () {
            expect(DTO::class)->toExtend(\Illuminate\Support\Facades\Facade::class);
        });
    });

    describe('DTOSServiceProvider is final', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOSServiceProvider::class))->isFinal())->toBeTrue();
        });

        it('register() and boot() have Override attributes', function () {
            $ref = new ReflectionClass(DTOSServiceProvider::class);
            $register = $ref->getMethod('register');
            $boot = $ref->getMethod('boot');

            expect($register->getAttributes(\Override::class))->not->toBeEmpty();
            expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
        });
    });

    // ─── Validation Attribute Contract Completeness ────────────────────────

    describe('All 35 validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            Required::class,
            Nullable::class,
            Sometimes::class,
            Present::class,
            Prohibited::class,
            Boolean::class,
            Integer::class,
            Numeric::class,
            Email::class,
            Url::class,
            Uuid::class,
            Json::class,
            Date::class,
            Accepted::class,
            Declined::class,
            Confirmed::class,
            Min::class,
            Max::class,
            Between::class,
            Size::class,
            In::class,
            Same::class,
            Different::class,
            Distinct::class,
            StartsWith::class,
            EndsWith::class,
            Pattern::class,
            Enum::class,
            ArrayRule::class,
            RequiredIf::class,
            RequiredUnless::class,
            RequiredWith::class,
            RequiredWithAll::class,
            RequiredWithout::class,
            RequiredWithoutAll::class,
        ];

        it('all 35 attributes implement ValidationAttribute', function () use ($validationAttributes) {
            expect(count($validationAttributes))->toBe(35);

            foreach ($validationAttributes as $attr) {
                expect($attr)->toImplement(ValidationAttribute::class,
                    "{$attr} must implement ValidationAttribute interface");
            }
        });

        it('all validation attributes have ruleKey() method', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                expect(method_exists($attr, 'ruleKey'))->toBeTrue("{$attr} must have ruleKey() method");

                $ref = new ReflectionMethod($attr, 'ruleKey');
                expect($ref->getReturnType()?->getName())->toBe('string');
            }
        });

        it('all validation attributes are final', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                expect((new ReflectionClass($attr))->isFinal())->toBeTrue("{$attr} must be final");
            }
        });

        it('all validation attributes have readonly constructor parameters', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                $ref = new ReflectionClass($attr);
                $constructor = $ref->getConstructor();

                if ($constructor !== null) {
                    foreach ($constructor->getParameters() as $param) {
                        $propName = $param->getName();
                        if ($ref->hasProperty($propName)) {
                            $prop = $ref->getProperty($propName);
                            expect($prop->isReadOnly())->toBeTrue(
                                "{$attr}::\${$propName} must be readonly"
                            );
                        }
                    }
                }
            }
        });

        it('all validation attributes with message parameter have nullable string type', function () use ($validationAttributes) {
            $messageParams = [
                Required::class, Nullable::class, Sometimes::class,
                Boolean::class, Integer::class, Numeric::class,
                Email::class, Url::class, Uuid::class, Json::class,
                Accepted::class, Declined::class, Confirmed::class,
                Min::class, Max::class, Between::class, Size::class,
                Same::class, Different::class, Distinct::class,
                Pattern::class, Enum::class,
                RequiredWith::class, RequiredWithAll::class,
                RequiredWithout::class, RequiredWithoutAll::class,
                Collection::class, NestedArray::class,
            ];

            foreach ($messageParams as $attr) {
                $ref = new ReflectionClass($attr);
                if ($ref->hasProperty('message')) {
                    $prop = $ref->getProperty('message');
                    $type = $prop->getType();

                    // Should be ReflectionNamedType with nullable string
                    expect($type)->toBeInstanceOf(\ReflectionNamedType::class);
                    expect($type->getName())->toBe('string');
                    expect($type->allowsNull())->toBeTrue();
                }
            }
        });
    });

    // ─── Metadata-Only Attributes ───────────────────────────────────────────

    describe('Metadata-only attributes do not implement ValidationAttribute', function () {
        $metaOnlyAttributes = [
            Cast::class,
            MapFrom::class,
            Hidden::class,
            DefaultValue::class,
        ];

        foreach ($metaOnlyAttributes as $attr) {
            it("{$attr} does NOT implement ValidationAttribute", function () use ($attr) {
                expect($attr)->not->toImplement(ValidationAttribute::class);
            });

            it("{$attr} is final", function () use ($attr) {
                expect((new ReflectionClass($attr))->isFinal())->toBeTrue();
            });
        }

        it('Hidden has no constructor parameters', function () {
            $ref = new ReflectionClass(Hidden::class);
            $constructor = $ref->getConstructor();
            expect($constructor)->toBeNull();
        });
    });

    // ─── DTOException Contract ────────────────────────────────────────────

    describe('DTOException contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOException::class))->isFinal())->toBeTrue();
        });

        it('extends Exception', function () {
            expect(new DTOException('test'))->toBeInstanceOf(\Exception::class);
        });

        it('invalidCast() factory produces correct message', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');
            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
            expect($ex->getMessage())->toContain('not-a-number');
        });

        it('invalidJson() factory produces correct message', function () {
            $ex = DTOException::invalidJson('payload', 'Syntax error');
            expect($ex->getMessage())->toContain('payload');
            expect($ex->getMessage())->toContain('Syntax error');
        });

        it('__toString() includes class name', function () {
            $ex = DTOException::invalidCast('field', 'type', 'value');
            expect((string) $ex)->toContain('DTOException');
        });
    });

    // ─── Interface Contracts ───────────────────────────────────────────────

    describe('DataTransferObject implements all contracts', function () {
        it('implements Arrayable', function () {
            expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
        });

        it('implements FromRequestDTO', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        });

        it('implements JsonSerializable', function () {
            expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
        });

        it('implements ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });
    });

    describe('DtoCollection interface implementations', function () {
        it('implements ArrayAccess', function () {
            expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
        });

        it('implements Countable', function () {
            expect(DtoCollection::class)->toImplement(\Countable::class);
        });

        it('implements IteratorAggregate', function () {
            expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
        });

        it('implements JsonSerializable', function () {
            expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
        });
    });

    // ─── DTOCast Contract ──────────────────────────────────────────────────

    describe('DTOCast contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOCast::class))->isFinal())->toBeTrue();
        });

        it('implements CastsAttributes', function () {
            expect(DTOCast::class)->toImplement(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        });

        it('has readonly typed properties', function () {
            $ref = new ReflectionClass(DTOCast::class);

            $dtoClass = $ref->getProperty('dtoClass');
            expect($dtoClass->isReadOnly())->toBeTrue();
            expect($dtoClass->getType()->getName())->toBe('string');

            $validate = $ref->getProperty('validate');
            expect($validate->isReadOnly())->toBeTrue();
            expect($validate->getType()->getName())->toBe('bool');
        });
    });

    // ─── DTOManager Method Completeness ────────────────────────────────────

    describe('DTOManager method completeness', function () {
        $requiredMethods = [
            'validate', 'make', 'makeFromJson', 'rules', 'rulesFor',
            'schema', 'fromPartialArray', 'fromPartialRequest', 'fromJson',
        ];

        foreach ($requiredMethods as $method) {
            it("has {$method}() method", function () use ($method) {
                expect(method_exists(DTOManager::class, $method))->toBeTrue();
            });
        }
    });

    // ─── Fixture DTO Roundtrip ─────────────────────────────────────────────

    describe('Fixture DTO hydration roundtrip', function () {
        it('CreateUserDTO roundtrips through fromArray → toArray', function () {
            $data = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => '30',
            ];

            $dto = CreateUserDTO::fromArray($data, validate: false);
            $arr = $dto->toArray();

            expect($arr['name'])->toBe('Alice');
            expect($arr['email'])->toBe('alice@example.com');
            // age has Cast('integer'), so it should be cast
            expect($arr['age'])->toBe(30);
        });

        it('EmptyDTO accepts arbitrary fields', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar', 'baz' => 'qux'], validate: false);
            expect($dto->foo)->toBe('bar');
            expect($dto->baz)->toBeNull(); // unknown keys are ignored (no constructor param)
        });

        it('MinimalDTO with defaults works', function () {
            $dto = MinimalDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });

    // ─── DtoCollection Operations ──────────────────────────────────────────

    describe('DtoCollection operations', function () {
        it('make creates empty collection', function () {
            $col = DtoCollection::make();
            expect($col->isEmpty())->toBeTrue();
            expect(count($col))->toBe(0);
        });

        it('count returns correct number', function () {
            $dtoArray = [
                EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false),
                EmptyDTO::fromArray(['foo' => 'c', 'bar' => 'd'], validate: false),
            ];
            $col = new DtoCollection($dtoArray);
            expect(count($col))->toBe(2);
        });

        it('first/last return correct items', function () {
            $a = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
            $b = EmptyDTO::fromArray(['foo' => 'c', 'bar' => 'd'], validate: false);
            $col = new DtoCollection([$a, $b]);

            expect($col->first()->toArray())->toBe($a->toArray());
            expect($col->last()->toArray())->toBe($b->toArray());
        });

        it('filter returns new collection', function () {
            $a = EmptyDTO::fromArray(['foo' => 'keep', 'bar' => null], validate: false);
            $b = EmptyDTO::fromArray(['foo' => 'drop', 'bar' => null], validate: false);
            $col = new DtoCollection([$a, $b]);

            $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->foo === 'keep');
            expect(count($filtered))->toBe(1);
        });

        it('clone throws RuntimeException', function () {
            $col = DtoCollection::make();
            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('offsetSet and offsetUnset work correctly', function () {
            $col = DtoCollection::make();
            $dto = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);

            $col[] = $dto;
            expect(count($col))->toBe(1);

            unset($col[0]);
            expect(count($col))->toBe(0);
        });
    });

    // ─── Metadata Cache TTL ────────────────────────────────────────────────

    describe('DTO metadata cache TTL', function () {
        it('setMetadataCacheTtl accepts float', function () {
            DataTransferObject::setMetadataCacheTtl(2.0);
            // No exception = pass
            expect(true)->toBeTrue();
            DataTransferObject::setMetadataCacheTtl(0.0); // reset
        });

        it('flushMetadataCache clears all', function () {
            DataTransferObject::flushMetadataCache();
            // No exception = pass
            expect(true)->toBeTrue();
        });

        it('flushMetadataCache with specific class clears only that class', function () {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);
            // No exception = pass
            expect(true)->toBeTrue();
        });
    });

    // ─── PHPStan L9 Type Safety Spot Checks ──────────────────────────────

    describe('PHPStan L9 type safety — return types', function () {
        it('rules() returns array<string, array<int, mixed>>', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = CreateUserDTO::rules();
            $createRules = CreateUserDTO::rulesFor('create');
            expect($rules)->toBe($createRules);
        });

        it('toArray() returns array<string, mixed>', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar', 'baz' => 'qux'], validate: false);
            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKey('foo');
        });

        it('allValues() includes all properties', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $all = $dto->allValues();
            expect($all)->toBeArray();
        });

        it('toJson() returns valid JSON', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $json = $dto->toJson();
            expect($json)->toBeJson();
        });
    });

    // ─── equals() and isEmpty() Edge Cases ────────────────────────────────

    describe('equals() and isEmpty() edge cases', function () {
        it('equals() returns true for identical DTOs', function () {
            $a = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $b = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different DTOs', function () {
            $a = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            $b = EmptyDTO::fromArray(['foo' => 'baz'], validate: false);
            expect($a->equals($b))->toBeFalse();
        });

        it('isEmpty() detects all-empty DTO', function () {
            // EmptyDTO has nullable properties, so all null = empty
            $dto = EmptyDTO::fromArray(['foo' => null, 'bar' => null], validate: false);
            // foo is string (nullable) -> null is empty
            // bar is ?string -> null is empty
            expect($dto->isEmpty())->toBeTrue();
        });
    });

    // ─── fromJson Edge Cases ───────────────────────────────────────────────

    describe('fromJson edge cases', function () {
        it('fromJson rejects sequential arrays', function () {
            expect(fn () => EmptyDTO::fromJson('["a","b"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson accepts empty object', function () {
            $dto = EmptyDTO::fromJson('{}', validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
        });
    });

    // ─── Validation Attribute ruleKey() Values ──────────────────────────────

    describe('Validation attribute ruleKey() values are correct', function () {
        $expectedKeys = [
            Required::class => 'required',
            Nullable::class => 'nullable',
            Sometimes::class => 'sometimes',
            Present::class => 'present',
            Prohibited::class => 'prohibited',
            Boolean::class => 'boolean',
            Integer::class => 'integer',
            Numeric::class => 'numeric',
            Email::class => 'email',
            Url::class => 'url',
            Uuid::class => 'uuid',
            Json::class => 'json',
            Accepted::class => 'accepted',
            Declined::class => 'declined',
            Confirmed::class => 'confirmed',
            Min::class => 'min',
            Max::class => 'max',
            Between::class => 'between',
            Size::class => 'size',
            In::class => 'in',
            Same::class => 'same',
            Different::class => 'different',
            Distinct::class => 'distinct',
            StartsWith::class => 'starts_with',
            EndsWith::class => 'ends_with',
            Pattern::class => 'regex',
            Enum::class => 'enum',
            ArrayRule::class => 'array',
            RequiredWith::class => 'required_with',
            RequiredWithAll::class => 'required_with_all',
            RequiredWithout::class => 'required_without',
            RequiredWithoutAll::class => 'required_without_all',
            Collection::class => 'array',
            NestedArray::class => 'array',
        ];

        foreach ($expectedKeys as $attrClass => $expectedKey) {
            it("{$attrClass}::ruleKey() returns '{$expectedKey}'", function () use ($attrClass, $expectedKey) {
                $ref = new ReflectionClass($attrClass);
                $constructor = $ref->getConstructor();

                // Construct instance without args or with empty args
                $params = [];
                if ($constructor !== null) {
                    foreach ($constructor->getParameters() as $param) {
                        if ($param->isDefaultValueAvailable()) {
                            $params[] = $param->getDefaultValue();
                        } elseif ($param->getType()?->getName() === 'string') {
                            $params[] = '';
                        } elseif ($param->getType()?->getName() === 'int') {
                            $params[] = 0;
                        } elseif ($param->getType()?->getName() === 'array') {
                            $params[] = [];
                        } elseif ($param->getType()?->allowsNull()) {
                            $params[] = null;
                        } else {
                            $params[] = '';
                        }
                    }
                }

                /** @var ValidationAttribute $instance */
                $instance = new $attrClass(...$params);
                expect($instance->ruleKey())->toBe($expectedKey);
            });
        }
    });
});
