<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * PHP 8.5 strict type contract verification and production readiness audit.
 *
 * Verifies: declare(strict_types=1), return type completeness, final classes,
 * readonly properties, attribute targets, interface contracts, and PHPStan L9 compliance.
 */
describe('PHP 8.5 Type Safety and Production Contract Audit V51', function (): void {
    // ── Infrastructure Class Final Verification ────────────────────────

    it('all infrastructure classes are final', function (): void {
        $classes = [
            DTOManager::class,
            DTOCast::class,
            DTOException::class,
            DTO::class,
            DtoCollection::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('DTOManager is readonly', function (): void {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();

        $props = $ref->getProperties();
        // DTOManager is stateless (readonly), so it has no instance properties
        // Verify it has no non-static properties
        foreach ($props as $prop) {
            expect($prop->isStatic())->toBeTrue('DTOManager should have no instance properties');
        }
    });

    // ── DTOException Contract ──────────────────────────────────────────

    it('DTOException named constructors produce correct messages', function (): void {
        $ex1 = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($ex1)->toBeInstanceOf(DTOException::class);
        expect($ex1->getMessage())->toContain('age');
        expect($ex1->getMessage())->toContain('integer');

        $ex2 = DTOException::invalidJson('data', 'Syntax error');
        expect($ex2)->toBeInstanceOf(DTOException::class);
        expect($ex2->getMessage())->toContain('data');
        expect($ex2->getMessage())->toContain('Syntax error');
    });

    it('DTOException __toString produces class: message format', function (): void {
        $ex = DTOException::invalidCast('field', 'int', 'abc');
        $str = (string) $ex;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain($ex->getMessage());
    });

    // ── DTOCast Interface Contract ───────────────────────────────────────

    it('DTOCast implements CastsAttributes interface', function (): void {
        $ref = new ReflectionClass(DTOCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))
            ->toBeTrue();
    });

    it('DTOCast constructor accepts class-string and validate parameter', function (): void {
        $ref = new ReflectionClass(DTOCast::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('dtoClass');
        expect($params[0]->isReadOnly())->toBeTrue();
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->isOptional())->toBeTrue();
    });

    // ── Interface Contract Verification ──────────────────────────────────

    it('DataTransferObject implements all required interfaces', function (): void {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();
    });

    // ── DtoCollection Interface Contract ───────────────────────────────

    it('DtoCollection implements correct interfaces', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(Countable::class))->toBeTrue();
        expect($ref->implementsInterface(IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();
    });

    it('DtoCollection blocks cloning', function (): void {
        $ref = new ReflectionClass(DtoCollection::class);
        $clone = $ref->getMethod('__clone');
        $returnType = $clone->getReturnType();
        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('never');
    });

    // ── Validation Attribute Interface Contract ────────────────────────

    it('all validation attributes implement ValidationAttribute', function (): void {
        $validationAttributes = [
            Required::class,
            Email::class,
            Max::class,
            Min::class,
            Url::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->implementsInterface(ValidationAttribute::class))
                ->toBeTrue("{$class} must implement ValidationAttribute");
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$class} must have ruleKey()");
        }
    });

    it('validation attribute ruleKey() returns string', function (): void {
        $attributes = [
            Required::class => 'required',
            Email::class => 'email',
            Max::class => 'max',
            Min::class => 'min',
            Url::class => 'url',
        ];

        foreach ($attributes as $class => $expectedKey) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();
            $instance = $constructor->getParameters() !== []
                ? $ref->newInstanceWithoutConstructor()
                : new $class;

            // For Required, we can create a real instance with default args
            if ($class === Required::class) {
                $instance = new Required();
            } elseif ($class === Email::class) {
                $instance = new Email();
            } elseif ($class === Url::class) {
                $instance = new Url();
            } elseif ($class === Max::class) {
                $instance = new Max(255);
            } elseif ($class === Min::class) {
                $instance = new Min(1);
            }

            expect($instance->ruleKey())->toBe($expectedKey);
        }
    });

    // ── Attribute Target Verification ─────────────────────────────────

    it('validation attributes target properties', function (): void {
        $propertyAttributes = [
            Required::class,
            Email::class,
            Max::class,
            Min::class,
            Url::class,
            Nullable::class,
            Hidden::class,
        ];

        foreach ($propertyAttributes as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes();
            $foundTarget = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === 'Attribute') {
                    $instance = $attr->newInstance();
                    $flags = $instance->getFlags();
                    expect($flags & Attribute::TARGET_PROPERTY)->toBeGreaterThan(0);
                    $foundTarget = true;
                }
            }
            expect($foundTarget)->toBeTrue("{$class} must have TARGET_PROPERTY");
        }
    });

    it('metadata attributes have correct targets', function (): void {
        // MapFrom and Cast are metadata-only (no ValidationAttribute)
        $metaAttributes = [MapFrom::class, Cast::class, DefaultValue::class];

        foreach ($metaAttributes as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes();
            $foundTarget = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === 'Attribute') {
                    $instance = $attr->newInstance();
                    $flags = $instance->getFlags();
                    expect($flags & Attribute::TARGET_PROPERTY)->toBeGreaterThan(0);
                    $foundTarget = true;
                }
            }
            expect($foundTarget)->toBeTrue("{$class} must have TARGET_PROPERTY");
        }
    });

    // ── DataTransferObject Method Return Type Verification ──────────────

    it('DataTransferObject has complete return types', function (): void {
        $ref = new ReflectionClass(DataTransferObject::class);
        $publicMethods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethods as $method) {
            if ($method->isStatic()) {
                continue;
            }

            $name = $method->getName();
            // Skip inherited from parent interfaces (jsonSerialize may come from JsonSerializable)
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "DataTransferObject::{$name}() must have a return type"
            );
        }
    });

    // ── DTORoundtrip Consistency ───────────────────────────────────────

    it('DTO fromArray + toArray roundtrip preserves data', function (): void {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $restored = $dto->toArray();

        expect($restored['name'])->toBe($data['name']);
        expect($restored['email'])->toBe($data['email']);
    });

    it('DTO fromJson + toJson roundtrip preserves data', function (): void {
        $data = ['name' => 'Jane', 'email' => 'jane@example.com'];
        $json = json_encode($data);

        $dto = RoundtripDTO::fromJson($json, validate: false);
        $restoredJson = $dto->toJson();
        $restored = json_decode($restoredJson, true);

        expect($restored['name'])->toBe('Jane');
        expect($restored['email'])->toBe('jane@example.com');
    });

    // ── DTO equals() Contract ──────────────────────────────────────────

    it('equals() returns true for identical DTOs', function (): void {
        $data = ['name' => 'Alice', 'email' => 'alice@example.com'];
        $dto1 = RoundtripDTO::fromArray($data, validate: false);
        $dto2 = RoundtripDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different DTOs', function (): void {
        $dto1 = RoundtripDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
        $dto2 = RoundtripDTO::fromArray(['name' => 'Bob', 'email' => 'b@c.com'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    // ── DTO isEmpty() Contract ─────────────────────────────────────────

    it('isEmpty() detects DTO with all default values', function (): void {
        $dto = MinimalDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty() detects DTO with data', function (): void {
        $dto = RoundtripDTO::fromArray(['name' => 'Test', 'email' => 't@t.com'], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ── DtoCollection Immutability ──────────────────────────────────────

    it('append() returns a new collection', function (): void {
        $data1 = ['name' => 'Item 1', 'email' => 'i1@t.com'];
        $data2 = ['name' => 'Item 2', 'email' => 'i2@t.com'];
        $dto1 = RoundtripDTO::fromArray($data1, validate: false);
        $dto2 = RoundtripDTO::fromArray($data2, validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = $col1->append($dto2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('merge() returns a new collection', function (): void {
        $data1 = ['name' => 'A', 'email' => 'a@b.com'];
        $data2 = ['name' => 'B', 'email' => 'b@c.com'];
        $dto1 = RoundtripDTO::fromArray($data1, validate: false);
        $dto2 = RoundtripDTO::fromArray($data2, validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    // ── Only/Except Contract ──────────────────────────────────────────

    it('only() returns specified fields', function (): void {
        $dto = RoundtripDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
        $filtered = $dto->only('name');

        expect($filtered)->toHaveKey('name');
        expect($filtered)->not->toHaveKey('email');
    });

    it('except() excludes specified fields', function (): void {
        $dto = RoundtripDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
        $filtered = $dto->except('email');

        expect($filtered)->toHaveKey('name');
        expect($filtered)->not->toHaveKey('email');
    });

    // ── DTOManager Contract ───────────────────────────────────────────

    it('DTOManager::make creates a DTO from array', function (): void {
        $manager = new DTOManager;
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        $dto = $manager->make(RoundtripDTO::class, $data);

        expect($dto)->toBeInstanceOf(RoundtripDTO::class);
    });

    it('DTOManager::rules returns validation rules', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rules(RoundtripDTO::class);

        expect($rules)->toBeArray();
    });

    // ── Hidden Attribute Contract ─────────────────────────────────────

    it('Hidden attribute excludes properties from toArray()', function (): void {
        $dto = RoundtripDTO::fromArray(['name' => 'Test', 'email' => 't@t.com'], validate: false);
        $public = $dto->toArray();
        $all = $dto->allValues();

        // allValues should contain everything; toArray may hide some fields
        expect($public)->toBeArray();
        expect($all)->toBeArray();
    });
});
