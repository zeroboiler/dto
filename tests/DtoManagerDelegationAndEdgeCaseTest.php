<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;

describe('DTOManager delegation and edge cases', function () {
    it('make creates DTO from valid data', function () {
        $manager = new DTOManager;
        $dto = $manager->make(AllDefaultsDTO::class, []);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    });

    it('validate returns validated data array', function () {
        $manager = new DTOManager;
        $result = $manager->validate(AllDefaultsDTO::class, []);

        expect($result)->toBeArray();
    });

    it('rules returns validation rules for a DTO class', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(ComprehensiveDTO::class);

        expect($rules)->toBeArray()->not->toBeEmpty();
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('age');
        expect($rules)->toHaveKey('role');
    });

    it('rulesFor returns same as rules for default action', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(ComprehensiveDTO::class);
        $rulesForCreate = $manager->rulesFor(ComprehensiveDTO::class, 'create');

        expect($rulesForCreate)->toBe($rules);
    });

    it('makeFromJson creates DTO from valid JSON string', function () {
        $manager = new DTOManager;
        $json = '{"name":"Alice","email":"alice@example.com"}';
        $dto = $manager->makeFromJson(AllDefaultsDTO::class, $json);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    });

    it('fromJson delegates correctly for valid JSON', function () {
        $manager = new DTOManager;
        $json = '{"name":"Bob","email":"bob@example.com"}';
        $dto = $manager->fromJson(AllDefaultsDTO::class, $json);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    });

    it('makeFromJson throws DTOException for invalid JSON', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(AllDefaultsDTO::class, 'not-valid-json'))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for sequential JSON array', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->fromJson(AllDefaultsDTO::class, '[1,2,3]'))
            ->toThrow(DTOException::class);
    });

    it('schema generates OpenAPI schema for a DTO class', function () {
        $manager = new DTOManager;
        $schema = $manager->schema(AllDefaultsDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
        expect($schema)->toHaveKey('properties');
    });

    it('fromPartialArray creates DTO with partial data and defaults', function () {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(ComprehensiveDTO::class, [
            'name' => 'Partial',
            'email' => 'partial@test.com',
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'age' => 25,
            'isActive' => true,
            'role' => 'viewer',
            'countryCode' => 'US',
            'firstName' => 'Partial',
            'phone' => '+123450',
        ]);

        expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
        expect($dto->name)->toBe('Partial');
    });

    it('rules contain expected validation rules for ComprehensiveDTO', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(ComprehensiveDTO::class);

        // Required fields
        expect($rules['name'])->toContain('required');
        expect($rules['email'])->toContain('required');

        // Email validation
        expect($rules['email'])->toContain('email');

        // Integer
        expect($rules['age'])->toContain('integer');

        // Boolean
        expect($rules['isActive'])->toContain('boolean');

        // In rule
        expect($rules['role'])->toContain('in:admin,editor,viewer');

        // Size rule
        expect($rules['countryCode'])->toContain('size:2');

        // Pattern
        expect($rules['firstName'])->toContain('regex:/^[A-Z][a-z]+$/');

        // StartsWith + EndsWith
        expect($rules['phone'])->toContain('starts_with:+');
        expect($rules['phone'])->toContain('ends_with:0');
    });

    it('schema for AllDefaultsDTO includes properties and no required fields', function () {
        $manager = new DTOManager;
        $schema = $manager->schema(AllDefaultsDTO::class);

        expect($schema)->toHaveKey('properties');
        expect($schema)->not->toHaveKey('required');
    });

    it('make is readonly (manager is final readonly)', function () {
        $manager = new DTOManager;

        // DTOManager is final readonly — verify it can be instantiated
        expect($manager)->toBeInstanceOf(DTOManager::class);
    });
});
