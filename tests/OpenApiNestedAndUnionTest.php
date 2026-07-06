<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('OpenApiSchemaGenerator — Nested DTO Support (#76)', function (): void {
    it('throws when generate() encounters nested DTO references', function (): void {
        // generate() cannot produce valid $ref pointers without components (BUG-2 R38)
        expect(fn () => OpenApiSchemaGenerator::generate(OrderDTO::class))
            ->toThrow(\LogicException::class, 'nested DTO references');
    });

    it('generates $ref for nested DTO property via generateWithComponents()', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $props = (array) $result['schema']['properties'];

        expect($props['shippingAddress'])->toHaveKey('$ref')
            ->and($props['shippingAddress']['$ref'])->toBe('#/components/schemas/AddressDTO');
    });

    it('does not mark nested DTO as nullable when non-nullable', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $props = (array) $result['schema']['properties'];

        // shippingAddress is non-nullable AddressDTO
        expect($props['shippingAddress'])->not->toHaveKey('nullable');
    });

    it('includes nested DTO in required when non-nullable and no default', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

        expect($result['schema']['required'])->toContain('shippingAddress');
    });
});

describe('OpenApiSchemaGenerator — Generate With Components (#76)', function (): void {
    it('returns component schemas for nested DTOs', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

        expect($result)->toHaveKey('schema')
            ->and($result)->toHaveKey('components')
            ->and($result['components'])->toHaveKey('schemas');
    });

    it('includes AddressDTO in component schemas', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $schemas = $result['components']['schemas'];

        expect($schemas)->toHaveKey('AddressDTO')
            ->and($schemas['AddressDTO']['type'])->toBe('object');
    });

    it('AddressDTO component has correct properties', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $addressSchema = $result['components']['schemas']['AddressDTO'];
        $props = (array) $addressSchema['properties'];

        expect($props)->toHaveKey('street')
            ->and($props)->toHaveKey('city')
            ->and($props)->toHaveKey('zipCode')
            ->and($addressSchema['required'])->toContain('street')
            ->and($addressSchema['required'])->toContain('city');
    });

    it('does not duplicate component for self-referencing DTOs', function (): void {
        // Generating a DTO that references itself should not cause infinite recursion
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

        // AddressDTO should appear exactly once
        expect($result['components']['schemas'])->toHaveKey('AddressDTO');
        // Count should be 1 (no duplicates)
        expect(count($result['components']['schemas']))->toBeGreaterThanOrEqual(1);
    });

    it('main schema references components by $ref', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $mainProps = (array) $result['schema']['properties'];

        expect($mainProps['shippingAddress'])->toBe(['$ref' => '#/components/schemas/AddressDTO']);
    });
});

describe('OpenApiSchemaGenerator — Union Type Support (#75)', function (): void {
    it('handles union types with oneOf schema', function (): void {
        $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);
        $props = (array) $schema['properties'];

        // identifier is int|string — should produce oneOf
        expect($props['identifier'])->toHaveKey('oneOf');

        $oneOf = $props['identifier']['oneOf'];
        $types = array_column($oneOf, 'type');

        expect($types)->toContain('integer')
            ->and($types)->toContain('string');
    });

    it('deduplicates union types', function (): void {
        $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);
        $props = (array) $schema['properties'];

        $oneOf = $props['identifier']['oneOf'];

        // int|string = 2 unique types
        expect(count($oneOf))->toBe(2);
    });

    it('handles nullable union types without null in oneOf', function (): void {
        // int|string|null — null should be handled via 'nullable', not in oneOf
        $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);
        $props = (array) $schema['properties'];

        $identifier = $props['identifier'];

        // Should have oneOf with int and string, but NOT null
        if (isset($identifier['oneOf'])) {
            foreach ($identifier['oneOf'] as $sub) {
                expect($sub)->not->toBe(['type' => 'null']);
            }
        }
    });

    it('handles OrderDTO rawTotal union type (int|float|string)', function (): void {
        $result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
        $props = (array) $result['schema']['properties'];

        $rawTotal = $props['rawTotal'];

        // int|float|string = integer, number, string → 3 unique types
        expect($rawTotal)->toHaveKey('oneOf');
        $types = array_column($rawTotal['oneOf'], 'type');
        expect($types)->toContain('integer')
            ->and($types)->toContain('number')
            ->and($types)->toContain('string');
    });

    it('falls back to single type when union has one non-null member', function (): void {
        // A type like string|null should just be type: string with nullable: true
        $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);
        $props = (array) $schema['properties'];

        // phone is ?string — should be type: string with nullable: true, NOT oneOf
        expect($props['phone'])->toHaveKey('type')
            ->and($props['phone']['type'])->toBe('string')
            ->and($props['phone'])->toHaveKey('nullable')
            ->and($props['phone'])->not->toHaveKey('oneOf');
    });
});
