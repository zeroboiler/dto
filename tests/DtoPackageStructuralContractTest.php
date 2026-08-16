<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Attribute;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use ReflectionClass;
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
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
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
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Structural contract test — verifies that every public class in the DTO
 * package has the correct modifiers, parent types, and attribute targets.
 *
 * This test prevents accidental breaking changes (e.g., removing `final`,
 * changing an attribute target, or breaking interface contracts).
 */
describe('DTO Package Structural Contract', function () {
    // -------------------------------------------------------------------
    // Validation attribute completeness
    // -------------------------------------------------------------------

    /**
     * All DTO attributes that implement ValidationAttribute interface.
     */
    it('all validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Confirmed::class, Date::class, Declined::class, Distinct::class,
            Email::class, EndsWith::class, EnumAttr::class, In::class,
            Integer::class, Json::class, Max::class, Min::class,
            Numeric::class, Pattern::class, Prohibited::class, Present::class,
            Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
            StartsWith::class, Url::class, Uuid::class,
        ];

        foreach ($validationAttributes as $class) {
            $interfaces = class_implements($class) ?: [];
            expect(in_array(ValidationAttribute::class, $interfaces, true))->toBeTrue("{$class} must implement ValidationAttribute");
        }
    });

    it('all validation attributes have a ruleKey() method returning non-empty string', function () {
        $validationAttributes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Confirmed::class, Date::class, Declined::class, Distinct::class,
            Email::class, EndsWith::class, EnumAttr::class, In::class,
            Integer::class, Json::class, Max::class, Min::class,
            Numeric::class, Pattern::class, Prohibited::class, Present::class,
            Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
            StartsWith::class, Url::class, Uuid::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$class} must have ruleKey() method");

            $method = $ref->getMethod('ruleKey');
            expect($method->getReturnType()?->getName())->toBe('string');
        }
    });

    it('all validation attributes target property only', function () {
        $validationAttributes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Confirmed::class, Date::class, Declined::class, Distinct::class,
            Email::class, EndsWith::class, EnumAttr::class, In::class,
            Integer::class, Json::class, Max::class, Min::class,
            Numeric::class, Pattern::class, Prohibited::class, Present::class,
            Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Size::class, Sometimes::class,
            StartsWith::class, Url::class, Uuid::class,
        ];

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->toHaveCount(1);

            $instance = $attrs[0]->newInstance();
            expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY);
        }
    });

    // -------------------------------------------------------------------
    // Metadata-only attributes (non-validation)
    // -------------------------------------------------------------------

    it('metadata attributes do NOT implement ValidationAttribute', function () {
        $metaAttributes = [
            Cast::class, MapFrom::class, Hidden::class,
            DefaultValue::class, NestedArray::class, Collection::class,
        ];

        foreach ($metaAttributes as $class) {
            $interfaces = class_implements($class) ?: [];
            expect(in_array(ValidationAttribute::class, $interfaces, true))->toBeFalse("{$class} must NOT implement ValidationAttribute");
        }
    });

    it('Cast attribute targets property only', function () {
        $ref = new ReflectionClass(Cast::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY);
    });

    it('MapFrom attribute targets property only', function () {
        $ref = new ReflectionClass(MapFrom::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY);
    });

    it('Hidden attribute targets property only', function () {
        $ref = new ReflectionClass(Hidden::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY);
    });

    it('DefaultValue attribute targets property and parameter', function () {
        $ref = new ReflectionClass(DefaultValue::class);
        $attrs = $ref->getAttributes(Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags)->toBe(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER);
    });

    // -------------------------------------------------------------------
    // Final class enforcement
    // -------------------------------------------------------------------

    it('all attributes are final classes', function () {
        $allAttributes = [
            // Validation attributes
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Cast::class, Collection::class, Confirmed::class, Date::class,
            Declined::class, DefaultValue::class, Different::class, Distinct::class,
            Email::class, EndsWith::class, EnumAttr::class, Hidden::class,
            In::class, Integer::class, Json::class, MapFrom::class, Max::class,
            Min::class, NestedArray::class, Nullable::class, Numeric::class,
            Pattern::class, Present::class, Prohibited::class, Required::class,
            RequiredIf::class, RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class, RequiredWithoutAll::class,
            Same::class, Size::class, Sometimes::class, StartsWith::class,
            Url::class, Uuid::class,
        ];

        foreach ($allAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('DTOManager is final and readonly', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DtoCollection is final', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOCast is final', function () {
        $ref = new ReflectionClass(DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOException is final', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DtoMetadataResolver is final', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('OpenApiSchemaGenerator is final', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider is final', function () {
        $ref = new ReflectionClass(DTOSServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTO facade is final', function () {
        $ref = new ReflectionClass(DTO::class);
        expect($ref->isFinal())->toBeTrue();
    });

    // -------------------------------------------------------------------
    // Interface implementations
    // -------------------------------------------------------------------

    it('DTOCast implements CastsAttributes', function () {
        expect(in_array(CastsAttributes::class, class_implements(DTOCast::class) ?: [], true))->toBeTrue();
    });

    it('DataTransferObject implements FromRequestDTO', function () {
        expect(in_array(FromRequestDTO::class, class_implements(DataTransferObject::class) ?: [], true))->toBeTrue();
    });

    it('DataTransferObject implements ValidatableDTO', function () {
        expect(in_array(ValidatableDTO::class, class_implements(DataTransferObject::class) ?: [], true))->toBeTrue();
    });

    it('DataTransferObject implements JsonSerializable', function () {
        expect(in_array(\JsonSerializable::class, class_implements(DataTransferObject::class) ?: [], true))->toBeTrue();
    });

    it('DataTransferObject implements Arrayable', function () {
        expect(in_array(\Illuminate\Contracts\Support\Arrayable::class, class_implements(DataTransferObject::class) ?: [], true))->toBeTrue();
    });

    it('DtoCollection implements ArrayAccess', function () {
        expect(in_array(\ArrayAccess::class, class_implements(DtoCollection::class) ?: [], true))->toBeTrue();
    });

    it('DtoCollection implements Countable', function () {
        expect(in_array(\Countable::class, class_implements(DtoCollection::class) ?: [], true))->toBeTrue();
    });

    it('DtoCollection implements IteratorAggregate', function () {
        expect(in_array(\IteratorAggregate::class, class_implements(DtoCollection::class) ?: [], true))->toBeTrue();
    });

    it('DtoCollection implements JsonSerializable', function () {
        expect(in_array(\JsonSerializable::class, class_implements(DtoCollection::class) ?: [], true))->toBeTrue();
    });

    it('DTOException extends Exception', function () {
        expect(is_subclass_of(DTOException::class, \Exception::class))->toBeTrue();
    });

    // -------------------------------------------------------------------
    // DataTransferObject abstract class completeness
    // -------------------------------------------------------------------

    it('DataTransferObject is abstract', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    it('DataTransferObject provides all expected static factory methods', function () {
        $expectedMethods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromPartialRequest',
            'fromJson', 'validateArray', 'validatePartialArray',
            'rules', 'rulesFor',
            'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        $ref = new ReflectionClass(DataTransferObject::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("DataTransferObject must have method {$method}()");
        }
    });

    it('DataTransferObject provides all expected instance methods', function () {
        $expectedMethods = [
            'toArray', 'allValues', 'toJson', 'jsonSerialize',
            'equals', 'isEmpty', 'isNotEmpty',
            'only', 'except', 'with',
        ];

        $ref = new ReflectionClass(DataTransferObject::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("DataTransferObject must have method {$method}()");
        }
    });

    // -------------------------------------------------------------------
    // DtoCollection method completeness
    // -------------------------------------------------------------------

    it('DtoCollection provides all expected methods', function () {
        $expectedMethods = [
            'toArray', 'allValues', 'items', 'make', 'push',
            'first', 'last', 'map', 'filter', 'pluck', 'pluckKey',
            'append', 'merge', 'isEmpty', 'isNotEmpty',
            'toArrayBy', 'toDictionary', 'count', 'getIterator',
        ];

        $ref = new ReflectionClass(DtoCollection::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("DtoCollection must have method {$method}()");
        }
    });

    // -------------------------------------------------------------------
    // DTOManager method completeness
    // -------------------------------------------------------------------

    it('DTOManager provides all expected methods', function () {
        $expectedMethods = [
            'validate', 'make', 'makeFromJson', 'fromJson',
            'rules', 'rulesFor', 'schema',
            'fromPartialArray', 'fromPartialRequest',
        ];

        $ref = new ReflectionClass(DTOManager::class);

        foreach ($expectedMethods as $method) {
            expect($ref->hasMethod($method))->toBeTrue("DTOManager must have method {$method}()");
        }
    });

    // -------------------------------------------------------------------
    // Contracts interface completeness
    // -------------------------------------------------------------------

    it('FromRequestDTO interface defines fromRequest with correct signature', function () {
        $ref = new ReflectionClass(FromRequestDTO::class);
        $method = $ref->getMethod('fromRequest');

        expect($method->isStatic())->toBeTrue();
        expect($method->getReturnType()?->getName())->toBe('static');
    });

    it('ValidatableDTO interface defines rules and rulesFor', function () {
        $ref = new ReflectionClass(ValidatableDTO::class);

        expect($ref->hasMethod('rules'))->toBeTrue();
        expect($ref->hasMethod('rulesFor'))->toBeTrue();

        $rulesMethod = $ref->getMethod('rules');
        expect($rulesMethod->isStatic())->toBeTrue();
        expect($rulesMethod->getReturnType()?->getName())->toBe('array');

        $rulesForMethod = $ref->getMethod('rulesFor');
        expect($rulesForMethod->isStatic())->toBeTrue();
        expect($rulesForMethod->getReturnType()?->getName())->toBe('array');
    });

    it('ValidationAttribute interface defines ruleKey returning string', function () {
        $ref = new ReflectionClass(ValidationAttribute::class);
        $method = $ref->getMethod('ruleKey');

        expect($method->getReturnType()?->getName())->toBe('string');
    });

    // -------------------------------------------------------------------
    // declare(strict_types=1) enforcement
    // -------------------------------------------------------------------

    it('all source files declare strict types', function () {
        $srcDir = dirname((new ReflectionClass(DataTransferObject::class))->getFileName());
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        expect($phpFiles)->not->toBeEmpty();

        foreach ($phpFiles as $filePath) {
            $contents = file_get_contents($filePath);
            $hasStrict = str_contains($contents, 'declare(strict_types=1)');
            expect($hasStrict)->toBeTrue("{$filePath} must declare strict_types=1");
        }
    });
});
