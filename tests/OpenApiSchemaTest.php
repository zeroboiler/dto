<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('OpenApiSchemaGenerator', function (): void {
    it('generates schema with properties', function (): void {
        $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toBeObject();
        expect($schema['required'])->toContain('email');
        expect($schema['required'])->toContain('name');
    });

    it('marks required fields', function (): void {
        $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

        expect($schema['required'])->toContain('email');
        expect($schema['required'])->toContain('name');
        // status has default, not required
        expect($schema['required'])->not->toContain('status');
    });

    it('excludes hidden properties', function (): void {
        $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);
        $props = (array) $schema['properties'];

        expect($props)->not->toHaveKey('password');
        expect($props)->toHaveKey('email');
    });

    it('infers property types', function (): void {
        $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);
        $props = (array) $schema['properties'];

        expect($props['email']['type'])->toBe('string');
        expect($props['tags']['type'])->toBe('array');
    });

    it('handles empty DTO', function (): void {
        $schema = OpenApiSchemaGenerator::generate(EmptyDTO::class);

        expect($schema['type'])->toBe('object');
    });
});
