<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Comprehensive edge case tests for DtoCollection toArrayBy(), toDictionary(),
 * pluck(), pluckKey() — PHPStan Level 9 type safety assertions.
 *
 * @see \ZeroBoiler\DTO\DtoCollection
 */

use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

// ── Test fixtures ──────────────────────────────────────────────

class IdNameDto extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        #[Required]
        public readonly string $name,
    ) {}
}

class NullableKeyDto extends DataTransferObject
{
    public function __construct(
        public ?int $id = null,
        #[Required]
        public string $name = '',
    ) {}
}

class CompositeDto extends DataTransferObject
{
    public function __construct(
        public readonly int $id,
        #[Required]
        public readonly string $name,
        #[Required]
        public readonly string $email,
    ) {}
}

// ── Tests ──────────────────────────────────────────────────────

describe('DtoCollection toArrayBy and toDictionary edge cases', function (): void {

    it('toArrayBy returns associative array keyed by property', function (): void {
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = IdNameDto::fromArray(['id' => 2, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->toArrayBy('id');
        expect($result)->toBeArray();
        expect($result)->toHaveKey('1');
        expect($result)->toHaveKey('2');
        expect($result[1])->toBe(['id' => 1, 'name' => 'Alice']);
        expect($result[2])->toBe(['id' => 2, 'name' => 'Bob']);
    });

    it('toArrayBy skips items with null key values', function (): void {
        $d1 = NullableKeyDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = NullableKeyDto::fromArray(['id' => null, 'name' => 'Bob'], validate: false);
        $d3 = NullableKeyDto::fromArray(['id' => 3, 'name' => 'Charlie'], validate: false);
        $col = DtoCollection::make([$d1, $d2, $d3]);

        $result = $col->toArrayBy('id');
        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('1');
        expect($result)->toHaveKey('3');
        expect($result)->not->toHaveKey(null);
    });

    it('toDictionary maps one property to another', function (): void {
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = IdNameDto::fromArray(['id' => 2, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->toDictionary('id', 'name');
        expect($result)->toBe([1 => 'Alice', 2 => 'Bob']);
    });

    it('toDictionary skips items with null key values', function (): void {
        $d1 = NullableKeyDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = NullableKeyDto::fromArray(['id' => null, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->toDictionary('id', 'name');
        expect($result)->toBe([1 => 'Alice']);
    });

    it('toArrayBy returns empty array for empty collection', function (): void {
        $col = DtoCollection::make([]);
        expect($col->toArrayBy('id'))->toBe([]);
    });

    it('toDictionary returns empty array for empty collection', function (): void {
        $col = DtoCollection::make([]);
        expect($col->toDictionary('id', 'name'))->toBe([]);
    });

    it('toArrayBy overwrites duplicate keys (last wins)', function (): void {
        // Same id, different name — last occurrence wins
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = IdNameDto::fromArray(['id' => 1, 'name' => 'Updated'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->toArrayBy('id');
        expect($result)->toHaveCount(1);
        expect($result[1])->toBe(['id' => 1, 'name' => 'Updated']);
    });

    it('pluck returns scalar values from property', function (): void {
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = IdNameDto::fromArray(['id' => 2, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        expect($col->pluck('id'))->toBe([1, 2]);
        expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
    });

    it('pluck returns null values correctly', function (): void {
        $d1 = NullableKeyDto::fromArray(['id' => null, 'name' => 'Alice'], validate: false);
        $d2 = NullableKeyDto::fromArray(['id' => 42, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $ids = $col->pluck('id');
        expect($ids)->toBe([null, 42]);
    });

    it('pluck returns empty array for empty collection', function (): void {
        expect(DtoCollection::make([])->pluck('name'))->toBe([]);
    });

    it('pluckKey returns full toArray when valueField is null', function (): void {
        $d1 = CompositeDto::fromArray(['id' => 1, 'name' => 'Alice', 'email' => 'a@b.com'], validate: false);
        $col = DtoCollection::make([$d1]);

        $result = $col->pluckKey('id');
        expect($result)->toHaveKey(1);
        expect($result[1])->toBe(['id' => 1, 'name' => 'Alice', 'email' => 'a@b.com']);
    });

    it('pluckKey returns string-keyed map', function (): void {
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $d2 = IdNameDto::fromArray(['id' => 2, 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->pluckKey('name', 'id');
        expect($result)->toBe(['Alice' => 1, 'Bob' => 2]);
    });
});

describe('DtoCollection JSON serialization with toArrayBy/toDictionary', function (): void {
    it('jsonSerialize returns plain arrays without hidden fields', function (): void {
        $d1 = IdNameDto::fromArray(['id' => 1, 'name' => 'Alice'], validate: false);
        $col = DtoCollection::make([$d1]);

        $json = json_encode($col);
        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0])->toBe(['id' => 1, 'name' => 'Alice']);
    });
});
