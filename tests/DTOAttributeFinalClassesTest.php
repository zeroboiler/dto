<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Attributes;

use Attribute;
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
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

describe('DTO attribute final classes', function () {
    it('all validation attributes are final', function () {
        $classes = [
            Accepted::class, ArrayRule::class, Between::class, Boolean::class,
            Confirmed::class, Date::class, Declined::class, Distinct::class,
            Email::class, EndsWith::class, EnumAttr::class, In::class,
            Integer::class, Numeric::class, Pattern::class, Prohibited::class,
            Present::class, Required::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, Same::class, Different::class, Size::class,
            Sometimes::class, StartsWith::class, Nullable::class, Json::class,
            Uuid::class, Url::class, NestedArray::class, Collection::class,
        ];

        foreach ($classes as $class) {
            expect((new \ReflectionClass($class))->isFinal())
                ->toBeTrue("Expected {$class} to be final");
        }
    });

    it('all metadata attributes are final', function () {
        $classes = [
            Cast::class, Hidden::class, MapFrom::class, DefaultValue::class,
        ];

        foreach ($classes as $class) {
            expect((new \ReflectionClass($class))->isFinal())
                ->toBeTrue("Expected {$class} to be final");
        }
    });
});

describe('DTO attribute strict types', function () {
    it('all attribute files have declare strict_types', function () {
        $classes = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, EnumAttr::class, Url::class, Uuid::class, Integer::class,
            Numeric::class, Boolean::class, Date::class, StartsWith::class,
            EndsWith::class, Cast::class, MapFrom::class, Hidden::class,
            DefaultValue::class, NestedArray::class, Collection::class,
            Between::class, Same::class, Different::class, Confirmed::class,
            Prohibited::class, Present::class, Declined::class, Accepted::class,
            Distinct::class, Sometimes::class, Nullable::class, Size::class,
            Json::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, ArrayRule::class,
        ];

        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $file = $reflection->getFileName();
            $contents = file_get_contents($file);
            expect($contents)->toContain('declare(strict_types=1)');
        }
    });
});

describe('DTO ValidationAttribute contract compliance', function () {
    it('validation attributes implement ValidationAttribute interface', function () {
        $validationClasses = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, EnumAttr::class, Url::class, Uuid::class, Integer::class,
            Numeric::class, Boolean::class, Date::class, StartsWith::class,
            EndsWith::class, Confirmed::class, Different::class, Same::class,
            Between::class, Prohibited::class, Present::class, Declined::class,
            Accepted::class, Distinct::class, Sometimes::class, Nullable::class,
            Size::class, Json::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, ArrayRule::class, NestedArray::class,
            Collection::class,
        ];

        foreach ($validationClasses as $class) {
            expect($class)->toImplement(ValidationAttribute::class);
        }
    });

    it('each ValidationAttribute returns a non-empty string from ruleKey()', function () {
        $attributes = [
            new Required, new Email, new Max(255), new Min(2),
            new Pattern('/test/'), new In(['a', 'b']), new Url, new Uuid,
            new Integer, new Numeric, new Boolean, new Date,
            new StartsWith('pre'), new EndsWith('suf'), new Confirmed,
            new Different('field'), new Same('field'), new Between(1, 10),
            new Prohibited, new Present, new Declined, new Accepted,
            new Distinct, new Sometimes, new Nullable, new Size(5),
            new Json, new RequiredIf('field', 'value'), new RequiredUnless('field', 'value'),
            new RequiredWith('field'), new RequiredWithAll('a', 'b'),
            new RequiredWithout('field'), new RequiredWithoutAll('a', 'b'),
            new ArrayRule, new NestedArray(\ZeroBoiler\DTO\DataTransferObject::class),
            new Collection(\ZeroBoiler\DTO\DataTransferObject::class),
        ];

        foreach ($attributes as $attr) {
            expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('DTO attribute target constraints', function () {
    it('validation attributes target properties only', function () {
        $propertyAttrs = [
            Required::class, Email::class, Max::class, Min::class, Pattern::class,
            In::class, EnumAttr::class, Url::class, Uuid::class, Integer::class,
            Numeric::class, Boolean::class, Date::class, StartsWith::class,
            EndsWith::class, Confirmed::class, Different::class, Same::class,
            Between::class, Prohibited::class, Present::class, Declined::class,
            Accepted::class, Distinct::class, Sometimes::class, Nullable::class,
            Size::class, Json::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class, ArrayRule::class, NestedArray::class,
            Collection::class,
        ];

        foreach ($propertyAttrs as $class) {
            $ref = new \ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->not->toBeEmpty();
            $flags = $attrs[0]->newInstance()->getFlags();
            expect($flags)->toBe(Attribute::TARGET_PROPERTY);
        }
    });

    it('metadata attributes target properties only', function () {
        $metaAttrs = [Cast::class, Hidden::class, MapFrom::class, DefaultValue::class];

        foreach ($metaAttrs as $class) {
            $ref = new \ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);
            expect($attrs)->not->toBeEmpty();
            $flags = $attrs[0]->newInstance()->getFlags();
            expect($flags)->toBe(Attribute::TARGET_PROPERTY);
        }
    });
});
