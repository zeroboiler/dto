<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
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
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO Production Readiness', function () {
    describe('Validation attributes are final', function () {
        $validationAttrs = [
            Accepted::class,
            ArrayRule::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Declined::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            Enum::class,
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
            Collection::class,
        ];

        foreach ($validationAttrs as $attr) {
            it("{$attr} is final", function () use ($attr) {
                expect((new ReflectionClass($attr))->isFinal())->toBeTrue();
            });
        }
    });

    describe('ValidationAttribute contract implementation', function () {
        $contractAttrs = [
            Accepted::class => 'accepted',
            ArrayRule::class => 'array',
            Boolean::class => 'boolean',
            Confirmed::class => 'confirmed',
            Declined::class => 'declined',
            Distinct::class => 'distinct',
            Email::class => 'email',
            EndsWith::class => 'ends_with',
            Enum::class => 'enum',
            In::class => 'in',
            Integer::class => 'integer',
            Json::class => 'json',
            Max::class => 'max',
            Min::class => 'min',
            NestedArray::class => 'array',
            Nullable::class => 'nullable',
            Numeric::class => 'numeric',
            Pattern::class => 'regex',
            Present::class => 'present',
            Prohibited::class => 'prohibited',
            Required::class => 'required',
            RequiredIf::class => 'required_if',
            RequiredUnless::class => 'required_unless',
            RequiredWith::class => 'required_with',
            RequiredWithAll::class => 'required_with_all',
            RequiredWithout::class => 'required_without',
            RequiredWithoutAll::class => 'required_without_all',
            Same::class => 'same',
            Size::class => 'size',
            Sometimes::class => 'sometimes',
            StartsWith::class => 'starts_with',
            Url::class => 'url',
            Uuid::class => 'uuid',
            Collection::class => 'array',
        ];

        foreach ($contractAttrs as $attr => $expectedKey) {
            it("{$attr} implements ValidationAttribute with ruleKey '{$expectedKey}'", function () use ($attr, $expectedKey) {
                expect((new ReflectionClass($attr))->implementsInterface(ValidationAttribute::class))->toBeTrue();

                $instance = match (true) {
                    $attr === Max::class => new Max(255),
                    $attr === Min::class => new Min(1),
                    $attr === Size::class => new Size(10),
                    $attr === In::class => new In(['a', 'b']),
                    $attr === Pattern::class => new Pattern('/^[a-z]+$/'),
                    $attr === StartsWith::class => new StartsWith('foo'),
                    $attr === EndsWith::class => new EndsWith('bar'),
                    $attr === Enum::class => new Enum(\stdClass::class),
                    $attr === RequiredIf::class => new RequiredIf('field', 'value'),
                    $attr === RequiredUnless::class => new RequiredUnless('field', 'value'),
                    $attr === RequiredWith::class => new RequiredWith('field'),
                    $attr === RequiredWithAll::class => new RequiredWithAll('f1', 'f2'),
                    $attr === RequiredWithout::class => new RequiredWithout('field'),
                    $attr === RequiredWithoutAll::class => new RequiredWithoutAll('f1', 'f2'),
                    $attr === NestedArray::class => new NestedArray(EmptyDTO::class),
                    $attr === Collection::class => new Collection(EmptyDTO::class),
                    $attr === Same::class => new Same('field'),
                    $attr === Different::class => new Different('field'),
                    default => new $attr(),
                };

                expect($instance->ruleKey())->toBe($expectedKey);
            });
        }
    });

    describe('Metadata attributes are final', function () {
        $metaAttrs = [
            Cast::class,
            DefaultValue::class,
            Hidden::class,
            MapFrom::class,
            Date::class,
        ];

        foreach ($metaAttrs as $attr) {
            it("{$attr} is final", function () use ($attr) {
                expect((new ReflectionClass($attr))->isFinal())->toBeTrue();
            });
        }
    });

    describe('All attributes target properties', function () {
        it('all DTO attributes have TARGET_PROPERTY', function () {
            $attrs = [
                Accepted::class, ArrayRule::class, Between::class, Boolean::class,
                Cast::class, Collection::class, Confirmed::class, Declined::class,
                DefaultValue::class, Different::class, Distinct::class, Email::class,
                EndsWith::class, Enum::class, Hidden::class, In::class,
                Integer::class, Json::class, MapFrom::class, Max::class,
                Min::class, NestedArray::class, Nullable::class, Numeric::class,
                Pattern::class, Present::class, Prohibited::class, Required::class,
                RequiredIf::class, RequiredUnless::class, RequiredWith::class,
                RequiredWithAll::class, RequiredWithout::class, RequiredWithoutAll::class,
                Same::class, Size::class, Sometimes::class, StartsWith::class,
                Url::class, Uuid::class,
            ];

            foreach ($attrs as $attr) {
                $ref = new ReflectionClass($attr);
                $attrRefs = $ref->getAttributes(\Attribute::class);
                $instance = $attrRefs[0]->newInstance();
                expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0,
                    "{$attr} must target properties");
            }
        });
    });

    describe('Readonly properties on attributes', function () {
        it('Required has readonly message', function () {
            expect((new ReflectionProperty(Required::class, 'message'))->isReadOnly())->toBeTrue();
        });

        it('Email has readonly message', function () {
            expect((new ReflectionProperty(Email::class, 'message'))->isReadOnly())->toBeTrue();
        });

        it('Max has readonly value and message', function () {
            expect((new ReflectionProperty(Max::class, 'value'))->isReadOnly())->toBeTrue();
            expect((new ReflectionProperty(Max::class, 'message'))->isReadOnly())->toBeTrue();
        });

        it('Cast has readonly type', function () {
            expect((new ReflectionProperty(Cast::class, 'type'))->isReadOnly())->toBeTrue();
        });

        it('MapFrom has readonly key', function () {
            expect((new ReflectionProperty(MapFrom::class, 'key'))->isReadOnly())->toBeTrue();
        });

        it('DefaultValue has readonly value', function () {
            expect((new ReflectionProperty(DefaultValue::class, 'value'))->isReadOnly())->toBeTrue();
        });

        it('Hidden has no properties (empty attribute)', function () {
            $props = (new ReflectionClass(Hidden::class))->getProperties();
            expect($props)->toBeEmpty();
        });
    });

    describe('Core classes are final', function () {
        $classes = [
            DtoCollection::class,
            DTOCast::class,
            DTOException::class,
        ];

        foreach ($classes as $class) {
            it("{$class} is final", function () use ($class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }

        it('DataTransferObject is abstract', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });
    });

    describe('Strict types enforcement', function () {
        it('all DTO source files declare strict types', function () {
            $dir = dirname(__DIR__, 2).'/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                expect($content)->toContain('declare(strict_types=1)');
            }
        });
    });

    describe('DTOException factory methods', function () {
        it('invalidCast includes property and type info', function () {
            $e = DTOException::invalidCast('email', 'integer', 'hello');
            expect($e->getMessage())->toContain('email');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('string'); // get_debug_type of 'hello'
        });

        it('invalidJson includes property and error', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    describe('DtoCollection type safety', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not_a_dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('rejects non-DTO items via offsetSet', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            $collection = new DtoCollection([$dto]);

            expect(fn () => $collection[] = 'invalid')
                ->toThrow(\InvalidArgumentException::class);
        });

        it('implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    describe('CreateUserDTO fixture structure', function () {
        it('has all readonly properties', function () {
            $props = ['email', 'name', 'status', 'tags', 'phone', 'password'];

            foreach ($props as $prop) {
                $ref = new ReflectionProperty(CreateUserDTO::class, $prop);
                expect($ref->isReadOnly())->toBeTrue("{$prop} must be readonly");
                expect($ref->isPublic())->toBeTrue("{$prop} must be public");
            }
        });

        it('has typed constructor parameters', function () {
            $expected = [
                'email' => 'string',
                'name' => 'string',
                'status' => 'string',
                'tags' => 'array',
                'phone' => 'string',
                'password' => 'string',
            ];

            $constructor = (new ReflectionClass(CreateUserDTO::class))->getConstructor();

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $type = $param->getType();
                expect($type)->not->toBeNull("{$name} must have a type");
            }
        });

        it('has validation rules', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('email rules contain required and email', function () {
            $rules = CreateUserDTO::rules();
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });
    });

    describe('EmptyDTO fixture', function () {
        it('can be created with no data', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
        });

        it('isEmpty returns true for default state', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isNotEmpty returns false for default state', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isNotEmpty())->toBeFalse();
        });
    });
});
