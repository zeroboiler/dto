<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\OpenApiValidationDTO;

describe('OpenApiSchemaGenerator — Validation Attributes (#7)', function (): void {
    $schema = null;

    beforeEach(function () use (&$schema): void {
        if ($schema === null) {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
        }
        $this->schema = $schema;
        $this->props = (array) $schema['properties'];
    });

    describe('format constraints', function (): void {
        it('maps Email attribute to format: email', function (): void {
            expect($this->props['email']['format'])->toBe('email');
        });

        it('maps Url attribute to format: uri', function (): void {
            expect($this->props['website']['format'])->toBe('uri');
        });

        it('maps Uuid attribute to format: uuid', function (): void {
            expect($this->props['externalId']['format'])->toBe('uuid');
        });

        it('maps Date attribute to format: date', function (): void {
            expect($this->props['birthDate']['format'])->toBe('date');
        });
    });

    describe('pattern constraints', function (): void {
        it('maps Pattern attribute to pattern', function (): void {
            expect($this->props['code']['pattern'])->toBe('^[A-Z]{3}$');
        });

        it('maps StartsWith attribute to a prefix pattern', function (): void {
            expect($this->props['apiUrl']['pattern'])->toContain('^https\\:\\/\\/');
        });

        it('maps EndsWith attribute to a suffix pattern', function (): void {
            expect($this->props['workEmail']['pattern'])->toContain('@company\\.com$');
        });
    });

    describe('type overrides', function (): void {
        it('maps Integer attribute to type: integer', function (): void {
            expect($this->props['quantity']['type'])->toBe('integer');
        });

        it('maps Numeric attribute to type: number', function (): void {
            expect($this->props['price']['type'])->toBe('number');
        });
    });

    describe('Min/Max for string types', function (): void {
        it('maps Min on string to minLength', function (): void {
            expect($this->props['name']['minLength'])->toBe(2);
        });

        it('maps Max on string to maxLength', function (): void {
            expect($this->props['name']['maxLength'])->toBe(50);
        });
    });

    describe('Min/Max for numeric types', function (): void {
        it('maps Min on integer to minimum', function (): void {
            expect($this->props['quantity']['minimum'])->toBe(1);
            expect($this->props['quantity'])->not->toHaveKey('minLength');
        });

        it('maps Max on integer to maximum', function (): void {
            expect($this->props['quantity']['maximum'])->toBe(100);
            expect($this->props['quantity'])->not->toHaveKey('maxLength');
        });
    });

    describe('Between constraint', function (): void {
        it('maps Between on number to minimum and maximum', function (): void {
            expect($this->props['price']['minimum'])->toBe(0);
            expect($this->props['price']['maximum'])->toBe(99);
            expect($this->props['price'])->not->toHaveKey('minLength');
            expect($this->props['price'])->not->toHaveKey('maxLength');
        });
    });

    describe('Required attribute', function (): void {
        it('forces nullable field into required list', function (): void {
            expect($this->schema['required'])->toContain('optionalButRequired');
        });

        it('field is still marked as nullable', function (): void {
            expect($this->props['optionalButRequired']['nullable'])->toBeTrue();
        });
    });
});
