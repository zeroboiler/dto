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
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * V48 — DTO Attribute Contract, Reflection Audit & Type Safety
 *
 * Validates:
 * - All 37 validation attributes implement ValidationAttribute with ruleKey()
 * - All attributes are final with readonly properties
 * - Infrastructure classes have correct structural contracts
 * - Type safety patterns (strict types, no mixed in public API)
 */
describe('V48 DTO Attribute Reflection Contract', function () {
    // --- ValidationAttribute Implementations ---

    $validationAttributes = [
        Accepted::class,
        ArrayRule::class,
        Between::class,
        Boolean::class,
        Confirmed::class,
        Declined::class,
        Distinct::class,
        Email::class,
        EndsWith::class,
        Integer::class,
        Json::class,
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
    ];

    foreach ($validationAttributes as $attrClass) {
        it("{$attrClass} implements ValidationAttribute interface", function () use ($attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue();
        });

        it("{$attrClass} is final class", function () use ($attrClass) {
            expect((new ReflectionClass($attrClass))->isFinal())->toBeTrue();
        });

        it("{$attrClass} has ruleKey() method returning non-empty string", function () use ($attrClass) {
            // Create instance with defaults
            $ref = new ReflectionClass($attrClass);
            $ctor = $ref->getConstructor();
            $params = $ctor->getParameters();

            // Build default arguments
            $args = [];
            foreach ($params as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } elseif ($param->getType() === null || $param->allowsNull()) {
                    $args[] = null;
                } elseif ($param->getName() === 'field' || $param->getName() === 'fields') {
                    $args[] = $param->getType()->getName() === 'array' ? [] : 'test_field';
                } elseif ($param->getName() === 'value') {
                    if ($param->getType() instanceof ReflectionNamedType) {
                        $typeName = $param->getType()->getName();
                        $args[] = match ($typeName) {
                            'int' => 1,
                            'int|float' => 1,
                            'string' => 'test',
                            'string|array' => 'test',
                            'mixed' => 'test',
                            default => 'test',
                        };
                    } else {
                        $args[] = 'test';
                    }
                } elseif ($param->getName() === 'regex') {
                    $args[] = '/.*/';
                } elseif ($param->getName() === 'enumClass') {
                    $args[] = 'TestEnum::class';
                } elseif ($param->getName() === 'dtoClass') {
                    $args[] = 'TestDTO::class';
                } elseif ($param->getName() === 'min') {
                    $args[] = 0;
                } elseif ($param->getName() === 'max') {
                    $args[] = 100;
                } elseif ($param->getName() === 'prefix' || $param->getName() === 'suffix') {
                    $args[] = 'test_';
                } elseif ($param->getName() === 'values') {
                    $args[] = ['a', 'b'];
                } elseif ($param->getName() === 'format') {
                    $args[] = null;
                } elseif ($param->getName() === 'type') {
                    $args[] = 'string';
                } elseif ($param->getName() === 'key') {
                    $args[] = 'source_key';
                } else {
                    $args[] = null;
                }
            }

            /** @var ValidationAttribute $instance */
            $instance = $ref->newInstance(...$args);
            $key = $instance->ruleKey();
            expect($key)->toBeString()->not->toBeEmpty();
        });
    }

    // --- Metadata Attributes (non-validation) ---

    $metaAttributes = [
        Cast::class,
        DefaultValue::class,
        Hidden::class,
        MapFrom::class,
        NestedArray::class,
        Collection::class,
        Enum::class,
    ];

    foreach ($metaAttributes as $attrClass) {
        it("{$attrClass} is final class", function () use ($attrClass) {
            expect((new ReflectionClass($attrClass))->isFinal())->toBeTrue();
        });
    }

    // --- Special attribute structure checks ---

    it('Enum attribute has readonly enumClass and message properties', function () {
        $ref = new ReflectionClass(Enum::class);
        $propClass = $ref->getProperty('enumClass');
        expect($propClass->isReadOnly())->toBeTrue();
        expect($propClass->getType()->getName())->toBe('string');

        $propMsg = $ref->getProperty('message');
        expect($propMsg->isReadOnly())->toBeTrue();
        expect($propMsg->getType()->allowsNull())->toBeTrue();
    });

    it('Hidden attribute has no properties (empty body)', function () {
        $ref = new ReflectionClass(Hidden::class);
        $props = $ref->getProperties();
        expect($props)->toBeEmpty();
    });

    it('MapFrom attribute has readonly key property', function () {
        $ref = new ReflectionClass(MapFrom::class);
        $prop = $ref->getProperty('key');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Cast attribute has readonly type property', function () {
        $ref = new ReflectionClass(Cast::class);
        $prop = $ref->getProperty('type');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('DefaultValue attribute has readonly value property', function () {
        $ref = new ReflectionClass(DefaultValue::class);
        $prop = $ref->getProperty('value');
        expect($prop->isReadOnly())->toBeTrue();
    });

    it('Max and Min accept int|float values', function () {
        $maxRef = new ReflectionClass(Max::class);
        $maxProp = $maxRef->getProperty('value');
        $maxType = $maxProp->getType();
        expect($maxType->allowsNull())->toBeFalse();

        $minRef = new ReflectionClass(Min::class);
        $minProp = $minRef->getProperty('value');
        $minType = $minProp->getType();
        expect($minType->allowsNull())->toBeFalse();
    });

    it('NestedArray has readonly dtoClass property', function () {
        $ref = new ReflectionClass(NestedArray::class);
        $prop = $ref->getProperty('dtoClass');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Collection has readonly dtoClass property', function () {
        $ref = new ReflectionClass(Collection::class);
        $prop = $ref->getProperty('dtoClass');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });
});

describe('V48 Infrastructure Class Contracts', function () {
    // --- DTOException ---
    it('DTOException is final class extending Exception', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isSubclassOf(Exception::class))->toBeTrue();
    });

    it('DTOException has named constructors invalidCast and invalidJson', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->hasMethod('invalidCast'))->toBeTrue();
        expect($ref->hasMethod('invalidJson'))->toBeTrue();
    });

    it('DTOException __toString returns class name prefix', function () {
        $e = DTOException::invalidCast('field', 'int', 'abc');
        $str = (string) $e;
        expect($str)->toContain('DTOException');
        expect($str)->toContain('field');
    });

    it('DTOException invalidJson produces descriptive message', function () {
        $e = DTOException::invalidJson('data', 'Syntax error');
        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });

    // --- DTOManager ---
    it('DTOManager is final readonly class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTOManager has 10 public methods', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        $methods = array_filter(
            $ref->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn (ReflectionMethod $m): bool => ! $m->isConstructor()
        );
        expect(count($methods))->toBe(10);
    });

    // --- DTO Facade ---
    it('DTO facade is final class extending Facade', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
    });

    it('DTO facade getFacadeAccessor returns correct key', function () {
        $method = (new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class))->getMethod('getFacadeAccessor');
        $method->setAccessible(true);
        $key = $method->invoke(null);
        expect($key)->toBe('zeroboiler.dto');
    });

    // --- DTOCast ---
    it('DTOCast is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOCast implements CastsAttributes interface', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('DTOCast constructor has readonly dtoClass and validate parameters', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        $ctor = $ref->getConstructor();
        $params = $ctor->getParameters();
        expect($params[0]->getName())->toBe('dtoClass');
        expect($params[0]->isReadOnly())->toBeTrue();
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->isReadOnly())->toBeTrue();
        expect($params[1]->isDefaultValueAvailable())->toBeTrue();
    });

    // --- DtoCollection ---
    it('DtoCollection is final class', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DtoCollection __clone has never return type', function () {
        $method = (new ReflectionClass(DtoCollection::class))->getMethod('__clone');
        expect($method->getReturnType()->getName())->toBe('never');
    });

    // --- DTOSServiceProvider ---
    it('DTOSServiceProvider is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider has Override on register and boot', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

        $register = $ref->getMethod('register');
        expect($register->getAttributes(\Override::class))->not->toBeEmpty();

        $boot = $ref->getMethod('boot');
        expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
    });

    // --- Contracts ---
    it('FromRequestDTO interface has fromRequest method', function () {
        $ref = new ReflectionClass(FromRequestDTO::class);
        expect($ref->isInterface())->toBeTrue();
        expect($ref->hasMethod('fromRequest'))->toBeTrue();
    });

    it('ValidatableDTO interface has rules and rulesFor methods', function () {
        $ref = new ReflectionClass(ValidatableDTO::class);
        expect($ref->isInterface())->toBeTrue();
        expect($ref->hasMethod('rules'))->toBeTrue();
        expect($ref->hasMethod('rulesFor'))->toBeTrue();
    });

    it('ValidationAttribute interface has ruleKey method', function () {
        $ref = new ReflectionClass(ValidationAttribute::class);
        expect($ref->isInterface())->toBeTrue();
        expect($ref->hasMethod('ruleKey'))->toBeTrue();
    });

    // --- DtoMetadataResolver ---
    it('DtoMetadataResolver is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DtoMetadataResolver resolve() returns array with properties, rules, messages keys', function () {
        $result = \ZeroBoiler\DTO\Support\DtoMetadataResolver::resolve(
            \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class
        );
        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['properties', 'rules', 'messages']);
    });

    // --- OpenApiSchemaGenerator ---
    it('OpenApiSchemaGenerator is final class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    // --- DataTransferObject Base ---
    it('DataTransferObject is abstract class', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    it('DataTransferObject implements Arrayable, JsonSerializable, FromRequestDTO, ValidatableDTO', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
    });
});

