<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('OpenAPI schema edge cases', function () {
    it('produces empty properties array for DTO without required fields', function () {
        $schema = OpenApiSchemaGenerator::generateWithComponents(EmptyDTO::class);

        expect($schema)->toHaveKey('schema');
        expect($schema)->toHaveKey('components');
        expect($schema['schema']['type'])->toBe('object');
        expect($schema['schema']['properties'])->toBeArray();

        // EmptyDTO has no required fields (both are nullable with defaults)
        expect($schema['schema'])->not->toHaveKey('required');
    });

    it('properties key is a plain array, not stdClass', function () {
        $schema = OpenApiSchemaGenerator::generateWithComponents(EmptyDTO::class);
        $properties = $schema['schema']['properties'];

        // Must be array, not stdClass
        expect($properties)->toBeArray();
        expect(is_object($properties) && !($properties instanceof ArrayAccess))->toBeFalse();
    });

    it('component schemas is an array', function () {
        $schema = OpenApiSchemaGenerator::generateWithComponents(EmptyDTO::class);

        expect($schema['components']['schemas'])->toBeArray();
        expect($schema['components']['schemas'])->toHaveKey('EmptyDTO');
    });
});
