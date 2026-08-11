<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
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
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facade as DTOFacade;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

describe('DTO — PHPStan Level 9 Structural Compliance Audit', function () {
    // -------------------------------------------------------------------------
    // 1. All source files declare strict_types=1
    // -------------------------------------------------------------------------
    it('all source files declare strict_types=1', function () {
        $files = glob_recursive(dirname(__DIR__, 2) . '/src', '*.php');
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)
                ->toContain('declare(strict_types=1)')
                ->or()->toContain("declare(strict_types = 1)");
        }
    });

    // -------------------------------------------------------------------------
    // 2. All concrete classes are final
    // -------------------------------------------------------------------------
    it('all concrete classes are final', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');

        $nonFinal = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, 'abstract class ')
                || str_contains($content, 'interface ')
            ) {
                continue;
            }
            if (preg_match('/\bclass\s+(\w+)/', $content, $m)) {
                if (! str_contains($content, 'final class ')) {
                    $nonFinal[] = $m[1];
                }
            }
        }

        expect($nonFinal)->toBeEmpty(
            'Non-final classes found: ' . implode(', ', $nonFinal)
        );
    });

    // -------------------------------------------------------------------------
    // 3. DataTransferObject is abstract
    // -------------------------------------------------------------------------
    it('DataTransferObject is abstract', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    // -------------------------------------------------------------------------
    // 4. DataTransferObject implements correct interfaces
    // -------------------------------------------------------------------------
    it('DataTransferObject implements Arrayable, FromRequestDTO, JsonSerializable, ValidatableDTO', function () {
        $implements = class_implements(DataTransferObject::class) ?: [];
        expect($implements)->toContain(\Illuminate\Contracts\Support\Arrayable::class);
        expect($implements)->toContain(FromRequestDTO::class);
        expect($implements)->toContain(\JsonSerializable::class);
        expect($implements)->toContain(ValidatableDTO::class);
    });

    // -------------------------------------------------------------------------
    // 5. DTOManager is readonly final
    // -------------------------------------------------------------------------
    it('DTOManager is final readonly', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        $content = file_get_contents($ref->getFileName());
        expect($content)->toContain('final readonly class DTOManager');
    });

    // -------------------------------------------------------------------------
    // 6. DtoCollection implements correct interfaces
    // -------------------------------------------------------------------------
    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $implements = class_implements(DtoCollection::class) ?: [];
        expect($implements)->toContain(\ArrayAccess::class);
        expect($implements)->toContain(\Countable::class);
        expect($implements)->toContain(\IteratorAggregate::class);
        expect($implements)->toContain(\JsonSerializable::class);
    });

    // -------------------------------------------------------------------------
    // 7. DTOCast implements CastsAttributes
    // -------------------------------------------------------------------------
    it('DTOCast implements CastsAttributes', function () {
        $implements = class_implements(DTOCast::class) ?: [];
        expect($implements)->toContain(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
    });

    // -------------------------------------------------------------------------
    // 8. DTOException is final with named constructors
    // -------------------------------------------------------------------------
    it('DTOException is final with invalidCast and invalidJson named constructors', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();

        $invalidCast = $ref->getMethod('invalidCast');
        expect($invalidCast->isStatic())->toBeTrue();
        expect($invalidCast->getReturnType()->getName())->toBe('self');

        $invalidJson = $ref->getMethod('invalidJson');
        expect($invalidJson->isStatic())->toBeTrue();
        expect($invalidJson->getReturnType()->getName())->toBe('self');
    });

    // -------------------------------------------------------------------------
    // 9. All ValidationAttribute implementations have ruleKey() returning string
    // -------------------------------------------------------------------------
    it('all validation attributes have ruleKey() with string return type', function () {
        $validationAttrs = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class, In::class, Integer::class,
            Numeric::class, Boolean::class, Uuid::class, Date::class,
            Confirmed::class, Different::class, Same::class,
            Between::class, ArrayRule::class, Prohibited::class,
            Present::class, Declined::class, Accepted::class,
            StartsWith::class, EndsWith::class, Nullable::class,
            Sometimes::class, Distinct::class, Size::class,
            Json::class, Enum::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, NestedArray::class,
            Collection::class,
        ];

        foreach ($validationAttrs as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$class} must have ruleKey()");

            $method = $ref->getMethod('ruleKey');
            $returnType = $method->getReturnType();
            expect($returnType)->not->BeNull("{$class}::ruleKey() must have a return type");
            expect($returnType->getName())->toBe('string');
        }
    });

    // -------------------------------------------------------------------------
    // 10. All public methods in core service classes have return types
    // -------------------------------------------------------------------------
    it('all public methods in service classes have return type declarations', function () {
        $classesToCheck = [
            DTOManager::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DtoCollection::class,
        ];

        $missingReturnTypes = [];
        foreach ($classesToCheck as $class) {
            $ref = new ReflectionClass($class);
            $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $returnType = $method->getReturnType();
                if ($returnType === null) {
                    $missingReturnTypes[] = "{$class}::{$method->getName()}()";
                }
            }
        }

        expect($missingReturnTypes)->toBeEmpty(
            'Missing return types: ' . implode(', ', $missingReturnTypes)
        );
    });

    // -------------------------------------------------------------------------
    // 11. DtoMetadataResolver is a static utility class
    // -------------------------------------------------------------------------
    it('DtoMetadataResolver is a final static utility class', function () {
        $ref = new ReflectionClass(DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();

        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            expect($method->isStatic())->toBeTrue(
                "DtoMetadataResolver::{$method->getName()}() must be static"
            );
        }
    });

    // -------------------------------------------------------------------------
    // 12. OpenApiSchemaGenerator is a final static utility class
    // -------------------------------------------------------------------------
    it('OpenApiSchemaGenerator is a final static utility class', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();

        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            expect($method->isStatic())->toBeTrue(
                "OpenApiSchemaGenerator::{$method->getName()}() must be static"
            );
        }
    });

    // -------------------------------------------------------------------------
    // 13. DTOCast has validate parameter for conditional validation
    // -------------------------------------------------------------------------
    it('DTOCast constructor has validate parameter defaulting to true', function () {
        $ref = new ReflectionClass(DTOCast::class);
        $ctor = $ref->getConstructor();
        expect($ctor)->not->BeNull();

        $params = $ctor->getParameters();
        expect($params)->toHaveCount(2);

        // First param: dtoClass (string)
        expect($params[0]->getName())->toBe('dtoClass');
        expect($params[0]->getType()->getName())->toBe('string');

        // Second param: validate (bool, default true)
        expect($params[1]->getName())->toBe('validate');
        expect($params[1]->getType()->getName())->toBe('bool');
        expect($params[1]->isDefaultValueAvailable())->toBeTrue();
        expect($params[1]->getDefaultValue())->toBe(true);
    });

    // -------------------------------------------------------------------------
    // 14. Metadata-only attributes don't implement ValidationAttribute
    // -------------------------------------------------------------------------
    it('metadata-only attributes do not implement ValidationAttribute', function () {
        $metaOnlyAttrs = [
            Cast::class,
            DefaultValue::class,
            Hidden::class,
            MapFrom::class,
        ];

        foreach ($metaOnlyAttrs as $class) {
            $implements = class_implements($class) ?: [];
            expect($implements)
                ->not->toContain(ValidationAttribute::class,
                    "{$class} should not implement ValidationAttribute (metadata-only)"
                );
        }
    });

    // -------------------------------------------------------------------------
    // 15. Facade accessor matches service provider binding
    // -------------------------------------------------------------------------
    it('DTO facade accessor matches service provider binding', function () {
        $facadeRef = new ReflectionClass(\ZeroBoiler\DTO\Facade::class);
        $method = $facadeRef->getMethod('getFacadeAccessor');
        $filename = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $lines = array_slice(file($filename), $start - 1, $end - $start + 1);
        $content = implode('', $lines);

        expect($content)->toContain('zeroboiler.dto');

        $spRef = new ReflectionClass(DTOSServiceProvider::class);
        $spMethod = $spRef->getMethod('register');
        $spFilename = $spMethod->getFileName();
        $spStart = $spMethod->getStartLine();
        $spEnd = $spMethod->getEndLine();
        $spLines = array_slice(file($spFilename), $spStart - 1, $spEnd - $spStart + 1);
        $spContent = implode('', $spLines);

        expect($spContent)->toContain('zeroboiler.dto');
        expect($spContent)->toContain('singleton');
    });

    // -------------------------------------------------------------------------
    // 16. Contracts have correct method signatures
    // -------------------------------------------------------------------------
    it('FromRequestDTO interface has correct method signature', function () {
        $ref = new ReflectionClass(FromRequestDTO::class);
        $methods = $ref->getMethods();

        expect($methods)->toHaveCount(1);
        $method = $methods[0];
        expect($method->getName())->toBe('fromRequest');
        expect($method->isStatic())->toBeTrue();
        expect($method->getReturnType()->getName())->toBe('static');

        $params = $method->getParameters();
        expect($params)->toHaveCount(2);
    });

    it('ValidatableDTO interface has rules() and rulesFor()', function () {
        $ref = new ReflectionClass(ValidatableDTO::class);
        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods()
        );

        expect($methods)->toContain('rules');
        expect($methods)->toContain('rulesFor');

        $rules = $ref->getMethod('rules');
        expect($rules->isStatic())->toBeTrue();
        expect($rules->getReturnType()->getName())->toBe('array');

        $rulesFor = $ref->getMethod('rulesFor');
        expect($rulesFor->isStatic())->toBeTrue();
        expect($rulesFor->getReturnType()->getName())->toBe('array');
    });

    it('ValidationAttribute interface has ruleKey() returning string', function () {
        $ref = new ReflectionClass(ValidationAttribute::class);
        $methods = $ref->getMethods();

        expect($methods)->toHaveCount(1);
        $method = $methods[0];
        expect($method->getName())->toBe('ruleKey');
        expect($method->getReturnType()->getName())->toBe('string');
    });

    // -------------------------------------------------------------------------
    // 17. DTOSServiceProvider extends ServiceProvider
    // -------------------------------------------------------------------------
    it('DTOSServiceProvider extends ServiceProvider with register and boot', function () {
        $ref = new ReflectionClass(DTOSServiceProvider::class);
        expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
        expect($ref->isFinal())->toBeTrue();

        expect($ref->hasMethod('register'))->toBeTrue();
        expect($ref->hasMethod('boot'))->toBeTrue();

        $register = $ref->getMethod('register');
        expect($register->getReturnType()->getName())->toBe('void');

        $boot = $ref->getMethod('boot');
        expect($boot->getReturnType()->getName())->toBe('void');
    });

    // -------------------------------------------------------------------------
    // 18. NestedArray and Collection attributes have dtoClass property
    // -------------------------------------------------------------------------
    it('NestedArray and Collection have typed dtoClass property', function () {
        foreach ([NestedArray::class, Collection::class] as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->hasProperty('dtoClass'))->toBeTrue("{$class} must have dtoClass property");

            $prop = $ref->getProperty('dtoClass');
            expect($prop->isReadOnly())->toBeTrue("{$class}::\$dtoClass must be readonly");
            $type = $prop->getType();
            expect($type)->not->BeNull();
            expect($type->getName())->toBe('string');
        }
    });

    // -------------------------------------------------------------------------
    // 19. Conditional validation attributes have correct properties
    // -------------------------------------------------------------------------
    it('RequiredIf has field and value properties', function () {
        $ref = new ReflectionClass(RequiredIf::class);
        expect($ref->hasProperty('field'))->toBeTrue();
        expect($ref->hasProperty('value'))->toBeTrue();

        $field = $ref->getProperty('field');
        expect($field->getType()->getName())->toBe('string');
        expect($field->isReadOnly())->toBeTrue();
    });

    it('RequiredWith has fields property (array)', function () {
        $ref = new ReflectionClass(RequiredWith::class);
        expect($ref->hasProperty('fields'))->toBeTrue();

        $fields = $ref->getProperty('fields');
        expect($fields->isReadOnly())->toBeTrue();
    });

    // -------------------------------------------------------------------------
    // 20. StartsWith/EndsWith have prefix/suffix properties
    // -------------------------------------------------------------------------
    it('StartsWith has prefix property', function () {
        $ref = new ReflectionClass(StartsWith::class);
        expect($ref->hasProperty('prefix'))->toBeTrue();

        $prop = $ref->getProperty('prefix');
        expect($prop->isReadOnly())->toBeTrue();
    });

    it('EndsWith has suffix property', function () {
        $ref = new ReflectionClass(EndsWith::class);
        expect($ref->hasProperty('suffix'))->toBeTrue();

        $prop = $ref->getProperty('suffix');
        expect($prop->isReadOnly())->toBeTrue();
    });

    // -------------------------------------------------------------------------
    // 21. All attribute classes use #[Attribute] with TARGET_PROPERTY
    // -------------------------------------------------------------------------
    it('all validation attributes target TARGET_PROPERTY', function () {
        $validationAttrs = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class, In::class, Integer::class,
            Numeric::class, Boolean::class, Uuid::class, Date::class,
            Confirmed::class, Different::class, Same::class,
            Between::class, ArrayRule::class, Prohibited::class,
            Present::class, Declined::class, Accepted::class,
            StartsWith::class, EndsWith::class, Nullable::class,
            Sometimes::class, Distinct::class, Size::class,
            Json::class, Enum::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class,
        ];

        foreach ($validationAttrs as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty("{$class} must have #[Attribute]");

            $instance = $attrs[0]->newInstance();
            expect($instance->flags & \Attribute::TARGET_PROPERTY)
                ->toBe(\Attribute::TARGET_PROPERTY,
                    "{$class} must target TARGET_PROPERTY"
                );
        }
    });

    it('Cast targets TARGET_PROPERTY', function () {
        $ref = new ReflectionClass(Cast::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)
            ->toBe(\Attribute::TARGET_PROPERTY);
    });

    it('MapFrom targets TARGET_PROPERTY', function () {
        $ref = new ReflectionClass(MapFrom::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)
            ->toBe(\Attribute::TARGET_PROPERTY);
    });

    it('Hidden targets TARGET_PROPERTY', function () {
        $ref = new ReflectionClass(Hidden::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        expect($attrs)->not->toBeEmpty();
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)
            ->toBe(\Attribute::TARGET_PROPERTY);
    });

    it('DefaultValue targets TARGET_PROPERTY | TARGET_PARAMETER', function () {
        $ref = new ReflectionClass(DefaultValue::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $instance = $attrs[0]->newInstance();
        $expected = \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER;
        expect($instance->flags)->toBe($expected);
    });
});

/**
 * Recursively glob for files matching a pattern.
 *
 * @return list<string>
 */
function glob_recursive(string $baseDir, string $pattern): array
{
    $results = [];
    $files = glob($baseDir . '/' . $pattern);

    if ($files !== false) {
        $results = array_values($files);
    }

    $dirs = glob($baseDir . '/*', GLOB_ONLYDIR);

    if ($dirs !== false) {
        foreach ($dirs as $dir) {
            $results = [...$results, ...glob_recursive($dir, $pattern)];
        }
    }

    return $results;
}
