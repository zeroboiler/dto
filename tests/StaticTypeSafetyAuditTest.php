<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 *
 * Static PHPStan Level 9 compliance assertions for the DTO package.
 * These tests verify type system guarantees at the source level.
 * They do not run PHPStan but assert that the public API
 * conforms to strict typing expectations.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DTO PHPStan L9 static compliance', function () {
    it('all public methods on DataTransferObject have return types', function () {
        $reflection = new ReflectionClass(DataTransferObject::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "DataTransferObject::{$method->getName()}() must have a return type declaration"
            );
        }
    });

    it('all public methods on DtoCollection have return types', function () {
        $reflection = new ReflectionClass(DtoCollection::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull(
                "DtoCollection::{$method->getName()}() must have a return type declaration"
            );
        }
    });

    it('DTOManager is final and readonly', function () {
        $reflection = new ReflectionClass(DTOManager::class);
        expect($reflection->isFinal())->toBeTrue();
        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('DtoCollection is final', function () {
        $reflection = new ReflectionClass(DtoCollection::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('DTOException is final', function () {
        $reflection = new ReflectionClass(DTOException::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('all validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            'Required', 'Email', 'Max', 'Min', 'Url', 'Pattern', 'In',
            'Integer', 'Numeric', 'Boolean', 'Uuid', 'Date', 'Enum',
            'Confirmed', 'Different', 'Same', 'Between', 'ArrayRule',
            'Prohibited', 'Present', 'Declined', 'Accepted', 'StartsWith',
            'EndsWith', 'Nullable', 'Sometimes', 'Distinct', 'Size',
            'Json', 'RequiredIf', 'RequiredUnless', 'RequiredWith',
            'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
            'NestedArray', 'Collection',
        ];

        foreach ($validationAttributes as $name) {
            $class = "ZeroBoiler\\DTO\\Attributes\\{$name}";
            expect(class_exists($class))->toBeTrue("Attribute class {$class} must exist");
            expect(is_a($class, ValidationAttribute::class, true))->toBeTrue(
                "{$class} must implement ValidationAttribute"
            );

            // Verify ruleKey() method returns string
            $reflection = new ReflectionClass($class);
            expect($reflection->hasMethod('ruleKey'))->toBeTrue("{$class} must have ruleKey() method");

            $method = $reflection->getMethod('ruleKey');
            $returnType = $method->getReturnType();
            expect($returnType?->getName())->toBe('string', "{$class}::ruleKey() must return string");
        }
    });

    it('metadata attributes do not implement ValidationAttribute', function () {
        $metadataOnly = [
            'MapFrom', 'Cast', 'Hidden', 'DefaultValue',
        ];

        foreach ($metadataOnly as $name) {
            $class = "ZeroBoiler\\DTO\\Attributes\\{$name}";
            expect(is_a($class, ValidationAttribute::class, true))->toBeFalse(
                "{$class} should NOT implement ValidationAttribute (it is a metadata attribute)"
            );
        }
    });

    it('all DTO attributes are final classes', function () {
        $attributesDir = dirname(__DIR__).'/src/Attributes';
        $files = glob("{$attributesDir}/*.php");

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if (preg_match('/namespace ZeroBoiler\\\\DTO\\\\Attributes;\s+.*?class (\w+)/s', $contents, $matches)) {
                $className = "ZeroBoiler\\DTO\\Attributes\\{$matches[1]}";
                $reflection = new ReflectionClass($className);
                expect($reflection->isFinal())->toBeTrue("{$className} must be final");
            }
        }
    });

    it('declare strict types is present in all source files', function () {
        $srcDir = dirname(__DIR__).'/src';
        $files = glob("{$srcDir}/**/*.php");

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)', "File {$file} must declare strict types");
        }
    });

    it('DTOCast is final', function () {
        $reflection = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        expect($reflection->isFinal())->toBeTrue();
    });

    it('all infrastructure classes are final', function () {
        $finalClasses = [
            DTOManager::class,
            DTOException::class,
            DtoCollection::class,
            \ZeroBoiler\DTO\Casts\DTOCast::class,
            \ZeroBoiler\DTO\Support\DtoMetadataResolver::class,
            \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class,
        ];

        foreach ($finalClasses as $class) {
            $reflection = new ReflectionClass($class);
            expect($reflection->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('CreateUserDTO fixture has readonly promoted properties', function () {
        $reflection = new ReflectionClass(\ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class);

        foreach ($reflection->getProperties() as $prop) {
            expect($prop->isReadOnly())->toBeTrue(
                "CreateUserDTO::\${$prop->getName()} must be readonly"
            );
        }
    });
});
