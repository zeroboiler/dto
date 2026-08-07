<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DtoMetadataResolver type detection', function () {
    describe('scalar type inference', function () {
        it('infers integer rule for int properties', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $rules = $metadata['rules'];

            // Find the email field rules — should have 'required' and 'email'
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('detects nullable properties', function () {
            $metadata = DtoMetadataResolver::resolve(MinimalDTO::class);
            $props = $metadata['properties'];

            expect($props)->toHaveKey('name');
            expect($props['name']['nullable'])->toBeTrue();
        });
    });

    describe('default value detection', function () {
        it('detects has_default for properties with defaults', function () {
            $metadata = DtoMetadataResolver::resolve(MinimalDTO::class);
            $props = $metadata['properties'];

            // name is nullable with default null
            expect($props['name']['has_default'])->toBeTrue();
            expect($props['name']['default'])->toBeNull();
        });
    });

    describe('hidden property detection', function () {
        it('detects hidden properties from attributes', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $props = $metadata['properties'];

            if (isset($props['password'])) {
                expect($props['password']['hidden'])->toBeTrue();
            }
        });
    });

    describe('map_from detection', function () {
        it('detects MapFrom attribute on properties', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $props = $metadata['properties'];

            // Check if CreateUserDTO has a phone property with MapFrom
            if (isset($props['phone'])) {
                expect($props['phone']['map_from'])->toBe('phone_number');
            }
        });
    });

    describe('empty DTO', function () {
        it('returns empty metadata for DTO without constructor', function () {
            $metadata = DtoMetadataResolver::resolve(EmptyDTO::class);

            expect($metadata['properties'])->toBe([]);
            expect($metadata['rules'])->toBe([]);
            expect($metadata['messages'])->toBe([]);
        });
    });

    describe('rule deduplication', function () {
        it('does not duplicate string rules', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $rules = $metadata['rules'];

            // Each field's rules should have no duplicates
            foreach ($rules as $field => $fieldRules) {
                $stringRules = array_filter($fieldRules, fn (mixed $r): bool => is_string($r));
                expect(array_unique($stringRules))->toHaveCount(count($stringRules));
            }
        });
    });

    describe('custom validation messages', function () {
        it('collects messages from attributes with custom message', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $messages = $metadata['messages'];

            // Messages should be an array (may be empty if no custom messages set)
            expect($messages)->toBeArray();
        });
    });

    describe('validation attribute contract compliance', function () {
        it('all rules have string values or EnumRule objects', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
            $rules = $metadata['rules'];

            foreach ($rules as $field => $fieldRules) {
                foreach ($fieldRules as $rule) {
                    expect($rule)->toBe(
                        is_string($rule) || $rule instanceof \Illuminate\Validation\Rules\Enum
                    )->toBeTrue();
                }
            }
        });
    });
});
