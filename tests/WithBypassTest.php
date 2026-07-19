<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

describe('Issue #4: fromArray() + with() validation chain', function (): void {
    it('validates on initial fromArray() call', function (): void {
        expect(fn (): BypassTestDTO => BypassTestDTO::fromArray([
            'email' => 'invalid',
            'count' => 5,
        ]))->toThrow(ValidationException::class);
    });

    it('cannot bypass validation by creating with validate:false then calling with()', function (): void {
        // Create with validation disabled (edge case)
        $dto = BypassTestDTO::fromArray([
            'email' => 'valid@example.com',
            'count' => 5,
        ], validate: false);

        // with() must still validate the full merged dataset
        expect(fn (): BypassTestDTO => $dto->with(['email' => 'invalid']))
            ->toThrow(ValidationException::class);
    });

    it('with() validates complete merged state, not just overrides', function (): void {
        $dto = BypassTestDTO::fromArray([
            'email' => 'valid@example.com',
            'count' => 5,
        ]);

        // Override count with an invalid value that exceeds max
        expect(fn (): BypassTestDTO => $dto->with(['count' => 999]))
            ->toThrow(ValidationException::class);
    });

    it('with() preserves valid state when overrides are valid', function (): void {
        $dto = BypassTestDTO::fromArray([
            'email' => 'valid@example.com',
            'count' => 5,
        ]);

        $updated = $dto->with([
            'email' => 'new@example.com',
            'count' => 10,
        ]);

        expect($updated->email)->toBe('new@example.com')
            ->and($updated->count)->toBe(10)
            ->and($dto->email)->toBe('valid@example.com');
    });

    it('chained with() calls each validate', function (): void {
        $dto = BypassTestDTO::fromArray([
            'email' => 'first@example.com',
            'count' => 1,
        ]);

        $updated = $dto->with(['email' => 'second@example.com']);

        expect(fn (): BypassTestDTO => $updated->with(['email' => 'bad']))
            ->toThrow(ValidationException::class);
    });
});

class BypassTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Integer, Min(0), Max(100)]
        public readonly int $count,
    ) {}
}
