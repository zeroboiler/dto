<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Required, Email, Hidden, Max, Min, Cast, MapFrom, DefaultValue, Nullable};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

// ── Fixtures ──────────────────────────────────────────────────

class ExceptOnlyDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $secret = '',
    ) {}
}

class PartialNullHandlingDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        #[Nullable]
        public readonly ?string $nickname = null,
        public readonly int $score = 0,
    ) {}
}

class DefaultValueDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[DefaultValue(25)]
        public readonly int $perPage = 25,

        #[DefaultValue(true)]
        public readonly bool $flag = true,
    ) {}
}

class MapFromDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly ?string $name = null,

        #[MapFrom('user_email')]
        public readonly ?string $email = null,
    ) {}
}

class SimpleDtoForCollection extends DataTransferObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
    ) {}
}

describe('DTO edge case boundary conditions', function () {
    // ── except() with non-existent key ─────────────────────────
    describe('except() edge cases', function () {
        it('silently ignores non-existent keys', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'secret' => 'hunter2',
            ], validate: false);

            $result = $dto->except('nonexistent', 'also_missing');

            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('nonexistent');
            expect($result)->not->toHaveKey('secret');
        });

        it('excludes all specified keys when all exist', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Bob',
                'email' => 'bob@example.com',
            ], validate: false);

            $result = $dto->except('name');

            expect($result)->not->toHaveKey('name');
            expect($result)->toHaveKey('email');
        });

        it('accepts a single string key', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Charlie',
                'email' => 'charlie@example.com',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });
    });

    // ── only() edge cases ───────────────────────────────────────
    describe('only() edge cases', function () {
        it('returns empty array when all keys are non-existent', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Dave',
                'email' => 'dave@example.com',
            ], validate: false);

            $result = $dto->only('nonexistent');

            expect($result)->toBe([]);
        });

        it('accepts a single string key and returns one field', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Eve',
                'email' => 'eve@example.com',
            ], validate: false);

            $result = $dto->only('name');

            expect($result)->toHaveCount(1);
            expect($result['name'])->toBe('Eve');
        });

        it('returns subset with multiple keys', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Frank',
                'email' => 'frank@example.com',
                'secret' => 'p@ssw0rd',
            ], validate: false);

            $result = $dto->only('name', 'email');

            expect($result)->toHaveCount(2);
            expect($result)->not->toHaveKey('secret');
        });
    });

    // ── equals() with different values ─────────────────────────
    describe('equals() comparison', function () {
        it('returns true for same values', function () {
            $dto1 = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $dto2 = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('returns false for different values', function () {
            $dto1 = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $dto2 = ExceptOnlyDTO::fromArray(['name' => 'Bob', 'email' => 'a@b.com'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('hidden fields are excluded from equals comparison', function () {
            $dto1 = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com', 'secret' => 'x'], validate: false);
            $dto2 = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com', 'secret' => 'y'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    // ── isEmpty / isNotEmpty ───────────────────────────────────
    describe('isEmpty and isNotEmpty', function () {
        it('isEmpty returns true for DTO with all defaults/nulls', function () {
            $dto = PartialNullHandlingDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
        });

        it('isEmpty returns false when a string property has a value', function () {
            $dto = PartialNullHandlingDTO::fromArray(['name' => 'Alice'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('isEmpty returns false when score is 0 (0 is valid value)', function () {
            // score=0 is set explicitly
            $dto = PartialNullHandlingDTO::fromArray(['name' => '', 'score' => 0], validate: false);

            // name is empty string, nickname is null -> score 0 is NOT empty for non-nullable
            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty is the negation of isEmpty', function () {
            $empty = PartialNullHandlingDTO::fromArray([], validate: false);
            $nonEmpty = PartialNullHandlingDTO::fromArray(['name' => 'Bob'], validate: false);

            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });
    });

    // ── fromPartialArray edge cases ────────────────────────────
    describe('fromPartialArray edge cases', function () {
        it('uses defaults for missing fields', function () {
            $dto = PartialNullHandlingDTO::fromPartialArray(['name' => 'Alice'], validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->nickname)->toBeNull();
            expect($dto->score)->toBe(0);
        });

        it('respects explicit null values as intentional', function () {
            $dto = PartialNullHandlingDTO::fromPartialArray([
                'name' => 'Alice',
                'nickname' => null,
            ], validate: false);

            expect($dto->nickname)->toBeNull();
        });

        it('overrides defaults when data is provided', function () {
            $dto = DefaultValueDTO::fromPartialArray([
                'status' => 'inactive',
                'perPage' => 50,
            ], validate: false);

            expect($dto->status)->toBe('inactive');
            expect($dto->perPage)->toBe(50);
            expect($dto->flag)->toBeTrue(); // default
        });
    });

    // ── MapFrom with dot notation ──────────────────────────────
    describe('MapFrom attribute', function () {
        it('maps flat source keys correctly', function () {
            $dto = MapFromDTO::fromArray([
                'user_name' => 'Alice',
                'user_email' => 'alice@example.com',
            ], validate: false);

            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('uses default when mapped source key is absent', function () {
            $dto = MapFromDTO::fromArray([], validate: false);

            expect($dto->name)->toBeNull();
            expect($dto->email)->toBeNull();
        });
    });

    // ── DefaultValue attribute ─────────────────────────────────
    describe('DefaultValue attribute', function () {
        it('applies default when key is absent', function () {
            $dto = DefaultValueDTO::fromArray([], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->perPage)->toBe(25);
            expect($dto->flag)->toBeTrue();
        });

        it('allows override of defaults', function () {
            $dto = DefaultValueDTO::fromArray([
                'status' => 'suspended',
                'flag' => false,
            ], validate: false);

            expect($dto->status)->toBe('suspended');
            expect($dto->flag)->toBeFalse();
            expect($dto->perPage)->toBe(25); // still default
        });
    });

    // ── DtoCollection push fluent chain ────────────────────────
    describe('DtoCollection fluent operations', function () {
        it('push() is fluent — returns same collection instance', function () {
            $dto1 = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = SimpleDtoForCollection::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $collection = new DtoCollection([$dto1]);
            $returned = $collection->push($dto2);

            expect($returned)->toBe($collection); // same instance
            expect($collection->count())->toBe(2);
            expect($collection->last())->toBe($dto2);
        });

        it('append() returns a new collection (immutable)', function () {
            $dto1 = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = SimpleDtoForCollection::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $original = new DtoCollection([$dto1]);
            $clone = $original->append($dto2);

            expect($clone)->not->toBe($original);
            expect($original->count())->toBe(1);
            expect($clone->count())->toBe(2);
        });

        it('merge() combines two collections immutably', function () {
            $d1 = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = SimpleDtoForCollection::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $d3 = SimpleDtoForCollection::fromArray(['id' => '3', 'label' => 'C'], validate: false);

            $col1 = new DtoCollection([$d1]);
            $col2 = new DtoCollection([$d2, $d3]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(2);
        });

        it('filter() returns a new collection with matching items', function () {
            $d1 = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = SimpleDtoForCollection::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $d3 = SimpleDtoForCollection::fromArray(['id' => '3', 'label' => 'A'], validate: false);

            $col = new DtoCollection([$d1, $d2, $d3]);
            $filtered = $col->filter(fn ($dto) => $dto->label === 'A');

            expect($filtered->count())->toBe(2);
            expect($col->count())->toBe(3); // original unchanged
        });

        it('map() returns plain array of results', function () {
            $d1 = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = SimpleDtoForCollection::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $labels = $col->map(fn ($dto) => $dto->label);

            expect($labels)->toEqual(['A', 'B']);
        });

        it('isEmpty / isNotEmpty on collection', function () {
            $empty = new DtoCollection;
            $nonEmpty = new DtoCollection([
                SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'A'], validate: false),
            ]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });
    });

    // ── DtoCollection jsonSerialize ────────────────────────────
    describe('DtoCollection serialization', function () {
        it('jsonSerialize returns array of toArray results', function () {
            $dto = SimpleDtoForCollection::fromArray(['id' => '1', 'label' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);

            $json = json_encode($col);

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded[0])->toHaveKey('id');
            expect($decoded[0])->toHaveKey('label');
        });
    });

    // ── with() always validates ────────────────────────────────
    describe('with() immutable update', function () {
        it('creates a new instance with updated values', function () {
            $dto = ExceptOnlyDTO::fromArray(['name' => 'Alice', 'email' => 'a@b.com'], validate: false);
            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice'); // original unchanged
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('a@b.com'); // preserved
        });
    });

    // ── allValues includes hidden ─────────────────────────────
    describe('allValues includes hidden fields', function () {
        it('toArray excludes hidden, allValues includes them', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Alice',
                'email' => 'a@b.com',
                'secret' => 'hunter2',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('secret');
            expect($dto->allValues())->toHaveKey('secret');
            expect($dto->allValues()['secret'])->toBe('hunter2');
        });
    });

    // ── toJson ────────────────────────────────────────────────
    describe('toJson serialization', function () {
        it('produces valid JSON', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Alice',
                'email' => 'a@b.com',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded['name'])->toBe('Alice');
        });

        it('respects JSON encoding options', function () {
            $dto = ExceptOnlyDTO::fromArray([
                'name' => 'Alice',
                'email' => 'a@b.com',
            ], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect(str_contains($json, "\n"))->toBeTrue();
        });
    });

    // ── rules() and rulesFor() ─────────────────────────────────
    describe('validation rules', function () {
        it('rules() returns array with all fields', function () {
            $rules = ExceptOnlyDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('email');
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = ExceptOnlyDTO::rules();
            $rulesForUpdate = ExceptOnlyDTO::rulesFor('update');

            expect($rules)->toEqual($rulesForUpdate);
        });
    });
});
