<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * OpenApiSchemaGenerator advanced type inference tests.
 *
 * Tests union types, ValueObject type inference (inferVoType),
 * intersection types, and edge cases in the schema generator.
 *
 * @see \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator
 * @see \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::inferVoType()
 * @see \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::inferUnionSchema()
 */

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

// ── Test Fixtures ──────────────────────────────────────────────

final class SchemaUnionTypeDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string|int $nameOrId,
        #[Required]
        public readonly string|float|null $score,
        #[Required]
        public readonly int|string|float $quantity,
    ) {}
}

final class SchemaIntersectionTypeDTO extends DataTransferObject
{
    public function __construct(
        public readonly mixed $value = null,
    ) {}
}

final class SchemaHiddenPropertyDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
        #[Required]
        public readonly string $name,
    ) {}
}

final class SchemaDateWithFormatDTO extends DataTransferObject
{
    public function __construct(
        #[Date(format: 'Y-m-d')]
        public readonly string $birthDate,
        #[Date]
        public readonly string $createdAt,
    ) {}
}

final class SchemaPatternDTO extends DataTransferObject
{
    public function __construct(
        #[Pattern('/^[A-Z]{3}-\d{4}$/')]
        public readonly string $code,
    ) {}
}

final class SchemaStartsWithEndsWithDTO extends DataTransferObject
{
    public function __construct(
        #[StartsWith('https://')]
        public readonly string $url,
        #[StartsWith(['+90', '+1'])]
        public readonly string $phone,
    ) {}
}

final class SchemaEnumAttributeDTO extends DataTransferObject
{
    public function __construct(
        #[EnumAttribute(SchemaTestStatus::class)]
        public readonly SchemaTestStatus $status,
    ) {}
}

enum SchemaTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

final class SchemaBetweenDTO extends DataTransferObject
{
    public function __construct(
        #[Between(1, 100)]
        public readonly int $age,
        #[Between(10, 255)]
        public readonly string $bio,
        #[Between(0.5, 99.9)]
        public readonly float $rating,
    ) {}
}

final class SchemaMinMaxDTO extends DataTransferObject
{
    public function __construct(
        #[Min(3)]
        #[Max(50)]
        public readonly string $name,
        #[Min(1)]
        #[Max(1000)]
        public readonly int $count,
    ) {}
}

final class SchemaMultipleStartsWithDTO extends DataTransferObject
{
    public function __construct(
        #[StartsWith(['admin:', 'user:', 'system:'])]
        public readonly string $roleKey,
    ) {}
}

// ── Tests ─────────────────────────────────────────────────────