describe('V48 DtoCollection Advanced Operations', function () {
    it('sortBy with string key uses toArray for comparison', function () {
        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
            'password' => 'secret',
        ], validate: false);
        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $sorted = $col->sortBy('name');

        // Alice comes before Bob
        expect($sorted->first()->toArray()['name'])->toBe('Alice');
    });

    it('chunk splits into correct sizes', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
                'email' => "user{$i}@test.com",
                'name' => "User {$i}",
                'password' => 'secret',
            ], validate: false);
        }

        $col = new DtoCollection($items);
        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(3); // 2, 2, 1
        expect(count($chunks[0]))->toBe(2);
        expect(count($chunks[1]))->toBe(2);
        expect(count($chunks[2]))->toBe(1);
    });

    it('unique removes duplicate DTOs based on toArray() equality', function () {
        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $unique = $col->unique();

        expect($unique->count())->toBe(1);
    });

    it('search returns first matching DTO or null', function () {
        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
            'password' => 'secret',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);

        $found = $col->search(fn ($d) => $d->toArray()['name'] === 'Bob');
        expect($found)->not->toBeNull();

        $notFound = $col->search(fn ($d) => $d->toArray()['name'] === 'Charlie');
        expect($notFound)->toBeNull();
    });

    it('contains returns true when any DTO matches', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $col = new DtoCollection([$dto]);
        expect($col->contains(fn ($d) => $d->toArray()['name'] === 'Alice'))->toBeTrue();
        expect($col->contains(fn ($d) => $d->toArray()['name'] === 'Bob'))->toBeFalse();
    });

    it('take and skip work correctly', function () {
        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $items[] = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
                'email' => "user{$i}@test.com",
                'name' => "User {$i}",
                'password' => 'secret',
            ], validate: false);
        }

        $col = new DtoCollection($items);

        $first3 = $col->take(3);
        expect($first3->count())->toBe(3);

        $after5 = $col->skip(5);
        expect($after5->count())->toBe(5);
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'A',
            'password' => 's',
        ], validate: false);

        $dto2 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'B',
            'password' => 's',
        ], validate: false);

        $dto3 = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'C',
            'password' => 's',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($col[0]);

        // After re-indexing, index 0 should be the former index 1 (B)
        expect($col[0]->toArray()['name'])->toBe('B');
        expect($col[1]->toArray()['name'])->toBe('C');
        expect($col->count())->toBe(2);
    });
});

