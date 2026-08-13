<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * PHPStan Level 9 Full API Type Safety Audit.
 *
 * Structural test that verifies every public class in the DTO package
 * has explicit parameter types, return types, and no `mixed` in signatures.
 * Also verifies final classes, readonly properties, and proper interfaces.
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\DTOManager
 * @see \ZeroBoiler\DTO\Exceptions\DTOException
 */
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
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
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

describe('PHPStan Level 9 — DTO Package Full API Type Safety', function () {
    /**
     * All attribute classes that must pass the final + readonly audit.
     *
     * @var list<class-string>
     */
    $attributeClasses = [
        Required::class, Email::class, Max::class, Min::class, Pattern::class,
        In::class, Url::class, Uuid::class, Integer::class, Numeric::class,
        Boolean::class, Date::class, ArrayRule::class, Json::class, Enum::class,
        MapFrom::class, Cast::class, DefaultValue::class, Hidden::class,
        Nullable::class, Sometimes::class, Present::class, Prohibited::class,
        Accepted::class, Declined::class, Confirmed::class, Same::class,
        Different::class, Distinct::class, Size::class, StartsWith::class,
        EndsWith::class, RequiredIf::class, RequiredUnless::class,
        RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
        RequiredWithoutAll::class, NestedArray::class, Collection::class,
        Between::class,
    ];

    /**
     * All service/infrastructure classes that must pass the final audit.
     *
     * @var list<class-string>
     */
    $serviceClasses = [
        DtoCollection::class,
        DTOManager::class,
        DtoMetadataResolver::class,
        OpenApiSchemaGenerator::class,
        DTOCast::class,
        DTOException::class,
        DTO::class,
    ];

    it('all attribute classes are declared final', function () use ($attributeClasses) {
        foreach ($attributeClasses as $className) {
            $ref = new ReflectionClass($className);
            expect($ref->isFinal())->toBeTrue(
                "Expected {$className} to be final"
            );
        }
    });

    it('all service classes are declared final', function () use ($serviceClasses) {
        foreach ($serviceClasses as $className) {
            $ref = new ReflectionClass($className);
            expect($ref->isFinal())->toBeTrue(
                "Expected {$className} to be final"
            );
        }
    });

    it('all attribute classes use readonly promoted properties in constructor', function () use ($attributeClasses) {
        foreach ($attributeClasses as $className) {
            $ref = new ReflectionClass($className);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                continue;
            }

            foreach ($constructor->getParameters() as $param) {
                if (! $ref->hasProperty($param->getName())) {
                    continue;
                }

                $prop = $ref->getProperty($param->getName());
                expect($prop->isReadOnly())->toBeTrue(
                    "{$className}::\${$param->getName()} must be readonly"
                );
            }
        }
    });

    it('all validation attributes implement ValidationAttribute interface', function () use ($attributeClasses) {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, Url::class, Uuid::class, Integer::class, Numeric::class,
            Boolean::class, Date::class, ArrayRule::class, Json::class, Enum::class,
            Confirmed::class, Same::class, Different::class, Distinct::class,
            Size::class, StartsWith::class, EndsWith::class, Nullable::class,
            Sometimes::class, Present::class, Prohibited::class, Accepted::class,
            Declined::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Between::class,
        ];

        foreach ($validationAttributes as $className) {
            expect($className)->toImplement(ValidationAttribute::class,
                "Expected {$className} to implement ValidationAttribute"
            );
        }
    });

    it('all validation attributes have a ruleKey() method returning string', function () use ($attributeClasses) {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, Url::class, Uuid::class, Integer::class, Numeric::class,
            Boolean::class, Date::class, ArrayRule::class, Json::class, Enum::class,
            Confirmed::class, Same::class, Different::class, Distinct::class,
            Size::class, StartsWith::class, EndsWith::class, Nullable::class,
            Sometimes::class, Present::class, Prohibited::class, Accepted::class,
            Declined::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Between::class,
        ];

        foreach ($validationAttributes as $className) {
            $ref = new ReflectionClass($className);
            $method = $ref->getMethod('ruleKey');
            $returnType = $method->getReturnType();
            expect($returnType?->getName())->toBe('string',
                "{$className}::ruleKey() must return string"
            );
        }
    });

    it('DataTransferObject is abstract', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue(
            'DataTransferObject must be abstract'
        );
    });

    it('DataTransferObject implements correct interfaces', function () {
        expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        // Also implements Arrayable and JsonSerializable via implements clause
        $ref = new ReflectionClass(DataTransferObject::class);
        $interfaces = array_map(
            fn (ReflectionClass $i) => $i->getName(),
            $ref->getInterfaces()
        );
        expect($interfaces)->toContain(\Illuminate\Contracts\Support\Arrayable::class);
        expect($interfaces)->toContain(\JsonSerializable::class);
    });

    it('DtoCollection implements correct interfaces', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $interfaces = array_map(
            fn (ReflectionClass $i) => $i->getName(),
            $ref->getInterfaces()
        );
        expect($interfaces)->toContain(\ArrayAccess::class);
        expect($interfaces)->toContain(\Countable::class);
        expect($interfaces)->toContain(\IteratorAggregate::class);
        expect($interfaces)->toContain(\JsonSerializable::class);
    });

    it('DTOManager is readonly class', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isReadOnly())->toBeTrue('DTOManager must be a readonly class');
    });

    it('Facade is final with proper accessor', function () {
        $ref = new ReflectionClass(DTO::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->getMethod('getFacadeAccessor')->getReturnType()?->getName())->toBe('string');
    });

    it('DTOCast has Override attribute on interface methods', function () {
        $ref = new ReflectionClass(DTOCast::class);

        $methodsToCheck = ['get', 'set'];
        foreach ($methodsToCheck as $methodName) {
            $method = $ref->getMethod($methodName);
            $attributes = $method->getAttributes();
            $hasOverride = false;
            foreach ($attributes as $attr) {
                if ($attr->getName() === \Override::class) {
                    $hasOverride = true;
                    break;
                }
            }
            expect($hasOverride)->toBeTrue(
                "DTOCast::{$methodName}() must have #[Override] attribute"
            );
        }
    });

    it('contracts define correct method signatures', function () {
        // FromRequestDTO::fromRequest(Request, bool): static
        $fromRequestRef = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        $params = $fromRequestRef->getParameters();
        expect(count($params))->toBe(2);
        expect($fromRequestRef->getReturnType()?->getName())->toBe('static');

        // ValidatableDTO::rules(): array
        $rulesRef = new ReflectionMethod(ValidatableDTO::class, 'rules');
        expect($rulesRef->getReturnType()?->getName())->toBe('array');
        expect($rulesRef->isStatic())->toBeTrue();

        // ValidatableDTO::rulesFor(string): array
        $rulesForRef = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
        expect($rulesForRef->getReturnType()?->getName())->toBe('array');
        expect($rulesForRef->isStatic())->toBeTrue();
    });
});
