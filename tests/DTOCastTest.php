<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateTimeCastDTO;

describe('DTOCast', function (): void {
    it('casts JSON to DTO instance', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            model: new class {},
            key: 'payload',
            value: json_encode([
                'email' => 'cast@example.com',
                'name' => 'Cast Test',
            ]),
            attributes: [],
        );

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
        expect($result->email)->toBe('cast@example.com');
    });

    it('returns null for null value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            model: new class {},
            key: 'payload',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });

    it('sets DTO to JSON string', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'set@example.com',
            'name' => 'Set Test',
        ], validate: false);

        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: $dto,
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('set@example.com');
    });

    it('handles array input in set', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            model: new class {},
            key: 'payload',
            value: ['email' => 'arr@example.com', 'name' => 'Arr'],
            attributes: [],
        );

        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('arr@example.com');
    });
});

it('casts date strings to Carbon instances (IMP-3 R34)', function (): void {
    $dto = DateCastDTO::fromArray(['event_date' => '2026-07-04'], validate: false);

    expect($dto->event_date)->toBeInstanceOf(Carbon::class)
        ->and($dto->event_date->toDateString())->toBe('2026-07-04');
});

it('casts datetime strings to Carbon instances (IMP-3 R34)', function (): void {
    $dto = DateTimeCastDTO::fromArray(['created_at' => '2026-07-04T19:37:00Z'], validate: false);

    expect($dto->created_at)->toBeInstanceOf(Carbon::class)
        ->and($dto->created_at->year)->toBe(2026)
        ->and($dto->created_at->month)->toBe(7)
        ->and($dto->created_at->day)->toBe(4);
});

it('passes through existing DateTimeInterface instances for date cast', function (): void {
    $date = new DateTime('2026-01-15');
    $dto = DateCastDTO::fromArray(['event_date' => $date], validate: false);

    expect($dto->event_date)->toBeInstanceOf(DateTimeInterface::class);
});

describe('DTOCast::serialize()', function (): void {
    it('serializes DTO to array', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'ser@example.com',
            'name' => 'Serialize Test',
        ], validate: false);

        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'payload',
            value: $dto,
            attributes: [],
        );

        expect($result)->toBeArray()
            ->and($result['email'])->toBe('ser@example.com')
            ->and($result['name'])->toBe('Serialize Test')
            ->and($result)->not->toHaveKey('password'); // hidden
    });

    it('returns null for null value', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->serialize(
            model: new class {},
            key: 'payload',
            value: null,
            attributes: [],
        );

        expect($result)->toBeNull();
    });
});
