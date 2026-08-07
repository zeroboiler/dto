<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

/**
 * Tests that verify PHPStan Level 9 compliance for DTO validation attributes.
 *
 * Focuses on:
 * - All ValidationAttribute implementations return valid ruleKey()
 * - Nullable message parameter is properly typed
 * - RequiredIf/RequiredUnless with mixed value types
 * - StartsWith/EndsWith with string|array prefix
 * - In attribute with array of string|int values
 * - Pattern attribute with regex string
 * - Between with int|float min/max
 * - Nested DTO hydration with type safety
 * - Collection hydration with DtoCollection wrapper
 */
describe('DTO Validation Attributes PHPStan L9 Compliance', function () {
    it('all validation attributes implement ValidationAttribute interface', function (): void {
        $attributes = [
            Required::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            Max::class,
            Min::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            Between::class,
            Pattern::class,
            In::class,
            EnumAttribute::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Date::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            StartsWith::class,
            EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            RequiredIf::class,
            RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            NestedArray::class,
            Collection::class,
        ];

        foreach ($attributes as $attrClass) {
            expect($attrClass)->toBeString();
            expect(class_implements($attrClass))->toContain(ValidationAttribute::class);
        }
    });

    it('all validation attributes return non-empty ruleKey', function (): void {
        $instances = [
            new Required,
            new \ZeroBoiler\DTO\Attributes\Email,
            new \ZeroBoiler\DTO\Attributes\Url,
            new \ZeroBoiler\DTO\Attributes\Uuid,
            new Max(255),
            new Min(1),
            new \ZeroBoiler\DTO\Attributes\Size(5),
            new Between(1, 100),
            new Pattern('/^[a-z]+$/'),
            new In(['a', 'b', 'c']),
            new EnumAttribute(UserStatus::class),
            new \ZeroBoiler\DTO\Attributes\Integer,
            new \ZeroBoiler\DTO\Attributes\Numeric,
            new \ZeroBoiler\DTO\Attributes\Boolean,
            new \ZeroBoiler\DTO\Attributes\Date,
            new \ZeroBoiler\DTO\Attributes\Date('Y-m-d'),
            new \ZeroBoiler\DTO\Attributes\Json,
            new StartsWith('https://'),
            new StartsWith(['+90', '+1']),
            new EndsWith('@example.com'),
            new EndsWith(['.com', '.org']),
            new \ZeroBoiler\DTO\Attributes\Accepted,
            new \ZeroBoiler\DTO\Attributes\Declined,
            new \ZeroBoiler\DTO\Attributes\Confirmed,
            new \ZeroBoiler\DTO\Attributes\Distinct,
            new \ZeroBoiler\DTO\Attributes\Prohibited,
            new \ZeroBoiler\DTO\Attributes\Present,
            new \ZeroBoiler\DTO\Attributes\Sometimes,
            new \ZeroBoiler\DTO\Attributes\Nullable,
            new \ZeroBoiler\DTO\Attributes\Same('password'),
            new \ZeroBoiler\DTO\Attributes\Different('email'),
            new RequiredIf('type', 'individual'),
            new RequiredIf('type', true),
            new RequiredIf('type', 1),
            new RequiredUnless('role', 'admin'),
            new RequiredUnless('role', ['admin', 'super']),
            new \ZeroBoiler\DTO\Attributes\RequiredWith('email'),
            new \ZeroBoiler\DTO\Attributes\RequiredWithAll(['email', 'phone']),
            new \ZeroBoiler\DTO\Attributes\RequiredWithout('email'),
            new \ZeroBoiler\DTO\Attributes\RequiredWithoutAll(['email', 'phone']),
            new \ZeroBoiler\DTO\Attributes\ArrayRule,
            new \ZeroBoiler\DTO\Attributes\ArrayRule(min: 1, max: 10),
            new NestedArray(AddressDTO::class),
            new Collection(OrderItemDTO::class),
        ];

        foreach ($instances as $instance) {
            expect($instance)->toBeInstanceOf(ValidationAttribute::class);
            $key = $instance->ruleKey();
            expect($key)->toBeString();
            expect($key)->not->toBeEmpty();
        }
    });

    it('Between accepts int and float min/max', function (): void {
        $intBetween = new Between(1, 100);
        expect($intBetween->min)->toBe(1);
        expect($intBetween->max)->toBe(100);
        expect($intBetween->ruleKey())->toBe('between');

        $floatBetween = new Between(0.5, 99.9);
        expect($floatBetween->min)->toBe(0.5);
        expect($floatBetween->max)->toBe(99.9);
    });

    it('StartsWith accepts string or array', function (): void {
        $stringPrefix = new StartsWith('https://');
        expect($stringPrefix->prefix)->toBe('https://');

        $arrayPrefix = new StartsWith(['+90', '+1', '+44']);
        expect($arrayPrefix->prefix)->toBe(['+90', '+1', '+44']);
    });

    it('EndsWith accepts string or array', function (): void {
        $stringSuffix = new EndsWith('@gmail.com');
        expect($stringSuffix->suffix)->toBe('@gmail.com');

        $arraySuffix = new EndsWith(['.com', '.org', '.net']);
        expect($arraySuffix->suffix)->toBe(['.com', '.org', '.net']);
    });

    it('In accepts array of string or int values', function (): void {
        $stringIn = new In(['draft', 'published', 'archived']);
        expect($stringIn->values)->toBe(['draft', 'published', 'archived']);

        $intIn = new In([1, 2, 3]);
        expect($intIn->values)->toBe([1, 2, 3]);
    });

    it('RequiredIf accepts mixed value types', function (): void {
        $stringValue = new RequiredIf('type', 'individual');
        expect($stringValue->value)->toBe('individual');

        $boolValue = new RequiredIf('agree', true);
        expect($boolValue->value)->toBe(true);

        $intValue = new RequiredIf('count', 5);
        expect($intValue->value)->toBe(5);

        $arrayValue = new RequiredIf('role', ['admin', 'super']);
        expect($arrayValue->value)->toBe(['admin', 'super']);

        $nullValue = new RequiredIf('optional', null);
        expect($nullValue->value)->toBeNull();
    });

    it('RequiredUnless accepts mixed value types', function (): void {
        $stringValue = new RequiredUnless('role', 'admin');
        expect($stringValue->value)->toBe('admin');

        $boolValue = new RequiredUnless('skip', false);
        expect($boolValue->value)->toBe(false);
    });

    it('message parameter is nullable string on all attributes', function (): void {
        $attributesWithMessage = [
            new Required(message: 'Field is required'),
            new Max(255, message: 'Too long'),
            new Min(1, message: 'Too short'),
            new \ZeroBoiler\DTO\Attributes\Email(message: 'Invalid email'),
            new Pattern('/^[a-z]+$/', message: 'Invalid format'),
            new In(['a', 'b'], message: 'Invalid value'),
            new EnumAttribute(UserStatus::class, message: 'Invalid status'),
            new StartsWith('https://', message: 'Must start with https'),
            new EndsWith('.com', message: 'Must end with .com'),
            new RequiredIf('type', 'x', message: 'Required when x'),
            new RequiredUnless('type', 'y', message: 'Required unless y'),
            new Between(1, 10, message: 'Out of range'),
            new \ZeroBoiler\DTO\Attributes\Boolean(message: 'Must be boolean'),
            new \ZeroBoiler\DTO\Attributes\Date(message: 'Invalid date'),
            new \ZeroBoiler\DTO\Attributes\Uuid(message: 'Invalid UUID'),
            new \ZeroBoiler\DTO\Attributes\Url(message: 'Invalid URL'),
            new \ZeroBoiler\DTO\Attributes\Integer(message: 'Must be integer'),
            new \ZeroBoiler\DTO\Attributes\Numeric(message: 'Must be numeric'),
            new \ZeroBoiler\DTO\Attributes\Json(message: 'Invalid JSON'),
            new \ZeroBoiler\DTO\Attributes\Accepted(message: 'Must be accepted'),
            new \ZeroBoiler\DTO\Attributes\Declined(message: 'Must be declined'),
            new \ZeroBoiler\DTO\Attributes\Confirmed(message: 'Must be confirmed'),
            new \ZeroBoiler\DTO\Attributes\Distinct(message: 'Must be unique'),
            new \ZeroBoiler\DTO\Attributes\Prohibited(message: 'Must be absent'),
            new \ZeroBoiler\DTO\Attributes\Present(message: 'Must be present'),
            new \ZeroBoiler\DTO\Attributes\Sometimes(message: 'Only if present'),
            new \ZeroBoiler\DTO\Attributes\Nullable(message: 'May be null'),
            new \ZeroBoiler\DTO\Attributes\Same('field', message: 'Must match'),
            new \ZeroBoiler\DTO\Attributes\Different('field', message: 'Must differ'),
            new \ZeroBoiler\DTO\Attributes\ArrayRule(message: 'Must be array'),
            new \ZeroBoiler\DTO\Attributes\ArrayRule(min: 1, max: 10, message: 'Array size 1-10'),
            new \ZeroBoiler\DTO\Attributes\Size(5, message: 'Must be 5'),
            new NestedArray(AddressDTO::class, message: 'Invalid nested'),
            new Collection(OrderItemDTO::class, message: 'Invalid collection'),
        ];

        foreach ($attributesWithMessage as $attr) {
            $message = $attr->message;
            expect($message)->toBeString();
            expect($message)->not->toBeEmpty();
        }
    });

    it('MapFrom attribute stores key as readonly string', function (): void {
        $mapFrom = new MapFrom('user_name');
        expect($mapFrom->key)->toBe('user_name');
    });

    it('Hidden attribute has no parameters', function (): void {
        $hidden = new \ZeroBoiler\DTO\Attributes\Hidden;
        expect($hidden)->toBeInstanceOf(\ZeroBoiler\DTO\Attributes\Hidden::class);
    });

    it('Cast attribute stores type as readonly string', function (): void {
        $cast = new \ZeroBoiler\DTO\Attributes\Cast('integer');
        expect($cast->type)->toBe('integer');
    });

    it('DefaultValue stores mixed value', function (): void {
        $stringDefault = new \ZeroBoiler\DTO\Attributes\DefaultValue('active');
        expect($stringDefault->value)->toBe('active');

        $arrayDefault = new \ZeroBoiler\DTO\Attributes\DefaultValue([]);
        expect($arrayDefault->value)->toBe([]);

        $nullDefault = new \ZeroBoiler\DTO\Attributes\DefaultValue(null);
        expect($nullDefault->value)->toBeNull();

        $intDefault = new \ZeroBoiler\DTO\Attributes\DefaultValue(42);
        expect($intDefault->value)->toBe(42);
    });
});