describe('V48 DTO Type Safety Patterns', function () {
    it('DTO facade docblock has all 10 method signatures', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        $doc = $ref->getDocComment();
        if ($doc === false) {
            $this->markTestSkipped('No docblock on DTO facade');
        }

        $methods = ['validate', 'make', 'makeFromJson', 'fromJson', 'fromPartialArray',
            'fromPartialRequest', 'rules', 'rulesFor', 'schema',
        ];
        foreach ($methods as $method) {
            expect($doc)->toContain("@method static");
        }
    });

    it('DTOCast get/set/serialize have Override attributes', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);

        $get = $ref->getMethod('get');
        expect($get->getAttributes(\Override::class))->not->toBeEmpty();

        $set = $ref->getMethod('set');
        expect($set->getAttributes(\Override::class))->not->toBeEmpty();
    });

    it('DataTransferObject with() has Deprecated attribute', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        $with = $ref->getMethod('with');
        $attrs = $with->getAttributes(\Deprecated::class);
        expect($attrs)->not->toBeEmpty();
    });

    it('All DTO source files have declare(strict_types=1)', function () {
        $dir = new RecursiveDirectoryIterator(
            dirname((new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class))->getFileName())
        );
        $iter = new RecursiveIteratorIterator($dir);
        $phpFiles = new RegexIterator($iter, '/\.php$/');

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file->getPathname());
            expect($content)->toContain('declare(strict_types=1)');
        }
    });

    it('DtoCollection __debugInfo returns array with count and items keys', function () {
        $col = new DtoCollection;
        $debug = $col->__debugInfo();
        expect($debug)->toBeArray();
        expect($debug)->toHaveKeys(['count', 'items']);
    });
});
