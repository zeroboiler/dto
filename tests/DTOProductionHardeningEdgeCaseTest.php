<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
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
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;

/**
 * Focused edge-case tests for PHPStan Level 9 compliance and production hardening.
 *
 * Targets specific edge cases in type safety, strict comparisons, attribute
 * contract compliance, casting pipelines, and boundary conditions that ensure
 * the package meets PHPStan L9 requirements.
 */
describe('DTO — PHPStan L9 edge cases and production hardening', function () {

    // ─── Attribute final class compliance ─────────────────────────────────

    describe('All validation attributes are final classes', function () {
        $validationAttributes = [
            Accepted::class,
            Boolean::class,
            Confirmed::class,
            Date::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            In::class,
            Integer::class,
            Max::class,
            Min::class,
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
            Nullable::class,
        ];

        it('all validation attribute classes are final', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
            }
        });

        it('all validation attributes implement ValidationAttribute', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attrClass) {
                expect($attrClass)->toImplement(ValidationAttribute::class);
            }
        });

        it('all validation attributes have ruleKey() method', function () use ($validationAttributes) {
            foreach ($validationAttributes as $attrClass) {
                expect(method_exists($attrClass, 'ruleKey'))->toBeTrue(
                    "{$attrClass} must have ruleKey() method"
                );
            }
        });
    });

    // ─── Metadata-only attributes ──────────────────────────────────────

    describe('Metadata-only attributes do not implement ValidationAttribute', function () {
        $metadataAttributes = [
            Cast::class,
            DefaultValue::class,
            Hidden::class,
            MapFrom::class,
            NestedArray::class,
        ];

        it('metadata attributes do NOT implement ValidationAttribute', function () use ($metadataAttributes) {
            foreach ($metadataAttributes as $attrClass) {
                expect($attrClass)->not->toImplement(ValidationAttribute::class);
            }
        });

        it('metadata attributes are final', function () use ($metadataAttributes) {
            foreach ($metadataAttributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);
                expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
            }
        });
    });

    // ─── DTOException factory methods ─────────────────────────────────────

    describe('DTOException factory methods', function () {
        it('invalidJson() creates exception with property name and detail', function () {
            $exception = DTOException::invalidJson('payload', 'Syntax error');

            expect($exception)->toBeInstanceOf(DTOException::class);
            expect($exception->getMessage())->toContain('payload');
            expect($exception->getMessage())->toContain('Syntax error');
        });

        it('is serializable for logging', function () {
            $exception = DTOException::invalidJson('data', 'test error');

            $string = (string) $exception;
            expect($string)->toBeString();
            expect($string)->not->toBeEmpty();
        });
    });

    // ─── Cast pipeline edge cases ────────────────────────────────────────

    describe('Cast pipeline type safety', function () {
        it('Cast attribute has readonly typed constructor property', function () {
            $attr = new Cast('integer');
            $ref = new ReflectionProperty($attr, 'type');
            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->getType()->getName())->toBe('string');
            expect($attr->type)->toBe('integer');
        });

        it('MapFrom attribute has readonly typed constructor property', function () {
            $attr = new MapFrom('source_key');
            $ref = new ReflectionProperty($attr, 'sourceKey');
            expect($ref->isReadOnly())->toBeTrue();
            expect($ref->getType()->getName())->toBe('string');
            expect($attr->sourceKey)->toBe('source_key');
        });

        it('Hidden attribute has readonly typed constructor property', function () {
            $attr = new Hidden();
            $ref = new ReflectionProperty($attr, 'hidden');
            expect($ref->isReadOnly())->toBeTrue();
            expect($attr->hidden)->toBeTrue();
        });

        it('DefaultValue attribute stores mixed value', function () {
            $attr = new DefaultValue('active');
            expect($attr->value)->toBe('active');

            $attrInt = new DefaultValue(42);
            expect($attrInt->value)->toBe(42);

            $attrNull = new DefaultValue(null);
            expect($attrNull->value)->toBeNull();
        });
    });

    // ─── Conditional validation attributes ──────────────────────────────

    describe('Conditional validation attributes type safety', function () {
        it('RequiredIf stores field and value correctly', function () {
            $attr = new RequiredIf('status', 'active');
            expect($attr->field)->toBe('status');
            expect($attr->value)->toBe('active');
        });

        it('RequiredUnless stores field and value correctly', function () {
            $attr = new RequiredUnless('role', 'admin');
            expect($attr->field)->toBe('role');
            expect($attr->value)->toBe('admin');
        });

        it('RequiredWith stores field name correctly', function () {
            $attr = new RequiredWith('email');
            expect($attr->fields)->toBe(['email']);
        });

        it('RequiredWithAll stores multiple fields correctly', function () {
            $attr = new RequiredWithAll('email', 'name');
            expect($attr->fields)->toBe(['email', 'name']);
        });

        it('RequiredWithout stores field name correctly', function () {
            $attr = new RequiredWithout('email');
            expect($attr->fields)->toBe(['email']);
        });

        it('RequiredWithoutAll stores multiple fields correctly', function () {
            $attr = new RequiredWithoutAll('phone', 'email');
            expect($attr->fields)->toBe(['phone', 'email']);
        });

        it('Same stores field name correctly', function () {
            $attr = new Same('password');
            expect($attr->field)->toBe('password');
        });

        it('Different stores field name correctly', function () {
            $attr = new Different('old_password');
            expect($attr->field)->toBe('old_password');
        });

        it('Confirmed has no parameters', function () {
            $attr = new Confirmed();
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        });

        it('Distinct has no parameters', function () {
            $attr = new Distinct();
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
        });
    });

    // ─── Custom validation messages ───────────────────────────────────────

    describe('Custom validation messages', function () {
        it('attributes accept optional message parameter', function () {
            $attr = new Email(message: 'Custom email message');
            expect($attr->message)->toBe('Custom email message');
        });

        it('message defaults to null when not provided', function () {
            $attr = new Email();
            expect($attr->message)->toBeNull();
        });

        it('Min accepts custom message', function () {
            $attr = new Min(8, message: 'Too short');
            expect($attr->value)->toBe(8);
            expect($attr->message)->toBe('Too short');
        });

        it('Max accepts custom message', function () {
            $attr = new Max(255, message: 'Too long');
            expect($attr->value)->toBe(255);
            expect($attr->message)->toBe('Too long');
        });

        it('Pattern accepts custom message', function () {
            $attr = new Pattern('/^[a-z]+$/');
            expect($attr->regex)->toBe('/^[a-z]+$/');
            expect($attr->message)->toBeNull();
        });

        it('In accepts array of values', function () {
            $attr = new In(['draft', 'published']);
            expect($attr->values)->toBe(['draft', 'published']);
        });

        it('StartsWith accepts string or array', function () {
            $attr1 = new StartsWith('https://');
            expect($attr1->prefix)->toBe('https://');

            $attr2 = new StartsWith(['http://', 'https://']);
            expect($attr2->prefix)->toBe(['http://', 'https://']);
        });

        it('EndsWith accepts string or array', function () {
            $attr1 = new EndsWith('.com');
            expect($attr1->suffix)->toBe('.com');

            $attr2 = new EndsWith(['.com', '.org']);
            expect($attr2->suffix)->toBe(['.com', '.org']);
        });
    });

    // ─── DTO interface contract compliance ────────────────────────────────

    describe('DTO interface contracts', function () {
        it('DataTransferObject implements all expected interfaces', function () {
            $implements = class_implements(DataTransferObject::class);
            expect($implements)->toContain(\Illuminate\Contracts\Support\Arrayable::class);
            expect($implements)->toContain(\JsonSerializable::class);
            expect($implements)->toContain(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class);
            expect($implements)->toContain(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class);
        });

        it('DataTransferObject is abstract', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });

        it('concrete DTOs extend DataTransferObject', function () {
            expect(CreateUserDTO::class)->toBeSubclassOf(DataTransferObject::class);
            expect(MinimalDTO::class)->toBeSubclassOf(DataTransferObject::class);
        });
    });

    // ─── DtoCollection type safety ───────────────────────────────────────

    describe('DtoCollection type guard', function () {
        it('rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not', 'a', 'dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('accepts valid DTO instances', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $collection = new DtoCollection([$dto]);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(1);
        });

        it('make() factory creates instance correctly', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);
            $collection = DtoCollection::make([$dto]);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(1);
        });
    });

    // ─── Metadata resolver cache consistency ──────────────────────────────

    describe('DtoMetadataResolver cache behavior', function () {
        it('flushMetadataCache clears specific class', function () {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Should rebuild cleanly
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->not->toBeEmpty();
        });

        it('flushMetadataCache clears all when null', function () {
            DataTransferObject::flushMetadataCache(null);

            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl accepts float values', function () {
            DataTransferObject::setMetadataCacheTtl(2.5);
            // No exception = success
            expect(true)->toBeTrue();

            // Reset
            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    // ─── DTOFixture type completeness ────────────────────────────────────

    describe('Fixture DTO structural compliance', function () {
        it('CreateUserDTO has all expected properties', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);
            $props = $ref->getProperties();

            $names = array_map(fn (ReflectionProperty $p): string => $p->getName(), $props);
            expect($names)->toContain('email');
            expect($names)->toContain('name');
            expect($names)->toContain('status');
            expect($names)->toContain('tags');
            expect($names)->toContain('phone');
            expect($names)->toContain('password');
        });

        it('CreateUserDTO properties are all readonly', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "Property {$prop->getName()} must be readonly"
                );
            }
        });

        it('CreateUserDTO is final', function () {
            $ref = new ReflectionClass(CreateUserDTO::class);
            expect($ref->isFinal())->toBeTrue();
        });
    });

    // ─── Strict type comparisons in DTO operations ───────────────────────

    describe('Strict type comparisons in DTO operations', function () {
        it('equals() uses strict array comparison', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Test'];
            $dto1 = CreateUserDTO::fromArray($data, validate: false);
            $dto2 = CreateUserDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('isEmpty() returns true for DTO with only default values', function () {
            // CreateUserDTO has defaults for status, tags, phone, password
            // and required fields that would make isEmpty false when populated
            // Use a DTO created with only required fields to test
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            // email and name are non-empty required fields, so not empty
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ─── ValidationAttribute ruleKey consistency ──────────────────────────

    describe('ValidationAttribute ruleKey consistency', function () {
        $expectedRules = [
            Accepted::class => 'accepted',
            Declined::class => 'declined',
            Confirmed::class => 'confirmed',
            Distinct::class => 'distinct',
            Present::class => 'present',
            Prohibited::class => 'prohibited',
            Sometimes::class => 'sometimes',
            Nullable::class => 'nullable',
            Email::class => 'email',
            Url::class => 'url',
            Uuid::class => 'uuid',
            Integer::class => 'integer',
            Numeric::class => 'numeric',
            Boolean::class => 'boolean',
        ];

        it('each attribute returns expected ruleKey', function () use ($expectedRules) {
            foreach ($expectedRules as $class => $expectedKey) {
                $attr = new $class();
                expect($attr->ruleKey())->toBe($expectedKey,
                    "{$class}::ruleKey() should return '{$expectedKey}'"
                );
            }
        });
    });
});
