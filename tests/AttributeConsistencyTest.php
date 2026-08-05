<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;

describe('ValidationAttribute interface compliance', function (): void {
    it('Required implements ValidationAttribute', function (): void {
        expect(new Required)->toBeInstanceOf(ValidationAttribute::class);
    });

    it('RequiredWithoutAll implements ValidationAttribute', function (): void {
        $attr = new RequiredWithoutAll('email', 'phone');
        expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        expect($attr->ruleKey())->toBe('required_without_all');
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('RequiredWithoutAll accepts array of fields', function (): void {
        $attr = new RequiredWithoutAll(['email', 'phone']);
        expect($attr->fields)->toBe(['email', 'phone']);
    });

    it('RequiredWithoutAll accepts single string field', function (): void {
        $attr = new RequiredWithoutAll('email');
        expect($attr->fields)->toBe(['email']);
    });

    it('RequiredWithoutAll fields are readonly', function (): void {
        $attr = new RequiredWithoutAll('email');
        // Reflection check: property should be readonly
        $ref = new \ReflectionProperty($attr, 'fields');
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('RequiredWith implements ValidationAttribute', function (): void {
        $attr = new RequiredWith('email', 'phone');
        expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        expect($attr->ruleKey())->toBe('required_with');
    });

    it('RequiredWith fields are readonly', function (): void {
        $attr = new RequiredWith('email');
        $ref = new \ReflectionProperty($attr, 'fields');
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('RequiredWithAll implements ValidationAttribute', function (): void {
        $attr = new RequiredWithAll(['email', 'phone']);
        expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        expect($attr->ruleKey())->toBe('required_with_all');
    });

    it('RequiredWithAll fields are readonly', function (): void {
        $attr = new RequiredWithAll('email');
        $ref = new \ReflectionProperty($attr, 'fields');
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('RequiredWithout implements ValidationAttribute', function (): void {
        $attr = new RequiredWithout('email');
        expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        expect($attr->ruleKey())->toBe('required_without');
    });

    it('RequiredWithout fields are readonly', function (): void {
        $attr = new RequiredWithout('email');
        $ref = new \ReflectionProperty($attr, 'fields');
        expect($ref->isReadOnly())->toBeTrue();
    });
});

describe('Metadata attribute properties', function (): void {
    it('Hidden is a simple attribute with no constructor args', function (): void {
        $attr = new Hidden;
        $ref = new \ReflectionClass($attr);
        expect($ref->getConstructor())->toBeNull();
    });

    it('DefaultValue stores mixed value', function (): void {
        $attr = new DefaultValue('active');
        expect($attr->value)->toBe('active');
    });

    it('DefaultValue stores array value', function (): void {
        $attr = new DefaultValue(['a', 'b']);
        expect($attr->value)->toBe(['a', 'b']);
    });

    it('DefaultValue stores null value', function (): void {
        $attr = new DefaultValue(null);
        expect($attr->value)->toBeNull();
    });

    it('MapFrom stores key', function (): void {
        $attr = new MapFrom('user_name');
        expect($attr->key)->toBe('user_name');
    });

    it('Cast stores type', function (): void {
        $attr = new \ZeroBoiler\DTO\Attributes\Cast('integer');
        expect($attr->type)->toBe('integer');
    });
});

describe('DTO readonly property consistency', function (): void {
    it('all DTO properties are public readonly', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::class);
        $props = $ref->getProperties();

        foreach ($props as $prop) {
            expect($prop->isPublic())->toBeTrue()
                ->and($prop->isReadOnly())->toBeTrue();
        }
    });

    it('nested DTO properties are public readonly', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::class);
        $props = $ref->getProperties();

        foreach ($props as $prop) {
            expect($prop->isPublic())->toBeTrue()
                ->and($prop->isReadOnly())->toBeTrue();
        }
    });
});

describe('DTO immutability', function (): void {
    it('cannot modify a readonly property after construction', function (): void {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // Reflection to verify readonly
        $ref = new \ReflectionProperty($dto, 'email');
        expect($ref->isReadOnly())->toBeTrue();
    });
});
