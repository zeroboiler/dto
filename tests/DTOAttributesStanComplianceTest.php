<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
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
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

describe('DTO Attributes PHPStan Level 9 Compliance', function () {
    it('all ValidationAttribute implementations have ruleKey(): string return type', function () {
        $attributes = [
            Accepted::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Date::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            Enum::class,
            In::class,
            Integer::class,
            Json::class,
            Max::class,
            Min::class,
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
            EndsWith::class,
            Url::class,
            Uuid::class,
            Collection::class,
        ];

        foreach ($attributes as $attributeClass) {
            $ref = new ReflectionClass($attributeClass);
            expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue();

            $method = $ref->getMethod('ruleKey');
            $returnType = $method->getReturnType();
            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('string');
            expect($returnType->allowsNull())->toBeFalse();
        }
    });

    it('all attributes have typed readonly constructor parameters', function () {
        $classes = [
            Accepted::class,
            Boolean::class,
            Cast::class,
            Collection::class,
            Confirmed::class,
            Date::class,
            Declined::class,
            DefaultValue::class,
            Different::class,
            Distinct::class,
            Email::class,
            Enum::class,
            Hidden::class,
            In::class,
            Integer::class,
            Json::class,
            MapFrom::class,
            Max::class,
            Min::class,
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
            EndsWith::class,
            Url::class,
            Uuid::class,
            Between::class,
        ];

        foreach ($classes as $className) {
            $ref = new ReflectionClass($className);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                // Hidden has no constructor — that's valid
                expect($className)->toBe(Hidden::class);

                continue;
            }

            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                expect($type)->not->toBeNull("{$className}::\${$param->getName()} must have a type declaration");

                $reflectionProperty = $ref->getProperty($param->getName());
                expect($reflectionProperty->isReadOnly())->toBeTrue(
                    "{$className}::\${$param->getName()} must be readonly"
                );
            }
        }
    });

    it('no attribute class is missing strict_types declaration', function () {
        $attributeDir = dirname(__DIR__) . '/src/Attributes';
        $files = glob($attributeDir . '/*.php');

        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });

    it('all attribute classes are final', function () {
        $attributeDir = dirname(__DIR__) . '/src/Attributes';
        $files = glob($attributeDir . '/*.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fqcn = "ZeroBoiler\\DTO\\Attributes\\{$className}";
            $ref = new ReflectionClass($fqcn);
            expect($ref->isFinal())->toBeTrue("{$fqcn} must be final");
        }
    });

    it('all attributes have #[Attribute] with correct target', function () {
        $attributeDir = dirname(__DIR__) . '/src/Attributes';
        $files = glob($attributeDir . '/*.php');

        $metadataOnly = [Hidden::class, Cast::class, DefaultValue::class, MapFrom::class];
        $validationAttributes = [];

        // Build list of all classes
        $allClasses = [];
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fqcn = "ZeroBoiler\\DTO\\Attributes\\{$className}";
            $allClasses[] = $fqcn;
        }

        // Determine which implement ValidationAttribute
        foreach ($allClasses as $fqcn) {
            $ref = new ReflectionClass($fqcn);
            if ($ref->implementsInterface(ValidationAttribute::class)) {
                $validationAttributes[] = $fqcn;
            }
        }

        foreach ($validationAttributes as $fqcn) {
            $ref = new ReflectionClass($fqcn);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty("{$fqcn} must have #[Attribute]");

            $attrInstance = $attrs[0]->newInstance();
            $flags = $attrInstance->getFlags();
            expect($flags & \Attribute::TARGET_PROPERTY)->not->toBe(0,
                "{$fqcn} must target properties");
        }
    });

    it('ruleKey returns non-empty string for all validation attributes', function () {
        $attributes = [
            new Accepted(),
            new Between(1, 10),
            new Boolean(),
            new Confirmed(),
            new Date(),
            new Declined(),
            new Different('other'),
            new Distinct(),
            new Email(),
            new Enum('SomeEnum'),
            new In(['a', 'b']),
            new Integer(),
            new Json(),
            new Max(255),
            new Min(1),
            new NestedArray('SomeDTO'),
            new Nullable(),
            new Numeric(),
            new Pattern('/^test$/'),
            new Present(),
            new Prohibited(),
            new Required(),
            new RequiredIf('field', 'value'),
            new RequiredUnless('field', 'value'),
            new RequiredWith(['field']),
            new RequiredWithAll(['field']),
            new RequiredWithout(['field']),
            new RequiredWithoutAll(['field']),
            new Same('field'),
            new Size(5),
            new Sometimes(),
            new StartsWith('prefix'),
            new EndsWith('suffix'),
            new Url(),
            new Uuid(),
            new Collection('SomeDTO'),
        ];

        foreach ($attributes as $attr) {
            expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });
});
