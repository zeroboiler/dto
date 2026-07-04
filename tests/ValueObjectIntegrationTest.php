<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;
use ZeroBoiler\ValueObjects\Currency;
use ZeroBoiler\ValueObjects\Email;
use ZeroBoiler\ValueObjects\Money;
use ZeroBoiler\ValueObjects\Url;

describe('DTO × ValueObjects: fromArray() auto-instantiation', function (): void {
    it('auto-instantiates single-value VO from string', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'Test@Example.COM',
        ], validate: false);

        expect($dto->email)->toBeInstanceOf(Email::class)
            ->and((string) $dto->email)->toBe('test@example.com');
    });

    it('auto-instantiates URL VO from string', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'website' => 'https://zeroboiler.dev',
        ], validate: false);

        expect($dto->website)->toBeInstanceOf(Url::class)
            ->and((string) $dto->website)->toBe('https://zeroboiler.dev');
    });

    it('auto-instantiates Money VO from array', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'balance' => ['amount' => 2500, 'currency' => 'USD'],
        ], validate: false);

        expect($dto->balance)->toBeInstanceOf(Money::class)
            ->and($dto->balance->amount)->toBe(2500)
            ->and($dto->balance->currency)->toBe('USD');
    });

    it('auto-instantiates Money VO from JSON string', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'balance' => '{"amount": 5000, "currency": "EUR"}',
        ], validate: false);

        expect($dto->balance)->toBeInstanceOf(Money::class)
            ->and($dto->balance->amount)->toBe(5000)
            ->and($dto->balance->currency)->toBe('EUR');
    });

    it('auto-instantiates Money VO from integer (USD fallback)', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'balance' => 1500,
        ], validate: false);

        expect($dto->balance)->toBeInstanceOf(Money::class)
            ->and($dto->balance->amount)->toBe(1500)
            ->and($dto->balance->currency)->toBe('USD');
    });

    it('handles null nullable VO properties', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        expect($dto->email)->toBeInstanceOf(Email::class)
            ->and($dto->website)->toBeNull()
            ->and($dto->balance)->toBeNull();
    });

    it('accepts pre-constructed VO instances without re-construction', function (): void {
        $email = new Email('direct@example.com');

        $dto = VoUserDTO::fromArray([
            'email' => $email,
        ], validate: false);

        expect($dto->email)->toBe($email);
    });

    it('throws on invalid VO value during construction', function (): void {
        expect(fn () => VoUserDTO::fromArray([
            'email' => 'not-an-email',
        ], validate: false))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('DTO × ValueObjects: toArray() serialization', function (): void {
    it('serializes single-value VO to primitive', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'website' => 'https://zeroboiler.dev',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['email'])->toBe('test@example.com')
            ->and($array['website'])->toBe('https://zeroboiler.dev');
    });

    it('serializes composite VO to array', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'balance' => ['amount' => 2500, 'currency' => 'USD'],
        ], validate: false);

        $array = $dto->toArray();

        expect($array['balance'])->toBe(['amount' => 2500, 'currency' => 'USD']);
    });

    it('serializes to JSON correctly', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
            'website' => 'https://zeroboiler.dev',
            'balance' => ['amount' => 100, 'currency' => 'USD'],
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['email'])->toBe('test@example.com')
            ->and($decoded['website'])->toBe('https://zeroboiler.dev')
            ->and($decoded['balance'])->toBe(['amount' => 100, 'currency' => 'USD']);
    });

    it('serializes null VO as null', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['email'])->toBe('test@example.com')
            ->and($array)->toHaveKey('website')
            ->and($array['website'])->toBeNull()
            ->and($array['balance'])->toBeNull();
    });
});

describe('DTO × ValueObjects: equals() comparison', function (): void {
    it('compares DTOs with VOs by value', function (): void {
        $dto1 = VoUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $dto2 = VoUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $dto3 = VoUserDTO::fromArray([
            'email' => 'other@example.com',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue()
            ->and($dto1->equals($dto3))->toBeFalse();
    });
});

describe('DTO × ValueObjects: with() immutability', function (): void {
    it('creates copy with VO override', function (): void {
        $dto = VoUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $updated = $dto->with([
            'email' => 'updated@example.com',
        ], validate: false);

        expect((string) $dto->email)->toBe('test@example.com')
            ->and((string) $updated->email)->toBe('updated@example.com');
    });
});

describe('DTO × ValueObjects: OpenApiSchemaGenerator', function (): void {
    it('infers string type for Email VO', function (): void {
        $schema = OpenApiSchemaGenerator::generate(VoUserDTO::class);
        $props = (array) $schema['properties'];

        expect($schema['type'])->toBe('object')
            ->and($props['email']['type'])->toBe('string');
    });

    it('infers string type for Url VO', function (): void {
        $schema = OpenApiSchemaGenerator::generate(VoUserDTO::class);
        $props = (array) $schema['properties'];

        expect($props['website']['type'])->toBe('string');
    });

    it('infers object type for Money VO (composite)', function (): void {
        $schema = OpenApiSchemaGenerator::generate(VoUserDTO::class);
        $props = (array) $schema['properties'];

        expect($props['balance']['type'])->toBe('object');
    });

    it('marks email as required', function (): void {
        $schema = OpenApiSchemaGenerator::generate(VoUserDTO::class);

        expect($schema['required'])->toContain('email');
    });
});
