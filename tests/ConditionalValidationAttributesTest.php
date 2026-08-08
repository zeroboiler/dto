<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

beforeEach(function (): void {
    $container = Container::getInstance();
    $loader = new ArrayLoader;
    $translator = new Translator($loader, 'en');
    $factory = new Factory($translator);
    $container->instance(ValidationFactory::class, $factory);
    $container->instance('validator', $factory);
    Validator::swap($factory);
});

describe('Required validation attributes', function (): void {
    it('Required implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Required;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('required')
            ->and($attr->message)->toBeNull();
    });

    it('Required accepts custom message', function (): void {
        $attr = new Required('Custom required message');
        expect($attr->message)->toBe('Custom required message');
    });

    it('Accepted implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Accepted;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('accepted')
            ->and($attr->message)->toBeNull();
    });

    it('Accepted accepts custom message', function (): void {
        $attr = new Accepted('Accept the terms');
        expect($attr->message)->toBe('Accept the terms');
    });

    it('Declined implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Declined;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('declined');
    });

    it('Distinct implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Distinct;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('distinct');
    });

    it('Present implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Present;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('present');
    });

    it('Prohibited implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Prohibited;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('prohibited');
    });

    it('Sometimes implements ValidationAttribute and has correct ruleKey', function (): void {
        $attr = new Sometimes;
        expect($attr)->toBeInstanceOf(ValidationAttribute::class)
            ->and($attr->ruleKey())->toBe('sometimes');
    });
});

describe('RequiredIf attribute', function (): void {
    it('has correct ruleKey', function (): void {
        $attr = new RequiredIf('type', 'individual');
        expect($attr->ruleKey())->toBe('required_if')
            ->and($attr->field)->toBe('type')
            ->and($attr->value)->toBe('individual');
    });

    it('accepts null value', function (): void {
        $attr = new RequiredIf('status');
        expect($attr->value)->toBeNull();
    });

    it('accepts array value', function (): void {
        $attr = new RequiredIf('role', ['admin', 'super']);
        expect($attr->value)->toBe(['admin', 'super']);
    });

    it('accepts int value', function (): void {
        $attr = new RequiredIf('count', 0);
        expect($attr->value)->toBe(0);
    });

    it('accepts bool value', function (): void {
        $attr = new RequiredIf('active', true);
        expect($attr->value)->toBe(true);
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredIf('type', 'company', 'Company name is required');
        expect($attr->message)->toBe('Company name is required');
    });
});

describe('RequiredUnless attribute', function (): void {
    it('has correct ruleKey', function (): void {
        $attr = new RequiredUnless('type', 'company');
        expect($attr->ruleKey())->toBe('required_unless')
            ->and($attr->field)->toBe('type')
            ->and($attr->value)->toBe('company');
    });

    it('accepts null value', function (): void {
        $attr = new RequiredUnless('status');
        expect($attr->value)->toBeNull();
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredUnless('type', 'personal', 'Personal name required');
        expect($attr->message)->toBe('Personal name required');
    });
});

describe('RequiredWith attribute', function (): void {
    it('has correct ruleKey with string input', function (): void {
        $attr = new RequiredWith('email');
        expect($attr->ruleKey())->toBe('required_with')
            ->and($attr->fields)->toBe(['email']);
    });

    it('normalizes array input', function (): void {
        $attr = new RequiredWith(['email', 'phone']);
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredWith('email', 'Username needed with email');
        expect($attr->message)->toBe('Username needed with email');
    });
});

describe('RequiredWithAll attribute', function (): void {
    it('has correct ruleKey with string input', function (): void {
        $attr = new RequiredWithAll('email');
        expect($attr->ruleKey())->toBe('required_with_all')
            ->and($attr->fields)->toBe(['email']);
    });

    it('normalizes array input', function (): void {
        $attr = new RequiredWithAll(['email', 'phone']);
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredWithAll(['email', 'phone'], 'All fields needed');
        expect($attr->message)->toBe('All fields needed');
    });
});

describe('RequiredWithout attribute', function (): void {
    it('has correct ruleKey with string input', function (): void {
        $attr = new RequiredWithout('email');
        expect($attr->ruleKey())->toBe('required_without')
            ->and($attr->fields)->toBe(['email']);
    });

    it('normalizes array input', function (): void {
        $attr = new RequiredWithout(['email', 'phone']);
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredWithout('email', 'Name needed without email');
        expect($attr->message)->toBe('Name needed without email');
    });
});

describe('RequiredWithoutAll attribute', function (): void {
    it('has correct ruleKey with string input', function (): void {
        $attr = new RequiredWithoutAll('email');
        expect($attr->ruleKey())->toBe('required_without_all')
            ->and($attr->fields)->toBe(['email']);
    });

    it('normalizes array input', function (): void {
        $attr = new RequiredWithoutAll(['email', 'phone']);
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('handles empty array default', function (): void {
        $attr = new RequiredWithoutAll;
        expect($attr->fields)->toBe([]);
    });

    it('accepts custom message', function (): void {
        $attr = new RequiredWithoutAll(['email', 'phone'], 'Name needed without any contact');
        expect($attr->message)->toBe('Name needed without any contact');
    });
});

describe('Attribute final class enforcement', function (): void {
    it('all Required* attributes are final', function (): void {
        expect(new ReflectionClass(Required::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredIf::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredUnless::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredWith::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredWithAll::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredWithout::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(RequiredWithoutAll::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Accepted::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Declined::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Distinct::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Present::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Prohibited::class))->isFinal()->toBeTrue();
        expect(new ReflectionClass(Sometimes::class))->isFinal()->toBeTrue();
    });
});
