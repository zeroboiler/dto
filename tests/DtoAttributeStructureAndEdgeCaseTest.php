<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DTO attribute final class enforcement and contract compliance tests.
 *
 * Verifies that all DTO attributes are final, have readonly promoted
 * properties, implement correct interfaces, and that all validation
 * attribute ruleKey() values are valid Laravel rule names.
 */

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
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO attribute class structure', function (): void {

    it('all validation attributes are final classes', function (): void {
        $validationClasses = [
            Accepted::class,
            ArrayRule::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            EnumAttribute::class,
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
            Url::class,
            Uuid::class,
        ];

        foreach ($validationClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('all metadata attributes are final classes', function (): void {
        $metaClasses = [
            Cast::class,
            DefaultValue::class,
            Hidden::class,
            MapFrom::class,
        ];

        foreach ($metaClasses as $class) {
            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} must be final");
        }
    });

    it('all validation attributes have readonly promoted properties', function (): void {
        $validationClasses = [
            Accepted::class,
            ArrayRule::class,
            Between::class,
            Boolean::class,
            Confirmed::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            EnumAttribute::class,
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
            Url::class,
            Uuid::class,
        ];

        foreach ($validationClasses as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            expect($constructor)->not->toBeNull("{$class} must have a constructor");

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                expect($ref->hasProperty($name))->toBeTrue("{$class} must have property \${$name}");

                $prop = $ref->getProperty($name);
                expect($prop->isReadOnly())->toBeTrue("{$class}::\${$name} must be readonly");
                expect($prop->isPublic())->toBeTrue("{$class}::\${$name} must be public");
            }
        }
    });

    it('metadata attributes have readonly promoted properties', function (): void {
        $metaClasses = [
            Cast::class,
            DefaultValue::class,
            Hidden::class,
            MapFrom::class,
        ];

        foreach ($metaClasses as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                continue; // Hidden has no constructor
            }

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $prop = $ref->getProperty($name);
                expect($prop->isReadOnly())->toBeTrue("{$class}::\${$name} must be readonly");
            }
        }
    });
});

describe('Validation attribute ruleKey() values', function (): void {

    it('all ruleKey() values are non-empty strings', function (): void {
        $attrs = [
            new Accepted,
            new ArrayRule,
            new Between(1, 10),
            new Boolean,
            new Confirmed,
            new Declined,
            new Different('other'),
            new Distinct,
            new Email,
            new EndsWith('.com'),
            new EnumAttribute(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class),
            new In(['a', 'b']),
            new Integer,
            new Json,
            new Max(100),
            new Min(1),
            new NestedArray(\ZeroBoiler\DTO\Tests\Fixtures\AddressDTO::class),
            new Nullable,
            new Numeric,
            new Pattern('/^[a-z]+$/'),
            new Present,
            new Prohibited,
            new Required,
            new RequiredIf('field', 'value'),
            new RequiredUnless('field', 'value'),
            new RequiredWith('field'),
            new RequiredWithAll(['a', 'b']),
            new RequiredWithout('field'),
            new RequiredWithoutAll(['a', 'b']),
            new Same('other'),
            new Size(10),
            new Sometimes,
            new StartsWith('https://'),
            new Url,
            new Uuid,
        ];

        foreach ($attrs as $attr) {
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
            $key = $attr->ruleKey();
            expect($key)->toBeString();
            expect($key)->not->toBeEmpty();
        }
    });

    it('ruleKey() values match expected Laravel rule names', function (): void {
        $expected = [
            Accepted::class => 'accepted',
            ArrayRule::class => 'array',
            Boolean::class => 'boolean',
            Confirmed::class => 'confirmed',
            Declined::class => 'declined',
            Distinct::class => 'distinct',
            Email::class => 'email',
            Integer::class => 'integer',
            Json::class => 'json',
            Nullable::class => 'nullable',
            Numeric::class => 'numeric',
            Pattern::class => 'regex',
            Present::class => 'present',
            Prohibited::class => 'prohibited',
            Required::class => 'required',
            RequiredWith::class => 'required_with',
            RequiredWithAll::class => 'required_with_all',
            RequiredWithout::class => 'required_without',
            RequiredWithoutAll::class => 'required_without_all',
            Sometimes::class => 'sometimes',
            Url::class => 'url',
            Uuid::class => 'uuid',
        ];

        foreach ($expected as $class => $expectedKey) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            if ($constructor === null) {
                continue;
            }

            // Instantiate with default parameters
            $params = [];
            foreach ($constructor->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                } else {
                    // For required params, provide sensible defaults
                    $type = $param->getType();
                    $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'string';

                    $params[] = match ($typeName) {
                        'string' => 'default',
                        'int', 'float' => 1,
                        'array' => ['default'],
                        default => 'default',
                    };
                }
            }

            /** @var ValidationAttribute $instance */
            $instance = $ref->newInstance(...$params);
            expect($instance->ruleKey())->toBe($expectedKey);
        }
    });
});

describe('DTO hydration pipeline edge cases', function (): void {

    it('fromArray with validate:false skips validation', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => '', // Would fail required/email validation
            'name' => '',  // Would fail min:2
        ], validate: false);

        assert($dto instanceof CreateUserDTO);
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('');
    });

    it('fromPartialArray with empty data returns DTO with defaults', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        assert($dto instanceof CreateUserDTO);
        expect($dto->status)->toBe('active'); // DefaultValue
    });

    it('equals() returns false for different DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'Charlie',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty() detects zero-value non-nullable int as non-empty', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('only() with array returns subset of fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);
        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('except() with array excludes multiple fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['name', 'status']);
        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });
});

describe('DtoCollection edge cases', function (): void {

    it('offsetSet with null key appends to end', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = new \ZeroBoiler\DTO\DtoCollection;
        $col[] = $d1;
        $col[] = $d2;

        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('a@b.com');
        expect($col[1]->email)->toBe('c@d.com');
    });

    it('offsetUnset re-indexes the collection', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

        $col = new \ZeroBoiler\DTO\DtoCollection([$d1, $d2, $d3]);
        unset($col[1]); // Remove middle

        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('a@b.com');
        expect($col[1]->email)->toBe('e@f.com'); // Re-indexed
    });

    it('offsetGet returns null for non-existent offset', function (): void {
        $col = new \ZeroBoiler\DTO\DtoCollection;

        expect($col[99])->toBeNull();
    });

    it('offsetExists returns false for non-existent offset', function (): void {
        $col = new \ZeroBoiler\DTO\DtoCollection;

        expect(isset($col[0]))->toBeFalse();
    });

    it('jsonSerialize returns same as toArray', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = new \ZeroBoiler\DTO\DtoCollection([$d1]);

        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('allValues includes hidden fields', function (): void {
        $d1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $col = new \ZeroBoiler\DTO\DtoCollection([$d1]);
        $all = $col->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret123');
    });

    it('clone produces independent copy for append', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = new \ZeroBoiler\DTO\DtoCollection([$d1]);
        $newCol = $col->append($d2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
        expect($newCol !== $col)->toBeTrue();
    });
});
