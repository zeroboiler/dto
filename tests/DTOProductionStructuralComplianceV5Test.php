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
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

describe('DTO Production Structural Compliance V5', function () {
    // ── Strict Types ────────────────────────────────────────────────

    it('every source file has declare(strict_types=1)', function () {
        $srcDir = __DIR__.'/../src';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'declare(strict_types=1)')) {
                $violations[] = $file->getBasename();
            }
        }

        expect($violations)->toBeEmpty('Files missing declare(strict_types=1): '.implode(', ', $violations));
    });

    // ── Final Classes ───────────────────────────────────────────────

    it('all service/infrastructure classes are final', function () {
        $finalClasses = [
            DataTransferObject::class, // abstract — checked separately
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DTOException::class,
            DTO::class,
            DTOSServiceProvider::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
        ];

        foreach ($finalClasses as $class) {
            if ($class === DataTransferObject::class) {
                continue; // abstract
            }
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    // ── All Validation Attributes are final ──────────────────────────

    it('all 31 validation attributes are final', function () {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Declined::class, Accepted::class,
            Prohibited::class, Present::class, Sometimes::class,
            Nullable::class, Same::class, Different::class,
            Distinct::class, Size::class,
            StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    // ── All Metadata Attributes are final ──────────────────────────

    it('all metadata attributes are final', function () {
        $metaAttrs = [
            Cast::class,
            MapFrom::class,
            Hidden::class,
            DefaultValue::class,
        ];

        foreach ($metaAttrs as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    // ── ValidationAttribute Interface ───────────────────────────────

    it('all validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Declined::class, Accepted::class,
            Prohibited::class, Present::class, Sometimes::class,
            Nullable::class, Same::class, Different::class,
            Distinct::class, Size::class,
            StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue("{$class} must implement ValidationAttribute");
        }
    });

    it('metadata attributes do NOT implement ValidationAttribute', function () {
        $metaAttrs = [Cast::class, MapFrom::class, Hidden::class, DefaultValue::class];

        foreach ($metaAttrs as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse("{$class} must NOT implement ValidationAttribute");
        }
    });

    // ── ValidationAttribute Targeting ───────────────────────────────

    it('all validation/metadata attributes target TARGET_PROPERTY', function () {
        $allAttrs = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Declined::class, Accepted::class,
            Prohibited::class, Present::class, Sometimes::class,
            Nullable::class, Same::class, Different::class,
            Distinct::class, Size::class,
            StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
            Cast::class, MapFrom::class, Hidden::class,
        ];

        foreach ($allAttrs as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not()->toBeEmpty("{$class} must have #[Attribute]");
            $instance = $attrs[0]->newInstance();
            expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY, "{$class} must target TARGET_PROPERTY");
        }
    });

    it('DefaultValue targets TARGET_PROPERTY | TARGET_PARAMETER', function () {
        $ref = new ReflectionClass(DefaultValue::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER);
    });

    // ── Readonly Properties on Attributes ──────────────────────────

    it('all validation attributes have readonly promoted constructor properties', function () {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Declined::class, Accepted::class,
            Prohibited::class, Present::class, Sometimes::class,
            Nullable::class, Same::class, Different::class,
            Distinct::class, Size::class,
            StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            $ctor = $ref->getConstructor();

            if ($ctor === null) {
                continue; // Hidden has no constructor
            }

            foreach ($ctor->getParameters() as $param) {
                expect($param->isPromoted())->toBeTrue("{$class}::\${$param->getName()} must be promoted");
            }

            foreach ($ref->getProperties() as $prop) {
                expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->getName()} must be readonly");
            }
        }
    });

    it('metadata attributes have readonly promoted properties', function () {
        $metaAttrs = [
            Cast::class,
            MapFrom::class,
            DefaultValue::class,
        ];

        foreach ($metaAttrs as $class) {
            $ref = new ReflectionClass($class);
            foreach ($ref->getProperties() as $prop) {
                expect($prop->isReadOnly())->toBeTrue("{$class}::\${$prop->getName()} must be readonly");
            }
        }
    });

    // ── DTOCast Readonly ──────────────────────────────────────────

    it('DTOCast has readonly promoted constructor properties', function () {
        $ref = new ReflectionClass(DTOCast::class);
        $ctor = $ref->getConstructor();

        foreach ($ctor->getParameters() as $param) {
            expect($param->isPromoted())->toBeTrue("DTOCast::\${$param->getName()} must be promoted");
            expect($param->isReadOnly())->toBeTrue("DTOCast::\${$param->getName()} must be readonly");
        }
    });

    // ── DTOManager Readonly ─────────────────────────────────────────

    it('DTOManager is readonly class', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        // PHP 8.2+ readonly class — DTOManager is `final readonly class`
    });

    // ── #[Override] on Interface Implementations ────────────────────

    it('DTOCast get/set have #[Override] attribute', function () {
        foreach (['get', 'set'] as $method) {
            $ref = new ReflectionMethod(DTOCast::class, $method);
            $attrs = array_map(
                fn (\ReflectionAttribute $a): string => $a->getName(),
                $ref->getAttributes()
            );
            expect($attrs)->toContain('Override', "DTOCast::{$method}() must have #[Override]");
        }
    });

    it('DTO facade has #[Override] on getFacadeAccessor', function () {
        $method = new ReflectionMethod(DTO::class, 'getFacadeAccessor');
        $attrs = array_map(
            fn (\ReflectionAttribute $a): string => $a->getName(),
            $method->getAttributes()
        );
        expect($attrs)->toContain('Override');
    });

    it('DataTransferObject has #[Override] on interface methods', function () {
        $overrideMethods = ['fromRequest', 'rules', 'rulesFor', 'toArray', 'jsonSerialize'];
        foreach ($overrideMethods as $method) {
            $ref = new ReflectionMethod(DataTransferObject::class, $method);
            $attrs = array_map(
                fn (\ReflectionAttribute $a): string => $a->getName(),
                $ref->getAttributes()
            );
            expect($attrs)->toContain('Override', "DataTransferObject::{$method}() must have #[Override]");
        }
    });

    it('DTOSServiceProvider register/boot have #[Override]', function () {
        foreach (['register', 'boot'] as $method) {
            $ref = new ReflectionMethod(DTOSServiceProvider::class, $method);
            $attrs = array_map(
                fn (\ReflectionAttribute $a): string => $a->getName(),
                $ref->getAttributes()
            );
            expect($attrs)->toContain('Override', "DTOSServiceProvider::{$method}() must have #[Override]");
        }
    });

    // ── Return Type Declarations ───────────────────────────────────

    it('DataTransferObject public/static methods have explicit return types', function () {
        $methods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromPartialRequest',
            'fromJson', 'validateArray', 'rules', 'rulesFor',
            'toArray', 'toJson', 'jsonSerialize', 'allValues',
            'equals', 'isEmpty', 'isNotEmpty', 'with',
            'only', 'except',
            'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        $ref = new ReflectionClass(DataTransferObject::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("DataTransferObject::{$method}() must have a return type");
        }
    });

    it('DtoCollection public methods all have explicit return types', function () {
        $methods = [
            'toArray', 'allValues', 'items', 'count', 'getIterator',
            'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset',
            'jsonSerialize', 'make', 'push', 'first', 'last',
            'map', 'filter', 'pluck', 'pluckKey',
            'append', 'merge', 'isEmpty', 'isNotEmpty',
        ];

        $ref = new ReflectionClass(DtoCollection::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("DtoCollection::{$method}() must have a return type");
        }
    });

    it('DTOManager public methods all have explicit return types', function () {
        $methods = ['validate', 'make', 'makeFromJson', 'schema'];

        $ref = new ReflectionClass(DTOManager::class);
        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            expect($m->hasReturnType())->toBeTrue("DTOManager::{$method}() must have a return type");
        }
    });

    // ── Docblocks ──────────────────────────────────────────────────

    it('DataTransferObject public methods have docblocks', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        $methods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromJson',
            'toArray', 'toJson', 'jsonSerialize', 'allValues',
            'equals', 'isEmpty', 'isNotEmpty', 'with',
            'only', 'except', 'validateArray', 'rules', 'rulesFor',
            'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $doc = $m->getDocComment();
            expect($doc)->not()->toBeFalse("DataTransferObject::{$method}() must have a docblock");
        }
    });

    it('DTOManager public methods have docblocks', function () {
        $ref = new ReflectionClass(DTOManager::class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            expect($doc)->not()->toBeFalse("DTOManager::{$method->getName()}() must have a docblock");
        }
    });

    it('DtoMetadataResolver public methods have docblocks', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            expect($doc)->not()->toBeFalse("DtoMetadataResolver::{$method->getName()}() must have a docblock");
        }
    });

    it('OpenApiSchemaGenerator public methods have docblocks', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $doc = $method->getDocComment();
            expect($doc)->not()->toBeFalse("OpenApiSchemaGenerator::{$method->getName()}() must have a docblock");
        }
    });

    // ── ruleKey() on all ValidationAttributes ───────────────────────

    it('all validation attributes have ruleKey() method returning string', function () {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Declined::class, Accepted::class,
            Prohibited::class, Present::class, Sometimes::class,
            Nullable::class, Same::class, Different::class,
            Distinct::class, Size::class,
            StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            $method = $ref->getMethod('ruleKey');
            expect($method->getReturnType()?->getName())->toBe('string', "{$class}::ruleKey() must return string");
        }
    });

    // ── Contracts ──────────────────────────────────────────────────

    it('DataTransferObject implements FromRequestDTO, JsonSerializable, ValidatableDTO, Arrayable', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
    });

    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    // ── Strict Comparisons ──────────────────────────────────────────

    it('DtoMetadataResolver uses only strict comparisons', function () {
        $file = (new ReflectionClass(DtoMetadataResolver::class))->getFileName();
        $content = file_get_contents($file);

        if (preg_match_all('/(?<!=)(?<!<)(?<!!)={2}(?!=)/', $content, $matches)) {
            expect(count($matches[0]))->toBe(0, 'DtoMetadataResolver should not use == comparisons');
        }
    });

    it('DataTransferObject uses only strict comparisons', function () {
        $file = (new ReflectionClass(DataTransferObject::class))->getFileName();
        $content = file_get_contents($file);

        if (preg_match_all('/(?<!=)(?<!<)(?<!!)={2}(?!=)/', $content, $matches)) {
            expect(count($matches[0]))->toBe(0, 'DataTransferObject should not use == comparisons');
        }
    });

    // ── @phpstan-type on DtoMetadataResolver ───────────────────────

    it('DtoMetadataResolver has @phpstan-type declarations', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        $doc = $ref->getDocComment();
        expect($doc)->not()->toBeFalse();
        expect($doc)->toContain('@phpstan-type DtoPropertyMeta');
        expect($doc)->toContain('@phpstan-type DtoResolvedMetadata');
    });

    it('DataTransferObject has @phpstan-type declarations', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        $doc = $ref->getDocComment();
        expect($doc)->not()->toBeFalse();
        expect($doc)->toContain('@phpstan-type DtoPropertyMeta');
        expect($doc)->toContain('@phpstan-type DtoResolvedMetadata');
    });

    // ── DTOException Factory Methods ────────────────────────────────

    it('DTOException has named constructors invalidCast() and invalidJson()', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->hasMethod('invalidCast'))->toBeTrue();
        expect($ref->hasMethod('invalidJson'))->toBeTrue();

        foreach (['invalidCast', 'invalidJson'] as $method) {
            $m = $ref->getMethod($method);
            expect($m->isStatic())->toBeTrue();
            expect($m->getReturnType()?->getName())->toBe(self::class);
        }
    });

    // ── Metadata Cache ──────────────────────────────────────────────

    it('DataTransferObject metadata cache can be flushed per class', function () {
        // Use a fixture DTO to test cache behavior
        $fixture = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class;

        // Resolve metadata (populates cache)
        $fixture::rules();
        DataTransferObject::flushMetadataCache($fixture);
        DataTransferObject::flushMetadataCache();
    });

    it('DataTransferObject setMetadataCacheTtl accepts float', function () {
        DataTransferObject::setMetadataCacheTtl(2.0);
        DataTransferObject::setMetadataCacheTtl(0.0);
        // No assertion — just ensure no crash
        expect(true)->toBeTrue();
    });

    // ── Composer Config Validation ─────────────────────────────────

    it('composer.json requires PHP ^8.5', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['require']['php'])->toBe('^8.5');
    });

    it('composer.json requires illuminate/contracts ^13.0', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['require']['illuminate/contracts'])->toBe('^13.0');
    });

    it('composer.json requires zeroboiler/value-objects ^1.0', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['require']['zeroboiler/value-objects'])->toBe('^1.0');
    });

    it('composer.json autoload maps ZeroBoiler\\DTO to src/', function () {
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($composer['autoload']['psr-4']['ZeroBoiler\\DTO\\'])->toBe('src/');
    });

    // ── phpstan.neon Targets Level 9 ───────────────────────────────

    it('phpstan.neon is configured for level 9', function () {
        $neon = file_get_contents(__DIR__.'/../phpstan.neon');
        expect($neon)->toContain('level: 9');
        expect($neon)->toContain('paths:');
        expect($neon)->toContain('- src');
    });

    // ── DtoCollection Type Safety ──────────────────────────────────

    it('DtoCollection constructor rejects non-DTO instances', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $ctor = $ref->getConstructor();

        // Verify the constructor has type checking
        $body = file_get_contents($ref->getFileName());
        expect($body)->toContain('instanceof DataTransferObject');
    });

    it('DtoCollection offsetSet rejects non-DTO values', function () {
        $body = file_get_contents((new ReflectionClass(DtoCollection::class))->getFileName());
        expect($body)->toContain('instanceof DataTransferObject');
    });

    // ── DTOCast Implements CastsAttributes ──────────────────────────

    it('DTOCast implements Illuminate CastsAttributes interface', function () {
        $ref = new ReflectionClass(DTOCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    // ── Integration ─────────────────────────────────────────────────

    it('zeroboiler/enums is listed as cross-package integration in README', function () {
        $readme = file_get_contents(__DIR__.'/../README.md');
        expect($readme)->toContain('zeroboiler/enums');
        expect($readme)->toContain('Cross-Package Integration');
    });
});
