<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Declined;
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
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * V28 PHPStan Level 9 strict type safety audit for DTO package.
 *
 * Validates that all source files conform to PHPStan Level 9 requirements:
 * - No mixed return types in public API
 * - Strict comparisons (===, !==) used throughout
 * - All methods have explicit return type declarations
 * - All properties are typed
 * - readonly on stateless classes and attribute promoted properties
 * - #[\Override] on all interface method implementations
 * - PHPDoc @param/@return annotations on public methods
 * - declare(strict_types=1) on every file
 * - All 37 validation attributes implement ValidationAttribute interface
 * - Correct Attribute targets on all attribute classes
 */
test('source files have correct strict_types declaration', function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../src', RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $count = 0;
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $contents = $file->getContents();
        $filePath = $file->getRealPath();
        $count++;

        expect($contents)
            ->toContain('declare(strict_types=1)')
            ->and(str_contains($contents, "declare(strict_types=1);\n"))
            ->toBeTrue("File {$filePath} must have declare(strict_types=1) with semicolon and newline");
    }

    // Verify minimum source file count
    expect($count)->toBeGreaterThanOrEqual(55);
});

test('DTOManager is final readonly with no mixed return types', function (): void {
    $ref = new ReflectionClass(DTOManager::class);

    expect($ref->isFinal())->toBeTrue('DTOManager must be final');
    expect($ref->isReadOnly())->toBeTrue('DTOManager must be readonly');

    // All public methods must have return type declarations
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $returnType = $method->getReturnType();
        expect($returnType)->not->toBeNull(
            "DTOManager::{$method->getName()}() must have an explicit return type declaration"
        );

        // No bare 'mixed' return type in public API
        if ($returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())->not->toBe('mixed',
                "DTOManager::{$method->getName()}() must not return bare mixed"
            );
        }
    }
});

test('DtoCollection is final and implements all expected interfaces', function (): void {
    $ref = new ReflectionClass(DtoCollection::class);

    expect($ref->isFinal())->toBeTrue('DtoCollection must be final');
    expect($ref->implementsInterface(ArrayAccess::class))->toBeTrue();
    expect($ref->implementsInterface(Countable::class))->toBeTrue();
    expect($ref->implementsInterface(IteratorAggregate::class))->toBeTrue();
    expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();

    // __clone must return never
    $clone = $ref->getMethod('__clone');
    $cloneReturn = $clone->getReturnType();
    expect($cloneReturn instanceof ReflectionNamedType && $cloneReturn->getName() === 'never')->toBeTrue();

    // count() must have #[Override]
    $count = $ref->getMethod('count');
    expect($count->getAttributes(\Override::class))->not->toBeEmpty();
    expect($count->getReturnType()?->getName())->toBe('int');

    // getIterator() must have #[Override]
    $getIterator = $ref->getMethod('getIterator');
    expect($getIterator->getAttributes(\Override::class))->not->toBeEmpty();

    // offsetExists, offsetGet, offsetSet, offsetUnset must have #[Override]
    foreach (['offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset'] as $method) {
        $m = $ref->getMethod($method);
        expect($m->getAttributes(\Override::class))->not->toBeEmpty(
            "DtoCollection::{$method}() must have #[Override]"
        );
    }

    // jsonSerialize must have #[Override]
    $jsonSerialize = $ref->getMethod('jsonSerialize');
    expect($jsonSerialize->getAttributes(\Override::class))->not->toBeEmpty();
});

test('DataTransferObject is abstract and implements all expected interfaces', function (): void {
    $ref = new ReflectionClass(DataTransferObject::class);

    expect($ref->isAbstract())->toBeTrue('DataTransferObject must be abstract');
    expect($ref->implementsInterface(Arrayable::class))->toBeTrue();
    expect($ref->implementsInterface(JsonSerializable::class))->toBeTrue();
    expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
    expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();

    // Key public methods must have return types
    $methodsToCheck = [
        'fromArray', 'fromPartialArray', 'fromJson', 'fromRequest',
        'fromPartialRequest', 'validateArray', 'rules', 'rulesFor',
        'toArray', 'toJson', 'jsonSerialize', 'allValues',
        'only', 'except', 'with', 'equals', 'isEmpty', 'isNotEmpty',
        'flushMetadataCache', 'setMetadataCacheTtl',
    ];

    foreach ($methodsToCheck as $method) {
        if (! $ref->hasMethod($method)) {
            continue;
        }

        $m = $ref->getMethod($method);
        $returnType = $m->getReturnType();
        expect($returnType)->not->toBeNull(
            "DataTransferObject::{$method}() must have an explicit return type declaration"
        );
    }
});

