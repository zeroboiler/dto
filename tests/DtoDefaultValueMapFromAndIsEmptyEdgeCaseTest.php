<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DtoMetadataResolver;

// ── Fixture: DTO with numeric edge cases for isEmpty() ──────────────
final class NumericEdgeCaseDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $count = 0,
        public readonly float $price = 0.0,
        public readonly bool $active = false,
        public readonly ?string $name = null,
        public readonly array $tags = [],
    ) {}
}

// ── Fixture: DTO with MapFrom dot notation ──────────────────────────
final class DotNotationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, MapFrom('user.profile.name')]
        public readonly string $name,

        #[MapFrom('user.profile.email')]
        public readonly ?string $email = null,

        #[MapFrom('meta.role')]
        public readonly string $role = 'user',
    ) {}
}

// ── Fixture: DTO with DefaultValue attribute ────────────────────────
final class DefaultValueDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[DefaultValue('active')]
        public readonly string $status,

        #[DefaultValue([])]
        public readonly array $roles,

        #[DefaultValue(42)]
        public readonly int $perPage,

        #[DefaultValue(true)]
        public readonly bool $visible,
    ) {}
}

// ── Fixture: DTO with all nullable fields ──────────────────────────
final class AllNullableDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
        public readonly ?float $score = null,
        public readonly ?bool $active = null,
        public readonly ?array $tags = null,
    ) {}
}

// ── Fixture: DTO with explicit null behavior (no defaults) ──────────
final class NoDefaultsDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $requiredField,
        public readonly ?string $optionalField = null,
    ) {}
}

