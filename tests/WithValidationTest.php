<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('with() validation (issue #2)', function (): void {
    it('validates overrides by default and rejects invalid email', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
        ], validate: false);

        expect(fn (): CreateUserDTO => $dto->with(['email' => 'not-an-email']))
            ->toThrow(ValidationException::class);
    });

    it('validates overrides by default and rejects too-short name', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
        ], validate: false);

        // min:2 rule on name
        expect(fn (): CreateUserDTO => $dto->with(['name' => 'X']))
            ->toThrow(ValidationException::class);
    });

    it('validates overrides by default and rejects missing required fields', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
        ], validate: false);

        // name has min:1 — empty string should fail
        expect(fn (): ProductDTO => $dto->with(['name' => '']))
            ->toThrow(ValidationException::class);
    });

    it('accepts valid overrides when validation is enabled (default)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
        ], validate: false);

        $updated = $dto->with(['email' => 'new@example.com']);

        expect($updated->email)->toBe('new@example.com');
        expect($updated->name)->toBe('Doruk');
    });

    it('allows opt-out of validation via validate: false', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
        ], validate: false);

        // Invalid email, but validation skipped — should succeed
        $updated = $dto->with(['email' => 'not-an-email'], validate: false);

        expect($updated->email)->toBe('not-an-email');
    });

    it('preserves hidden fields when creating copy (uses allValues, not toArray)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $updated = $dto->with(['name' => 'NewName'], validate: false);

        // password is hidden — toArray() would lose it, but with() must preserve it
        expect($updated->password)->toBe('secret123');
        expect($updated->name)->toBe('NewName');

        // Confirm toArray() still hides password on the copy
        $array = $updated->toArray();
        expect($array)->not->toHaveKey('password');
    });

    it('does not mutate the original DTO (immutability)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $updated = $dto->with(['status' => 'inactive'], validate: false);

        expect($dto->status)->toBe('active');
        expect($updated->status)->toBe('inactive');
    });

    it('merges overrides onto existing values (not replacing entire DTO)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'keep@test.com',
            'name' => 'KeepName',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        $updated = $dto->with(['tags' => ['new-tag']], validate: false);

        // tags should be replaced (merge semantics: override key wins)
        expect($updated->tags)->toBe(['new-tag']);
        // other fields should be preserved
        expect($updated->email)->toBe('keep@test.com');
        expect($updated->name)->toBe('KeepName');
    });

    it('validates complete merged data, not just overrides', function (): void {
        // Start with an invalid DTO (bypass validation to create invalid state)
        $dto = ProductDTO::fromArray([
            'name' => '',     // invalid (min:1) but bypassed
            'price' => '10.00',
        ], validate: false);

        // Even though we override a valid field, the existing invalid
        // field should be caught when validation runs on the merged data
        expect(fn (): ProductDTO => $dto->with(['price' => '20.00']))
            ->toThrow(ValidationException::class);
    });

    it('applies type casting during with() round-trip', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'valid@test.com',
            'name' => 'Doruk',
            'tags' => ['php'],
        ], validate: false);

        // Override tags with a JSON string — should be cast to array
        $updated = $dto->with(['tags' => '["new", "tags"]'], validate: false);

        expect($updated->tags)->toBe(['new', 'tags']);
    });
});
