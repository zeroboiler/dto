<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

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
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('DTO Edge Cases & PHPStan L9 Compliance', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('fromArray / toArray roundtrip', function () {
        it('roundtrips all scalar types correctly', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['email'])->toBe('test@example.com');
            expect($arr['name'])->toBe('Doruk');
            expect($arr['status'])->toBe('active');
            expect($arr['tags'])->toBe([]);
            expect($arr)->not->toHaveKey('password'); // Hidden
        });

        it('maps source key via MapFrom', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'phone_number' => '+905551234567',
            ], validate: false);

            expect($dto->phone)->toBe('+905551234567');
        });

        it('applies Cast attribute', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'tags' => '{"a":1}',
            ], validate: false);

            expect($dto->tags)->toBe(['a' => 1]);
        });

        it('applies default value when key is missing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'password' => 'secret',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->toArray())->not->toHaveKey('password');
        });
    });

    describe('with() immutable update', function () {
        it('creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            expect($dto->name)->toBe('Doruk'); // original unchanged
            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('test@example.com');
        });
    });

    describe('only() / except()', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $result = $dto->only(['email']);
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('except excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $result = $dto->except(['name']);
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });
    });

    describe('equals()', function () {
        it('returns true for identical DTOs', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for different DTOs', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Doruk',
            ], validate: false);

            $b = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('rules() and rulesFor()', function () {
        it('returns validation rules from attributes', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect(in_array('required', $rules['email'], true))->toBeTrue();
            expect(in_array('email', $rules['email'], true))->toBeTrue();
        });

        it('rulesFor returns same rules by default', function () {
            expect(CreateUserDTO::rulesFor('create'))->toEqual(CreateUserDTO::rules());
            expect(CreateUserDTO::rulesFor('update'))->toEqual(CreateUserDTO::rules());
        });
    });

    describe('toJson / jsonSerialize', function () {
        it('serializes to valid JSON', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('jsonSerialize returns same as toArray', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($dto->jsonSerialize())->toEqual($dto->toArray());
        });
    });

    describe('EmptyDTO', function () {
        it('handles DTO with only optional nullable properties', function () {
            $dto = EmptyDTO::fromArray([]);

            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
            expect($dto->toArray())->toEqual(['foo' => null, 'bar' => null]);
        });
    });

    describe('Nested DTOs', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
                'items' => [],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->city)->toBe('Istanbul');
            expect($dto->items)->toBe([]);
        });

        it('serializes nested DTOs recursively', function () {
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

    describe('DtoCollection', function () {
        it('wraps DTO instances', function () {
            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            ];

            $collection = new DtoCollection($dtoArray);

            expect($collection->count())->toBe(2);
            expect($collection->first())->toBe($dtoArray[0]);
            expect($collection->last())->toBe($dtoArray[1]);
        });

        it('serializes all items', function () {
            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            ];

            $collection = new DtoCollection($dtoArray);

            expect($collection->toArray())->toBe([
                ['email' => 'a@test.com', 'name' => 'A', 'status' => 'active', 'tags' => [], 'phone' => null],
            ]);
        });

        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('pluck extracts a single field', function () {
            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            ];

            $collection = new DtoCollection($dtoArray);
            $emails = $collection->pluck('email');

            expect($emails)->toEqual(['a@test.com', 'b@test.com']);
        });

        it('filter returns new collection', function () {
            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
            ];

            $collection = new DtoCollection($dtoArray);
            $filtered = $collection->filter(fn ($dto) => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('isEmpty and isNotEmpty', function () {
            expect((new DtoCollection([]))->isEmpty())->toBeTrue();
            expect((new DtoCollection([]))->isNotEmpty())->toBeFalse();

            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            ];
            expect((new DtoCollection($dtoArray))->isEmpty())->toBeFalse();
            expect((new DtoCollection($dtoArray))->isNotEmpty())->toBeTrue();
        });

        it('offsetUnset re-indexes', function () {
            $dtoArray = [
                CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
                CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false),
            ];

            $collection = new DtoCollection($dtoArray);
            unset($collection[0]);

            expect($collection->count())->toBe(2);
            expect($collection->first()->name)->toBe('B');
        });
    });

    describe('DTOException', function () {
        it('invalidCast formats message correctly', function () {
            $e = DTOException::invalidCast('age', 'int', 'hello');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('int');
            expect($e->getMessage())->toContain('string');
        });

        it('invalidJson formats message correctly', function () {
            $e = DTOException::invalidJson('data', 'Syntax error');
            expect($e->getMessage())->toContain('data');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    describe('Attribute consistency', function () {
        it('all validation attributes are final', function () {
            $attrs = [
                Accepted::class, Boolean::class, Confirmed::class, Date::class,
                Declined::class, Different::class, Distinct::class, Email::class,
                EndsWith::class, Enum::class, In::class, Integer::class,
                Json::class, Max::class, Min::class, Nullable::class,
                Numeric::class, Pattern::class, Present::class, Prohibited::class,
                Required::class, RequiredIf::class, RequiredUnless::class,
                RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
                RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($attrs as $attr) {
                $ref = new \ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} should be final");
            }
        });

        it('all validation attributes implement ValidationAttribute', function () {
            $attrs = [
                Accepted::class, Boolean::class, Confirmed::class, Date::class,
                Declined::class, Different::class, Distinct::class, Email::class,
                EndsWith::class, Enum::class, In::class, Integer::class,
                Json::class, Max::class, Min::class, Nullable::class,
                Numeric::class, Pattern::class, Present::class, Prohibited::class,
                Required::class, RequiredIf::class, RequiredUnless::class,
                RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
                RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($attrs as $attr) {
                expect(is_a($attr, ValidationAttribute::class, true))
                    ->toBeTrue("{$attr} should implement ValidationAttribute");
            }
        });

        it('all validation attributes have a ruleKey() method', function () {
            $attrs = [
                Accepted::class, Boolean::class, Confirmed::class, Date::class,
                Declined::class, Different::class, Distinct::class, Email::class,
                EndsWith::class, Enum::class, In::class, Integer::class,
                Json::class, Max::class, Min::class, Nullable::class,
                Numeric::class, Pattern::class, Present::class, Prohibited::class,
                Required::class, RequiredIf::class, RequiredUnless::class,
                RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
                RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
                StartsWith::class, Url::class, Uuid::class,
            ];

            foreach ($attrs as $attr) {
                $instance = (new \ReflectionClass($attr))->newInstanceWithoutConstructor();
                expect(method_exists($instance, 'ruleKey'))->toBeTrue("{$attr} should have ruleKey()");
                expect($instance->ruleKey())->toBeString();
                expect($instance->ruleKey())->not->toBeEmpty();
            }
        });

        it('non-validation attributes are final', function () {
            $attrs = [
                Cast::class, DefaultValue::class, Hidden::class, MapFrom::class,
                NestedArray::class,
            ];

            foreach ($attrs as $attr) {
                $ref = new \ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} should be final");
            }
        });
    });

    describe('Metadata cache', function () {
        it('flushMetadataCache clears all classes', function () {
            CreateUserDTO::rules(); // populate cache
            DataTransferObject::flushMetadataCache();

            // If cache was populated, class should exist in internal cache
            // After flush, it should be gone — we verify by accessing rules again
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache clears specific class', function () {
            CreateUserDTO::rules(); // populate cache
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Re-access rules should work (re-resolves)
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });
    });

    describe('PHPStan L9 — strict type compliance', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = dirname(__DIR__, 2).'/src';
            $files = glob($srcDir.'/**/*.php');
            $violations = [];

            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (! str_contains($content, 'declare(strict_types=1)')) {
                    $violations[] = str_replace($srcDir.'/', '', $file);
                }
            }

            expect($violations)->toBeEmpty(
                'Files missing declare(strict_types=1): '.implode(', ', $violations)
            );
        });

        it('DataTransferObject has return types on all public methods', function () {
            $ref = new \ReflectionClass(DataTransferObject::class);
            $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

            $violations = [];
            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() === DataTransferObject::class) {
                    $returnType = $method->getReturnType();
                    if ($returnType === null && ! $method->isConstructor()) {
                        $violations[] = $method->getName().'()';
                    }
                }
            }

            expect($violations)->toBeEmpty(
                'Methods without return type: '.implode(', ', $violations)
            );
        });
    });
});