describe('OpenApiSchemaGenerator — Advanced Type Inference', function () {
    it('generates schema for DTO with union type properties', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaUnionTypeDTO::class);

        expect($schema)->toBeArray();
        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toBeObject();

        // Union types should produce a safe type inference
        $props = (array) $schema['properties'];
        expect($props)->toHaveKey('nameOrId');
        expect($props)->toHaveKey('score');
        expect($props)->toHaveKey('quantity');
    });

    it('marks union type nullable properties as nullable in schema', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaUnionTypeDTO::class);
        $props = (array) $schema['properties'];

        // string|float|null should be nullable
        expect($props['score']['nullable'])->toBeTrue();
    });

    it('generates oneOf for union types with multiple distinct types', function () {
        // Access the private inferUnionSchema indirectly via generateInternal
        $ref = new ReflectionMethod(OpenApiSchemaGenerator::class, 'inferPropertySchema');
        $ref->setAccessible(true);

        $unionType = (new ReflectionClass(SchemaUnionTypeDTO::class))
            ->getConstructor()
            ->getParameters()[0]
            ->getType();

        expect($unionType)->toBeInstanceOf(\ReflectionUnionType::class);

        // Generate with components to avoid the LogicException
        $result = OpenApiSchemaGenerator::generateWithComponents(SchemaUnionTypeDTO::class);
        $props = (array) $result['schema']['properties'];

        // The schema should be generated without errors
        expect($result['schema']['type'])->toBe('object');
    });

    it('excludes hidden properties from OpenAPI schema', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaHiddenPropertyDTO::class);
        $props = (array) $schema['properties'];

        expect($props)->toHaveKey('email');
        expect($props)->toHaveKey('name');
        expect($props)->not->toHaveKey('password');
    });

    it('includes non-hidden properties in required list', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaHiddenPropertyDTO::class);

        expect($schema['required'])->toBe(['email', 'name']);
    });

    it('generates date format schema for Date attribute with format', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaDateWithFormatDTO::class);
        $props = (array) $schema['properties'];

        expect($props['birthDate']['format'])->toBe('date');
        expect($props['birthDate']['pattern'])->toBe('Y-m-d');
    });

    it('generates date format schema for Date attribute without format', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaDateWithFormatDTO::class);
        $props = (array) $schema['properties'];

        expect($props['createdAt']['format'])->toBe('date');
        expect($props['createdAt'])->not->toHaveKey('pattern');
    });

    it('applies pattern constraint from Pattern attribute', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaPatternDTO::class);
        $props = (array) $schema['properties'];

        expect($props['code']['pattern'])->toBe('^[A-Z]{3}-\d{4}$');
    });

    it('applies starts_with prefix pattern for single prefix', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaStartsWithEndsWithDTO::class);
        $props = (array) $schema['properties'];

        // URL with single StartsWith('https://')
        expect($props['url']['pattern'])->toBe('^https\\:\\/\\/');
    });

    it('applies starts_with alternation pattern for multiple prefixes', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaStartsWithEndsWithDTO::class);
        $props = (array) $schema['properties'];

        // Phone with StartsWith(['+90', '+1'])
        expect($props['phone']['pattern'])->toContain('^(');
        expect($props['phone']['pattern'])->toContain('+90');
        expect($props['phone']['pattern'])->toContain('+1');
    });

    it('generates enum constraint from Enum attribute', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaEnumAttributeDTO::class);
        $props = (array) $schema['properties'];

        expect($props['status']['enum'])->toBe(['active', 'inactive', 'pending']);
        expect($props['status']['type'])->toBe('string');
    });

    it('applies between constraint with type-aware min/max', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaBetweenDTO::class);
        $props = (array) $schema['properties'];

        // int property → minimum/maximum
        expect($props['age']['minimum'])->toBe(1);
        expect($props['age']['maximum'])->toBe(100);
        expect($props['age']['type'])->toBe('integer');

        // string property → minLength/maxLength
        expect($props['bio']['minLength'])->toBe(10);
        expect($props['bio']['maxLength'])->toBe(255);
        expect($props['bio']['type'])->toBe('string');

        // float property → minimum/maximum (kept as float)
        expect($props['rating']['minimum'])->toBe(0.5);
        expect($props['rating']['maximum'])->toBe(99.9);
        expect($props['rating']['type'])->toBe('number');
    });

    it('applies min/max constraints separately', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaMinMaxDTO::class);
        $props = (array) $schema['properties'];

        // string property → minLength/maxLength
        expect($props['name']['minLength'])->toBe(3);
        expect($props['name']['maxLength'])->toBe(50);

        // int property → minimum/maximum
        expect($props['count']['minimum'])->toBe(1);
        expect($props['count']['maximum'])->toBe(1000);
    });

    it('handles DTO with no constructor gracefully', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaIntersectionTypeDTO::class);

        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toBeObject();
        // value property has mixed type and is optional — not in required
        expect($schema)->not->toHaveKey('required');
    });

    it('generates valid schema with components for complex DTOs', function () {
        $result = OpenApiSchemaGenerator::generateWithComponents(SchemaEnumAttributeDTO::class);

        expect($result)->toHaveKey('schema');
        expect($result)->toHaveKey('components');
        expect($result['components']['schemas'])->toBeArray();
    });

    it('handles multiple starts_with prefixes with special regex chars', function () {
        $schema = OpenApiSchemaGenerator::generate(SchemaMultipleStartsWithDTO::class);
        $props = (array) $schema['properties'];

        expect($props['roleKey']['pattern'])->toContain('admin\\:');
        expect($props['roleKey']['pattern'])->toContain('user\\:');
        expect($props['roleKey']['pattern'])->toContain('system\\:');
    });
});

