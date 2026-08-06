<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
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

describe('ValidationAttribute Contract Compliance', function () {
    $validationAttributes = [
        'Required' => new Required,
        'Email' => new Email,
        'Url' => new Url,
        'Uuid' => new Uuid,
        'Max' => new Max(255),
        'Min' => new Min(3),
        'Size' => new Size(10),
        'Pattern' => new Pattern('/^[a-z]+$/'),
        'In' => new In(['a', 'b', 'c']),
        'Enum' => new EnumAttribute(\BackedEnum::class),
        'Integer' => new Integer,
        'Numeric' => new Numeric,
        'Boolean' => new Boolean,
        'Date' => new Date,
        'Date with format' => new Date('Y-m-d'),
        'Json' => new Json,
        'StartsWith' => new StartsWith('https://'),
        'StartsWith array' => new StartsWith(['+90', '+1']),
        'EndsWith' => new EndsWith('@example.com'),
        'EndsWith array' => new EndsWith(['@a.com', '@b.com']),
        'Accepted' => new Accepted,
        'Declined' => new Declined,
        'Confirmed' => new Confirmed,
        'Distinct' => new Distinct,
        'Prohibited' => new Prohibited,
        'Present' => new Present,
        'Sometimes' => new Sometimes,
        'Nullable' => new Nullable,
        'Same' => new Same('password'),
        'Different' => new Different('email'),
        'RequiredIf' => new RequiredIf('type', 'individual'),
        'RequiredUnless' => new RequiredUnless('type', 'company'),
        'RequiredWith' => new RequiredWith(['email']),
        'RequiredWith array' => new RequiredWith('email'),
        'RequiredWithAll' => new RequiredWithAll(['email', 'phone']),
        'RequiredWithout' => new RequiredWithout(['email']),
        'RequiredWithoutAll' => new RequiredWithoutAll(['email', 'phone']),
        'ArrayRule' => new ArrayRule,
        'ArrayRule min/max' => new ArrayRule(min: 1, max: 10),
        'Between' => new Between(1, 100),
        'Between float' => new Between(0.5, 99.9),
        'NestedArray' => new NestedArray(\ZeroBoiler\DTO\DataTransferObject::class),
        'Collection' => new \ZeroBoiler\DTO\Attributes\Collection(\ZeroBoiler\DTO\DataTransferObject::class),
    ];

    it('all validation attributes implement ValidationAttribute interface', function () use ($validationAttributes) {
        foreach ($validationAttributes as $name => $instance) {
            expect($instance)->toBeInstanceOf(ValidationAttribute::class, "Failed for: {$name}");
        }
    });

    it('all validation attributes return non-empty ruleKey()', function () use ($validationAttributes) {
        foreach ($validationAttributes as $name => $instance) {
            expect($instance->ruleKey())->toBeString()
                ->and($instance->ruleKey())->not->toBeEmpty("Empty ruleKey for: {$name}");
        }
    });

    it('ruleKey values match expected Laravel rule names', function () use ($validationAttributes) {
        $expected = [
            'Required' => 'required',
            'Email' => 'email',
            'Url' => 'url',
            'Uuid' => 'uuid',
            'Max' => 'max',
            'Min' => 'min',
            'Size' => 'size',
            'Pattern' => 'regex',
            'In' => 'in',
            'Enum' => 'enum',
            'Integer' => 'integer',
            'Numeric' => 'numeric',
            'Boolean' => 'boolean',
            'Date' => 'date',
            'Date with format' => 'date',
            'Json' => 'json',
            'StartsWith' => 'starts_with',
            'StartsWith array' => 'starts_with',
            'EndsWith' => 'ends_with',
            'EndsWith array' => 'ends_with',
            'Accepted' => 'accepted',
            'Declined' => 'declined',
            'Confirmed' => 'confirmed',
            'Distinct' => 'distinct',
            'Prohibited' => 'prohibited',
            'Present' => 'present',
            'Sometimes' => 'sometimes',
            'Nullable' => 'nullable',
            'Same' => 'same',
            'Different' => 'different',
            'RequiredIf' => 'required_if',
            'RequiredUnless' => 'required_unless',
            'RequiredWith' => 'required_with',
            'RequiredWith array' => 'required_with',
            'RequiredWithAll' => 'required_with_all',
            'RequiredWithout' => 'required_without',
            'RequiredWithoutAll' => 'required_without_all',
            'ArrayRule' => 'array',
            'ArrayRule min/max' => 'array',
            'Between' => 'between',
            'Between float' => 'between',
            'NestedArray' => 'array',
            'Collection' => 'array',
        ];

        foreach ($expected as $name => $ruleKey) {
            expect($validationAttributes[$name]->ruleKey())->toBe($ruleKey, "Mismatch for: {$name}");
        }
    });
});

describe('Non-validation attributes do not implement ValidationAttribute', function () {
    $nonValidationAttributes = [
        'Hidden' => new Hidden,
        'MapFrom' => new MapFrom('source_key'),
    ];

    it('non-validation attributes do not implement ValidationAttribute', function () use ($nonValidationAttributes) {
        foreach ($nonValidationAttributes as $name => $instance) {
            expect($instance)->not->toBeInstanceOf(ValidationAttribute::class, "Failed for: {$name}");
        }
    });
});

describe('ValidationAttribute message property', function () {
    it('accepts custom message on validation attributes', function () {
        $email = new Email(message: 'Custom email error');
        $required = new Required(message: 'This field is required');

        expect($email->message)->toBe('Custom email error');
        expect($required->message)->toBe('This field is required');
    });

    it('defaults message to null', function () {
        $email = new Email;
        $max = new Max(255);

        expect($email->message)->toBeNull();
        expect($max->message)->toBeNull();
    });
});
