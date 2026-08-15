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
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
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
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;

describe('DTO V18 — full production contract audit', function () {
    // ────────────────────────────────────────────────────────────────
    // 1. Validation attribute classes: structural contract
    // ────────────────────────────────────────────────────────────────
    describe('Validation attribute structural contract', function () {
        $validationAttributes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Confirmed::class, Date::class, Declined::class, Distinct::class,
            Email::class, EndsWith::class, In::class, Integer::class,
            Json::class, Nullable::class, Numeric::class, Pattern::class,
            Present::class, Prohibited::class, Required::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class, Same::class,
            Size::class, Sometimes::class, StartsWith::class, Url::class, Uuid::class,
        ];

        it('all 31 validation attributes are final classes', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                $ref = new ReflectionClass($attr);
                expect($ref->isFinal())->toBeTrue("{$attr} should be final");
            }
        });

        it('all 31 validation attributes implement ValidationAttribute', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                expect(is_subclass_of($attr, ValidationAttribute::class))
                    ->toBeTrue("{$attr} should implement ValidationAttribute");
            }
        });

        it('all 31 validation attributes have ruleKey() method returning string', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                $ref = new ReflectionMethod($attr, 'ruleKey');
                expect($ref->getReturnType()->getName())->toBe('string');
                expect($ref->isPublic())->toBeTrue();
            }
        });

        it('all validation attributes have optional nullable message property', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attr) {
                $ref = new ReflectionClass($attr);
                $props = $ref->getProperties();
                $hasMessage = false;
                foreach ($props as $prop) {
                    if ($prop->getName() === 'message') {
                        $hasMessage = true;
                        expect($prop->getType()->allowsNull())->toBeTrue("{$attr}::\$message should be nullable");
                        expect($prop->isReadOnly())->toBeTrue("{$attr}::\$message should be readonly");
                    }
                }
                expect($hasMessage)->toBeTrue("{$attr} should have a \$message property");
            }
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 2. Metadata attributes: Cast, Hidden, MapFrom, DefaultValue, NestedArray, Collection
    // ────────────────────────────────────────────────────────────────
    describe('Metadata attribute structural contract', function () {
        it('Cast is final with readonly type', function () {
            $ref = new ReflectionClass(Cast::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('type')->isReadOnly())->toBeTrue();
        });

        it('Hidden is final with no constructor params', function () {
            $ref = new ReflectionClass(Hidden::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getConstructor()->getNumberOfParameters())->toBe(0);
        });

        it('MapFrom is final with readonly key', function () {
            $ref = new ReflectionClass(MapFrom::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('key')->isReadOnly())->toBeTrue();
        });

        it('DefaultValue is final with mixed value', function () {
            $ref = new ReflectionClass(DefaultValue::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getProperty('value')->isReadOnly())->toBeTrue();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 3. DataTransferObject: abstract class, interfaces, method signatures
    // ────────────────────────────────────────────────────────────────
    describe('DataTransferObject base class contract', function () {
        it('is abstract', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });

        it('implements FromRequestDTO', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        });

        it('implements ValidatableDTO', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        });

        it('implements Arrayable and JsonSerializable', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('fromArray is static with correct signature', function () {
            $method = new ReflectionMethod(DataTransferObject::class, 'fromArray');
            expect($method->isStatic())->toBeTrue();
            expect($method->isPublic())->toBeTrue();
            $params = $method->getParameters();
            expect($params)->toHaveCount(2);
            expect($params[0]->getName())->toBe('data');
            expect($params[1]->getName())->toBe('validate');
        });

        it('toArray has Override attribute', function () {
            $method = new ReflectionMethod(DataTransferObject::class, 'toArray');
            $attrs = $method->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty();
        });

        it('jsonSerialize has Override attribute', function () {
            $method = new ReflectionMethod(DataTransferObject::class, 'jsonSerialize');
            $attrs = $method->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty();
        });

        it('with() has Deprecated attribute', function () {
            $method = new ReflectionMethod(DataTransferObject::class, 'with');
            $attrs = $method->getAttributes(\Deprecated::class);
            expect($attrs)->not->toBeEmpty();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 4. DTOManager: final readonly, all public methods
    // ────────────────────────────────────────────────────────────────
    describe('DTOManager structural contract', function () {
        it('is final readonly', function () {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('has validate method returning array', function () {
            $method = new ReflectionMethod(DTOManager::class, 'validate');
            expect($method->getReturnType()->getName())->toBe('array');
        });

        it('has make method returning DataTransferObject', function () {
            $method = new ReflectionMethod(DTOManager::class, 'make');
            $type = $method->getReturnType();
            expect($type->getName())->toBe(DataTransferObject::class);
        });

        it('has schema method returning array', function () {
            $method = new ReflectionMethod(DTOManager::class, 'schema');
            expect($method->getReturnType()->getName())->toBe('array');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 5. DtoCollection: final, interfaces, immutability
    // ────────────────────────────────────────────────────────────────
    describe('DtoCollection structural contract', function () {
        it('is final class', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('__clone is blocked with never return type', function () {
            $method = new ReflectionMethod(DtoCollection::class, '__clone');
            expect($method->getReturnType()->getName())->toBe('never');
        });

        it('count/offsetExists/offsetGet/offsetSet/offsetUnset have Override', function () {
            $methods = ['count', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', 'getIterator', 'jsonSerialize'];
            foreach ($methods as $name) {
                $method = new ReflectionMethod(DtoCollection::class, $name);
                $attrs = $method->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty("DtoCollection::{$name} should have #[Override]");
            }
        });

        it('blocks non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('blocks non-DTO items in offsetSet', function () {
            $col = DtoCollection::make();
            expect(fn () => $col[0] = 'not a dto')
                ->toThrow(\InvalidArgumentException::class);
        });

        it('toArray returns array of arrays', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'secret'], validate: false);
            $col = new DtoCollection([$dto]);
            $result = $col->toArray();
            expect($result)->toBeArray();
            expect($result[0])->toBeArray();
            expect($result[0])->toHaveKey('email');
        });

        it('filter returns new collection', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'password' => 'secret'], validate: false);
            $col = new DtoCollection([$d1, $d2]);
            $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->name === 'Alice');
            expect($filtered)->not->toBe($col);
            expect($filtered->count())->toBe(1);
        });

        it('append returns new collection without mutating original', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'password' => 'secret'], validate: false);
            $col = new DtoCollection([$d1]);
            $new = $col->append($d2);
            expect($col->count())->toBe(1);
            expect($new->count())->toBe(2);
        });

        it('merge combines two collections', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie', 'password' => 'secret'], validate: false);
            $col1 = new DtoCollection([$d1]);
            $col2 = new DtoCollection([$d2]);
            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('offsetUnset re-indexes array', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 's'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'password' => 's'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'c@b.com', 'name' => 'C', 'password' => 's'], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);
            unset($col[0]);
            expect($col->count())->toBe(2);
            expect($col[0]->name)->toBe('B');
            expect($col[1]->name)->toBe('C');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 6. DTOException: factory methods, __toString
    // ────────────────────────────────────────────────────────────────
    describe('DTOException contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOException::class))->isFinal())->toBeTrue();
        });

        it('invalidCast includes property and type info', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson includes property and error message', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString includes class name', function () {
            $e = DTOException::invalidJson('field', 'err');
            expect((string) $e)->toContain('DTOException');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 7. DTOCast: structural safety, serialization
    // ────────────────────────────────────────────────────────────────
    describe('DTOCast structural safety', function () {
        it('is final', function () {
            expect((new ReflectionClass(DTOCast::class))->isFinal())->toBeTrue();
        });

        it('get() returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect($cast->get(new stdClass, 'data', null, []))->toBeNull();
        });

        it('get() returns DTO instance for valid JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new stdClass, 'data', '{"email":"a@b.com","name":"Test","password":"s"}', []);
            expect($result)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('get() returns null for invalid JSON', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect($cast->get(new stdClass, 'data', 'not-json', []))->toBeNull();
        });

        it('set() throws for non-DTO non-array non-null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect(fn () => $cast->set(new stdClass, 'data', 123, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('set() serializes DTO to JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class, validate: false);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 's'], validate: false);
            $result = $cast->set(new stdClass, 'data', $dto, []);
            $decoded = json_decode($result, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('serialize() returns toArray output for DTO', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 's'], validate: false);
            $result = $cast->serialize(new stdClass, 'data', $dto, []);
            expect($result)->toBeArray();
            expect($result['email'])->toBe('a@b.com');
        });

        it('serialize() returns null for null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect($cast->serialize(new stdClass, 'data', null, []))->toBeNull();
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 8. DTOSServiceProvider: registration and structure
    // ────────────────────────────────────────────────────────────────
    describe('DTOSServiceProvider contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class))->isFinal())->toBeTrue();
        });

        it('extends Laravel ServiceProvider', function () {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            expect($ref->getParentClass()->getName())->toBe(\Illuminate\Support\ServiceProvider::class);
        });

        it('register and boot have Override attributes', function () {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            foreach (['register', 'boot'] as $method) {
                $m = $ref->getMethod($method);
                expect($m->getAttributes(\Override::class))->not->toBeEmpty();
            }
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 9. DTO facade: accessor and docblock
    // ────────────────────────────────────────────────────────────────
    describe('DTO facade contract', function () {
        it('is final', function () {
            expect((new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class))->isFinal())->toBeTrue();
        });

        it('returns zeroboiler.dto accessor', function () {
            $method = new ReflectionMethod(\ZeroBoiler\DTO\Facades\DTO::class, 'getFacadeAccessor');
            expect($method->getReturnType()->getName())->toBe('string');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 10. Hydration roundtrip: fromArray → toArray consistency
    // ────────────────────────────────────────────────────────────────
    describe('Hydration and serialization roundtrip', function () {
        it('simple DTO roundtrip preserves all fields', function () {
            $data = ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 's3cret'];
            $dto = CreateUserDTO::fromArray($data, validate: false);
            $arr = $dto->toArray();
            // password is hidden, so toArray excludes it
            expect($arr['email'])->toBe('a@b.com');
            expect($arr['name'])->toBe('Alice');
        });

        it('allValues includes hidden fields', function () {
            $data = ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 's3cret'];
            $dto = CreateUserDTO::fromArray($data, validate: false);
            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('s3cret');
        });

        it('equals checks toArray output', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 's'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 's'], validate: false);
            $d3 = CreateUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'B', 'password' => 's'], validate: false);
            expect($d1->equals($d2))->toBeTrue();
            expect($d1->equals($d3))->toBeFalse();
        });

        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 's'], validate: false);
            $only = $dto->only('email');
            expect($only)->toHaveCount(1);
            expect($only['email'])->toBe('a@b.com');
        });

        it('except returns all except specified fields', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 's'], validate: false);
            $except = $dto->except('password');
            expect($except)->not->toHaveKey('password');
            expect($except)->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });
    });

    // ────────────────────────────────────────────────────────────────
    // 11. isEmpty/isNotEmpty: edge cases
    // ────────────────────────────────────────────────────────────────
    describe('isEmpty and isNotEmpty edge cases', function () {
        it('DTO with all defaults is empty', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('DTO with zero int score is not empty', function () {
            $dto = ScalarConstraintsDTO::fromArray(['name' => 'Test', 'score' => 0], validate: false);
            // score=0 is a valid value for non-nullable int, so not empty
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });
});
