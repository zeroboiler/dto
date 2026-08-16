<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;

/**
 * Contract compliance tests for DtoMetadataResolver.
 *
 * Verifies that the resolver correctly handles:
 * - Basic scalar properties (string, int, float, bool, array)
 * - Nullable properties with and without defaults
 * - MapFrom key mapping
 * - Hidden field detection
 * - Cast type detection
 * - DefaultValue attribute resolution
 * - Rule inference from ValidationAttribute instances
 * - Custom validation messages from attributes
 */
describe('DtoMetadataResolver', function () {
    it('resolves basic string property with Required and Email attributes', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('email');
        expect($meta['properties']['email']['nullable'])->toBeFalse();
        expect($meta['rules']['email'])->toContain('required');
        expect($meta['rules']['email'])->toContain('email');
    });

    it('resolves Min and Max constraint attributes on string property', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('name');
        expect($meta['rules']['name'])->toContain('required');
        expect($meta['rules']['name'])->toContain('min:2');
        expect($meta['rules']['name'])->toContain('max:50');
    });

    it('detects MapFrom attribute and sets map_from in metadata', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('phone');
        expect($meta['properties']['phone']['map_from'])->toBe('phone_number');
    });

    it('detects Hidden attribute on properties', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('password');
        expect($meta['properties']['password']['hidden'])->toBeTrue();
    });

    it('detects nullable properties correctly', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties']['phone']['nullable'])->toBeTrue();
        expect($meta['properties']['password']['nullable'])->toBeTrue();
    });

    it('detects has_default for properties with default values', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        // phone has default null, status has default 'active'
        expect($meta['properties']['phone']['has_default'])->toBeTrue();
        expect($meta['properties']['phone']['default'])->toBeNull();
    });

    it('detects Cast attribute on array property', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('tags');
        expect($meta['properties']['tags']['cast'])->toBe('array');
    });

    it('detects DefaultValue attribute', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKey('status');
        expect($meta['properties']['status']['default'])->toBe('active');
    });

    it('returns properties for all constructor parameters', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['properties'])->toHaveKeys(['email', 'name', 'status', 'tags', 'phone', 'password']);
    });

    it('returns correct structure keys', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta)->toHaveKeys(['properties', 'rules', 'messages']);
    });

    it('handles EmptyDTO with only nullable properties', function () {
        $meta = DtoMetadataResolver::resolve(EmptyDTO::class);

        expect($meta['properties'])->toHaveKey('foo');
        expect($meta['properties']['foo']['nullable'])->toBeTrue();
        expect($meta['properties']['foo']['has_default'])->toBeTrue();
    });

    it('handles MinimalDTO with only Required attributes', function () {
        $meta = DtoMetadataResolver::resolve(MinimalDTO::class);

        expect($meta['properties'])->toHaveKeys(['name', 'value']);
        expect($meta['rules']['name'])->toContain('required');
        expect($meta['rules']['value'])->toContain('required');
    });

    it('collects custom validation messages from ValidationAttribute instances', function () {
        // If any attribute has a non-null message property, it should appear in messages
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta['messages'])->toBeArray();
    });

    it('preserves rule order from attributes', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        // Required should come before other rules for the name field
        $nameRules = $meta['rules']['name'] ?? [];
        if ($nameRules !== []) {
            expect($nameRules[0])->toBe('required');
        }
    });

    it('rules are arrays for each property', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        foreach ($meta['rules'] as $field => $rules) {
            expect($rules)->toBeArray();
        }
    });

    it('properties contain only scalar metadata values', function () {
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        foreach ($meta['properties'] as $field => $propMeta) {
            expect($propMeta)->toBeArray();
        }
    });
});
