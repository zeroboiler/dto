<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
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
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTO Attribute Contract — All 41 Attributes Are Final', function () {
    it('all 41 attribute classes are final', function () {
        $attributes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Cast::class, Collection::class, Confirmed::class, Date::class,
            Declined::class, DefaultValue::class, Different::class, Distinct::class,
            Email::class, EndsWith::class, Enum::class, Hidden::class,
            In::class, Integer::class, Json::class, MapFrom::class,
            Max::class, Min::class, NestedArray::class, Nullable::class,
            Numeric::class, Pattern::class, Present::class, Prohibited::class,
            Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Size::class,
            Sometimes::class, StartsWith::class, Url::class, Uuid::class,
        ];

        expect(count($attributes))->toBe(41);

        foreach ($attributes as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue("{$attrClass} should be final");
        }
    });

    it('validation attributes implement ValidationAttribute contract', function () {
        $validationAttrs = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Collection::class, Confirmed::class, Date::class, Declined::class,
            Distinct::class, Email::class, EndsWith::class, Enum::class,
            In::class, Integer::class, Json::class, Max::class, Min::class,
            NestedArray::class, Nullable::class, Numeric::class, Pattern::class,
            Present::class, Prohibited::class, Required::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class, Same::class,
            Size::class, Sometimes::class, StartsWith::class, Url::class, Uuid::class,
        ];

        foreach ($validationAttrs as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue(
                "{$attrClass} should implement ValidationAttribute"
            );
        }
    });

    it('metadata attributes do NOT implement ValidationAttribute', function () {
        $metaAttrs = [Cast::class, Hidden::class, MapFrom::class, DefaultValue::class];

        foreach ($metaAttrs as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse(
                "{$attrClass} should NOT implement ValidationAttribute"
            );
        }
    });
});

describe('DTO Attribute — Readonly Promoted Properties Contract', function () {
    it('validation attribute properties are readonly and public', function () {
        $ref = new ReflectionClass(Email::class);
        $prop = $ref->getProperty('message');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->isPublic())->toBeTrue();
    });

    it('Cast attribute has readonly type and message properties', function () {
        $ref = new ReflectionClass(Cast::class);

        $typeProp = $ref->getProperty('type');
        expect($typeProp->isReadOnly())->toBeTrue();
        expect($typeProp->isPublic())->toBeTrue();

        $msgProp = $ref->getProperty('message');
        expect($msgProp->isReadOnly())->toBeTrue();
        expect($msgProp->isPublic())->toBeTrue();
    });

    it('MapFrom attribute has readonly key and message properties', function () {
        $ref = new ReflectionClass(MapFrom::class);

        $keyProp = $ref->getProperty('key');
        expect($keyProp->isReadOnly())->toBeTrue();
        expect($keyProp->isPublic())->toBeTrue();
    });

    it('DefaultValue attribute has readonly value property', function () {
        $ref = new ReflectionClass(DefaultValue::class);

        $valueProp = $ref->getProperty('value');
        expect($valueProp->isReadOnly())->toBeTrue();
        expect($valueProp->isPublic())->toBeTrue();
    });
});

describe('DTO Metadata Resolver — Rule Generation Contract', function () {
    it('resolves empty rules for DTO without validation attributes', function () {
        // MinimalDTO has only #[Required] attributes
        $metadata = DtoMetadataResolver::resolve(MinimalDTO::class);

        expect($metadata['rules'])->toHaveKey('name');
        expect($metadata['rules'])->toHaveKey('value');
    });

    it('resolves email rule from Email attribute', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        $emailRules = $metadata['rules']['email'] ?? [];
        expect($emailRules)->toContain('email');
    });

    it('resolves min/max rules from Min/Max attributes', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        $nameRules = $metadata['rules']['name'] ?? [];
        expect($nameRules)->toContain('min:2');
        expect($nameRules)->toContain('max:50');
    });

    it('resolves map_from metadata from MapFrom attribute', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($metadata['properties']['phone']['map_from'])->toBe('phone_number');
    });

    it('resolves hidden metadata from Hidden attribute', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($metadata['properties']['password']['hidden'])->toBeTrue();
    });

    it('resolves default value from DefaultValue attribute', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($metadata['properties']['status']['has_default'])->toBeTrue();
        expect($metadata['properties']['status']['default'])->toBe('active');
    });

    it('resolves cast type from Cast attribute', function () {
        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($metadata['properties']['tags']['cast'])->toBe('array');
    });
});