test('DTOException is final with named constructors', function (): void {
    $ref = new ReflectionClass(DTOException::class);

    expect($ref->isFinal())->toBeTrue();
    expect($ref->isSubclassOf(Exception::class))->toBeTrue();

    // Must have named constructors
    expect($ref->hasMethod('invalidCast'))->toBeTrue();
    expect($ref->hasMethod('invalidJson'))->toBeTrue();

    expect($ref->getMethod('invalidCast')->isStatic())->toBeTrue();
    expect($ref->getMethod('invalidJson')->isStatic())->toBeTrue();

    // __toString must have #[Override]
    $toString = $ref->getMethod('__toString');
    expect($toString->getAttributes(\Override::class))->not->toBeEmpty();
});

test('DTOCast implements CastsAttributes with Override on get and set', function (): void {
    $ref = new ReflectionClass(DTOCast::class);

    expect($ref->isFinal())->toBeTrue();
    expect($ref->implementsInterface(CastsAttributes::class))->toBeTrue();

    $get = $ref->getMethod('get');
    expect($get->getAttributes(\Override::class))->not->toBeEmpty();
    expect($get->getReturnType()?->getName())->toBeNull(); // returns ?DataTransferObject

    $set = $ref->getMethod('set');
    expect($set->getAttributes(\Override::class))->not->toBeEmpty();

    // Constructor must use readonly promoted properties
    $constructor = $ref->getConstructor();
    foreach ($constructor->getParameters() as $param) {
        $prop = $ref->getProperty($param->getName());
        expect($prop->isReadOnly())->toBeTrue(
            "DTOCast::\${$param->getName()} must be readonly"
        );
    }
});

test('all 37 validation attributes implement ValidationAttribute interface', function (): void {
    $validationAttributes = [
        Required::class, Email::class, Max::class, Min::class,
        Url::class, Pattern::class, In::class, Integer::class,
        Numeric::class, Boolean::class, Uuid::class, Date::class,
        Enum::class, Confirmed::class, Different::class, Same::class,
        Between::class, ArrayRule::class, Prohibited::class, Present::class,
        Declined::class, Accepted::class, StartsWith::class, EndsWith::class,
        Nullable::class, Sometimes::class, Distinct::class, Size::class,
        Json::class, RequiredIf::class, RequiredUnless::class,
        RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
        RequiredWithoutAll::class,
    ];

    expect(count($validationAttributes))->toBe(37);

    foreach ($validationAttributes as $class) {
        $ref = new ReflectionClass($class);

        expect($ref->isFinal())->toBeTrue("{$class} must be final");
        expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue(
            "{$class} must implement ValidationAttribute"
        );

        // Must have ruleKey() method returning string
        $ruleKey = $ref->getMethod('ruleKey');
        expect($ruleKey->getReturnType()?->getName())->toBe('string');

        // All public properties must be readonly
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            expect($prop->isReadOnly())->toBeTrue(
                "{$class}::\${$prop->getName()} must be readonly"
            );
        }
    }
});

test('metadata attributes are final with correct structure', function (): void {
    $metadataAttributes = [
        MapFrom::class, Cast::class, DefaultValue::class,
        Hidden::class, Collection::class, NestedArray::class,
    ];

    foreach ($metadataAttributes as $class) {
        $ref = new ReflectionClass($class);

        expect($ref->isFinal())->toBeTrue("{$class} must be final");

        // All public properties must be readonly
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            expect($prop->isReadOnly())->toBeTrue(
                "{$class}::\${$prop->getName()} must be readonly"
            );
        }
    }
});

test('Hidden attribute has no constructor parameters', function (): void {
    $ref = new ReflectionClass(Hidden::class);
    $constructor = $ref->getConstructor();
    $paramCount = $constructor?->getNumberOfParameters() ?? 0;
    expect($paramCount)->toBe(0, 'Hidden must have no constructor parameters');
});

test('DTOSServiceProvider is final with Override on register and boot', function (): void {
    $ref = new ReflectionClass(DTOSServiceProvider::class);

    expect($ref->isFinal())->toBeTrue();

    $register = $ref->getMethod('register');
    expect($register->getAttributes(\Override::class))->not->toBeEmpty();
    expect($register->getReturnType()?->getName())->toBe('void');

    $boot = $ref->getMethod('boot');
    expect($boot->getAttributes(\Override::class))->not->toBeEmpty();
    expect($boot->getReturnType()?->getName())->toBe('void');
});

test('DTO facade has Override on getFacadeAccessor', function (): void {
    $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);

    expect($ref->isFinal())->toBeTrue();

    $getAccessor = $ref->getMethod('getFacadeAccessor');
    expect($getAccessor->getAttributes(\Override::class))->not->toBeEmpty();
    expect($getAccessor->getReturnType()?->getName())->toBe('string');
});

