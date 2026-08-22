<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO — Cast attribute edge cases', function (): void {
    final class CastDTO extends DataTransferObject
    {
        public function __construct(
            #[Cast('integer')]
            public readonly int|string $count,

            #[Cast('boolean')]
            public readonly bool|string $active,

            #[Cast('float')]
            public readonly float|string $price,

            #[Cast('string')]
            public readonly string|int $label,
        ) {}
    }

    it('casts string to integer', function (): void {
        $dto = CastDTO::fromArray(['count' => '42', 'active' => 'true', 'price' => '99.99', 'label' => 123], validate: false);

        expect($dto->count)->toBe(42);
    });

    it('casts string to boolean', function (): void {
        $dto = CastDTO::fromArray(['count' => 0, 'active' => 'yes', 'price' => 0.0, 'label' => ''], validate: false);

        expect($dto->active)->toBeTrue();
    });

    it('casts string to float', function (): void {
        $dto = CastDTO::fromArray(['count' => 0, 'active' => false, 'price' => '49.95', 'label' => ''], validate: false);

        expect($dto->price)->toBe(49.95);
    });

    it('casts int to string', function (): void {
        $dto = CastDTO::fromArray(['count' => 0, 'active' => false, 'price' => 0.0, 'label' => 42], validate: false);

        expect($dto->label)->toBe('42');
    });
});

describe('DTO — Pattern and Uuid validation', function (): void {
    final class PatternUuidDTO extends DataTransferObject
    {
        public function __construct(
            #[Required, Pattern('/^[A-Z]{3}-\d{4}$/')]
            public readonly string $code,

            #[Required, Uuid]
            public readonly string $id,
        ) {}
    }

    it('validates pattern correctly', function (): void {
        $rules = PatternUuidDTO::rules();

        expect($rules['code'])->toContain('regex:/^[A-Z]{3}-\d{4}$/');
    });

    it('validates UUID format in rules', function (): void {
        $rules = PatternUuidDTO::rules();

        expect($rules['id'])->toContain('uuid');
    });

    it('creates DTO with valid pattern and UUID', function (): void {
        $dto = PatternUuidDTO::fromArray([
            'code' => 'ABC-1234',
            'id' => '550e8400-e29b-41d4-a716-446655440000',
        ], validate: false);

        expect($dto->code)->toBe('ABC-1234');
        expect($dto->id)->toBe('550e8400-e29b-41d4-a716-446655440000');
    });
});

describe('DTO — StartsWith validation attribute', function (): void {
    final class StartsWithDTO extends DataTransferObject
    {
        public function __construct(
            #[Required, StartsWith('https://')]
            public readonly string $url,
        ) {}
    }

    it('generates starts_with rule', function (): void {
        $rules = StartsWithDTO::rules();

        expect($rules['url'])->toContain('starts_with:https://');
    });
});

describe('DTO — In validation attribute', function (): void {
    final class InDTO extends DataTransferObject
    {
        public function __construct(
            #[Required, In(['draft', 'published', 'archived'])]
            public readonly string $status,
        ) {}
    }

    it('generates in rule with comma-separated values', function (): void {
        $rules = InDTO::rules();

        expect($rules['status'])->toContain('in:draft,published,archived');
    });
});

describe('DTO — Date validation attribute', function (): void {
    final class DateAttrDTO extends DataTransferObject
    {
        public function __construct(
            #[Date]
            public readonly string $birthDate,

            #[Date(format: 'd-m-Y')]
            public readonly string $customDate,
        ) {}
    }

    it('generates date rule without format', function (): void {
        $rules = DateAttrDTO::rules();

        expect($rules['birthDate'])->toContain('date');
    });

    it('generates date_format rule with custom format', function (): void {
        $rules = DateAttrDTO::rules();

        expect($rules['customDate'])->toContain('date_format:d-m-Y');
    });
});

describe('DTO — Nullable + Required edge cases', function (): void {
    final class NullableRequiredDTO extends DataTransferObject
    {
        public function __construct(
            #[Required, Nullable]
            public readonly ?string $name,

            #[Required, Integer]
            public readonly int $age,
        ) {}
    }

    it('generates required, nullable, and integer rules together', function (): void {
        $rules = NullableRequiredDTO::rules();

        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('nullable');
        expect($rules['age'])->toContain('required');
        expect($rules['age'])->toContain('integer');
    });
});

describe('DTO — equals() and isEmpty() edge cases', function (): void {
    it('equals returns true for identical DTOs', function (): void {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function (): void {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns true when all fields are null', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty returns false when a field has a value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty is the inverse of isEmpty', function (): void {
        $dtoEmpty = EmptyDTO::fromArray([], validate: false);
        $dtoFilled = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dtoEmpty->isNotEmpty())->toBeFalse();
        expect($dtoFilled->isNotEmpty())->toBeTrue();
    });

    it('isEmpty considers zero as empty', function (): void {
        final class ZeroDTO extends DataTransferObject
        {
            public function __construct(
                public $count = 0,
            ) {}
        }

        $dto = ZeroDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty considers false as empty', function (): void {
        final class FalseDTO extends DataTransferObject
        {
            public function __construct(
                public bool $active = false,
            ) {}
        }

        $dto = FalseDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });
});

describe('DTO — only() and except() edge cases', function (): void {
    it('only() with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'test@example.com']);
    });

    it('only() with multiple keys as string arguments', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveKeys(['email', 'name']);
    });

    it('only() ignores hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret',
        ], validate: false);

        // password is hidden, so toArray() won't have it
        // only() uses toArray(), so it can't select hidden fields
        $result = $dto->only('password');

        expect($result)->toBeEmpty();
    });

    it('except() with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        expect($result)->not->toHaveKey('status');
        expect($result)->toHaveKeys(['email', 'name']);
    });

    it('except() silently ignores non-existent keys', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toBe(['foo' => 'bar']);
    });
});

describe('DTO — toJson() encoding', function (): void {
    it('returns valid JSON string', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        expect(json_decode($json, true))->toBe($dto->toArray());
    });

    it('jsonSerialize returns same as toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DTO — rulesFor() action scoping', function (): void {
    it('returns same rules for unknown action by default', function (): void {
        $defaultRules = CreateUserDTO::rules();
        $actionRules = CreateUserDTO::rulesFor('unknown_action');

        expect($actionRules)->toBe($defaultRules);
    });
});

describe('DTO — fromPartialArray() with explicit nulls', function (): void {
    it('preserves explicit null values', function (): void {
        $dto = EmptyDTO::fromPartialArray(['foo' => null], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('overrides defaults with provided values', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'status' => 'inactive',
        ], validate: false);

        expect($dto->status)->toBe('inactive');
    });
});

describe('DTO — validatePartialArray returns only provided data', function (): void {
    it('passes validation for partial data with valid fields', function (): void {
        $result = EmptyDTO::validatePartialArray(['foo' => 'bar']);

        expect($result)->toBe(['foo' => 'bar']);
    });

    it('returns empty array when no data provided', function (): void {
        $result = EmptyDTO::validatePartialArray([]);

        expect($result)->toBe([]);
    });
});
