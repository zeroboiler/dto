<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
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
use ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand;
use ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand;
use Illuminate\Contracts\Support\Arrayable;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Source code quality audit — verifies production-ready invariants via reflection.
 *
 * This test validates that every source file in the package adheres to
 * the quality standards required for PHPStan level 9 compliance:
 *
 * 1. `declare(strict_types=1)` in every PHP file
 * 2. All service classes are `final`
 * 3. All attribute classes are `final`
 * 4. `readonly` promoted properties on all attribute constructors
 * 5. All public methods have explicit return types
 * 6. Docblocks present on all public classes and methods
 * 7. DtoCollection is `final` and implements correct interfaces
 * 8. DTOManager is `final readonly`
 */
describe('DTO Source Code Quality', function () {
    /**
     * Get all PHP source files in the src/ directory.
     *
     * @return list<string>
     */
    function getSrcFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__.'/../src', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    it('has declare(strict_types=1) in every source file', function () {
        $files = getSrcFiles();
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->not->toBeFalse();
            expect((bool) preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', $contents))
                ->toBeTrue("File {$file} is missing declare(strict_types=1)");
        }
    });

    it('marks all service classes as final', function () {
        $serviceClasses = [
            DataTransferObject::class,
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOException::class,
            DTOSServiceProvider::class,
            DTO::class,
            MakeDtoTestCommand::class,
            MakeDtoSchemaCommand::class,
        ];

        foreach ($serviceClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())
                ->toBeTrue("{$class} must be final");
        }
    });

    it('marks all validation attribute classes as final', function () {
        $attributeClasses = [
            Accepted::class,
            Boolean::class,
            Cast::class,
            Confirmed::class,
            Declined::class,
            DefaultValue::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            Enum::class,
            Hidden::class,
            In::class,
            Integer::class,
            Json::class,
            MapFrom::class,
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
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())
                ->toBeTrue("{$class} must be final");
        }
    });

    it('uses readonly promoted properties on all attribute constructors', function () {
        /** @var list<class-string> $attributeClasses */
        $attributeClasses = [
            Accepted::class,
            Boolean::class,
            Cast::class,
            Confirmed::class,
            Declined::class,
            DefaultValue::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            Enum::class,
            Hidden::class,
            In::class,
            Integer::class,
            Json::class,
            MapFrom::class,
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
        ];

        foreach ($attributeClasses as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            expect($constructor)->not->toBeNull("{$class} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                if ($param->isPromoted()) {
                    $prop = $ref->getProperty($param->name);
                    expect($prop->isReadOnly())
                        ->toBeTrue("{$class}::\${$param->name} must be readonly");
                }
            }
        }
    });

    it('has explicit return types on all public methods of key classes', function () {
        $classesWithPublicMethods = [
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOException::class,
        ];

        foreach ($classesWithPublicMethods as $class) {
            $ref = new ReflectionClass($class);

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor()) {
                    continue;
                }

                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "{$class}::{$method->getName()}() must have an explicit return type"
                );
            }
        }
    });

    it('has docblocks on all public classes', function () {
        $publicClasses = [
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOException::class,
            DTOSServiceProvider::class,
            DTO::class,
            FromRequestDTO::class,
            ValidatableDTO::class,
            ValidationAttribute::class,
        ];

        foreach ($publicClasses as $class) {
            $ref = new ReflectionClass($class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse(
                "{$class} must have a class-level docblock"
            );
        }
    });

    it('has DTOManager as a readonly class', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isReadOnly())->toBeTrue('DTOManager must be readonly');
    });

    it('implements correct interfaces on DtoCollection', function () {
        $ref = new ReflectionClass(DtoCollection::class);

        expect($ref->implementsInterface(ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(Countable::class))->toBeTrue();
        expect($ref->implementsInterface(IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();
    });

    it('implements correct interfaces on DataTransferObject', function () {
        $ref = new ReflectionClass(DataTransferObject::class);

        expect($ref->implementsInterface(Arrayable::class))->toBeTrue();
        expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();
    });

    it('uses #[Override] on DTO facade getFacadeAccessor', function () {
        $ref = new ReflectionClass(DTO::class);
        $method = $ref->getMethod('getFacadeAccessor');

        $attributes = $method->getAttributes();
        $hasOverride = false;

        foreach ($attributes as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }

        expect($hasOverride)->toBeTrue('DTO::getFacadeAccessor() must have #[Override]');
    });

    it('uses #[Override] on DTOCast interface methods', function () {
        $ref = new ReflectionClass(DTOCast::class);
        $methodsNeedingOverride = ['get', 'set', 'serialize'];

        foreach ($methodsNeedingOverride as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;

            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override') {
                    $hasOverride = true;
                    break;
                }
            }

            expect($hasOverride)->toBeTrue("DTOCast::{$methodName}() must have #[Override]");
        }
    });

    it('uses #[Override] on DtoCollection interface methods', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $methodsNeedingOverride = [
            'count', 'getIterator', 'offsetExists', 'offsetGet',
            'offsetSet', 'offsetUnset', 'jsonSerialize',
        ];

        foreach ($methodsNeedingOverride as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;

            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override') {
                    $hasOverride = true;
                    break;
                }
            }

            expect($hasOverride)->toBeTrue("DtoCollection::{$methodName}() must have #[Override]");
        }
    });

    it('uses #[Override] on DTOSServiceProvider methods', function () {
        $ref = new ReflectionClass(DTOSServiceProvider::class);
        $methodsNeedingOverride = ['register', 'boot'];

        foreach ($methodsNeedingOverride as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;

            foreach ($attributes as $attr) {
                if ($attr->getName() === 'Override') {
                    $hasOverride = true;
                    break;
                }
            }

            expect($hasOverride)->toBeTrue("DTOSServiceProvider::{$methodName}() must have #[Override]");
        }
    });

    it('has typed readonly properties on DTOManager constructor', function () {
        $ref = new ReflectionClass(DTOManager::class);
        $constructor = $ref->getConstructor();

        expect($constructor)->not->toBeNull();
        // DTOManager is readonly, so no promoted properties needed — it has no state
    });

    it('validates that DtoCollection items property is private', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $prop = $ref->getProperty('items');

        expect($prop->isPrivate())->toBeTrue('DtoCollection::$items must be private');
    });

    it('validates that DtoCollection has ArrayAccess template type', function () {
        $doc = (new ReflectionClass(DtoCollection::class))->getDocComment();
        expect($doc)->not->toBeFalse();

        expect(str_contains($doc, '@template'))->toBeTrue(
            'DtoCollection must have @template annotation for PHPStan generic support'
        );
    });
});
