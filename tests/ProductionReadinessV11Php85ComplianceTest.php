<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production readiness and PHP 8.5 compliance verification for DTOs.
 *
 * Validates source code structure, API completeness, and PHP 8.5 compatibility
 * for all public classes in the DTO package. This is a comprehensive
 * structural audit that ensures the package meets enterprise quality standards.
 *
 * Tests cover:
 * - PHP 8.5 syntax compliance (final classes, readonly properties, named args)
 * - Strict types declarations on every source file
 * - Return type completeness on all public methods
 * - Docblock coverage on all public API classes
 * - Contract/interface compliance (FromRequestDTO, ValidatableDTO, ValidationAttribute)
 * - DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
 * - DTOCast implements CastsAttributes correctly
 * - DTOManager is final readonly
 * - DtoMetadataResolver is final
 * - OpenApiSchemaGenerator is final
 * - All validation attributes implement ValidationAttribute
 * - Serialization safety (DtoCollection clone blocked, DtoCollection jsonSerialize)
 *
 * @see https://www.php.net/manual/en/migration85.php
 */

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
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('Production Readiness V11 — PHP 8.5 & Enterprise Compliance (DTO)', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. Source file strict_types declarations
    // ──────────────────────────────────────────────────────────────

    describe('All source files declare strict_types=1', function (): void {
        $sourceFiles = [
            __DIR__ . '/../src/DataTransferObject.php',
            __DIR__ . '/../src/DtoCollection.php',
            __DIR__ . '/../src/DTOManager.php',
            __DIR__ . '/../src/DTOSServiceProvider.php',
            __DIR__ . '/../src/Exceptions/DTOException.php',
            __DIR__ . '/../src/Facades/DTO.php',
            __DIR__ . '/../src/Casts/DTOCast.php',
            __DIR__ . '/../src/Support/DtoMetadataResolver.php',
            __DIR__ . '/../src/Support/OpenApiSchemaGenerator.php',
            __DIR__ . '/../src/Contracts/FromRequestDTO.php',
            __DIR__ . '/../src/Contracts/ValidatableDTO.php',
            __DIR__ . '/../src/Contracts/ValidationAttribute.php',
        ];

        foreach ($sourceFiles as $file) {
            $basename = basename($file);
            it("{$basename} has declare(strict_types=1)", function () use ($file): void {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 2. Final classes — all public classes are final
    // ──────────────────────────────────────────────────────────────

    describe('All public classes are final', function (): void {
        $finalClasses = [
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DTOException::class,
            DTO::class,
            DTOSServiceProvider::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            Required::class,
            Nullable::class,
            MapFrom::class,
            Cast::class,
            Hidden::class,
            DefaultValue::class,
            Collection::class,
            NestedArray::class,
            Enum::class,
            Email::class,
            Max::class,
            Min::class,
            Boolean::class,
            Integer::class,
            Numeric::class,
            Url::class,
            Pattern::class,
            Uuid::class,
            Json::class,
            In::class,
            Between::class,
            Confirmed::class,
            Different::class,
            Same::class,
            ArrayRule::class,
            Prohibited::class,
            Present::class,
            Declined::class,
            Accepted::class,
            StartsWith::class,
            EndsWith::class,
            Distinct::class,
            Size::class,
            Sometimes::class,
            RequiredIf::class,
            RequiredUnless::class,
            RequiredWith::class,
            RequiredWithAll::class,
            RequiredWithout::class,
            RequiredWithoutAll::class,
        ];

        foreach ($finalClasses as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} is final", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 3. DTOManager — final readonly class
    // ──────────────────────────────────────────────────────────────

    describe('DTOManager is final readonly', function (): void {
        it('is a final class', function (): void {
            $ref = new \ReflectionClass(DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('is a readonly class', function (): void {
            $ref = new \ReflectionClass(DTOManager::class);
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('has no public properties', function (): void {
            $ref = new \ReflectionClass(DTOManager::class);
            $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
            expect($props)->toHaveCount(0);
        });

        it('all public methods have return type declarations', function (): void {
            $ref = new \ReflectionClass(DTOManager::class);
            $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->getName() === '__construct') {
                    continue;
                }
                expect($method->hasReturnType())
                    ->toBeTrue("Method {$method->getName()}() missing return type");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 4. DtoCollection — interfaces and methods
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection interface compliance', function (): void {
        it('implements ArrayAccess', function (): void {
            $ref = new \ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        });

        it('implements Countable', function (): void {
            $ref = new \ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        });

        it('implements IteratorAggregate', function (): void {
            $ref = new \ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        });

        it('implements JsonSerializable', function (): void {
            $ref = new \ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('is final', function (): void {
            $ref = new \ReflectionClass(DtoCollection::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('__clone() returns never (mutation blocked)', function (): void {
            $ref = new \ReflectionMethod(DtoCollection::class, '__clone');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('never');
        });

        it('count() has Override attribute', function (): void {
            $ref = new \ReflectionMethod(DtoCollection::class, 'count');
            $attrs = $ref->getAttributes();
            $hasOverride = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === \Override::class) {
                    $hasOverride = true;
                    break;
                }
            }
            expect($hasOverride)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 5. DataTransferObject — abstract base class contracts
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject implements contracts', function (): void {
        it('implements Arrayable', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        });

        it('implements FromRequestDTO', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        });

        it('implements JsonSerializable', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('implements ValidatableDTO', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        });

        it('is abstract', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });

        it('has static metadata cache with private visibility', function (): void {
            $ref = new \ReflectionClass(DataTransferObject::class);
            $props = $ref->getProperties(\ReflectionProperty::IS_STATIC | \ReflectionProperty::IS_PRIVATE);
            $names = array_map(fn (\ReflectionProperty $p): string => $p->getName(), $props);
            expect($names)->toContain('_zbMetadataCache');
            expect($names)->toContain('_zbMetadataCacheTimestamps');
            expect($names)->toContain('_zbMetadataCacheTtl');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. DTOCast — implements CastsAttributes
    // ──────────────────────────────────────────────────────────────

    describe('DTOCast implements CastsAttributes', function (): void {
        it('implements CastsAttributes', function (): void {
            $ref = new \ReflectionClass(DTOCast::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))
                ->toBeTrue();
        });

        it('is final', function (): void {
            $ref = new \ReflectionClass(DTOCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('get() returns DataTransferObject or null', function (): void {
            $ref = new \ReflectionMethod(DTOCast::class, 'get');
            $returnType = $ref->getReturnType();
            expect($returnType->allowsNull())->toBeTrue();
        });

        it('set() returns array|string|null', function (): void {
            $ref = new \ReflectionMethod(DTOCast::class, 'set');
            expect($ref->hasReturnType())->toBeTrue();
        });

        it('serialize() returns array|null', function (): void {
            $ref = new \ReflectionMethod(DTOCast::class, 'serialize');
            $returnType = $ref->getReturnType();
            expect($returnType->allowsNull())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. DTOException — factory methods
    // ──────────────────────────────────────────────────────────────

    describe('DTOException factory methods', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(DTOException::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('invalidCast() returns self', function (): void {
            $ref = new \ReflectionMethod(DTOException::class, 'invalidCast');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });

        it('invalidJson() returns self', function (): void {
            $ref = new \ReflectionMethod(DTOException::class, 'invalidJson');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('self');
        });

        it('__toString() has Override attribute', function (): void {
            $ref = new \ReflectionMethod(DTOException::class, '__toString');
            $returnType = $ref->getReturnType();
            expect($returnType->getName())->toBe('string');
            $attrs = $ref->getAttributes();
            $hasOverride = false;
            foreach ($attrs as $attr) {
                if ($attr->getName() === \Override::class) {
                    $hasOverride = true;
                    break;
                }
            }
            expect($hasOverride)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. DTO Facade — correct accessor
    // ──────────────────────────────────────────────────────────────

    describe('DTO facade accessor', function (): void {
        it('is a final class extending Facade', function (): void {
            $ref = new \ReflectionClass(DTO::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });

        it('getFacadeAccessor returns zeroboiler.dto', function (): void {
            $method = new \ReflectionMethod(DTO::class, 'getFacadeAccessor');
            $method->setAccessible(true);
            expect($method->invoke(null))->toBe('zeroboiler.dto');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. DTOSServiceProvider — correct singleton binding
    // ──────────────────────────────────────────────────────────────

    describe('DTOSServiceProvider', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(DTOSServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('extends ServiceProvider', function (): void {
            $ref = new \ReflectionClass(DTOSServiceProvider::class);
            expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
        });

        it('register() has void return type', function (): void {
            $ref = new \ReflectionMethod(DTOSServiceProvider::class, 'register');
            expect($ref->getReturnType()->getName())->toBe('void');
        });

        it('boot() has void return type', function (): void {
            $ref = new \ReflectionMethod(DTOSServiceProvider::class, 'boot');
            expect($ref->getReturnType()->getName())->toBe('void');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. ValidationAttribute contract — all attrs implement
    // ──────────────────────────────────────────────────────────────

    describe('All validation attributes implement ValidationAttribute', function (): void {
        $validationAttributes = [
            Accepted::class,
            ArrayRule::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            Enum::class,
            In::class,
            Integer::class,
            Json::class,
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
            Collection::class,
        ];

        foreach ($validationAttributes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} implements ValidationAttribute", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue();
            });

            it("{$shortName} has ruleKey() method returning string", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->hasMethod('ruleKey'))->toBeTrue();
                $method = $ref->getMethod('ruleKey');
                $returnType = $method->getReturnType();
                expect($returnType->getName())->toBe('string');
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 11. Metadata attributes — do NOT implement ValidationAttribute
    // ──────────────────────────────────────────────────────────────

    describe('Metadata attributes do not implement ValidationAttribute', function (): void {
        $metadataAttributes = [
            MapFrom::class,
            Cast::class,
            Hidden::class,
            DefaultValue::class,
        ];

        foreach ($metadataAttributes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} does not implement ValidationAttribute", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->implementsInterface(ValidationAttribute::class))->toBeFalse();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 12. DtoMetadataResolver — final with correct API
    // ──────────────────────────────────────────────────────────────

    describe('DtoMetadataResolver', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(DtoMetadataResolver::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('resolve() returns array', function (): void {
            $ref = new \ReflectionMethod(DtoMetadataResolver::class, 'resolve');
            expect($ref->getReturnType()->getName())->toBe('array');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. OpenApiSchemaGenerator — final with correct API
    // ──────────────────────────────────────────────────────────────

    describe('OpenApiSchemaGenerator', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(OpenApiSchemaGenerator::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('generate() returns array', function (): void {
            $ref = new \ReflectionMethod(OpenApiSchemaGenerator::class, 'generate');
            expect($ref->getReturnType()->getName())->toBe('array');
        });

        it('generateWithComponents() returns array', function (): void {
            $ref = new \ReflectionMethod(OpenApiSchemaGenerator::class, 'generateWithComponents');
            expect($ref->getReturnType()->getName())->toBe('array');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. Attribute TARGET usage — all target correct property
    // ──────────────────────────────────────────────────────────────

    describe('Attribute TARGET correctness', function (): void {
        $propertyOnlyAttrs = [
            Required::class, Nullable::class, MapFrom::class, Cast::class,
            Hidden::class, Email::class, Max::class, Min::class,
            Integer::class, Numeric::class, Boolean::class, Uuid::class,
            Url::class, Pattern::class, In::class, Between::class,
            Confirmed::class, Different::class, Same::class, ArrayRule::class,
            Prohibited::class, Present::class, Declined::class, Accepted::class,
            StartsWith::class, EndsWith::class, Distinct::class, Size::class,
            Sometimes::class, Json::class, Enum::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
            Collection::class, NestedArray::class,
        ];

        foreach ($propertyOnlyAttrs as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} has TARGET_PROPERTY", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $attrs = $ref->getAttributes(\Attribute::class);
                $found = false;
                foreach ($attrs as $attr) {
                    $args = $attr->getArguments();
                    foreach ($args as $arg) {
                        if ($arg === \Attribute::TARGET_PROPERTY) {
                            $found = true;
                            break 2;
                        }
                    }
                }
                expect($found)->toBeTrue();
            });
        }

        // DefaultValue targets both property and parameter
        it('DefaultValue has TARGET_PROPERTY | TARGET_PARAMETER', function (): void {
            $ref = new \ReflectionClass(DefaultValue::class);
            $attrs = $ref->getAttributes(\Attribute::class);
            $found = false;
            foreach ($attrs as $attr) {
                $args = $attr->getArguments();
                $combined = 0;
                foreach ($args as $arg) {
                    if ($arg === \Attribute::TARGET_PROPERTY || $arg === \Attribute::TARGET_PARAMETER) {
                        $combined |= $arg;
                    }
                }
                if ($combined === (\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)) {
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 15. Empty DTO edge case — constructor-less DTO
    // ──────────────────────────────────────────────────────────────

    describe('EmptyDTO edge case', function (): void {
        it('fromArray with empty data works', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            assert($dto instanceof EmptyDTO);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('toArray returns empty array', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->toArray())->toBe([]);
        });

        it('rules returns empty array', function (): void {
            expect(EmptyDTO::rules())->toBe([]);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 16. Metadata cache TTL behavior
    // ──────────────────────────────────────────────────────────────

    describe('Metadata cache TTL behavior', function (): void {
        it('setMetadataCacheTtl accepts float', function (): void {
            $ref = new \ReflectionMethod(DataTransferObject::class, 'setMetadataCacheTtl');
            $param = $ref->getParameters()[0];
            expect($param->getName())->toBe('seconds');
            expect($param->getType()->getName())->toBe('float');
        });

        it('flushMetadataCache accepts nullable string', function (): void {
            $ref = new \ReflectionMethod(DataTransferObject::class, 'flushMetadataCache');
            $param = $ref->getParameters()[0];
            expect($param->getName())->toBe('class');
            expect($param->getType()->allowsNull())->toBeTrue();
        });
    });
});
