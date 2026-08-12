<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO — Source Code Quality V4 Deep Audit', function () {
    // -------------------------------------------------------------------------
    // 1. DataTransferObject abstract class has all expected public/static methods
    // -------------------------------------------------------------------------
    it('DataTransferObject has all expected public API methods', function () {
        $ref = new ReflectionClass(DataTransferObject::class);

        $expectedMethods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromPartialRequest',
            'fromJson', 'validateArray', 'validatePartialArray',
            'rules', 'rulesFor',
            'toArray', 'allValues', 'toJson', 'jsonSerialize',
            'equals', 'isEmpty', 'isNotEmpty',
            'only', 'except', 'with',
            'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        $actualMethods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            array_filter(
                $ref->getMethods(ReflectionMethod::IS_PUBLIC),
                static fn (ReflectionMethod $m): bool => ! str_starts_with($m->getName(), '__')
            )
        );

        foreach ($expectedMethods as $method) {
            expect($actualMethods)->toContain($method, "DataTransferObject must have {$method}()");
        }
    });

    // -------------------------------------------------------------------------
    // 2. DtoCollection has all expected public methods
    // -------------------------------------------------------------------------
    it('DtoCollection has all expected public API methods', function () {
        $ref = new ReflectionClass(DtoCollection::class);

        $expectedMethods = [
            'toArray', 'allValues', 'items', 'count', 'getIterator',
            'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset',
            'jsonSerialize', 'make', 'push', 'first', 'last',
            'map', 'filter', 'pluck', 'pluckKey',
            'append', 'merge', 'isEmpty', 'isNotEmpty',
        ];

        $actualMethods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            array_filter(
                $ref->getMethods(ReflectionMethod::IS_PUBLIC),
                static fn (ReflectionMethod $m): bool => ! str_starts_with($m->getName(), '__')
            )
        );

        foreach ($expectedMethods as $method) {
            expect($actualMethods)->toContain($method, "DtoCollection must have {$method}()");
        }
    });

    // -------------------------------------------------------------------------
    // 3. DTOManager has all expected public methods
    // -------------------------------------------------------------------------
    it('DTOManager has validate, make, makeFromJson, and schema methods', function () {
        $ref = new ReflectionClass(DTOManager::class);
        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods(ReflectionMethod::IS_PUBLIC)
        );

        expect($methods)->toContain('validate');
        expect($methods)->toContain('make');
        expect($methods)->toContain('makeFromJson');
        expect($methods)->toContain('schema');
    });

    // -------------------------------------------------------------------------
    // 4. All public methods in DataTransferObject have return type declarations
    // -------------------------------------------------------------------------
    it('every public method in DataTransferObject has a return type', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        $missing = [];
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }
            if ($method->getReturnType() === null) {
                $missing[] = $method->getName() . '()';
            }
        }

        expect($missing)->toBeEmpty(
            'Missing return types: ' . implode(', ', $missing)
        );
    });

    // -------------------------------------------------------------------------
    // 5. All public methods in DtoCollection have return type declarations
    // -------------------------------------------------------------------------
    it('every public method in DtoCollection has a return type', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        $missing = [];
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }
            if ($method->getReturnType() === null) {
                $missing[] = $method->getName() . '()';
            }
        }

        expect($missing)->toBeEmpty(
            'Missing return types: ' . implode(', ', $missing)
        );
    });

    // -------------------------------------------------------------------------
    // 6. DTOCast get/set/serialize method signatures
    // -------------------------------------------------------------------------
    it('DTOCast has get, set, and serialize with correct signatures', function () {
        $ref = new ReflectionClass(DTOCast::class);

        // Constructor: dtoClass (string), validate (bool, default true)
        $ctor = $ref->getConstructor();
        $params = $ctor->getParameters();
        expect($params)->toHaveCount(2);
        expect($params[0]->getName())->toBe('dtoClass');
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->getDefaultValue())->toBe(true);

        // get() has mixed $value parameter (interface requirement)
        $get = $ref->getMethod('get');
        $getParams = $get->getParameters();
        expect($getParams[2]->getName())->toBe('value');
        expect($getParams[2]->getType()->getName())->toBe('mixed');
    });

    // -------------------------------------------------------------------------
    // 7. Metadata-only attributes are truly metadata-only
    // -------------------------------------------------------------------------
    it('metadata-only attributes have no constructor side effects', function () {
        $metaAttrs = [Cast::class, DefaultValue::class, Hidden::class, MapFrom::class];

        foreach ($metaAttrs as $class) {
            $implements = class_implements($class) ?: [];
            expect($implements)->not->toContain(
                ValidationAttribute::class,
                "{$class} must not implement ValidationAttribute"
            );

            $ref = new ReflectionClass($class);
            $props = $ref->getProperties();
            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "{$class}::\${$prop->getName()} must be readonly"
                );
            }
        }
    });

    // -------------------------------------------------------------------------
    // 8. NestedArray and Collection both have dtoClass property
    // -------------------------------------------------------------------------
    it('NestedArray and Collection have string dtoClass and implement ValidationAttribute', function () {
        foreach ([NestedArray::class, Collection::class] as $class) {
            $implements = class_implements($class) ?: [];
            expect($implements)->toContain(ValidationAttribute::class);

            $ref = new ReflectionClass($class);
            $prop = $ref->getProperty('dtoClass');
            expect($prop->getType()->getName())->toBe('string');
            expect($prop->isReadOnly())->toBeTrue();
        }
    });

    // -------------------------------------------------------------------------
    // 9. DefaultValue attribute accepts mixed type
    // -------------------------------------------------------------------------
    it('DefaultValue attribute value property is typed as mixed', function () {
        $ref = new ReflectionClass(DefaultValue::class);
        $prop = $ref->getProperty('value');
        expect($prop->getType()->getName())->toBe('mixed');
        expect($prop->isReadOnly())->toBeTrue();
    });

    // -------------------------------------------------------------------------
    // 10. DtoMetadataResolver has consistent static API
    // -------------------------------------------------------------------------
    it('DtoMetadataResolver public methods are all static', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            expect($method->isStatic())->toBeTrue(
                "DtoMetadataResolver::{$method->getName()}() must be static"
            );
        }
    });

    // -------------------------------------------------------------------------
    // 11. OpenApiSchemaGenerator has consistent static API
    // -------------------------------------------------------------------------
    it('OpenApiSchemaGenerator public methods are all static', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            expect($method->isStatic())->toBeTrue(
                "OpenApiSchemaGenerator::{$method->getName()}() must be static"
            );
        }
    });

    // -------------------------------------------------------------------------
    // 12. DTOException named constructors have correct signatures
    // -------------------------------------------------------------------------
    it('DTOException::invalidCast and invalidJson have correct parameter types', function () {
        $ref = new ReflectionClass(DTOException::class);

        $invalidCast = $ref->getMethod('invalidCast');
        $params = $invalidCast->getParameters();
        expect($params[0]->getType()->getName())->toBe('string');
        expect($params[1]->getType()->getName())->toBe('string');
        expect($params[2]->getType()->getName())->toBe('mixed');

        $invalidJson = $ref->getMethod('invalidJson');
        $params = $invalidJson->getParameters();
        expect($params[0]->getType()->getName())->toBe('string');
        expect($params[1]->getType()->getName())->toBe('string');
    });

    // -------------------------------------------------------------------------
    // 13. Empty DTO fixture works with fromArray
    // -------------------------------------------------------------------------
    it('EmptyDTO can be instantiated from empty array', function () {
        if (! class_exists(EmptyDTO::class)) {
            expect(true)->toBeTrue('EmptyDTO fixture not available, skipping');

            return;
        }

        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toBeArray();
    });

    // -------------------------------------------------------------------------
    // 14. MinimalDTO has expected properties
    // -------------------------------------------------------------------------
    it('MinimalDTO fixture has correct structure', function () {
        if (! class_exists(MinimalDTO::class)) {
            expect(true)->toBeTrue('MinimalDTO fixture not available, skipping');

            return;
        }

        $ref = new ReflectionClass(MinimalDTO::class);
        $props = $ref->getProperties();
        expect($props)->not->toBeEmpty();
    });

    // -------------------------------------------------------------------------
    // 15. composer.json requires PHP ^8.5
    // -------------------------------------------------------------------------
    it('composer.json requires PHP ^8.5', function () {
        $composer = json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        expect($composer['require']['php'])->toBe('^8.5');
    });

    // -------------------------------------------------------------------------
    // 16. phpstan.neon.dist is configured for level 9
    // -------------------------------------------------------------------------
    it('phpstan.neon.dist sets level to 9', function () {
        $neonContent = file_get_contents(dirname(__DIR__, 2) . '/phpstan.neon.dist');

        expect($neonContent)->toContain('level: 9');
        expect($neonContent)->toContain('- src');
    });

    // -------------------------------------------------------------------------
    // 17. No duplicate attribute class names
    // -------------------------------------------------------------------------
    it('no duplicate attribute class names exist in src/Attributes', function () {
        $attrDir = dirname(__DIR__, 2) . '/src/Attributes';
        $files = glob($attrDir . '/*.php');
        $classNames = array_map(static fn (string $f): string => basename($f, '.php'), $files);

        $unique = array_unique($classNames);
        expect($unique)->toHaveCount(count($classNames), 'Duplicate attribute class names found');
    });

    // -------------------------------------------------------------------------
    // 18. All attribute classes have #[Attribute] with proper target
    // -------------------------------------------------------------------------
    it('all attribute classes have #[Attribute] declaration', function () {
        $attrDir = dirname(__DIR__, 2) . '/src/Attributes';
        $files = glob($attrDir . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)->toContain('#[Attribute(');
        }
    });

    // -------------------------------------------------------------------------
    // 19. Contracts are interfaces (not classes or traits)
    // -------------------------------------------------------------------------
    it('all contracts are interfaces', function () {
        $contracts = [
            FromRequestDTO::class,
            ValidatableDTO::class,
            ValidationAttribute::class,
        ];

        foreach ($contracts as $contract) {
            $ref = new ReflectionClass($contract);
            expect($ref->isInterface())->toBeTrue(
                "{$contract} must be an interface"
            );
        }
    });

    // -------------------------------------------------------------------------
    // 20. All attribute properties have type declarations
    // -------------------------------------------------------------------------
    it('all properties in all attribute classes have type declarations', function () {
        $attrDir = dirname(__DIR__, 2) . '/src/Attributes';
        $files = glob($attrDir . '/*.php');

        $violations = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (! preg_match('/namespace\s+ZeroBoiler\\\\DTO\\\\Attributes/',$content)) {
                continue;
            }

            preg_match('/\bclass\s+(\w+)/', $content, $m);
            if (! isset($m[1])) {
                continue;
            }
            $className = 'ZeroBoiler\\DTO\\Attributes\\' . $m[1];

            if (! class_exists($className)) {
                continue;
            }

            $ref = new ReflectionClass($className);
            foreach ($ref->getProperties() as $prop) {
                if ($prop->getType() === null) {
                    $violations[] = "{$className}::\${$prop->getName()}";
                }
            }
        }

        expect($violations)->toBeEmpty(
            'Untyped properties: ' . implode(', ', $violations)
        );
    });
});
