<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Issue #10: Tests for newly added validation attributes.
 */
describe('Issue #10: New validation attributes', function (): void {
    describe('Nullable', function (): void {
        it('allows null values when Nullable is set', function (): void {
            $dto = NullableTestDTO::fromArray([
                'name' => 'John',
                'bio' => null,
            ]);

            expect($dto->bio)->toBeNull();
        });
    });

    describe('Sometimes', function (): void {
        it('passes validation when sometimes field is omitted', function (): void {
            $dto = SometimesTestDTO::fromArray([
                'name' => 'John',
            ]);

            expect($dto->nickname)->toBeNull();
        });

        it('validates when sometimes field is present', function (): void {
            $dto = SometimesTestDTO::fromArray([
                'name' => 'John',
                'nickname' => 'Johnny',
            ]);

            expect($dto->nickname)->toBe('Johnny');
        });
    });

    describe('Accepted', function (): void {
        it('passes when terms accepted with true', function (): void {
            $dto = AcceptedTestDTO::fromArray([
                'name' => 'John',
                'terms' => true,
            ]);

            expect($dto->terms)->toBeTrue();
        });

        it('rejects when terms not accepted', function (): void {
            expect(fn () => AcceptedTestDTO::fromArray([
                'name' => 'John',
                'terms' => false,
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Size', function (): void {
        it('passes when string matches exact size', function (): void {
            $dto = SizeTestDTO::fromArray([
                'name' => 'John',
                'code' => 'ABCDE',
            ]);

            expect($dto->code)->toBe('ABCDE');
        });

        it('rejects when string does not match size', function (): void {
            expect(fn () => SizeTestDTO::fromArray([
                'name' => 'John',
                'code' => 'ABCD',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Json', function (): void {
        it('passes with valid JSON string', function (): void {
            $dto = JsonTestDTO::fromArray([
                'name' => 'John',
                'metadata' => '{"key":"value"}',
            ]);

            expect($dto->metadata)->toBe('{"key":"value"}');
        });

        it('rejects invalid JSON string', function (): void {
            expect(fn () => JsonTestDTO::fromArray([
                'name' => 'John',
                'metadata' => 'not-json{',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Distinct', function (): void {
        it('passes with unique array elements', function (): void {
            $dto = DistinctTestDTO::fromArray([
                'name' => 'John',
                'tags' => ['php', 'laravel', 'dto'],
            ]);

            expect($dto->tags)->toBe(['php', 'laravel', 'dto']);
        });

        it('rejects with duplicate array elements', function (): void {
            expect(fn () => DistinctTestDTO::fromArray([
                'name' => 'John',
                'tags' => ['php', 'php', 'laravel'],
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('RequiredIf', function (): void {
        it('requires field when condition met', function (): void {
            expect(fn () => RequiredIfTestDTO::fromArray([
                'name' => 'John',
                'type' => 'individual',
            ]))->toThrow(ValidationException::class);
        });

        it('does not require field when condition not met', function (): void {
            $dto = RequiredIfTestDTO::fromArray([
                'name' => 'John',
                'type' => 'company',
            ]);

            expect($dto->firstName)->toBeNull();
        });
    });

    describe('RequiredWith', function (): void {
        it('requires field when dependent field is present', function (): void {
            expect(fn () => RequiredWithTestDTO::fromArray([
                'name' => 'John',
                'email' => 'test@test.com',
            ]))->toThrow(ValidationException::class);
        });

        it('does not require field when dependent field is absent', function (): void {
            $dto = RequiredWithTestDTO::fromArray([
                'name' => 'John',
            ]);

            expect($dto->username)->toBeNull();
        });
    });
});

// --- Fixtures ---

class NullableTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Nullable]
        public readonly ?string $bio = null,
    ) {}
}

class SometimesTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Sometimes]
        public readonly ?string $nickname = null,
    ) {}
}

class AcceptedTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Accepted]
        public readonly mixed $terms = null,
    ) {}
}

class SizeTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Size(5)]
        public readonly ?string $code = null,
    ) {}
}

class JsonTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Json]
        public readonly ?string $metadata = null,
    ) {}
}

class DistinctTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Distinct]
        public readonly array $tags = [],
    ) {}
}

class RequiredIfTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[RequiredIf('type', 'individual')]
        public readonly ?string $firstName = null,

        public readonly ?string $type = null,
    ) {}
}

class RequiredWithTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[RequiredWith(['email'])]
        public readonly ?string $username = null,

        public readonly ?string $email = null,
    ) {}
}
