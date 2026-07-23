<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

describe('Enum roundtrip in with()', function (): void {
    it('reconstructs a backed enum from its scalar value in fromArray()', function (): void {
        $dto = EnumRoundtripDTO::fromArray([
            'name' => 'Test',
            'priority' => 'high',
        ]);

        expect($dto->priority)->toBe(TestPriority::HIGH);
    });

    it('with() roundtrips enum properties correctly', function (): void {
        $dto = EnumRoundtripDTO::fromArray([
            'name' => 'Original',
            'priority' => 'low',
        ]);

        $updated = $dto->with(['name' => 'Updated']);

        // Original is unchanged
        expect($dto->name)->toBe('Original')
            ->and($dto->priority)->toBe(TestPriority::LOW)
            // Updated has new name
            ->and($updated->name)->toBe('Updated')
            // Enum is properly reconstructed as enum instance, not string
            ->and($updated->priority)->toBe(TestPriority::LOW);
    });

    it('with() can override enum property with a new enum instance', function (): void {
        $dto = EnumRoundtripDTO::fromArray([
            'name' => 'Test',
            'priority' => 'low',
        ]);

        $updated = $dto->with(['priority' => TestPriority::HIGH]);

        expect($updated->priority)->toBe(TestPriority::HIGH)
            ->and($dto->priority)->toBe(TestPriority::LOW);
    });

    it('with() can override enum property with a backed value', function (): void {
        $dto = EnumRoundtripDTO::fromArray([
            'name' => 'Test',
            'priority' => 'low',
        ]);

        $updated = $dto->with(['priority' => 'high']);

        expect($updated->priority)->toBe(TestPriority::HIGH);
    });

    it('fromPartialArray() also reconstructs enums from backed values', function (): void {
        $dto = EnumRoundtripDTO::fromPartialArray([
            'name' => 'Partial',
            'priority' => 'medium',
        ]);

        expect($dto->priority)->toBe(TestPriority::MEDIUM);
    });

    it('toArray() serializes enum to backed value', function (): void {
        $dto = EnumRoundtripDTO::fromArray([
            'name' => 'Test',
            'priority' => 'high',
        ]);

        $arr = $dto->toArray();

        expect($arr['priority'])->toBe('high');
    });
});

enum TestPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}

class EnumRoundtripDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required, Enum(TestPriority::class)]
        public readonly TestPriority $priority,
    ) {}
}
