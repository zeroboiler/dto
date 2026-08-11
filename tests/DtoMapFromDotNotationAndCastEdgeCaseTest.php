<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

describe('MapFrom dot notation with partial updates', function () {
    it('fromArray resolves dot notation keys', function () {
        $dto = TestMapFromDto::fromArray([
            'user' => [
                'display_name' => 'Alice',
            ],
        ], validate: false);

        expect($dto->displayName)->toBe('Alice');
    });

    it('fromPartialArray resolves dot notation keys', function () {
        $dto = TestMapFromDto::fromPartialArray([
            'user' => [
                'display_name' => 'Updated',
            ],
        ], validate: false);

        expect($dto->displayName)->toBe('Updated');
    });

    it('fromArray resolves flat MapFrom key', function () {
        $dto = TestMapFromDto::fromArray([
            'source_email' => 'test@example.com',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
    });

    it('prefers explicit value over default when key is present', function () {
        $dto = TestMapFromDto::fromArray([
            'user' => [
                'display_name' => 'Bob',
            ],
        ], validate: false);

        expect($dto->displayName)->toBe('Bob');
    });

    it('falls back to default when mapped key is absent', function () {
        $dto = TestMapFromDto::fromArray([], validate: false);

        expect($dto->displayName)->toBe('Guest');
    });

    it('toArray uses property names not source keys', function () {
        $dto = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
            'user' => ['display_name' => 'Alice'],
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('displayName');
        expect($arr)->not->toHaveKey('source_email');
        expect($arr)->not->toHaveKey('user');
    });

    it('rules use property names as keys', function () {
        $rules = TestMapFromDto::rules();

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('displayName');
        expect($rules)->not->toHaveKey('source_email');
    });

    it('only() and except() work with mapped properties', function () {
        $dto = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
            'user' => ['display_name' => 'Alice'],
        ], validate: false);

        expect($dto->only('email'))->toHaveKey('email');
        expect($dto->except('email'))->not->toHaveKey('email');
    });

    it('with() roundtrip preserves mapped values', function () {
        $dto = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
            'user' => ['display_name' => 'Alice'],
        ], validate: false);

        $updated = $dto->with(['displayName' => 'Bob']);

        expect($updated->displayName)->toBe('Bob');
        expect($updated->email)->toBe('a@example.com');
    });

    it('equals compares serialized output correctly', function () {
        $dto1 = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
            'user' => ['display_name' => 'Alice'],
        ], validate: false);
        $dto2 = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
            'user' => ['display_name' => 'Alice'],
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different values', function () {
        $dto1 = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
        ], validate: false);
        $dto2 = TestMapFromDto::fromArray([
            'source_email' => 'b@example.com',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('Type casting edge cases', function () {
    it('casts string to integer', function () {
        $dto = TestCastDto::fromArray([
            'count' => '42',
        ], validate: false);

        expect($dto->count)->toBe(42);
        expect($dto->count)->toBeInt();
    });

    it('casts string to boolean', function () {
        $dto = TestCastDto::fromArray([
            'active' => 'yes',
        ], validate: false);

        expect($dto->active)->toBeTrue();
    });

    it('casts zero string to false boolean', function () {
        $dto = TestCastDto::fromArray([
            'active' => '0',
        ], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('casts JSON string to array', function () {
        $dto = TestCastDto::fromArray([
            'meta' => '{"key":"value"}',
        ], validate: false);

        expect($dto->meta)->toBe(['key' => 'value']);
    });

    it('returns empty array for empty string cast to array', function () {
        $dto = TestCastDto::fromArray([
            'meta' => '',
        ], validate: false);

        expect($dto->meta)->toBe([]);
    });

    it('preserves existing array for array cast', function () {
        $dto = TestCastDto::fromArray([
            'meta' => ['already' => 'array'],
        ], validate: false);

        expect($dto->meta)->toBe(['already' => 'array']);
    });

    it('casts non-numeric string to 0 for integer', function () {
        $dto = TestCastDto::fromArray([
            'count' => 'not-a-number',
        ], validate: false);

        expect($dto->count)->toBe(0);
    });

    it('casts float string to float', function () {
        $dto = TestCastDto::fromArray([
            'price' => '19.99',
        ], validate: false);

        expect($dto->price)->toBe(19.99);
        expect($dto->price)->toBeFloat();
    });
});

describe('isEmpty and isNotEmpty behavior', function () {
    it('returns true when all properties have empty/default values', function () {
        $dto = TestMapFromDto::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false when at least one property has value', function () {
        $dto = TestMapFromDto::fromArray([
            'source_email' => 'a@example.com',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('considers zero as non-empty for non-nullable int', function () {
        $dto = TestCastDto::fromArray([
            'count' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('considers 0.0 as non-empty for non-nullable float', function () {
        $dto = TestCastDto::fromArray([
            'price' => 0.0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

// ── Fixtures ────────────────────────────────────────────────────

final class TestMapFromDto extends DataTransferObject
{
    public function __construct(
        #[MapFrom('source_email')]
        public readonly string $email = '',

        #[MapFrom('user.display_name')]
        #[DefaultValue('Guest')]
        public readonly string $displayName = 'Guest',
    ) {}
}

final class TestCastDto extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count = 0,

        #[Cast('boolean')]
        public readonly bool $active = false,

        #[Cast('array')]
        public readonly array $meta = [],

        #[Cast('float')]
        public readonly float $price = 0.0,
    ) {}
}
