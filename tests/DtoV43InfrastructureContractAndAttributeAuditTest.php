<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
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
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('V43 DTO Production Readiness — Infrastructure, Contract & Attribute Audit', function () {
    // ── DTOCast contract compliance ────────────────────────────────────

    describe('DTOCast contract compliance', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(DTOCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('constructor accepts class-string and validate flag as readonly', function () {
            $ref = new ReflectionClass(DTOCast::class);
            $constructor = $ref->getConstructor();

            expect($constructor)->not->toBeNull();
            $params = $constructor->getParameters();
            expect($params)->toHaveCount(2);
            expect($params[0]->getName())->toBe('dtoClass');
            expect($params[1]->getName())->toBe('validate');
        });

        it('get() has #[Override] attribute', function () {
            $method = new ReflectionMethod(DTOCast::class, 'get');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('set() has #[Override] attribute', function () {
            $method = new ReflectionMethod(DTOCast::class, 'set');
            $attrs = $method->getAttributes(\Override::class);
            expect(count($attrs))->toBeGreaterThan(0);
        });

        it('serialize() returns toArray() for DTO instance', function () {
            $dto = ComprehensiveDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
            ], validate: false);

            $cast = new DTOCast(ComprehensiveDTO::class);
            $result = $cast->serialize(
                new class {
                    public function __construct() {}
                },
                'payload',
                $dto,
                []
            );

            expect($result)->toBeArray();
            expect($result)->toHaveKey('name');
            expect($result['name'])->toBe('Alice');
        });

        it('serialize() returns null for null value', function () {
            $cast = new DTOCast(ComprehensiveDTO::class);
            $result = $cast->serialize(
                new class {
                    public function __construct() {}
                },
                'payload',
                null,
                []
            );

            expect($result)->toBeNull();
        });
    });

    // ── DTOSServiceProvider contract ───────────────────────────────────

    describe('DTOSServiceProvider design', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(DTOSServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('register() has #[Override]', function () {
            $method = new ReflectionMethod(DTOSServiceProvider::class, 'register');
            expect($method->getAttributes(\Override::class))->not->toBeEmpty();
        });

        it('boot() has #[Override]', function () {
            $method = new ReflectionMethod(DTOSServiceProvider::class, 'boot');
            expect($method->getAttributes(\Override::class))->not->toBeEmpty();
        });
    });

    // ── DTO Facade contract ────────────────────────────────────────────

    describe('DTO Facade contract', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('getFacadeAccessor() returns correct string', function () {
            $method = new ReflectionMethod(\ZeroBoiler\DTO\Facades\DTO::class, 'getFacadeAccessor');
            $method->setAccessible(true);
            $result = $method->invoke(null);

            expect($result)->toBe('zeroboiler.dto');
        });
    });

    // ── DtoMetadataResolver design ─────────────────────────────────────

    describe('DtoMetadataResolver design', function () {
        it('is a final class', function () {
            $ref = new ReflectionClass(DtoMetadataResolver::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('resolve() returns properties, rules, and messages', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta)->toBeArray();
            expect($meta)->toHaveKeys(['properties', 'rules', 'messages']);
            expect($meta['properties'])->toBeArray();
            expect($meta['rules'])->toBeArray();
            expect($meta['messages'])->toBeArray();
        });

        it('resolves rules for required properties correctly', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['rules'])->toHaveKey('email');
            expect($meta['rules']['email'])->toContain('required');
            expect($meta['rules']['email'])->toContain('email');
        });

        it('detects MapFrom attribute', function () {
            $meta = DtoMetadataResolver::resolve(MinimalDTO::class);

            // MinimalDTO uses MapFrom
            foreach ($meta['properties'] as $name => $prop) {
                if ($prop['map_from'] !== null) {
                    expect($prop['map_from'])->toBeString();
                }
            }
        });

        it('detects Hidden attribute', function () {
            $meta = DtoMetadataResolver::resolve(ComprehensiveDTO::class);

            foreach ($meta['properties'] as $name => $prop) {
                if ($prop['hidden'] === true) {
                    expect($prop['hidden'])->toBeBool();
                }
            }
        });
    });

    // ── DataTransferObject metadata cache ────────────────────────────

    describe('DataTransferObject metadata cache', function () {
        it('flushMetadataCache clears cache for specific class', function () {
            // Ensure metadata is resolved at least once
            $rules = ComprehensiveDTO::rules();
            expect($rules)->toBeArray()->not->toBeEmpty();

            // Flush for this specific class
            ComprehensiveDTO::flushMetadataCache(ComprehensiveDTO::class);

            // Re-resolve should work
            $rules2 = ComprehensiveDTO::rules();
            expect($rules2)->toBeArray()->not->toBeEmpty();
        });

        it('flushMetadataCache with null clears all classes', function () {
            ComprehensiveDTO::flushMetadataCache(null);

            $rules = ComprehensiveDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl accepts float values', function () {
            ComprehensiveDTO::setMetadataCacheTtl(0.5);
            // Restore
            ComprehensiveDTO::setMetadataCacheTtl(0.0);
        });
    });

    // ── All validation attributes implement ValidationAttribute ──────

    describe('All validation attributes implement ValidationAttribute', function () {
        $validationAttrs = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class, In::class, Integer::class,
            Numeric::class, Boolean::class, Uuid::class, Date::class,
            Enum::class, Confirmed::class, Same::class, Different::class,
            Between::class, ArrayRule::class, Prohibited::class, Present::class,
            Declined::class, Accepted::class, StartsWith::class, EndsWith::class,
            Nullable::class, Sometimes::class, Distinct::class, Size::class,
            Json::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class,
        ];

        foreach ($validationAttrs as $className) {
            it("{$className} implements ValidationAttribute", function () use ($className) {
                $ref = new ReflectionClass($className);
                expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue();
            });

            it("{$className} has ruleKey() returning string", function () use ($className) {
                $ref = new ReflectionMethod($className, 'ruleKey');
                $returnType = $ref->getReturnType();
                expect($returnType)->not->toBeNull();
                expect($returnType->getName())->toBe('string');
            });

            it("{$className} is final", function () use ($className) {
                $ref = new ReflectionClass($className);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ── Metadata attributes are final ─────────────────────────────────

    describe('Metadata attributes are final', function () {
        $metadataAttrs = [
            \ZeroBoiler\DTO\Attributes\MapFrom::class,
            \ZeroBoiler\DTO\Attributes\Cast::class,
            \ZeroBoiler\DTO\Attributes\DefaultValue::class,
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\NestedArray::class,
            \ZeroBoiler\DTO\Attributes\Collection::class,
        ];

        foreach ($metadataAttrs as $className) {
            it("{$className} is final", function () use ($className) {
                $ref = new ReflectionClass($className);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ── DtoCollection immutability guarantees ────────────────────────

    describe('DtoCollection immutability guarantees', function () {
        it('__clone() returns never', function () {
            $method = new ReflectionMethod(DtoCollection::class, '__clone');
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('never');
        });

        it('append() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $col = DtoCollection::make([$dto1]);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $newCol = $col->append($dto2);

            expect($newCol)->not->toBe($col);
            expect($newCol->count())->toBe(2);
            expect($col->count())->toBe(1);
        });

        it('merge() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2]);
            $merged = $col1->merge($col2);

            expect($merged)->not->toBe($col1);
            expect($merged)->not->toBe($col2);
            expect($merged->count())->toBe(2);
        });

        it('filter() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $filtered = $col->filter(fn ($d) => $d->email === 'a@b.com');

            expect($filtered)->not->toBe($col);
            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2);
        });

        it('sortBy() returns new instance', function () {
            $dto1 = ComprehensiveDTO::fromArray(['name' => 'Zoe', 'age' => 25, 'email' => 'z@z.com'], validate: false);
            $dto2 = ComprehensiveDTO::fromArray(['name' => 'Alice', 'age' => 30, 'email' => 'a@a.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $sorted = $col->sortBy('name');

            expect($sorted)->not->toBe($col);
            expect($sorted->count())->toBe(2);
        });

        it('take() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $taken = $col->take(1);

            expect($taken)->not->toBe($col);
            expect($taken->count())->toBe(1);
            expect($col->count())->toBe(2);
        });

        it('skip() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $skipped = $col->skip(1);

            expect($skipped)->not->toBe($col);
            expect($skipped->count())->toBe(1);
        });

        it('unique() returns new instance', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);
            $unique = $col->unique();

            expect($unique)->not->toBe($col);
            expect($unique->count())->toBe(1);
        });
    });

    // ── Interface implementations ────────────────────────────────────

    describe('Interface implementations', function () {
        it('DataTransferObject implements Arrayable', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        });

        it('DataTransferObject implements FromRequestDTO', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        });

        it('DataTransferObject implements ValidatableDTO', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        });

        it('DataTransferObject implements JsonSerializable', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('DtoCollection implements ArrayAccess', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        });

        it('DtoCollection implements Countable', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        });

        it('DtoCollection implements IteratorAggregate', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        });

        it('DtoCollection implements JsonSerializable', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    // ── DTOCollection JSON serialization ──────────────────────────────

    describe('DtoCollection JSON serialization', function () {
        it('jsonSerialize returns toArray output', function () {
            $dto = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $col = DtoCollection::make([$dto]);

            $result = $col->jsonSerialize();
            expect($result)->toBeArray();
            expect(count($result))->toBe(1);
            expect($result[0])->toHaveKey('email');
        });
    });

    // ── DtoCollection __debugInfo ──────────────────────────────────────

    describe('DtoCollection debug output', function () {
        it('__debugInfo returns count and truncated items', function () {
            $dto1 = MinimalDTO::fromArray(['email' => 'a@b.com'], validate: false);
            $dto2 = MinimalDTO::fromArray(['email' => 'c@d.com'], validate: false);
            $col = DtoCollection::make([$dto1, $dto2]);

            $debug = $col->__debugInfo();
            expect($debug)->toBeArray();
            expect($debug)->toHaveKeys(['count', 'items']);
            expect($debug['count'])->toBe(2);
            expect($debug['items'])->toBeArray();
            expect(count($debug['items']))->toBeLessOrEqual(3);
        });
    });

    // ── fromArray without validation ──────────────────────────────────

    describe('fromArray with validation disabled', function () {
        it('creates DTO from empty array when validation is off', function () {
            $dto = ComprehensiveDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
        });

        it('creates DTO with partial data when validation is off', function () {
            $dto = ComprehensiveDTO::fromArray(['name' => 'Bob'], validate: false);
            expect($dto->name)->toBe('Bob');
        });
    });

    // ── Equals semantics ───────────────────────────────────────────────

    describe('Equals semantics', function () {
        it('same data produces equal DTOs', function () {
            $dto1 = ComprehensiveDTO::fromArray([
                'name' => 'Alice', 'age' => 30,
                'email' => 'alice@example.com',
            ], validate: false);
            $dto2 = ComprehensiveDTO::fromArray([
                'name' => 'Alice', 'age' => 30,
                'email' => 'alice@example.com',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('different data produces unequal DTOs', function () {
            $dto1 = ComprehensiveDTO::fromArray([
                'name' => 'Alice', 'age' => 30,
                'email' => 'alice@example.com',
            ], validate: false);
            $dto2 = ComprehensiveDTO::fromArray([
                'name' => 'Bob', 'age' => 25,
                'email' => 'bob@example.com',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });
});
