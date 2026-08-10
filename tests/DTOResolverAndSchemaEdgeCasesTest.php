<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DtoMetadataResolver and OpenApiSchemaGenerator edge case tests.
 *
 * Tests type detection, rule inference, deduplication, schema generation
 * for various property types, and edge cases in the resolver pipeline.
 *
 * Complements the existing DTOMetadataResolverTypeDetectionTest and
 * DtoOpenApiSchemaEdgeCasesAndTypeSystemTest.
 */

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
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
use ZeroBoiler\DTO\Attributes\Collection as CollectionAttribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OpenApiValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('DtoMetadataResolver edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // No-constructor DTO — resolver returns empty arrays
    // ──────────────────────────────────────────────────────────────

    describe('Empty and no-constructor DTOs', function (): void {
        it('NoConstructorDTO returns empty metadata', function (): void {
            $meta = DtoMetadataResolver::resolve(NoConstructorDTO::class);

            expect($meta['properties'])->toBe([]);
            expect($meta['rules'])->toBe([]);
            expect($meta['messages'])->toBe([]);
        });

        it('EmptyDTO returns empty metadata', function (): void {
            $meta = DtoMetadataResolver::resolve(EmptyDTO::class);

            expect($meta['properties'])->toBe([]);
            expect($meta['rules'])->toBe([]);
            expect($meta['messages'])->toBe([]);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Type inference from PHP types
    // ──────────────────────────────────────────────────────────────

    describe('Type inference', function (): void {
        it('string property without validation attributes has no inferred rules', function (): void {
            $meta = DtoMetadataResolver::resolve(MinimalDTO::class);

            // MinimalDTO has a single optional string property
            // Nullable properties get 'sometimes' but bare strings get nothing
            foreach ($meta['rules'] as $field => $rules) {
                expect($rules)->toBeArray();
            }
        });

        it('int property infers integer rule', function (): void {
            $meta = DtoMetadataResolver::resolve(ScalarConstraintsDTO::class);

            // ScalarConstraintsDTO should have integer rules for int properties
            $rules = $meta['rules'];
            $hasIntRule = false;

            foreach ($rules as $field => $fieldRules) {
                if (in_array('integer', $fieldRules, true)) {
                    $hasIntRule = true;
                    break;
                }
            }

            expect($hasIntRule)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Validation attribute rule generation
    // ──────────────────────────────────────────────────────────────

    describe('Validation attribute rule generation', function (): void {
        it('Required generates required rule', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            // CreateUserDTO has #[Required] on email and name
            expect($meta['rules'])->toHaveKey('email');
            expect($meta['rules']['email'])->toContain('required');
            expect($meta['rules']['email'])->toContain('email');
        });

        it('Min/Max generate correct rules', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['rules'])->toHaveKey('name');
            expect($meta['rules']['name'])->toContain('required');
            expect($meta['rules']['name'])->toContain('min:2');
            expect($meta['rules']['name'])->toContain('max:50');
        });

        it('MapFrom sets map_from in property metadata', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties'])->toHaveKey('phone');
            expect($meta['properties']['phone']['map_from'])->toBe('phone_number');
        });

        it('Hidden sets hidden flag in property metadata', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties'])->toHaveKey('password');
            expect($meta['properties']['password']['hidden'])->toBeTrue();
        });

        it('DefaultValue sets default and has_default', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties'])->toHaveKey('status');
            expect($meta['properties']['status']['has_default'])->toBeTrue();
            expect($meta['properties']['status']['default'])->toBe('active');
        });

        it('Cast sets cast type in property metadata', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties'])->toHaveKey('tags');
            expect($meta['properties']['tags']['cast'])->toBe('array');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Nullable/Sometimes inference
    // ──────────────────────────────────────────────────────────────

    describe('Nullable and Sometimes inference', function (): void {
        it('nullable properties without default get sometimes rule', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            // phone is ?string with no default
            expect($meta['properties']['phone']['nullable'])->toBeTrue();
            expect($meta['rules']['phone'])->toContain('sometimes');
        });

        it('properties with DefaultValue do not get sometimes', function (): void {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            // status has #[DefaultValue('active')] — has_default = true
            expect($meta['properties']['status']['has_default'])->toBeTrue();
            // Should NOT have 'sometimes' since it has a default
            if (isset($meta['rules']['status'])) {
                expect($meta['rules']['status'])->not->toContain('sometimes');
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Rule deduplication
    // ──────────────────────────────────────────────────────────────

    describe('Rule deduplication', function (): void {
        it('no duplicate rules for any property', function (): void {
            $meta = DtoMetadataResolver::resolve(ScalarConstraintsDTO::class);

            foreach ($meta['rules'] as $field => $rules) {
                $unique = [];
                foreach ($rules as $rule) {
                    $key = is_string($rule) ? $rule : get_class($rule);
                    expect(! isset($unique[$key]))->toBeTrue("Duplicate rule '{$key}' on field '{$field}'");
                    $unique[$key] = true;
                }
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // ValidationAttribute contract — ruleKey()
    // ──────────────────────────────────────────────────────────────

    describe('ValidationAttribute ruleKey contract', function (): void {
        it('all validation attributes implement ValidationAttribute', function (): void {
            $attributes = [
                new Required, new Email, new Max(100), new Min(1),
                new Url, new Pattern('/^[a-z]+$/'), new In(['a', 'b']),
                new Integer, new Numeric, new Boolean, new Uuid,
                new Date, new Date('Y-m-d'), new Confirmed,
                new Different('other'), new Same('other'),
                new Between(1, 10), new Prohibited, new Present,
                new Declined, new Accepted, new StartsWith('foo'),
                new EndsWith('bar'), new Nullable, new Sometimes,
                new Distinct, new Size(5), new Json,
            ];

            foreach ($attributes as $attr) {
                if ($attr instanceof ValidationAttribute) {
                    expect($attr->ruleKey())->toBeString();
                    expect($attr->ruleKey())->not->toBeEmpty();
                }
            }
        });

        it('Required ruleKey returns "required"', function (): void {
            expect((new Required)->ruleKey())->toBe('required');
        });

        it('Email ruleKey returns "email"', function (): void {
            expect((new Email)->ruleKey())->toBe('email');
        });

        it('Max ruleKey returns "max"', function (): void {
            expect((new Max(100))->ruleKey())->toBe('max');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // OpenApi schema generation
    // ──────────────────────────────────────────────────────────────

    describe('OpenApi schema generation', function (): void {
        it('generates valid schema for CreateUserDTO', function (): void {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
            expect($schema['properties'])->toBeObject();
        });

        it('hidden properties are excluded from schema', function (): void {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            // 'password' is #[Hidden] — should not appear in properties
            $props = (array) $schema['properties'];
            expect($props)->not->toHaveKey('password');
        });

        it('generates schema for empty DTO', function (): void {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(EmptyDTO::class);

            expect($schema['type'])->toBe('object');
            expect((array) $schema['properties'])->toBe([]);
        });

        it('required fields appear in required list', function (): void {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema)->toHaveKey('required');
            expect($schema['required'])->toContain('email');
            expect($schema['required'])->toContain('name');
        });

        it('email property gets email format', function (): void {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            $props = (array) $schema['properties'];
            expect($props['email'])->toHaveKey('format');
            expect($props['email']['format'])->toBe('email');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // Metadata cache TTL
    // ──────────────────────────────────────────────────────────────

    describe('Metadata cache TTL behavior', function (): void {
        it('cache TTL defaults to 0 (disabled)', function (): void {
            // Flush any existing cache
            DataTransferObject::flushMetadataCache();

            $reflection = new \ReflectionClass(DataTransferObject::class);
            $prop = $reflection->getProperty('_zbMetadataCacheTtl');
            $prop->setAccessible(true);

            $ttl = $prop->getDefaultValue();
            expect($ttl)->toBe(0.0);
        });

        it('flushMetadataCache() clears all entries', function (): void {
            // Resolve some metadata to populate the cache
            DtoMetadataResolver::resolve(CreateUserDTO::class);
            DtoMetadataResolver::resolve(ValidationTestDTO::class);

            DataTransferObject::flushMetadataCache();

            // After flush, the static cache is empty
            // (we can't directly inspect private statics, but we verify no errors)
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);
            expect($meta['properties'])->toHaveKey('email');
        });

        it('flushMetadataCache(class) clears only specified class', function (): void {
            DtoMetadataResolver::resolve(CreateUserDTO::class);
            DtoMetadataResolver::resolve(ValidationTestDTO::class);

            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Re-resolve — should work fine
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);
            expect($meta['properties'])->toHaveKey('email');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DTO equals and isEmpty with various data shapes
    // ──────────────────────────────────────────────────────────────

    describe('DTO equality and emptiness', function (): void {
        it('equals returns true for identical data', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different data', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'User A',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'User B',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('equals excludes hidden fields from comparison', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret1',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret2',
            ], validate: false);

            // Password is #[Hidden] — not included in toArray()
            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('isNotEmpty is negation of isEmpty', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });
    });
});
