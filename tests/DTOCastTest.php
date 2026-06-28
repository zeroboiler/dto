<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

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
