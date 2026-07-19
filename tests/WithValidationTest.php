<?php

/**
 * Tests for Issue #2: DTO::with() bypasses validation and type checking.
 *
 * Verifies that with() always validates data, preventing invalid values
 * from silently corrupting DTO state.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use Illuminate\Validation\ValidationException;

describe('Issue #2: with() validation bypass prevention', function (): void {
    it('rejects invalid email in with() overrides', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        expect(fn () => $dto->with(['email' => 'not-an-email']))
            ->toThrow(ValidationException::class);
    });

    it('rejects string value for integer-typed property in with()', function (): void {
        $dto = WithValidationDTO::fromArray([
            'count' => 5,
            'label' => 'test',
        ]);

        // 'count' is typed as int with Integer attribute — passing a non-numeric string
        // should fail validation
        expect(fn () => $dto->with(['count' => 'abc']))
            ->toThrow(ValidationException::class);
    });

    it('rejects value exceeding max constraint in with()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        // name has Max(50) — a 51+ char string should fail
        expect(fn () => $dto->with(['name' => str_repeat('x', 60)]))
            ->toThrow(ValidationException::class);
    });

    it('rejects value below min constraint in with()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        // name has Min(2) – a single char should fail
        expect(fn () => $dto->with(['name' => 'x']))
            ->toThrow(ValidationException::class);
    });

    it('does NOT bypass validation even when validate=false is passed', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        // Even with validate: false, with() must still validate (#2)
        expect(fn () => $dto->with(['email' => 'invalid'], validate: false))
            ->toThrow(ValidationException::class);
    });

    it('accepts valid overrides in with()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        $updated = $dto->with([
            'email' => 'updated@example.com',
            'name' => 'NewName',
        ]);

        expect($updated->email)->toBe('updated@example.com')
            ->and($updated->name)->toBe('NewName')
            ->and($dto->email)->toBe('valid@example.com'); // original unchanged
    });

    it('preserves immutability — original DTO is not modified', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'original@example.com',
            'name' => 'Original',
        ]);

        $updated = $dto->with(['name' => 'Updated']);

        expect($dto->name)->toBe('Original')
            ->and($updated->name)->toBe('Updated');
    });

    it('validates all merged data, not just overrides', function (): void {
        // Create a DTO, then use with() to override a field.
        // The entire merged dataset must pass validation.
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@example.com',
            'name' => 'Doruk',
        ]);

        // Even a valid override should work when all existing data is valid
        $updated = $dto->with(['status' => 'inactive']);

        expect($updated->status)->toBe('inactive')
            ->and($updated->email)->toBe('valid@example.com');
    });
});

/**
 * Simple DTO fixture for type-checking tests.
 */
class WithValidationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Integer, Min(0), Max(100)]
        public readonly int $count,

        #[Required]
        public readonly string $label,
    ) {}
}