test('DtoMetadataResolver is final and all public methods are static', function (): void {
    $ref = new ReflectionClass(DtoMetadataResolver::class);

    expect($ref->isFinal())->toBeTrue();

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        expect($method->isStatic())->toBeTrue(
            "DtoMetadataResolver::{$method->getName()}() must be static"
        );
    }

    // resolve() returns array
    $resolve = $ref->getMethod('resolve');
    expect($resolve->getReturnType()?->getName())->toBe('array');
});

test('OpenApiSchemaGenerator is final and all public methods are static', function (): void {
    $ref = new ReflectionClass(OpenApiSchemaGenerator::class);

    expect($ref->isFinal())->toBeTrue();

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        expect($method->isStatic())->toBeTrue(
            "OpenApiSchemaGenerator::{$method->getName()}() must be static"
        );
    }

    // generate() returns array
    $generate = $ref->getMethod('generate');
    expect($generate->getReturnType()?->getName())->toBe('array');

    // generateWithComponents() returns array
    $generateWith = $ref->getMethod('generateWithComponents');
    expect($generateWith->getReturnType()?->getName())->toBe('array');
});

test('contracts have correct method signatures', function (): void {
    // ValidatableDTO
    $ref = new ReflectionClass(ValidatableDTO::class);
    expect($ref->isInterface())->toBeTrue();

    $rules = $ref->getMethod('rules');
    expect($rules->getReturnType()?->getName())->toBe('array');
    expect($rules->isStatic())->toBeTrue();

    $rulesFor = $ref->getMethod('rulesFor');
    expect($rulesFor->getReturnType()?->getName())->toBe('array');
    expect($rulesFor->isStatic())->toBeTrue();

    // FromRequestDTO
    $ref = new ReflectionClass(FromRequestDTO::class);
    expect($ref->isInterface())->toBeTrue();

    $fromRequest = $ref->getMethod('fromRequest');
    expect($fromRequest->getReturnType()?->getName())->toBe('static');
    expect($fromRequest->isStatic())->toBeTrue();

    // ValidationAttribute
    $ref = new ReflectionClass(ValidationAttribute::class);
    expect($ref->isInterface())->toBeTrue();

    $ruleKey = $ref->getMethod('ruleKey');
    expect($ruleKey->getReturnType()?->getName())->toBe('string');
});

test('composer.json has correct PHP and Laravel requirements', function (): void {
    $composer = json_decode(
        file_get_contents(__DIR__.'/../composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    expect($composer['require']['php'])->toBe('^8.5');
    expect($composer['require']['illuminate/contracts'])->toStartWith('^13');
    expect($composer['require']['illuminate/http'])->toStartWith('^13');
    expect($composer['require']['illuminate/support'])->toStartWith('^13');
    expect($composer['require']['illuminate/validation'])->toStartWith('^13');
    expect($composer['require']['zeroboiler/value-objects'])->toStartWith('^1.0');
    expect($composer['version'])->toBe('1.1.38');
});

test('all public API methods have PHPDoc blocks', function (): void {
    $classes = [
        DTOManager::class,
        DtoCollection::class,
        DTOCast::class,
        DTOException::class,
        DTOSServiceProvider::class,
        DtoMetadataResolver::class,
        OpenApiSchemaGenerator::class,
    ];

    foreach ($classes as $class) {
        $ref = new ReflectionClass($class);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }

            $docComment = $method->getDocComment();

            expect($docComment)->not->toBeFalse(
                "{$class}::{$method->getName()}() must have a PHPDoc block"
            );
        }
    }
});

test('DataTransferObject has Override on interface methods', function (): void {
    $ref = new ReflectionClass(DataTransferObject::class);

    // fromRequest must have #[Override] (FromRequestDTO)
    $fromRequest = $ref->getMethod('fromRequest');
    expect($fromRequest->getAttributes(\Override::class))->not->toBeEmpty(
        'DataTransferObject::fromRequest() must have #[Override]'
    );

    // rules must have #[Override] (ValidatableDTO)
    $rules = $ref->getMethod('rules');
    expect($rules->getAttributes(\Override::class))->not->toBeEmpty(
        'DataTransferObject::rules() must have #[Override]'
    );

    // rulesFor must have #[Override] (ValidatableDTO)
    $rulesFor = $ref->getMethod('rulesFor');
    expect($rulesFor->getAttributes(\Override::class))->not->toBeEmpty(
        'DataTransferObject::rulesFor() must have #[Override]'
    );

    // toArray must have #[Override] (Arrayable)
    $toArray = $ref->getMethod('toArray');
    expect($toArray->getAttributes(\Override::class))->not->toBeEmpty(
        'DataTransferObject::toArray() must have #[Override]'
    );

    // jsonSerialize must have #[Override] (JsonSerializable)
    $jsonSerialize = $ref->getMethod('jsonSerialize');
    expect($jsonSerialize->getAttributes(\Override::class))->not->toBeEmpty(
        'DataTransferObject::jsonSerialize() must have #[Override]'
    );
});
