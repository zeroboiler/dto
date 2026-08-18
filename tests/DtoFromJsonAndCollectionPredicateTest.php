<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// --- Fixtures ---

class FromJsonProductDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,
        public readonly ?string $description = null,
    ) {}
}

class SearchItemDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $code,
        public readonly int $score,
    ) {}
}

class ContainsUserDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,
        public readonly bool $active,
    ) {}
}

// --- Tests ---

describe('DataTransferObject fromJson edge cases', function () {
    it('creates DTO from valid JSON string', function () {
        $dto = FromJsonProductDTO::fromJson('{"name": "Widget", "price": 42}');

        expect($dto->name)->toBe('Widget');
        expect($dto->price)->toBe(42);
        expect($dto->description)->toBeNull();
    });

    it('rejects invalid JSON with DTOException', function () {
        expect(fn () => FromJsonProductDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class);
    });

    it('rejects JSON array (sequential) with DTOException', function () {
        expect(fn () => FromJsonProductDTO::fromJson('["name", "Widget"]'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object as valid input', function () {
        // description has default null, but name and price are required
        // So this should throw ValidationException (required fields missing)
        expect(fn () => FromJsonProductDTO::fromJson('{}'))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('accepts empty JSON array as valid (empty object)', function () {
        // [] is both sequential list AND empty JSON object — allowed
        // But required fields will cause validation exception
        expect(fn () => FromJsonProductDTO::fromJson('[]'))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('can skip validation when validate=false', function () {
        $dto = FromJsonProductDTO::fromJson('{"name": "Widget", "price": 42}', validate: false);

        expect($dto->name)->toBe('Widget');
        expect($dto->price)->toBe(42);
    });

    it('throws DTOException with property context in error message', function () {
        try {
            FromJsonProductDTO::fromJson('{invalid');
            expect(true)->toBeFalse('Should have thrown');
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('(root)');
        }
    });

    it('roundtrips through toJson and fromJson', function () {
        $original = FromJsonProductDTO::fromArray([
            'name' => 'Widget',
            'price' => 42,
            'description' => 'A test widget',
        ]);

        $json = $original->toJson();
        $restored = FromJsonProductDTO::fromJson($json, validate: false);

        expect($restored->toArray())->toBe($original->toArray());
    });
});

describe('DtoCollection contains', function () {
    it('returns true when predicate matches at least one item', function () {
        $col = new DtoCollection([
            new ContainsUserDTO('a@test.com', false),
            new ContainsUserDTO('b@test.com', true),
        ]);

        expect($col->contains(fn (ContainsUserDTO $d) => $d->active === true))->toBeTrue();
    });

    it('returns false when predicate matches no items', function () {
        $col = new DtoCollection([
            new ContainsUserDTO('a@test.com', false),
            new ContainsUserDTO('b@test.com', false),
        ]);

        expect($col->contains(fn (ContainsUserDTO $d) => $d->active === true))->toBeFalse();
    });

    it('returns false for empty collection', function () {
        $col = new DtoCollection([]);

        expect($col->contains(fn () => true))->toBeFalse();
    });
});

describe('DtoCollection search', function () {
    it('returns first matching DTO', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 10),
            new SearchItemDTO('B', 20),
            new SearchItemDTO('C', 30),
        ]);

        $found = $col->search(fn (SearchItemDTO $d) => $d->score === 20);

        expect($found)->not->toBeNull();
        expect($found->code)->toBe('B');
    });

    it('returns null when no match', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 10),
        ]);

        expect($col->search(fn (SearchItemDTO $d) => $d->score === 999))->toBeNull();
    });

    it('returns null for empty collection', function () {
        $col = new DtoCollection([]);

        expect($col->search(fn () => true))->toBeNull();
    });

    it('returns first match when multiple items match', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 10),
            new SearchItemDTO('B', 10),
            new SearchItemDTO('C', 10),
        ]);

        $found = $col->search(fn (SearchItemDTO $d) => $d->score === 10);

        expect($found->code)->toBe('A');
    });
});

describe('DtoCollection take and skip', function () {
    it('take returns first N items', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
            new SearchItemDTO('B', 2),
            new SearchItemDTO('C', 3),
        ]);

        $taken = $col->take(2);

        expect($taken->count())->toBe(2);
        expect($taken->map(fn (SearchItemDTO $d) => $d->code))->toBe(['A', 'B']);
    });

    it('take with count larger than collection returns all items', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
        ]);

        expect($col->take(100)->count())->toBe(1);
    });

    it('take(0) returns empty collection', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
        ]);

        expect($col->take(0)->isEmpty())->toBeTrue();
    });

    it('skip returns items after N', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
            new SearchItemDTO('B', 2),
            new SearchItemDTO('C', 3),
        ]);

        $skipped = $col->skip(1);

        expect($skipped->count())->toBe(2);
        expect($skipped->map(fn (SearchItemDTO $d) => $d->code))->toBe(['B', 'C']);
    });

    it('skip with count larger than collection returns empty', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
        ]);

        expect($col->skip(100)->isEmpty())->toBeTrue();
    });
});

describe('DtoCollection chunk', function () {
    it('splits into chunks of correct size', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
            new SearchItemDTO('B', 2),
            new SearchItemDTO('C', 3),
            new SearchItemDTO('D', 4),
            new SearchItemDTO('E', 5),
        ]);

        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(3); // 2, 2, 1
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    it('single item chunk for size 1', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
            new SearchItemDTO('B', 2),
        ]);

        $chunks = $col->chunk(1);

        expect($chunks)->toHaveCount(2);
        expect($chunks[0]->first()?->code)->toBe('A');
        expect($chunks[1]->first()?->code)->toBe('B');
    });

    it('chunk larger than collection returns single chunk', function () {
        $col = new DtoCollection([
            new SearchItemDTO('A', 1),
        ]);

        $chunks = $col->chunk(100);

        expect($chunks)->toHaveCount(1);
        expect($chunks[0]->count())->toBe(1);
    });

    it('empty collection chunks to empty array', function () {
        $col = new DtoCollection([]);

        expect($col->chunk(2))->toBe([]);
    });
});
