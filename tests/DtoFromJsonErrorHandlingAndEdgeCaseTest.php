<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DTO fromJson validation and edge-case tests.
 *
 * Tests JSON decoding behavior, sequential array rejection, empty input handling,
 * invalid JSON error messages, and roundtrip integrity.
 *
 * @see \ZeroBoiler\DTO\DataTransferObject::fromJson()
 * @see \ZeroBoiler\DTO\Exceptions\DTOException
 */

use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

// ── Simple Test DTO ───────────────────────────────────────────

final class FromJsonEdgeDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,
    ) {}
}

// ── Tests ─────────────────────────────────────────────────────

describe('DTO fromJson — Valid Input', function (): void {
    it('creates DTO from valid JSON object', function (): void {
        $dto = FromJsonEdgeDTO::fromJson(
            '{"email":"test@example.com","name":"Alice"}',
            validate: false,
        );

        expect($dto)->toBeInstanceOf(FromJsonEdgeDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('creates DTO from JSON with extra fields (ignored)', function (): void {
        $dto = FromJsonEdgeDTO::fromJson(
            '{"email":"test@example.com","name":"Alice","age":30}',
            validate: false,
        );

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('creates DTO from empty JSON object', function (): void {
        $dto = CreateUserDTO::fromJson('{}', validate: false);

        // All fields use defaults when not present
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('');
        expect($dto->status)->toBe('active');
    });

    it('roundtrips DTO through JSON encode/decode', function (): void {
        $original = CreateUserDTO::fromArray([
            'email' => 'roundtrip@test.com',
            'name' => 'Roundtrip',
            'status' => 'active',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        $json = $original->toJson();
        $restored = CreateUserDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe($original->email);
        expect($restored->name)->toBe($original->name);
        expect($restored->status)->toBe($original->status);
        expect($restored->tags)->toBe($original->tags);
    });
});

describe('DTO fromJson — Error Handling', function (): void {
    it('throws DTOException for invalid JSON syntax', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for truncated JSON', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('{"email":"test', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('["test@example.com","Alice"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential numeric JSON array', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('[1, 2, 3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('allows empty JSON array [] (ambiguous empty object)', function (): void {
        // Empty array [] is ambiguous (could be empty object or empty list)
        // The DTO should accept it
        $dto = CreateUserDTO::fromJson('[]', validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('throws DTOException for JSON null', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('null', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON boolean', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('true', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON number', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('42', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON string (not object)', function (): void {
        expect(fn () => FromJsonEdgeDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('error message contains (root) context', function (): void {
        try {
            FromJsonEdgeDTO::fromJson('{bad}', validate: false);
            $this->fail('Expected DTOException');
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('(root)');
        }
    });
});

describe('DTO fromJson — Nested JSON Handling', function (): void {
    it('handles nested object JSON correctly', function (): void {
        $dto = CreateUserDTO::fromJson('{"email":"nested@test.com","name":"Nested"}', validate: false);

        expect($dto->email)->toBe('nested@test.com');
    });

    it('handles JSON with unicode characters', function (): void {
        $dto = FromJsonEdgeDTO::fromJson(
            '{"email":"test@example.com","name":"日本語テスト"}',
            validate: false,
        );

        expect($dto->name)->toBe('日本語テスト');
    });

    it('handles JSON with escaped characters', function (): void {
        $dto = FromJsonEdgeDTO::fromJson(
            '{"email":"test@example.com","name":"Line1\\nLine2"}',
            validate: false,
        );

        expect($dto->name)->toBe("Line1\nLine2");
    });
});

describe('DTO fromJson — Structural Contract', function (): void {
    it('fromJson has strict types', function (): void {
        $ref = new ReflectionMethod(CreateUserDTO::class, 'fromJson');
        $file = $ref->getFileName();
        $contents = file_get_contents((string) $file);
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('fromJson returns static type', function (): void {
        $ref = new ReflectionMethod(DataTransferObject::class, 'fromJson');
        $returnType = $ref->getReturnType();
        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe('static');
    });
});
