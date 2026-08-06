<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\ConstraintCompositeDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('Constraint composite tests', function (): void {
    describe('Between attribute validation rules', function (): void {
        it('generates between rule with integer bounds', function (): void {
            $rules = ConstraintCompositeDTO::rules();

            expect($rules['score'])->toContain('between:1,255');
            expect($rules['score'])->toContain('integer');
            expect($rules['score'])->toContain('required');
        });

        it('generates min/max rules for string properties', function (): void {
            $rules = ConstraintCompositeDTO::rules();

            expect($rules['username'])->toContain('min:3');
            expect($rules['username'])->toContain('max:100');
            expect($rules['username'])->toContain('required');
        });
    });

    describe('StartsWith/EndsWith validation rules', function (): void {
        it('generates starts_with rule', function (): void {
            $rules = ConstraintCompositeDTO::rules();

            expect($rules['website'])->toContain('starts_with:https://');
            expect($rules['website'])->toContain('required');
        });

        it('generates ends_with rule', function (): void {
            $rules = ConstraintCompositeDTO::rules();

            expect($rules['email'])->toContain('ends_with:@zeroboiler.dev');
            expect($rules['email'])->toContain('required');
        });
    });

    describe('OpenAPI schema generation with constraints', function (): void {
        it('generates minLength and maxLength from Min/Max on string', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            $usernameSchema = $schema['properties']->username;
            expect($usernameSchema)->toHaveKey('minLength');
            expect($usernameSchema)->toHaveKey('maxLength');
            expect($usernameSchema['minLength'])->toBe(3);
            expect($usernameSchema['maxLength'])->toBe(100);
        });

        it('generates minimum and maximum from Between on integer', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            $scoreSchema = $schema['properties']->score;
            expect($scoreSchema)->toHaveKey('minimum');
            expect($scoreSchema)->toHaveKey('maximum');
            expect($scoreSchema['minimum'])->toBe(1);
            expect($scoreSchema['maximum'])->toBe(255);
            expect($scoreSchema['type'])->toBe('integer');
        });

        it('generates pattern from StartsWith', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            $websiteSchema = $schema['properties']->website;
            expect($websiteSchema)->toHaveKey('pattern');
            expect($websiteSchema['pattern'])->toBe('^https\:\/\/');
        });

        it('generates pattern from EndsWith', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            $emailSchema = $schema['properties']->email;
            expect($emailSchema)->toHaveKey('pattern');
            // EndsWith produces a suffix pattern
            expect($emailSchema['pattern'])->toContain('@zeroboiler\.dev');
        });

        it('excludes hidden properties from schema', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            expect((array) $schema['properties'])->not->toHaveKey('secret');
        });

        it('includes required properties list', function (): void {
            $schema = OpenApiSchemaGenerator::generate(ConstraintCompositeDTO::class);

            expect($schema)->toHaveKey('required');
            expect($schema['required'])->toContain('username');
            expect($schema['required'])->toContain('score');
            expect($schema['required'])->toContain('website');
            expect($schema['required'])->toContain('email');
            expect($schema['required'])->not->toContain('secret');
        });
    });

    describe('validation execution', function (): void {
        it('validates min/max string length', function (): void {
            $result = ConstraintCompositeDTO::validateArray([
                'username' => 'AB',           // too short (min:3)
                'score' => 50,
                'website' => 'https://test.com',
                'email' => 'user@zeroboiler.dev',
            ]);

            // Should fail because username is too short
        })->throws(\Illuminate\Validation\ValidationException::class);

        it('validates between numeric bounds', function (): void {
            $result = ConstraintCompositeDTO::validateArray([
                'username' => 'testuser',
                'score' => 500,               // exceeds between:1,255
                'website' => 'https://test.com',
                'email' => 'user@zeroboiler.dev',
            ]);

            // Should fail because score exceeds max
        })->throws(\Illuminate\Validation\ValidationException::class);

        it('validates starts_with prefix', function (): void {
            $result = ConstraintCompositeDTO::validateArray([
                'username' => 'testuser',
                'score' => 50,
                'website' => 'http://test.com',  // wrong prefix
                'email' => 'user@zeroboiler.dev',
            ]);

            // Should fail because website doesn't start with https://
        })->throws(\Illuminate\Validation\ValidationException::class);

        it('validates ends_with suffix', function (): void {
            $result = ConstraintCompositeDTO::validateArray([
                'username' => 'testuser',
                'score' => 50,
                'website' => 'https://test.com',
                'email' => 'user@gmail.com',   // wrong suffix
            ]);

            // Should fail because email doesn't end with @zeroboiler.dev
        })->throws(\Illuminate\Validation\ValidationException::class);

        it('passes validation with all valid data', function (): void {
            $result = ConstraintCompositeDTO::validateArray([
                'username' => 'testuser',
                'score' => 100,
                'website' => 'https://zeroboiler.dev',
                'email' => 'admin@zeroboiler.dev',
            ]);

            expect($result)->toBeArray();
            expect($result['username'])->toBe('testuser');
            expect($result['score'])->toBe(100);
        });

        it('creates DTO from valid array', function (): void {
            $dto = ConstraintCompositeDTO::fromArray([
                'username' => 'admin',
                'score' => 42,
                'website' => 'https://zeroboiler.dev',
                'email' => 'info@zeroboiler.dev',
                'secret' => 'hidden-value',
            ]);

            expect($dto->username)->toBe('admin');
            expect($dto->score)->toBe(42);
            expect($dto->secret)->toBe('hidden-value');

            // Hidden property excluded from toArray
            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('secret');

            // But present in allValues
            $all = $dto->allValues();
            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('hidden-value');
        });
    });
});