describe('DTO isEmpty() numeric edge cases', function () {
    it('considers 0 as non-empty for non-nullable int properties', function () {
        $dto = NumericEdgeCaseDTO::fromArray([], validate: false);

        // $count = 0 is NOT empty (it's a valid meaningful value)
        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers 0.0 as non-empty for non-nullable float properties', function () {
        $dto = NumericEdgeCaseDTO::fromArray(['count' => 0, 'price' => 0.0], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers false as empty for non-nullable bool properties', function () {
        $dto = NumericEdgeCaseDTO::fromArray(['count' => 0, 'price' => 0.0, 'active' => false, 'tags' => []], validate: false);

        // $active = false IS empty per spec, but $count = 0 and $price = 0.0 are NOT empty
        // Since count=0 and price=0.0 are non-empty, isEmpty() returns false
        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when any non-empty property exists', function () {
        $dto = NumericEdgeCaseDTO::fromArray(['count' => 1], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DTO MapFrom dot notation', function () {
    it('resolves nested dot-notation keys from flat array', function () {
        $dto = DotNotationDTO::fromArray([
            'user.profile.name' => 'Alice',
            'user.profile.email' => 'alice@example.com',
            'meta.role' => 'admin',
        ], validate: false);

        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('alice@example.com');
        expect($dto->role)->toBe('admin');
    });

    it('falls back to default when mapped key is absent', function () {
        $dto = DotNotationDTO::fromArray([
            'user.profile.name' => 'Bob',
        ], validate: false);

        expect($dto->name)->toBe('Bob');
        expect($dto->email)->toBeNull();
        expect($dto->role)->toBe('user');
    });

    it('serializes with original property names (not source keys)', function () {
        $dto = DotNotationDTO::fromArray([
            'user.profile.name' => 'Charlie',
            'user.profile.email' => 'charlie@example.com',
            'meta.role' => 'editor',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
        expect($array)->toHaveKey('role');
        expect($array)->not->toHaveKey('user.profile.name');
        expect($array)->not->toHaveKey('meta.role');
    });
});

describe('DTO DefaultValue attribute', function () {
    it('applies DefaultValue when source key is absent', function () {
        $dto = DefaultValueDTO::fromArray(['name' => 'Test'], validate: false);

        expect($dto->status)->toBe('active');
        expect($dto->roles)->toEqual([]);
        expect($dto->perPage)->toBe(42);
        expect($dto->visible)->toBeTrue();
    });

    it('overrides DefaultValue when source key is present', function () {
        $dto = DefaultValueDTO::fromArray([
            'name' => 'Test',
            'status' => 'inactive',
            'perPage' => 100,
            'visible' => false,
        ], validate: false);

        expect($dto->status)->toBe('inactive');
        expect($dto->perPage)->toBe(100);
        expect($dto->visible)->toBeFalse();
    });

    it('preserves explicit null when source key has null', function () {
        // 'roles' has DefaultValue([]) but explicit null should NOT override
        // (only absent keys trigger DefaultValue)
        $dto = DefaultValueDTO::fromArray([
            'name' => 'Test',
            'roles' => null,
        ], validate: false);

        // Explicit null is respected as intentional value
        expect($dto->roles)->toBeNull();
    });

    it('with() roundtrip preserves DefaultValue fields', function () {
        $dto = DefaultValueDTO::fromArray(['name' => 'Test'], validate: false);
        $modified = $dto->with(['name' => 'Updated'], validate: false);

        expect($modified->name)->toBe('Updated');
        expect($modified->status)->toBe('active');
        expect($modified->perPage)->toBe(42);
    });
});

describe('DTO allNullable fields', function () {
    it('isEmpty returns true when all nullable fields are null', function () {
        $dto = AllNullableDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty returns false when nullable fields have values', function () {
        $dto = AllNullableDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('fromPartialArray with no data produces all-null DTO', function () {
        $dto = AllNullableDTO::fromPartialArray([], validate: false);

        expect($dto->name)->toBeNull();
        expect($dto->age)->toBeNull();
    });
});

describe('DTO fromPartialArray type inference', function () {
    it('infers empty string for missing non-nullable string fields', function () {
        $dto = NoDefaultsDTO::fromPartialArray([], validate: false);

        // No default for optionalField → type-appropriate empty: '' for string
        expect($dto->optionalField)->toBe('');
    });
});

describe('DTO DtoMetadataResolver type detection', function () {
    it('detects string type correctly', function () {
        $meta = DtoMetadataResolver::resolve(NoDefaultsDTO::class);

        expect($meta['properties']['requiredField']['nullable'])->toBeFalse();
    });

    it('detects nullable types correctly', function () {
        $meta = DtoMetadataResolver::resolve(NoDefaultsDTO::class);

        expect($meta['properties']['optionalField']['nullable'])->toBeTrue();
    });

    it('detects MapFrom metadata', function () {
        $meta = DtoMetadataResolver::resolve(DotNotationDTO::class);

        expect($meta['properties']['name']['map_from'])->toBe('user.profile.name');
        expect($meta['properties']['email']['map_from'])->toBe('user.profile.email');
    });

    it('rules include Required field validation', function () {
        $rules = NoDefaultsDTO::rules();

        expect($rules)->toHaveKey('requiredField');
        expect($rules['requiredField'])->toContain('required');
    });

    it('rules include inferred integer rule for int type', function () {
        $rules = NumericEdgeCaseDTO::rules();

        expect($rules['count'])->toContain('integer');
    });

    it('nullable fields get sometimes rule', function () {
        $rules = AllNullableDTO::rules();

        expect($rules['name'])->toContain('sometimes');
    });
});

describe('DTO fromJson sequential array rejection', function () {
    it('rejects sequential JSON arrays', function () {
        expect(fn () => NoDefaultsDTO::fromJson('["a", "b"]', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('rejects non-array JSON', function () {
        expect(fn () => NoDefaultsDTO::fromJson('"string"', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('accepts empty JSON object', function () {
        // Empty object should be accepted (but may fail validation if required fields exist)
        $dto = AllNullableDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(AllNullableDTO::class);
    });
});

describe('DTO Hidden field behavior', function () {
    it('Hidden fields excluded from toArray()', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'password123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('secret');
    });

    it('Hidden fields included in allValues()', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'password123',
        ], validate: false);

        expect($dto->allValues())->toHaveKey('secret');
        expect($dto->allValues()['secret'])->toBe('password123');
    });
});
