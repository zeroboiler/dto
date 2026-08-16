<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, Collection, DefaultValue, Email, Hidden, MapFrom, Max, Min, NestedArray, Required};
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\{FromRequestDTO, ValidationAttribute, ValidatableDTO};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\{DtoMetadataResolver, OpenApiSchemaGenerator};

describe('V27 — DTO source code structural integrity audit', function () {
    // -----------------------------------------------------------------------
    // 1. Source file structure
    // -----------------------------------------------------------------------
    it('all 55 source files exist in src/', function () {
        $srcDir = realpath(__DIR__.'/../src');
        expect($srcDir)->not->toBeFalse();

        $files = glob($srcDir.'/**/*.php');
        expect($files)->not->toBeEmpty();
        expect(count($files))->toBeGreaterThanOrEqual(55);
    });

    it('all source files have declare(strict_types=1)', function () {
        $srcDir = realpath(__DIR__.'/../src');
        $violations = [];

        foreach (glob($srcDir.'/**/*.php') as $file) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);

            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_DECLARE) {
                    $rest = implode('', array_slice($tokens, array_search($token, $tokens, true)));
                    if (! str_contains($rest, 'strict_types') || ! str_contains($rest, '1')) {
                        $violations[] = basename($file);
                    }
                    break;
                }
            }
        }

        expect($violations)->toBeEmpty('Files missing declare(strict_types=1): '.implode(', ', $violations));
    });

    it('all source files end with a newline', function () {
        $srcDir = realpath(__DIR__.'/../src');
        $violations = [];

        foreach (glob($srcDir.'/**/*.php') as $file) {
            $content = file_get_contents($file);
            if ($content !== '' && ! str_ends_with($content, "\n")) {
                $violations[] = basename($file);
            }
        }

        expect($violations)->toBeEmpty('Files not ending with newline: '.implode(', ', $violations));
    });

    // -----------------------------------------------------------------------
    // 2. Class and interface structure
    // -----------------------------------------------------------------------
    it('DataTransferObject is abstract and implements expected interfaces', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
        expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DtoCollection is final and implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DTOManager is final readonly', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTOException is final with named constructors', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();

        $expected = ['invalidCast', 'invalidJson'];
        $actual = array_map(
            fn (ReflectionMethod $m) => $m->getName(),
            $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC)
        );

        foreach ($expected as $method) {
            expect(in_array($method, $actual, true))->toBeTrue("Missing static method: {$method}");
        }
    });

    it('DTOCast is final', function () {
        $ref = new ReflectionClass(DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider is final', function () {
        $ref = new ReflectionClass(DTOSServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 3. Return type declarations
    // -----------------------------------------------------------------------
    it('DataTransferObject abstract public methods have return types', function () {
        $ref = new ReflectionClass(DataTransferObject::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if ($method->isStatic() && in_array($name, ['setMetadataCacheTtl', 'flushMetadataCache'], true)) {
                // Static utility methods — still check return type
            }
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("DataTransferObject::{$name}() missing return type");
        }
    });

    it('DtoCollection all public methods have return types', function () {
        $ref = new ReflectionClass(DtoCollection::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($name === '__clone') {
                $returnType = $method->getReturnType();
                expect($returnType)->not->toBeNull();
                expect((string) $returnType)->toBe('never');
                continue;
            }

            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("DtoCollection::{$name}() missing return type");
        }
    });

    it('DTOManager all public methods have return types', function () {
        $ref = new ReflectionClass(DTOManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("DTOManager::{$name}() missing return type");
        }
    });

    // -----------------------------------------------------------------------
    // 4. Attribute classes
    // -----------------------------------------------------------------------
    it('all validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            Required::class,
            Email::class,
            Max::class,
            Min::class,
            NestedArray::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Between::class,
            \ZeroBoiler\DTO\Attributes\Date::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue(
                "{$class} must implement ValidationAttribute"
            );
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
            expect($ref->getMethod('ruleKey')->getReturnType())->not->toBeNull(
                "{$class}::ruleKey() must have return type"
            );
        }
    });

    it('metadata-only attributes are final', function () {
        $metaAttributes = [
            MapFrom::class,
            Hidden::class,
            Cast::class,
            DefaultValue::class,
            Collection::class,
        ];

        foreach ($metaAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('Hidden attribute has no constructor parameters', function () {
        $ref = new ReflectionClass(Hidden::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->toBeNull('Hidden should have no constructor');
    });

    // -----------------------------------------------------------------------
    // 5. Docblock quality
    // -----------------------------------------------------------------------
    it('DtoMetadataResolver is final and @internal tagged', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
        $doc = $ref->getDocComment();
        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@internal');
    });

    it('OpenApiSchemaGenerator is final and @internal tagged', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
        $doc = $ref->getDocComment();
        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@internal');
    });

    it('DataTransferObject has comprehensive class-level docblock', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        $doc = $ref->getDocComment();
        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@implements');
        expect($doc)->toContain('@phpstan-consistent-constructor');
    });

    // -----------------------------------------------------------------------
    // 6. #[\Override] compliance
    // -----------------------------------------------------------------------
    it('DTOCast get, set have #[Override]', function () {
        $ref = new ReflectionClass(DTOCast::class);

        foreach (['get', 'set'] as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("DTOCast::{$method}() missing #[Override]");
        }
    });

    it('DtoCollection interface implementations have #[Override]', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $overrideMethods = [
            'count', 'getIterator', 'offsetExists', 'offsetGet', 'offsetSet',
            'offsetUnset', 'jsonSerialize',
        ];

        foreach ($overrideMethods as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("DtoCollection::{$method}() missing #[Override]");
        }
    });

    it('DataTransferObject interface implementations have #[Override]', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        $overrideMethods = ['fromRequest', 'rules', 'rulesFor', 'toArray', 'jsonSerialize'];

        foreach ($overrideMethods as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("DataTransferObject::{$method}() missing #[Override]");
        }
    });

    it('DTOSServiceProvider register and boot have #[Override]', function () {
        $ref = new ReflectionClass(DTOSServiceProvider::class);
        foreach (['register', 'boot'] as $method) {
            $m = $ref->getMethod($method);
            $attrs = $m->getAttributes(\Override::class);
            expect($attrs)->not->toBeEmpty("DTOSServiceProvider::{$method}() missing #[Override]");
        }
    });

    it('DTO facade getFacadeAccessor has #[Override]', function () {
        $m = new ReflectionMethod(\ZeroBoiler\DTO\Facades\DTO::class, 'getFacadeAccessor');
        $attrs = $m->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty('DTO facade::getFacadeAccessor() missing #[Override]');
    });

    it('DTOException __toString has #[Override]', function () {
        $m = new ReflectionMethod(DTOException::class, '__toString');
        $attrs = $m->getAttributes(\Override::class);
        expect($attrs)->not->toBeEmpty('DTOException::__toString() missing #[Override]');
    });

    // -----------------------------------------------------------------------
    // 7. Type safety — no mixed return types in public API of DTO/Manager
    // -----------------------------------------------------------------------
    it('DataTransferObject public methods do not return mixed (excluding private/protected helpers)', function () {
        $ref = new ReflectionClass(DataTransferObject::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() && str_starts_with($method->getName(), '_zb')) {
                continue; // Internal static cache methods
            }
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $typeString = (string) $returnType;
                // toArray, allValues, only, except, etc. return array<string, mixed> which is fine
                // with() returns static which is fine
                // We only flag methods that return bare 'mixed'
                if ($typeString === 'mixed') {
                    expect(true)->toBeFalse(
                        "DataTransferObject::{$method->getName()}() returns bare mixed — violates PHPStan L9"
                    );
                }
            }
        }
    });

    it('DTOManager all public methods have non-mixed return types', function () {
        $ref = new ReflectionClass(DTOManager::class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if ($returnType !== null) {
                $typeString = (string) $returnType;
                expect($typeString)->not->toBe('mixed',
                    "DTOManager::{$method->getName()}() returns mixed — violates PHPStan L9");
            }
        }
    });

    // -----------------------------------------------------------------------
    // 8. Console commands
    // -----------------------------------------------------------------------
    it('MakeDtoTestCommand is final with handle() returning int', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand::class);
        expect($ref->isFinal())->toBeTrue();

        $handle = $ref->getMethod('handle');
        expect($handle->getReturnType())->not->toBeNull();
        expect((string) $handle->getReturnType())->toBe('int');
    });

    it('MakeDtoSchemaCommand is final with handle() returning int', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand::class);
        expect($ref->isFinal())->toBeTrue();

        $handle = $ref->getMethod('handle');
        expect($handle->getReturnType())->not->toBeNull();
        expect((string) $handle->getReturnType())->toBe('int');
    });

    // -----------------------------------------------------------------------
    // 9. composer.json consistency
    // -----------------------------------------------------------------------
    it('composer.json requires PHP ^8.5', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['require']['php'])->toBe('^8.5');
    });

    it('composer.json requires illuminate/contracts ^13.0', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['require']['illuminate/contracts'])->toBe('^13.0');
    });

    it('composer.json requires zeroboiler/value-objects ^1.0', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['require']['zeroboiler/value-objects'])->toBe('^1.0');
    });

    it('composer.json autoload matches namespace', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['autoload']['psr-4']['ZeroBoiler\\DTO\\'])->toBe('src/');
    });

    it('composer.json version matches latest release', function () {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
        expect($json['version'])->toBe('1.1.33');
    });

    // -----------------------------------------------------------------------
    // 10. Contracts completeness
    // -----------------------------------------------------------------------
    it('FromRequestDTO contract has fromRequest with correct signature', function () {
        $ref = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        expect($ref->isPublic())->toBeTrue();
        expect($ref->isStatic())->toBeTrue();

        $params = $ref->getParameters();
        expect(count($params))->toBe(2); // $request, $validate
        expect($params[0]->getType()?->getName())->toBe(\Illuminate\Http\Request::class);
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->isDefaultValueAvailable())->toBeTrue();

        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
    });

    it('ValidatableDTO contract has rules and rulesFor with correct signatures', function () {
        $rules = new ReflectionMethod(ValidatableDTO::class, 'rules');
        expect($rules->isPublic())->toBeTrue();
        expect($rules->isStatic())->toBeTrue();
        expect($rules->getReturnType())->not->toBeNull();

        $rulesFor = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
        expect($rulesFor->isPublic())->toBeTrue();
        expect($rulesFor->isStatic())->toBeTrue();
        expect($rulesFor->getReturnType())->not->toBeNull();

        $params = $rulesFor->getParameters();
        expect(count($params))->toBe(1);
        expect($params[0]->getName())->toBe('action');
    });

    it('ValidationAttribute contract has ruleKey method', function () {
        $ref = new ReflectionMethod(ValidationAttribute::class, 'ruleKey');
        expect($ref->isPublic())->toBeTrue();
        expect($ref->getReturnType())->not->toBeNull();
        expect((string) $ref->getReturnType())->toBe('string');
    });
});