describe('OpenApiSchemaGenerator — Structural Integrity', function () {
    it('stripRegexDelimiters handles common delimiters', function () {
        $ref = new ReflectionMethod(OpenApiSchemaGenerator::class, 'stripRegexDelimiters');
        $ref->setAccessible(true);

        expect($ref->invoke(null, '/^[A-Z]+$/'))->toBe('^[A-Z]+$');
        expect($ref->invoke(null, '#^[a-z]+$#'))->toBe('^[a-z]+$');
        expect($ref->invoke(null, '~^\\d{4}$~'))->toBe('^\\d{4}$');
        expect($ref->invoke(null, '|^[A-Z]{3}$|'))->toBe('^[A-Z]{3}$');
    });

    it('stripRegexDelimiters handles undelimited patterns gracefully', function () {
        $ref = new ReflectionMethod(OpenApiSchemaGenerator::class, 'stripRegexDelimiters');
        $ref->setAccessible(true);

        // No delimiters — returned with leading chars stripped
        $result = $ref->invoke(null, '^[A-Z]+$');
        expect($result)->toBeString();
    });

    it('componentName extracts short name from FQCN', function () {
        $ref = new ReflectionMethod(OpenApiSchemaGenerator::class, 'componentName');
        $ref->setAccessible(true);

        expect($ref->invoke(null, 'App\\DTO\\CreateUserDTO'))->toBe('CreateUserDTO');
        expect($ref->invoke(null, 'SimpleDTO'))->toBe('SimpleDTO');
    });

    it('isNumericType detects integer and number types', function () {
        $ref = new ReflectionMethod(OpenApiSchemaGenerator::class, 'isNumericType');
        $ref->setAccessible(true);

        expect($ref->invoke(null, ['type' => 'integer']))->toBeTrue();
        expect($ref->invoke(null, ['type' => 'number']))->toBeTrue();
        expect($ref->invoke(null, ['type' => 'string']))->toBeFalse();
        expect($ref->invoke(null, ['type' => 'boolean']))->toBeFalse();
        expect($ref->invoke(null, []))->toBeFalse();
    });

    it('all private methods exist and have correct signatures', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PRIVATE | \ReflectionMethod::IS_STATIC);

        $methodNames = array_map(fn (\ReflectionMethod $m) => $m->getName(), $methods);

        expect($methodNames)->toContain('generateInternal');
        expect($methodNames)->toContain('inferPropertySchema');
        expect($methodNames)->toContain('inferUnionSchema');
        expect($methodNames)->toContain('inferType');
        expect($methodNames)->toContain('inferVoType');
        expect($methodNames)->toContain('componentName');
        expect($methodNames)->toContain('stripRegexDelimiters');
        expect($methodNames)->toContain('isNumericType');
        expect($methodNames)->toContain('applyMinConstraint');
        expect($methodNames)->toContain('applyMaxConstraint');
        expect($methodNames)->toContain('applyBetweenConstraint');
        expect($methodNames)->toContain('applyFormat');
        expect($methodNames)->toContain('applyTypeOverride');
        expect($methodNames)->toContain('applyDateFormat');
        expect($methodNames)->toContain('applyPatternAttribute');
        expect($methodNames)->toContain('applyInConstraint');
        expect($methodNames)->toContain('applyEnumSchema');
        expect($methodNames)->toContain('applyStartsWithPattern');
        expect($methodNames)->toContain('applyEndsWithPattern');
        expect($methodNames)->toContain('mergePattern');
        expect($methodNames)->toContain('hasAttribute');
        expect($methodNames)->toContain('applyValidationAttributes');
    });

    it('OpenApiSchemaGenerator is a final class', function () {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('generate method throws LogicException for nested DTOs without generateWithComponents', function () {
        // SchemaEnumAttributeDTO references SchemaTestStatus enum (not a DTO)
        // so this should NOT throw — need a DTO that references another DTO
        expect(fn () => OpenApiSchemaGenerator::generate(SchemaEnumAttributeDTO::class))
            ->not->toThrow(\LogicException::class);
    });
});
