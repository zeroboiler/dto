<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
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
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DTO — Production Final Audit (Source Code Quality)', function () {
    // -----------------------------------------------------------------------
    // 1. Strict types in every source file
    // -----------------------------------------------------------------------
    it('all source files have declare(strict_types=1)', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');
        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            expect($content)->toContain('declare(strict_types=1)');
        }
    });

    // -----------------------------------------------------------------------
    // 2. All classes are final (except abstract DataTransferObject)
    // -----------------------------------------------------------------------
    it('all concrete classes are final', function () {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $files = glob_recursive($srcDir, '*.php');

        $nonFinalClasses = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Skip abstract class (DataTransferObject)
            if (str_contains($content, 'abstract class ')) {
                continue;
            }
            if (preg_match('/\bfinal\s+)?class\s+(\w+)/', $content, $m)) {
                if (! str_contains($content, 'final class ')) {
                    $nonFinalClasses[] = $m[1];
                }
            }
        }

        expect($nonFinalClasses)->toBeEmpty(
            'Non-final classes found: ' . implode(', ', $nonFinalClasses)
        );
    });

    // -----------------------------------------------------------------------
    // 3. All validation attributes implement ValidationAttribute interface
    // -----------------------------------------------------------------------
    it('all validation attributes implement ValidationAttribute', function () {
        $validationAttributes = [
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

        foreach ($validationAttributes as $class) {
            $implements = class_implements($class) ?: [];
            expect($implements)->toContain(ValidationAttribute::class,
                "{$class} must implement ValidationAttribute"
            );
        }
    });

    // -----------------------------------------------------------------------
    // 4. All ValidationAttribute implementations have ruleKey() method
    // -----------------------------------------------------------------------
    it('all validation attributes have ruleKey() returning string', function () {
        $validationAttributes = [
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

        foreach ($validationAttributes as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$class} must have ruleKey()");
            $method = $ref->getMethod('ruleKey');
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull("{$class}::ruleKey() must have return type");
            expect($returnType->getName())->toBe('string');
        }
    });

    // -----------------------------------------------------------------------
    // 5. DataTransferObject abstract contract
    // -----------------------------------------------------------------------
    it('DataTransferObject is abstract with correct methods', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();

        $expectedMethods = [
            'fromArray', 'fromRequest', 'fromJson',
            'fromPartialArray', 'fromPartialRequest',
            'validateArray', 'validatePartialArray',
            'rules', 'rulesFor',
            'toArray', 'allValues', 'toJson',
            'jsonSerialize', 'only', 'except',
            'with', 'equals', 'isEmpty', 'isNotEmpty',
            'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        foreach ($expectedMethods as $method) {
            expect(in_array($method, $methods, true))
                ->toBeTrue("Missing method: {$method}");
        }
    });

    // -----------------------------------------------------------------------
    // 6. DtoCollection implements correct interfaces
    // -----------------------------------------------------------------------
    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $implements = class_implements(DtoCollection::class) ?: [];

        expect($implements)->toContain(\ArrayAccess::class);
        expect($implements)->toContain(\Countable::class);
        expect($implements)->toContain(\IteratorAggregate::class);
        expect($implements)->toContain(\JsonSerializable::class);
    });

    // -----------------------------------------------------------------------
    // 7. DtoCollection has complete API
    // -----------------------------------------------------------------------
    it('DtoCollection has all documented methods', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        $methods = array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $expected = [
            'make', 'push', 'append', 'merge',
            'first', 'last', 'map', 'filter',
            'pluck', 'pluckKey', 'items',
            'toArray', 'allValues',
            'count', 'isEmpty', 'isNotEmpty',
            'getIterator', 'offsetExists', 'offsetGet',
            'offsetSet', 'offsetUnset', 'jsonSerialize',
        ];

        foreach ($expected as $method) {
            expect(in_array($method, $methods, true))
                ->toBeTrue("Missing DtoCollection method: {$method}");
        }
    });

    // -----------------------------------------------------------------------
    // 8. DTOException has named constructors
    // -----------------------------------------------------------------------
    it('DTOException has invalidCast() and invalidJson() named constructors', function () {
        $ref = new ReflectionClass(DTOException::class);

        expect($ref->hasMethod('invalidCast'))->toBeTrue();
        expect($ref->getMethod('invalidCast')->isStatic())->toBeTrue();

        expect($ref->hasMethod('invalidJson'))->toBeTrue();
        expect($ref->getMethod('invalidJson')->isStatic())->toBeTrue();

        // Test factory methods
        $e = DTOException::invalidJson('field', 'Syntax error');
        expect($e)->toBeInstanceOf(DTOException::class);
        expect($e->getMessage())->toContain('field');
        expect($e->getMessage())->toContain('Syntax error');

        $e2 = DTOException::invalidCast('age', 'integer', 'abc');
        expect($e2)->toBeInstanceOf(DTOException::class);
        expect($e2->getMessage())->toContain('age');
        expect($e2->getMessage())->toContain('integer');
    });

    // -----------------------------------------------------------------------
    // 9. Metadata attributes (Hidden, MapFrom, Cast, DefaultValue)
    // -----------------------------------------------------------------------
    it('metadata attributes have correct types', function () {
        // Hidden — no constructor params
        $hiddenRef = new ReflectionClass(Hidden::class);
        $ctor = $hiddenRef->getConstructor();
        expect($ctor)->toBeNull('Hidden should have no constructor');

        // MapFrom — string param
        $mapRef = new ReflectionClass(MapFrom::class);
        $mapProp = $mapRef->getProperty('key');
        expect($mapProp->isReadOnly())->toBeTrue();
        expect($mapProp->getType()->getName())->toBe('string');

        // Cast — string param
        $castRef = new ReflectionClass(Cast::class);
        $castProp = $castRef->getProperty('type');
        expect($castProp->isReadOnly())->toBeTrue();
        expect($castProp->getType()->getName())->toBe('string');

        // DefaultValue — mixed param
        $dvRef = new ReflectionClass(DefaultValue::class);
        $dvProp = $dvRef->getProperty('value');
        expect($dvProp->isReadOnly())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 10. DTOCast implements CastsAttributes
    // -----------------------------------------------------------------------
    it('DTOCast implements CastsAttributes with correct methods', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        $implements = class_implements(\ZeroBoiler\DTO\Casts\DTOCast::class) ?: [];

        expect($implements)->toContain(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        expect($ref->hasMethod('get'))->toBeTrue();
        expect($ref->hasMethod('set'))->toBeTrue();
        expect($ref->hasMethod('serialize'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 11. DTOManager has facade-compatible methods
    // -----------------------------------------------------------------------
    it('DTOManager has validate, make, makeFromJson, schema methods', function () {
        $ref = new ReflectionClass(DTOManager::class);

        expect($ref->hasMethod('validate'))->toBeTrue();
        expect($ref->hasMethod('make'))->toBeTrue();
        expect($ref->hasMethod('makeFromJson'))->toBeTrue();
        expect($ref->hasMethod('schema'))->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // 12. Facade accessor matches service provider binding
    // -----------------------------------------------------------------------
    it('DTO facade accessor matches service provider binding', function () {
        $facadeRef = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        $method = $facadeRef->getMethod('getFacadeAccessor');
        // Read the method source
        $filename = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $lines = array_slice(file($filename), $start - 1, $end - $start + 1);
        $content = implode('', $lines);

        expect($content)->toContain('zeroboiler.dto');

        // Check service provider
        $spRef = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
        $spMethod = $spRef->getMethod('register');
        $spFilename = $spMethod->getFileName();
        $spStart = $spMethod->getStartLine();
        $spEnd = $spMethod->getEndLine();
        $spLines = array_slice(file($spFilename), $spStart - 1, $spEnd - $spStart + 1);
        $spContent = implode('', $spLines);

        expect($spContent)->toContain('zeroboiler.dto');
        expect($spContent)->toContain('singleton');
    });

    // -----------------------------------------------------------------------
    // 13. RequiredWith* attributes normalize string|array fields
    // -----------------------------------------------------------------------
    it('RequiredWith* attributes normalize fields to array', function () {
        $cases = [
            [new RequiredWith('email'), ['email']],
            [new RequiredWith(['email', 'phone']), ['email', 'phone']],
            [new RequiredWithAll('email'), ['email']],
            [new RequiredWithAll(['email', 'phone']), ['email', 'phone']],
            [new RequiredWithout('email'), ['email']],
            [new RequiredWithout(['email', 'phone']), ['email', 'phone']],
            [new RequiredWithoutAll('email'), ['email']],
            [new RequiredWithoutAll(['email', 'phone']), ['email', 'phone']],
        ];

        foreach ($cases as [$attr, $expected]) {
            expect($attr->fields)->toBe($expected);
        }
    });

    // -----------------------------------------------------------------------
    // 14. All attribute classes use readonly properties
    // -----------------------------------------------------------------------
    it('all attribute classes have readonly promoted properties', function () {
        $srcDir = dirname(__DIR__, 2) . '/src/Attributes';
        $files = glob($srcDir . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');
            $fqcn = 'ZeroBoiler\\DTO\\Attributes\\' . $className;

            if (! class_exists($fqcn)) {
                continue;
            }

            $ref = new ReflectionClass($fqcn);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())
                    ->toBeTrue("{$fqcn}::\${$prop->getName()} must be readonly");
            }
        }
    });

    // -----------------------------------------------------------------------
    // 15. OpenApiSchemaGenerator is final with correct static methods
    // -----------------------------------------------------------------------
    it('OpenApiSchemaGenerator has generate and generateWithComponents methods', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();

        expect($ref->hasMethod('generate'))->toBeTrue();
        expect($ref->getMethod('generate')->isStatic())->toBeTrue();

        expect($ref->hasMethod('generateWithComponents'))->toBeTrue();
        expect($ref->getMethod('generateWithComponents')->isStatic())->toBeTrue();
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
