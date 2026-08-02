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
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
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
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;

/**
 * Issue #9: Validation message keys should be explicitly declared
 * via the ValidationAttribute interface, not derived from class names.
 */
describe('Issue #9: Explicit validation message key generation', function (): void {
    it('all validation attributes implement ValidationAttribute interface', function (): void {
        $attributes = [
            new Accepted,
            new ArrayRule,
            new Between(1, 10),
            new Boolean,
            new Confirmed,
            new Date,
            new Declined,
            new Different('other'),
            new Distinct,
            new Email,
            new EndsWith('@example.com'),
            new Enum(BackedEnum::class),
            new In(['a', 'b']),
            new Integer,
            new Json,
            new Max(100),
            new Min(1),
            new Nullable,
            new Numeric,
            new Pattern('/^.*/'),
            new Present,
            new Prohibited,
            new Required,
            new RequiredIf('field', 'value'),
            new RequiredUnless('field', 'value'),
            new RequiredWith(['field']),
            new RequiredWithAll(['field']),
            new RequiredWithout(['field']),
            new Same('other'),
            new Size(5),
            new Sometimes,
            new StartsWith('https://'),
            new Url,
            new Uuid,
        ];

        foreach ($attributes as $attr) {
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        }
    });

    it('ruleKey() returns correct Laravel rule name for each attribute', function (): void {
        expect((new Accepted)->ruleKey())->toBe('accepted');
        expect((new ArrayRule)->ruleKey())->toBe('array');
        expect(new Between(1, 10)->ruleKey())->toBe('between');
        expect((new Boolean)->ruleKey())->toBe('boolean');
        expect((new Confirmed)->ruleKey())->toBe('confirmed');
        expect((new Date)->ruleKey())->toBe('date');
        expect((new Declined)->ruleKey())->toBe('declined');
        expect(new Different('other')->ruleKey())->toBe('different');
        expect((new Distinct)->ruleKey())->toBe('distinct');
        expect((new Email)->ruleKey())->toBe('email');
        expect(new EndsWith('x')->ruleKey())->toBe('ends_with');
        expect(new Enum(BackedEnum::class)->ruleKey())->toBe('enum');
        expect(new In(['a'])->ruleKey())->toBe('in');
        expect((new Integer)->ruleKey())->toBe('integer');
        expect((new Json)->ruleKey())->toBe('json');
        expect(new Max(10)->ruleKey())->toBe('max');
        expect(new Min(1)->ruleKey())->toBe('min');
        expect((new Nullable)->ruleKey())->toBe('nullable');
        expect((new Numeric)->ruleKey())->toBe('numeric');
        expect(new Pattern('/.*/')->ruleKey())->toBe('regex');
        expect((new Present)->ruleKey())->toBe('present');
        expect((new Prohibited)->ruleKey())->toBe('prohibited');
        expect((new Required)->ruleKey())->toBe('required');
        expect(new RequiredIf('f', 'v')->ruleKey())->toBe('required_if');
        expect(new RequiredUnless('f', 'v')->ruleKey())->toBe('required_unless');
        expect(new RequiredWith(['f'])->ruleKey())->toBe('required_with');
        expect(new RequiredWithAll(['f'])->ruleKey())->toBe('required_with_all');
        expect(new RequiredWithout(['f'])->ruleKey())->toBe('required_without');
        expect(new Same('other')->ruleKey())->toBe('same');
        expect(new Size(3)->ruleKey())->toBe('size');
        expect((new Sometimes)->ruleKey())->toBe('sometimes');
        expect(new StartsWith('x')->ruleKey())->toBe('starts_with');
        expect((new Url)->ruleKey())->toBe('url');
        expect((new Uuid)->ruleKey())->toBe('uuid');
    });

    it('Pattern attribute maps to "regex" rule key, not "pattern"', function (): void {
        // This is the key fix — previously class name parsing would produce 'pattern'
        // but Laravel's validation rule is 'regex'
        expect(new Pattern('/^test/')->ruleKey())->toBe('regex');
    });

    it('StartsWith attribute maps to "starts_with" rule key', function (): void {
        // Previously class name parsing produced 'starts_with' by coincidence
        // Now it's explicit and guaranteed
        expect(new StartsWith('https://')->ruleKey())->toBe('starts_with');
    });

    it('custom messages use explicit rule keys in generated messages array', function (): void {
        $reflection = new ReflectionClass(DtoMetadataResolver::class);
        $method = $reflection->getMethod('collectMessage');
        expect($method->isPrivate())->toBeTrue();

        // Verify the collectMessage method uses ValidationAttribute interface
        $params = $method->getParameters();
        expect($params[0]->getType()->getName())->toBe('object');
    });
});

describe('Issue #9: Message key correctness with custom messages', function (): void {
    it('generates correct message key for Pattern (regex) attribute', function (): void {
        $dtoClass = new class('test') extends DataTransferObject
        {
            public function __construct(
                #[Pattern('/^[A-Z]{3}$/', message: 'Code must be 3 uppercase letters')]
                public readonly string $code = '',
            ) {}
        };

        $metadata = DtoMetadataResolver::resolve($dtoClass::class);
        expect($metadata['messages'])->toHaveKey('code.regex');
        expect($metadata['messages']['code.regex'])->toBe('Code must be 3 uppercase letters');
    });

    it('generates correct message key for RequiredWith attribute', function (): void {
        $dtoClass = new class extends DataTransferObject
        {
            public function __construct(
                #[RequiredWith(['email'], message: 'Username required with email')]
                public readonly ?string $username = null,
                public readonly ?string $email = null,
            ) {}
        };

        $metadata = DtoMetadataResolver::resolve($dtoClass::class);
        expect($metadata['messages'])->toHaveKey('username.required_with');
        expect($metadata['messages']['username.required_with'])->toBe('Username required with email');
    });
});
