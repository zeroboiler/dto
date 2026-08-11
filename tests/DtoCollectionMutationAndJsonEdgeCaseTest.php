<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

describe('DtoCollection re-indexing and mutation edge cases', function () {
    it('re-indexes after offsetUnset preserving iteration order', function () {
        $dtoList = [
            new TestSimpleDTO('a@example.com', 'Alice'),
            new TestSimpleDTO('b@example.com', 'Bob'),
            new TestSimpleDTO('c@example.com', 'Charlie'),
        ];

        $col = new DtoCollection($dtoList);
        expect($col->count())->toBe(3);

        // Remove middle element
        unset($col[1]);

        // Should be re-indexed: [0] => Alice, [1] => Charlie
        expect($col->count())->toBe(2);
        expect($col[0]->name)->toBe('Alice');
        expect($col[1]->name)->toBe('Charlie');
        expect($col->offsetExists(2))->toBeFalse();
    });

    it('last() works correctly after offsetUnset', function () {
        $col = new DtoCollection([
            new TestSimpleDTO('a@example.com', 'Alice'),
            new TestSimpleDTO('b@example.com', 'Bob'),
        ]);

        unset($col[0]);

        $last = $col->last();
        expect($last)->not->toBeNull();
        expect($last->name)->toBe('Bob');
    });

    it('push returns same instance for chaining', function () {
        $col = new DtoCollection();
        $dto = new TestSimpleDTO('a@example.com', 'Alice');

        $result = $col->push($dto);

        expect($result)->toBe($col); // Same instance
        expect($col->count())->toBe(1);
    });

    it('append returns new instance (immutable)', function () {
        $dto1 = new TestSimpleDTO('a@example.com', 'Alice');
        $dto2 = new TestSimpleDTO('b@example.com', 'Bob');

        $col1 = new DtoCollection([$dto1]);
        $col2 = $col1->append($dto2);

        expect($col1)->not->toBe($col2); // Different instances
        expect($col1->count())->toBe(1); // Original unchanged
        expect($col2->count())->toBe(2); // New has both
    });

    it('offsetSet with null appends to end', function () {
        $col = new DtoCollection();
        $dto1 = new TestSimpleDTO('a@example.com', 'Alice');
        $dto2 = new TestSimpleDTO('b@example.com', 'Bob');

        $col[] = $dto1;
        $col[] = $dto2;

        expect($col->count())->toBe(2);
        expect($col[0]->name)->toBe('Alice');
        expect($col[1]->name)->toBe('Bob');
    });

    it('offsetSet with index replaces existing element', function () {
        $col = new DtoCollection([
            new TestSimpleDTO('a@example.com', 'Alice'),
        ]);

        $replacement = new TestSimpleDTO('b@example.com', 'Bob');
        $col[0] = $replacement;

        expect($col->count())->toBe(1);
        expect($col[0]->name)->toBe('Bob');
    });

    it('filter returns new DtoCollection with matching items', function () {
        $col = new DtoCollection([
            new TestSimpleDTO('a@example.com', 'Alice'),
            new TestSimpleDTO('b@example.com', 'Bob'),
            new TestSimpleDTO('c@example.com', 'Charlie'),
        ]);

        $filtered = $col->filter(fn (TestSimpleDTO $dto): bool => str_starts_with($dto->name, 'A') || str_starts_with($dto->name, 'B'));

        expect($filtered)->toBeInstanceOf(DtoCollection::class);
        expect($filtered->count())->toBe(2);
    });

    it('merge combines two collections into new instance', function () {
        $col1 = new DtoCollection([
            new TestSimpleDTO('a@example.com', 'Alice'),
        ]);
        $col2 = new DtoCollection([
            new TestSimpleDTO('b@example.com', 'Bob'),
        ]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1); // Originals unchanged
        expect($col2->count())->toBe(1);
    });

    it('isEmpty and isNotEmpty work correctly', function () {
        $empty = new DtoCollection();
        $nonEmpty = new DtoCollection([new TestSimpleDTO('a@example.com', 'Alice')]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('items() returns raw DTO instances', function () {
        $dto = new TestSimpleDTO('a@example.com', 'Alice');
        $col = new DtoCollection([$dto]);

        $items = $col->items();

        expect($items)->toBe([$dto]);
    });

    it('allValues() includes hidden fields from nested DTOs', function () {
        $dto = new TestHiddenDTO('secret@example.com', 'Alice', 'p@ss');
        $col = new DtoCollection([$dto]);

        $allValues = $col->allValues();

        expect($allValues[0])->toHaveKey('password');
    });

    it('toArray() excludes hidden fields from nested DTOs', function () {
        $dto = new TestHiddenDTO('secret@example.com', 'Alice', 'p@ss');
        $col = new DtoCollection([$dto]);

        $arr = $col->toArray();

        expect($arr[0])->not->toHaveKey('password');
        expect($arr[0])->toHaveKey('email');
    });

    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in offsetSet', function () {
        $col = new DtoCollection();

        expect(fn () => $col[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('jsonSerialize produces array of arrays', function () {
        $col = new DtoCollection([
            new TestSimpleDTO('a@example.com', 'Alice'),
        ]);

        $result = $col->jsonSerialize();

        expect($result)->toBeArray();
        expect($result[0])->toBeArray();
        expect($result[0])->toHaveKey('email');
    });
});

describe('fromJson edge cases', function () {
    it('throws DTOException for invalid JSON', function () {
        expect(fn () => TestSimpleDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function () {
        expect(fn () => TestSimpleDTO::fromJson('["a@example.com", "Alice"]'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        $dto = TestSimpleDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(TestSimpleDTO::class);
    });

    it('accepts valid JSON with correct fields', function () {
        $dto = TestSimpleDTO::fromJson('{"email":"a@example.com","name":"Alice"}', validate: false);

        expect($dto->email)->toBe('a@example.com');
        expect($dto->name)->toBe('Alice');
    });
});

// ── Fixtures ────────────────────────────────────────────────────

final class TestSimpleDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}
}

final class TestHiddenDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}
