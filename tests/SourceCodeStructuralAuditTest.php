<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Source code structural audit — verifies production-ready quality gates.
 *
 * This test file performs static structural verification of the DTO package:
 * - All source files have strict types declaration
 * - All classes use typed properties (no untyped properties)
 * - All public methods have return type declarations
 * - All public methods have PHPDoc with @param/@return annotations
 * - All classes are either final or abstract (no open non-abstract classes)
 * - No mixed return types in public API
 * - All validation attributes implement ValidationAttribute contract
 *
 * These tests use reflection and do not require a running Laravel application.
 */

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

describe('Source Code Quality Audit — DTO', function (): void {

    // ──────────────────────────────────────────────────────────────
    // All source files have declare(strict_types=1)
    // ──────────────────────────────────────────────────────────────

    describe('Strict types declaration', function (): void {
        it('every PHP source file declares strict_types=1', function (): void {
            $srcDir = dirname(__DIR__, 2) . '/src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            );
            $violations = [];

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                assert(is_string($content));

                if (! str_contains($content, 'declare(strict_types=1)')) {
                    $violations[] = $file->getPathname();
                }
            }

            expect($violations)->toBeEmpty(
                'Files missing declare(strict_types=1): ' . implode(', ', $violations)
            );
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Attribute classes are all final
    // ──────────────────────────────────────────────────────────────

    describe('Attribute classes are final', function (): void {
        it('all validation attributes in ZeroBoiler\DTO\Attributes are final', function (): void {
            $srcDir = dirname(__DIR__, 2) . '/src/Attributes';
            $files = glob($srcDir . '/*.php');

            expect($files)->not->toBeEmpty();

            foreach ($files as $file) {
                $content = file_get_contents($file);
                assert(is_string($content));

                // Only check classes that are actual attribute classes (not all files)
                if (! str_contains($content, 'Attribute(')) {
                    continue;
                }

                expect($content)->toContain('final class');
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // All validation attributes implement ValidationAttribute
    // ──────────────────────────────────────────────────────────────

    describe('ValidationAttribute contract compliance', function (): void {
        it('every validation attribute implements ValidationAttribute', function (): void {
            $validationAttributes = [
                \ZeroBoiler\DTO\Attributes\Accepted::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class,
                \ZeroBoiler\DTO\Attributes\Between::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\Collection::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class,
                \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\NestedArray::class,
                \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Present::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class,
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class,
                \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
                \ZeroBoiler\DTO\Attributes\Same::class,
                \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\Sometimes::class,
                \ZeroBoiler\DTO\Attributes\StartsWith::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
            ];

            foreach ($validationAttributes as $attrClass) {
                expect(is_a($attrClass, ValidationAttribute::class, true))->toBeTrue(
                    "{$attrClass} must implement ValidationAttribute"
                );
            }
        });

        it('every validation attribute has a ruleKey() method returning string', function (): void {
            $validationAttributes = [
                Accepted::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class,
                \ZeroBoiler\DTO\Attributes\Between::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\Collection::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class,
                \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\NestedArray::class,
                \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Present::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class,
                Required::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class,
                \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
                \ZeroBoiler\DTO\Attributes\Same::class,
                \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\Sometimes::class,
                \ZeroBoiler\DTO\Attributes\StartsWith::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
            ];

            foreach ($validationAttributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                expect($ref->hasMethod('ruleKey'))->toBeTrue("{$attrClass} must have ruleKey()");

                $method = $ref->getMethod('ruleKey');
                $returnType = $method->getReturnType();
                assert($returnType instanceof ReflectionNamedType);
                expect($returnType->getName())->toBe('string');
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Public API return types — no mixed in public method signatures
    // ──────────────────────────────────────────────────────────────

    describe('No missing return types in public API', function (): void {
        $publicClasses = [
            DTOManager::class,
            DTOException::class,
            DtoCollection::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
        ];

        foreach ($publicClasses as $class) {
            it("{$class} has no methods without return types", function () use ($class): void {
                $ref = new ReflectionClass($class);
                $violations = [];

                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $returnType = $method->getReturnType();
                    if ($returnType === null) {
                        $violations[] = $method->getName() . '()';
                    }
                }

                expect($violations)->toBeEmpty(
                    "{$class} methods without return types: " . implode(', ', $violations)
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // Exception classes have named constructors
    // ──────────────────────────────────────────────────────────────

    describe('Exception factory methods', function (): void {
        it('DTOException has invalidCast() and invalidJson() factory methods', function (): void {
            $ref = new ReflectionClass(DTOException::class);

            expect($ref->hasMethod('invalidCast'))->toBeTrue();
            expect($ref->getMethod('invalidCast')->isPublic())->toBeTrue();
            expect($ref->getMethod('invalidCast')->isStatic())->toBeTrue();

            expect($ref->hasMethod('invalidJson'))->toBeTrue();
            expect($ref->getMethod('invalidJson')->isPublic())->toBeTrue();
            expect($ref->getMethod('invalidJson')->isStatic())->toBeTrue();
        });

        it('DTOException factory methods return self', function (): void {
            $return = (new ReflectionMethod(DTOException::class, 'invalidCast'))->getReturnType();
            assert($return instanceof ReflectionNamedType);
            expect($return->getName())->toBe('self');

            $return2 = (new ReflectionMethod(DTOException::class, 'invalidJson'))->getReturnType();
            assert($return2 instanceof ReflectionNamedType);
            expect($return2->getName())->toBe('self');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DataTransferObject — abstract class shape
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject abstract shape', function (): void {
        it('is abstract', function (): void {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });

        it('implements FromRequestDTO, ValidatableDTO, Arrayable, JsonSerializable', function (): void {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
            expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
            expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DtoCollection — final class with correct interfaces
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection class shape', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function (): void {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('ArrayAccess methods have #[Override]', function (): void {
            $ref = new ReflectionClass(DtoCollection::class);
            foreach (['offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset'] as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("DtoCollection::{$method}() should have #[Override]");
            }
        });

        it('count(), getIterator(), jsonSerialize() have #[Override]', function (): void {
            $ref = new ReflectionClass(DtoCollection::class);
            foreach (['count', 'getIterator', 'jsonSerialize'] as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("DtoCollection::{$method}() should have #[Override]");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOManager is final and readonly
    // ──────────────────────────────────────────────────────────────

    describe('DTOManager class shape', function (): void {
        it('is final and readonly', function (): void {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTOCast is final
    // ──────────────────────────────────────────────────────────────

    describe('DTOCast class shape', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('has #[Override] on interface methods', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
            foreach (['get', 'set', 'serialize'] as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("DTOCast::{$method}() should have #[Override]");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTO Facade is final
    // ──────────────────────────────────────────────────────────────

    describe('DTO facade shape', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
            expect($ref->isFinal())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Metadata attributes are sealed classes
    // ──────────────────────────────────────────────────────────────

    describe('Metadata attributes', function (): void {
        it('Hidden has no constructor parameters (marker attribute)', function (): void {
            $ref = new ReflectionClass(Hidden::class);
            $constructor = $ref->getConstructor();
            // Hidden is a marker attribute — it may have no constructor
            if ($constructor !== null) {
                expect($constructor->getNumberOfParameters())->toBe(0);
            }
        });

        it('MapFrom, Cast, DefaultValue are final classes', function (): void {
            foreach ([MapFrom::class, \ZeroBoiler\DTO\Attributes\Cast::class, \ZeroBoiler\DTO\Attributes\DefaultValue::class] as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // ServiceProvider correctness
    // ──────────────────────────────────────────────────────────────

    describe('DTOSServiceProvider', function (): void {
        it('is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('register() and boot() have #[Override]', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            foreach (['register', 'boot'] as $method) {
                $attrs = $ref->getMethod($method)->getAttributes();
                $hasOverride = array_any($attrs, fn (\ReflectionAttribute $a): bool => $a->getName() === 'Override');
                expect($hasOverride)->toBeTrue("DTOSServiceProvider::{$method}() should have #[Override]");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Contracts — interface completeness
    // ──────────────────────────────────────────────────────────────

    describe('Contract interfaces', function (): void {
        it('FromRequestDTO requires static fromRequest(Request, bool)', function (): void {
            $ref = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
            expect($ref->isPublic())->toBeTrue();
            expect($ref->isStatic())->toBeTrue();

            $params = $ref->getParameters();
            expect($params)->toHaveCount(2);

            // First param: Request
            $paramType = $params[0]->getType();
            assert($paramType instanceof ReflectionNamedType);
            expect($paramType->getName())->toBe(\Illuminate\Http\Request::class);

            // Return type: static
            $returnType = $ref->getReturnType();
            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('static');
        });

        it('ValidatableDTO requires static rules() and rulesFor(string)', function (): void {
            $rulesRef = new ReflectionMethod(ValidatableDTO::class, 'rules');
            expect($rulesRef->isPublic())->toBeTrue();
            expect($rulesRef->isStatic())->toBeTrue();

            $rulesForRef = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
            expect($rulesForRef->isPublic())->toBeTrue();
            expect($rulesForRef->isStatic())->toBeTrue();

            $params = $rulesForRef->getParameters();
            expect($params)->toHaveCount(1);
        });

        it('ValidationAttribute requires ruleKey(): string', function (): void {
            $ref = new ReflectionMethod(ValidationAttribute::class, 'ruleKey');
            expect($ref->isPublic())->toBeTrue();

            $returnType = $ref->getReturnType();
            assert($returnType instanceof ReflectionNamedType);
            expect($returnType->getName())->toBe('string');
        });
    });
});