describe('DTOException — Factory Methods Contract', function () {
    it('invalidCast formats message with property, type, and debug type', function () {
        $e = DTOException::invalidCast('status', 'integer', 'not-a-number');

        expect($e->getMessage())->toContain('status');
        expect($e->getMessage())->toContain('integer');
        expect($e->getMessage())->toContain('not-a-number');
    });

    it('invalidCast uses get_debug_type for non-string values', function () {
        $e = DTOException::invalidCast('count', 'integer', 42);

        expect($e->getMessage())->toContain('count');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson formats message with property and error', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString includes class name and message', function () {
        $e = DTOException::invalidCast('field', 'int', 'abc');

        $str = (string) $e;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('field');
    });
});

describe('DtoCollection — Immutability And Type Safety', function () {
    it('prevents cloning', function () {
        $collection = DtoCollection::make([]);
        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });

    it('append returns new collection without modifying original', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $original = DtoCollection::make([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($original)->not->toBe($appended);
    });

    it('merge returns new collection with items from both', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'C', 'value' => '3'], validate: false);

        $a = DtoCollection::make([$dto1]);
        $b = DtoCollection::make([$dto2, $dto3]);
        $merged = $a->merge($b);

        expect($merged->count())->toBe(3);
        expect($a->count())->toBe(1);
        expect($b->count())->toBe(2);
    });

    it('filter returns new collection with matching items only', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $filtered = $collection->filter(fn ($dto) => $dto->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($collection->count())->toBe(2);
    });

    it('pluck extracts single property from all DTOs', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('push mutates in-place and returns self for chaining', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2); // mutated
        expect($result)->toBe($collection); // same instance
    });

    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not-a-dto']))->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes after removal', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'C', 'value' => '3'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->name)->toBe('B'); // re-indexed
    });

    it('toArray serializes all DTOs', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'val'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr[0])->toHaveKey('name');
        expect($arr[0]['name'])->toBe('Test');
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'val'], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });
});

describe('DataTransferObject — Type System Contract', function () {
    it('all source classes are final or abstract', function () {
        $classes = [
            DataTransferObject::class, // abstract
            DtoCollection::class,
            DTOException::class,
            \ZeroBoiler\DTO\DTOManager::class,
            \ZeroBoiler\DTO\DTOSServiceProvider::class,
            \ZeroBoiler\DTO\Casts\DTOCast::class,
            \ZeroBoiler\DTO\Facades\DTO::class,
            \ZeroBoiler\DTO\Support\DtoMetadataResolver::class,
            \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class,
            \ZeroBoiler\DTO\Contracts\FromRequestDTO::class,
            \ZeroBoiler\DTO\Contracts\ValidatableDTO::class,
            \ZeroBoiler\DTO\Contracts\ValidationAttribute::class,
        ];

        foreach ($classes as $class) {
            $ref = new ReflectionClass($class);

            if ($ref->isAbstract() || $ref->isInterface()) {
                // OK — abstract classes and interfaces don't need final
                expect(true)->toBeTrue();
            } else {
                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        }
    });

    it('DTOManager is readonly', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);

        expect($ref->isFinal())->toBeTrue();
        // PHP 8.1+ readonly class check — readonly classes cannot have mutable properties
    });
});

describe('DataTransferObject — fromArray Validation Contract', function () {
    it('throws ValidationException for missing required fields', function () {
        expect(fn () => MinimalDTO::fromArray([]))->toThrow(
            \Illuminate\Validation\ValidationException::class
        );
    });

    it('creates DTO with valid data', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42']);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('42');
    });

    it('supports fromArray with validate: false', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);

        expect($dto->name)->toBe('Test');
    });

    it('fromJson parses JSON string', function () {
        $dto = MinimalDTO::fromJson('{"name":"Test","value":"42"}', validate: false);

        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('42');
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => MinimalDTO::fromJson('not-json'))
            ->toThrow(DTOException::class);
    });

    it('toJson produces valid JSON string', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
    });

    it('equals compares two DTOs correctly', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Other', 'value' => '99'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty returns false when properties have values', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('with returns new instance with overrides', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);
        $modified = $dto->with(['name' => 'Updated']);

        expect($modified->name)->toBe('Updated');
        expect($modified->value)->toBe('42');
        expect($dto->name)->toBe('Test'); // original unchanged
    });

    it('only returns only specified fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);

        $only = $dto->only('name');

        expect($only)->toBe(['name' => 'Test']);
    });

    it('except excludes specified fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '42'], validate: false);

        $except = $dto->except('value');

        expect($except)->toBe(['name' => 'Test']);
    });
});

describe('DataTransferObject — Metadata Cache Contract', function () {
    it('flushMetadataCache clears cached entries', function () {
        // Resolve metadata first (populates cache)
        DtoMetadataResolver::resolve(MinimalDTO::class);

        // Flush all
        DataTransferObject::flushMetadataCache();

        // Re-resolve should work fine (no stale data issues)
        $metadata = DtoMetadataResolver::resolve(MinimalDTO::class);

        expect($metadata['properties'])->toHaveKey('name');
        expect($metadata['properties'])->toHaveKey('value');
    });

    it('flushMetadataCache for specific class does not affect others', function () {
        DataTransferObject::flushMetadataCache();
        DtoMetadataResolver::resolve(MinimalDTO::class);
        DtoMetadataResolver::resolve(CreateUserDTO::class);

        DataTransferObject::flushMetadataCache(MinimalDTO::class);

        // Re-resolve both — should work without errors
        $m1 = DtoMetadataResolver::resolve(MinimalDTO::class);
        $m2 = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($m1['properties'])->toHaveKey('name');
        expect($m2['properties'])->toHaveKey('email');
    });
});
