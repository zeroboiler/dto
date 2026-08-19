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
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * PHPStan Level 9 contract compliance tests for the DTO package.
 *
 * Verifies that all public API surfaces conform to Level 9 requirements:
 * - No `mixed` return types on public methods
 * - All public methods have explicit return type declarations
 * - All classes use `declare(strict_types=1)`
 * - All attribute classes are `final`
 * - All service classes are `final`
 * - All attribute classes use `readonly` promoted properties
 * - The `mixed` type is only used in private methods and interface-required signatures
 * - Strict `===` comparisons (no loose `==` on type checks)
 */
describe('PHPStan Level 9 contract compliance', function () {
    // §1. All source files have declare(strict_types=1)
    describe('§1. Strict types declaration', function () {
        it('all source files start with declare(strict_types=1)', function () {
            $srcDir = dirname(__DIR__) . '/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            $violations = [];

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                if (! str_contains($content, "declare(strict_types=1)")) {
                    $violations[] = $file->getPathname();
                }
            }

            expect($violations)->toBeEmpty(
                'All PHP source files must have declare(strict_types=1). Violations: ' . implode(', ', $violations)
            );
        });
    });

    // §2. All attribute classes are final
    describe('§2. Attribute classes are final', function () {
        $attributeClasses = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Cast::class, Collection::class, Confirmed::class, Date::class,
            Declined::class, DefaultValue::class, Different::class, Distinct::class,
            Email::class, EndsWith::class, Enum::class, Hidden::class,
            In::class, Integer::class, Json::class, MapFrom::class,
            Max::class, Min::class, NestedArray::class, Nullable::class,
            Numeric::class, Pattern::class, Present::class, Prohibited::class,
            Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
            StartsWith::class, Url::class, Uuid::class,
        ];

        foreach ($attributeClasses as $class) {
            it("{$class} is final", function () use ($class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final for PHPStan L9 compliance");
            });
        }
    });

    // §3. All service/infrastructure classes are final
    describe('§3. Service classes are final', function () {
        $serviceClasses = [
            DataTransferObject::class,  // abstract — not final, but verify it IS abstract
            DtoCollection::class,
            DTOManager::class,
            DTOException::class,
            DTOSServiceProvider::class,
        ];

        it('DataTransferObject is abstract (not final, by design)', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue('DataTransferObject must be abstract');
        });

        $finalClasses = [DtoCollection::class, DTOManager::class, DTOException::class, DTOSServiceProvider::class];

        foreach ($finalClasses as $class) {
            it("{$class} is final", function () use ($class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            });
        }
    });

    // §4. No `mixed` return type on public API methods (only private or interface-required)
    describe('§4. No mixed return types on public API', function () {
        $publicClasses = [
            DtoCollection::class,
            DTOManager::class,
            DTOException::class,
            DTOSServiceProvider::class,
        ];

        foreach ($publicClasses as $class) {
            it("{$class} has no public methods returning mixed", function () use ($class) {
                $ref = new ReflectionClass($class);
                $violations = [];

                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    // Skip magic methods (offsetGet, etc.) which are required by interfaces
                    if (str_starts_with($method->getName(), '__')) {
                        continue;
                    }

                    $returnType = $method->getReturnType();
                    if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'mixed') {
                        $violations[] = $class . '::' . $method->getName() . '()';
                    }
                }

                expect($violations)->toBeEmpty(
                    "{$class} has public methods with mixed return type: " . implode(', ', $violations)
                );
            });
        }
    });

    // §5. All public methods have explicit return types
    describe('§5. All public methods have return type declarations', function () {
        $classes = [
            DtoCollection::class,
            DTOManager::class,
            DTOException::class,
        ];

        foreach ($classes as $class) {
            it("{$class} public methods all have return types", function () use ($class) {
                $ref = new ReflectionClass($class);
                $violations = [];

                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }

                    if ($method->getReturnType() === null) {
                        $violations[] = $method->getName() . '()';
                    }
                }

                expect($violations)->toBeEmpty(
                    "{$class} has public methods without return types: " . implode(', ', $violations)
                );
            });
        }
    });

    // §6. Attribute classes use readonly promoted properties only
    describe('§6. Attribute classes use readonly promoted properties', function () {
        $sampleAttributes = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, Between::class, Boolean::class, Integer::class, Numeric::class,
            Date::class, Cast::class, MapFrom::class, Hidden::class, DefaultValue::class,
            NestedArray::class, Collection::class, Nullable::class, Enum::class,
        ];

        foreach ($sampleAttributes as $class) {
            it("{$class} constructor has only promoted readonly properties", function () use ($class) {
                $ref = new ReflectionClass($class);
                $constructor = $ref->getConstructor();

                if ($constructor === null) {
                    expect(true)->toBeTrue(); // No constructor, nothing to check

                    return;
                }

                foreach ($constructor->getParameters() as $param) {
                    if (! $param->isPromoted()) {
                        // Fail — should be promoted
                        expect($param->isPromoted())->toBeTrue(
                            "{$class}::\${$param->getName()} must be a promoted property"
                        );
                    }

                    if ($param->getName() === 'message' && $param->isOptional()) {
                        // message is nullable string in ValidationAttribute — allowed
                        continue;
                    }

                    // Check that the property is readonly
                    $property = $ref->getProperty($param->getName());
                    expect($property->isReadOnly())->toBeTrue(
                        "{$class}::\${$param->getName()} must be readonly"
                    );
                }
            });
        }
    });

    // §7. Contracts are interfaces with proper method signatures
    describe('§7. Contract interfaces are properly typed', function () {
        it('ValidationAttribute::ruleKey() returns string', function () {
            $ref = new ReflectionMethod(ValidationAttribute::class, 'ruleKey');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull('ValidationAttribute::ruleKey() must have a return type');
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('string');
        });

        it('ValidatableDTO::rules() returns array', function () {
            $ref = new ReflectionMethod(ValidatableDTO::class, 'rules');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull('ValidatableDTO::rules() must have a return type');
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('array');
        });

        it('ValidatableDTO::rulesFor() returns array', function () {
            $ref = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull('ValidatableDTO::rulesFor() must have a return type');
            expect($returnType instanceof ReflectionNamedType)->toBeTrue();
            expect($returnType->getName())->toBe('array');
        });

        it('FromRequestDTO::fromRequest() returns static', function () {
            $ref = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
            $returnType = $ref->getReturnType();

            expect($returnType)->not->toBeNull('FromRequestDTO::fromRequest() must have a return type');
            expect($returnType->getName())->toBe('static');
        });
    });

    // §8. DTOManager is readonly (stateless proxy)
    describe('§8. DTOManager is readonly', function () {
        it('DTOManager is a readonly class', function () {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isReadOnly())->toBeTrue('DTOManager must be a readonly class');
            expect($ref->isFinal())->toBeTrue('DTOManager must be final');
        });
    });

    // §9. No loose comparisons in source code
    describe('§9. No loose comparisons (==) in source code', function () {
        it('source files use strict comparisons', function () {
            $srcDir = dirname(__DIR__) . '/src';
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            $violations = [];

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                $tokens = token_get_all($content);
                $tokenCount = count($tokens);

                for ($i = 0; $i < $tokenCount - 1; $i++) {
                    // Match == but NOT ===, !==, !===, <=, >=
                    if (is_array($tokens[$i]) && $tokens[$i][0] === T_IS_EQUAL) {
                        $line = $tokens[$i][2] ?? 'unknown';
                        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file->getPathname());
                        $violations[] = "{$relativePath}:{$line}";
                    }
                }
            }

            expect($violations)->toBeEmpty(
                'Loose == comparison found. Use === instead. Locations: ' . implode(', ', $violations)
            );
        });
    });

    // §10. phpstan.neon.dist configuration
    describe('§10. phpstan.neon.dist configuration', function () {
        it('phpstan.neon.dist exists and targets level 9', function () {
            $path = dirname(__DIR__) . '/phpstan.neon.dist';
            expect(file_exists($path))->toBeTrue('phpstan.neon.dist should exist');

            $content = file_get_contents($path);
            expect($content)->toContain('level(9)');
        });
    });
});
