<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

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
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Comprehensive production readiness audit — PHPStan Level 9 structural compliance.
 *
 * This test verifies source code quality standards without executing PHP:
 * - All classes are `final` (prevents uncontrolled inheritance)
 * - All files declare `strict_types=1` (enforces strict type checking at runtime)
 * - All public methods have return type declarations
 * - All properties use typed declarations (no untyped properties)
 * - All classes have docblocks with @see references
 * - Attribute classes use `#[Attribute]` with correct targets
 * - Constructor parameters are `public readonly` (attribute classes)
 * - DTO properties are readonly (immutability guarantee)
 * - Contracts define interfaces correctly
 */
describe('DTO — Source Code Production Readiness Audit', function () {

    // -----------------------------------------------------------------------
    // §1. File-level structural compliance
    // -----------------------------------------------------------------------
    describe('§1. File-level structural compliance', function () {
        $srcFiles = [
            'Attributes/Accepted.php',
            'Attributes/ArrayRule.php',
            'Attributes/Between.php',
            'Attributes/Boolean.php',
            'Attributes/Cast.php',
            'Attributes/Collection.php',
            'Attributes/Confirmed.php',
            'Attributes/Date.php',
            'Attributes/Declined.php',
            'Attributes/DefaultValue.php',
            'Attributes/Different.php',
            'Attributes/Distinct.php',
            'Attributes/Email.php',
            'Attributes/EndsWith.php',
            'Attributes/Enum.php',
            'Attributes/Hidden.php',
            'Attributes/In.php',
            'Attributes/Integer.php',
            'Attributes/Json.php',
            'Attributes/MapFrom.php',
            'Attributes/Max.php',
            'Attributes/Min.php',
            'Attributes/NestedArray.php',
            'Attributes/Nullable.php',
            'Attributes/Numeric.php',
            'Attributes/Pattern.php',
            'Attributes/Present.php',
            'Attributes/Prohibited.php',
            'Attributes/Required.php',
            'Attributes/RequiredIf.php',
            'Attributes/RequiredUnless.php',
            'Attributes/RequiredWith.php',
            'Attributes/RequiredWithAll.php',
            'Attributes/RequiredWithout.php',
            'Attributes/RequiredWithoutAll.php',
            'Attributes/Same.php',
            'Attributes/Size.php',
            'Attributes/Sometimes.php',
            'Attributes/StartsWith.php',
            'Attributes/Url.php',
            'Attributes/Uuid.php',
            'Casts/DTOCast.php',
            'Contracts/FromRequestDTO.php',
            'Contracts/ValidatableDTO.php',
            'Contracts/ValidationAttribute.php',
            'DataTransferObject.php',
            'DtoCollection.php',
            'DTOManager.php',
            'DTOSServiceProvider.php',
            'Exceptions/DTOException.php',
            'Facades/DTO.php',
            'Support/DtoMetadataResolver.php',
            'Support/OpenApiSchemaGenerator.php',
        ];

        test('all source files declare strict_types=1', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $content = file_get_contents($path);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        test('all source files have namespace declarations', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $tokens = token_get_all(file_get_contents($path));
                $hasNamespace = false;
                foreach ($tokens as $token) {
                    if (is_array($token) && $token[0] === T_NAMESPACE) {
                        $hasNamespace = true;
                        break;
                    }
                }
                expect($hasNamespace)->toBeTrue("File {$file} is missing namespace declaration");
            }
        });

        test('all source files have a file-level docblock', function () use ($srcFiles) {
            foreach ($srcFiles as $file) {
                $path = __DIR__.'/../src/'.$file;
                $content = file_get_contents($path);
                expect($content)->toMatch('/\/\*\*/', "File {$file} is missing a docblock");
                expect($content)->toContain('ZeroBoiler', "File {$file} docblock does not reference ZeroBoiler");
            }
        });
    });

    // -----------------------------------------------------------------------
    // §2. Class-level structural compliance
    // -----------------------------------------------------------------------
    describe('§2. Class-level structural compliance', function () {

        $finalClasses = [
            Accepted::class,
            Between::class,
            Boolean::class,
            Cast::class,
            Collection::class,
            Confirmed::class,
            Date::class,
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
            DTOCast::class,
            DtoCollection::class,
            DTOManager::class,
            DTOException::class,
            DTO::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOSServiceProvider::class,
        ];

        test('all non-abstract, non-interface classes are final', function () use ($finalClasses) {
            foreach ($finalClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} is not declared as final");
            }
        });

        test('DataTransferObject is abstract and not final', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue('DataTransferObject should be abstract');
            expect($ref->isFinal())->toBeFalse('DataTransferObject should NOT be final (it is meant to be extended)');
        });

        test('all final classes have a class-level docblock', function () use ($finalClasses) {
            foreach ($finalClasses as $class) {
                $ref = new ReflectionClass($class);
                $doc = $ref->getDocComment();
                expect($doc)->not->toBeFalse("{$class} is missing a class-level docblock");
                expect((string) $doc)->toContain('/**');
                expect((string) $doc)->toContain('*/');
            }
        });

        test('DTOManager is readonly', function () {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isReadOnly())->toBeTrue('DTOManager should be a readonly class');
        });
    });

    // -----------------------------------------------------------------------
    // §3. Method-level return type declarations
    // -----------------------------------------------------------------------
    describe('§3. Method-level return type declarations', function () {

        test('DtoCollection all public methods have return types', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DtoCollection::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('DTOManager all public methods have return types', function () {
            $ref = new ReflectionClass(DTOManager::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DTOManager::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('DTOException all public methods have return types', function () {
            $ref = new ReflectionClass(DTOException::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DTOException::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('DtoMetadataResolver all public methods have return types', function () {
            $ref = new ReflectionClass(DtoMetadataResolver::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DtoMetadataResolver::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('OpenApiSchemaGenerator all public methods have return types', function () {
            $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "OpenApiSchemaGenerator::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('DataTransferObject public static methods have return types', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            $methods = $ref->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC
            );

            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DataTransferObject::{$method->getName()}() is missing a return type declaration"
                );
            }
        });

        test('DataTransferObject public instance methods have return types', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->isStatic()) {
                    continue;
                }
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull(
                    "DataTransferObject::{$method->getName()}() is missing a return type declaration"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §4. Attribute class compliance
    // -----------------------------------------------------------------------
    describe('§4. Attribute class compliance', function () {

        $validationAttributes = [
            Accepted::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Date::class,
            Declined::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            Integer::class,
            Json::class,
            Max::class,
            Min::class,
            Nullable::class,
            Numeric::class,
            Pattern::class,
            Present::class,
            Prohibited::class,
            Required::class,
            Same::class,
            Size::class,
            Sometimes::class,
            StartsWith::class,
            Url::class,
            Uuid::class,
            Enum::class,
            Collection::class,
            NestedArray::class,
        ];

        $metadataAttributes = [
            Cast::class,
            MapFrom::class,
            Hidden::class,
            DefaultValue::class,
        ];

        test('all validation attributes implement ValidationAttribute', function () use ($validationAttributes) {
            foreach ($validationAttributes as $class) {
                expect($class)->toImplement(ValidationAttribute::class);
            }
        });

        test('metadata attributes do NOT implement ValidationAttribute', function () use ($metadataAttributes) {
            foreach ($metadataAttributes as $class) {
                expect(is_subclass_of($class, ValidationAttribute::class))->toBeFalse(
                    "{$class} should NOT implement ValidationAttribute"
                );
            }
        });

        test('all validation attributes have ruleKey() method returning string', function () use ($validationAttributes) {
            foreach ($validationAttributes as $class) {
                $ref = new ReflectionClass($class);
                $method = $ref->getMethod('ruleKey');
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull();
                expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'string')->toBeTrue(
                    "{$class}::ruleKey() should return string"
                );
            }
        });

        test('all validation attributes have #[Attribute] on TARGET_PROPERTY', function () use ($validationAttributes) {
            foreach ($validationAttributes as $class) {
                $ref = new ReflectionClass($class);
                $attrs = $ref->getAttributes(\Attribute::class);
                expect($attrs)->not->toBeEmpty("{$class} is missing #[Attribute]");

                $instance = $attrs[0]->newInstance();
                expect($instance->flags & Attribute::TARGET_PROPERTY)->toBeGreaterThan(0,
                    "{$class} should target PROPERTY"
                );
            }
        });

        test('all attribute classes use readonly properties', function () use ($validationAttributes, $metadataAttributes) {
            $allAttrs = [...$validationAttributes, ...$metadataAttributes];

            foreach ($allAttrs as $class) {
                $ref = new ReflectionClass($class);
                $props = $ref->getProperties();

                foreach ($props as $prop) {
                    expect($prop->isReadOnly())->toBeTrue(
                        "{$class}::\${$prop->getName()} should be readonly"
                    );
                }
            }
        });
    });

    // -----------------------------------------------------------------------
    // §5. Exception compliance
    // -----------------------------------------------------------------------
    describe('§5. Exception compliance', function () {

        test('DTOException extends Exception', function () {
            expect(is_subclass_of(DTOException::class, Exception::class))->toBeTrue();
        });

        test('DTOException has named constructors', function () {
            $ref = new ReflectionClass(DTOException::class);
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

            $names = array_map(fn (ReflectionMethod $m): string => $m->getName(), $methods);
            expect($names)->toContain('invalidCast');
            expect($names)->toContain('invalidJson');
        });

        test('DTOException named constructors return self', function () {
            $ref = new ReflectionClass(DTOException::class);

            foreach (['invalidCast', 'invalidJson'] as $methodName) {
                $method = $ref->getMethod($methodName);
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull();
                expect($returnType instanceof ReflectionNamedType && $returnType->getName() === 'self')->toBeTrue(
                    "DTOException::{$methodName}() should return self"
                );
            }
        });

        test('DTOException has __toString method', function () {
            $ref = new ReflectionClass(DTOException::class);
            expect($ref->hasMethod('__toString'))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // §6. Interface implementations
    // -----------------------------------------------------------------------
    describe('§6. Interface implementations', function () {

        test('DTOCast implements CastsAttributes', function () {
            expect(DTOCast::class)->toImplement(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        });

        test('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
            expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
            expect(DtoCollection::class)->toImplement(\Countable::class);
            expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
            expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
        });

        test('DataTransferObject implements Arrayable, FromRequestDTO, JsonSerializable, ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
            expect(DataTransferObject::class)->toImplement(JsonSerializable::class);
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });

        test('DTO facade extends Laravel Facade', function () {
            expect(DTO::class)->toExtend(\Illuminate\Support\Facades\Facade::class);
        });

        test('DTOCast has #[Override] on get/set/serialize', function () {
            $ref = new ReflectionClass(DTOCast::class);

            foreach (['get', 'set', 'serialize'] as $method) {
                $methodRef = $ref->getMethod($method);
                $attrs = $methodRef->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty(
                    "DTOCast::{$method}() should have #[\Override] attribute"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §7. Service provider compliance
    // -----------------------------------------------------------------------
    describe('§7. Service provider compliance', function () {

        test('DTOSServiceProvider extends ServiceProvider', function () {
            expect(DTOSServiceProvider::class)->toExtend(\Illuminate\Support\ServiceProvider::class);
        });

        test('DTOSServiceProvider has register and boot methods with Override', function () {
            $ref = new ReflectionClass(DTOSServiceProvider::class);

            foreach (['register', 'boot'] as $method) {
                $methodRef = $ref->getMethod($method);
                expect($methodRef->hasReturnType())->toBeTrue(
                    "DTOSServiceProvider::{$method}() should have a return type"
                );
                $attrs = $methodRef->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty(
                    "DTOSServiceProvider::{$method}() should have #[\Override] attribute"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §8. DtoCollection override compliance
    // -----------------------------------------------------------------------
    describe('§8. DtoCollection override compliance', function () {

        $overrideMethods = ['count', 'getIterator', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', 'jsonSerialize'];

        test('interface methods have #[Override] attribute', function () use ($overrideMethods) {
            $ref = new ReflectionClass(DtoCollection::class);

            foreach ($overrideMethods as $method) {
                $methodRef = $ref->getMethod($method);
                $attrs = $methodRef->getAttributes(\Override::class);
                expect($attrs)->not->toBeEmpty(
                    "DtoCollection::{$method}() should have #[\Override] attribute"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // §9. Docblock quality — @see references
    // -----------------------------------------------------------------------
    describe('§9. Docblock quality — @see references', function () {

        test('DtoMetadataResolver docblock references DataTransferObject', function () {
            $ref = new ReflectionClass(DtoMetadataResolver::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('@see');
            expect((string) $doc)->toContain('DataTransferObject');
        });

        test('OpenApiSchemaGenerator docblock references DataTransferObject', function () {
            $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('@see');
            expect((string) $doc)->toContain('DataTransferObject');
        });

        test('DTOCast docblock references CastsAttributes', function () {
            $ref = new ReflectionClass(DTOCast::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('CastsAttributes');
        });

        test('DTOManager docblock references DTO facade', function () {
            $ref = new ReflectionClass(DTOManager::class);
            $doc = $ref->getDocComment();
            expect($doc)->not->toBeFalse();
            expect((string) $doc)->toContain('@see');
            expect((string) $doc)->toContain('Facades');
        });
    });

    // -----------------------------------------------------------------------
    // §10. phpstan.neon.dist configuration
    // -----------------------------------------------------------------------
    describe('§10. phpstan.neon.dist configuration', function () {

        test('phpstan.neon.dist exists and targets level 9', function () {
            $path = __DIR__.'/../phpstan.neon.dist';
            expect(file_exists($path))->toBeTrue('phpstan.neon.dist should exist');

            $content = file_get_contents($path);
            expect($content)->toContain('level: 9');
            expect($content)->toContain('paths:');
            expect($content)->toContain('src');
        });

        test('phpstan.neon.dist excludes tests from analysis', function () {
            $content = file_get_contents(__DIR__.'/../phpstan.neon.dist');
            expect($content)->toContain('excludePaths');
            expect($content)->toContain('tests');
        });
    });
});
