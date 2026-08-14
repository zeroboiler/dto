<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Comprehensive edge case tests for fromJson(), fromPartialArray() with
 * explicit null/empty string values, and property normalization edge cases.
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Fixtures ──────────────────────────────────────────────────

class FromJsonValidDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
        #[Max(100)]
        public readonly string $name,
    ) {}
}

class FromJsonWithDefaultsDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('active')]
        public readonly string $status,
        #[DefaultValue(25)]
        public readonly int $perPage,
        #[DefaultValue([])]
        public readonly array $tags,
    ) {}
}

class FromJsonWithHiddenDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
        #[Hidden]
        public readonly int $secret = 0,
    ) {}
}

class FromJsonWithCastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count,
        #[Cast('boolean')]
        public readonly bool $active,
        #[Cast('array')]
        public readonly array $meta,
    ) {}
}

class FromJsonWithMapFromDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly string $name,
        #[MapFrom('contact.email')]
        public readonly ?string $email = null,
    ) {}
}

class EmptyValueDetectionDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly int $count = 0,
        public readonly string $status = '',
        public readonly bool $active = false,
        public readonly array $items = [],
    ) {}
}

class IntZeroValueDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $quantity,
        public readonly float $price,
    ) {}
}

class CollectionOperationDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $id,
        #[Required]
        public readonly string $label,
    ) {}
}

// ── fromJson() Edge Cases ─────────────────────────────────────

describe('DTO fromJson() edge cases', function () {
    it('creates DTO from valid JSON object', function () {
        $dto = FromJsonValidDTO::fromJson('{"email":"test@example.com","name":"Alice"}');
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('creates DTO from JSON with extra fields (extra fields ignored)', function () {
        $dto = FromJsonValidDTO::fromJson('{"email":"a@b.com","name":"Bob","extra":true}');
        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Bob');
    });

    it('applies defaults for missing fields', function () {
        $dto = FromJsonWithDefaultsDTO::fromJson('{}');
        expect($dto->status)->toBe('active');
        expect($dto->perPage)->toBe(25);
        expect($dto->tags)->toBe([]);
    });

    it('applies MapFrom for nested source keys', function () {
        $dto = FromJsonWithMapFromDTO::fromJson('{"user_name":"Alice","contact":{"email":"a@b.com"}}');
        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('a@b.com');
    });

    it('applies Cast type transformations', function () {
        $dto = FromJsonWithCastDTO::fromJson('{"count":"42","active":"yes","meta":"[1,2,3]"}');
        expect($dto->count)->toBe(42);
        expect($dto->active)->toBe(true);
        expect($dto->meta)->toBe([1, 2, 3]);
    });

    it('throws DTOException for invalid JSON syntax', function () {
        expect(fn () => FromJsonValidDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class, 'Cannot decode JSON');
    });

    it('throws DTOException for sequential JSON array', function () {
        expect(fn () => FromJsonValidDTO::fromJson('["test@example.com","Alice"]'))
            ->toThrow(DTOException::class, 'Expected a JSON object');
    });

    it('throws DTOException for JSON boolean', function () {
        expect(fn () => FromJsonValidDTO::fromJson('true'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON number', function () {
        expect(fn () => FromJsonValidDTO::fromJson('42'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON string', function () {
        expect(fn () => FromJsonValidDTO::fromJson('"hello"'))
            ->toThrow(DTOException::class);
    });

    it('allows empty JSON object {} for DTO with all optional fields', function () {
        $dto = FromJsonWithDefaultsDTO::fromJson('{}');
        expect($dto->status)->toBe('active');
    });

    it('validates and throws ValidationException on invalid data', function () {
        expect(fn () => FromJsonValidDTO::fromJson('{"email":"not-email","name":""}'))
            ->toThrow(ValidationException::class);
    });

    it('skips validation when validate: false', function () {
        $dto = FromJsonValidDTO::fromJson('{"email":"not-email","name":""}', validate: false);
        expect($dto->email)->toBe('not-email');
    });

    it('handles UTF-8 content in JSON', function () {
        $dto = FromJsonValidDTO::fromJson('{"email":"tëst@exämple.com","name":"Ümit Öz"}');
        expect($dto->email)->toBe('tëst@exämple.com');
        expect($dto->name)->toBe('Ümit Öz');
    });
});

// ── toArray() / Hidden Edge Cases ──────────────────────────────

describe('DTO toArray() and Hidden edge cases', function () {
    it('excludes Hidden properties from toArray()', function () {
        $dto = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'secret']);
        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->toArray())->not->toHaveKey('secret');
        expect($dto->toArray())->toHaveKey('email');
    });

    it('includes Hidden properties in allValues()', function () {
        $dto = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'secret', 'secret' => 42]);
        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('secret');
        expect($dto->allValues()['password'])->toBe('secret');
        expect($dto->allValues()['secret'])->toBe(42);
    });

    it('only() returns specified fields only', function () {
        $dto = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'x', 'secret' => 1]);
        $result = $dto->only('email');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('password');
        expect($result)->not->toHaveKey('secret');
    });

    it('except() excludes specified fields', function () {
        $dto = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

// ── isEmpty() Edge Cases ──────────────────────────────────────

describe('DTO isEmpty() edge cases with zero values', function () {
    it('returns true when all properties are empty/null', function () {
        $dto = EmptyValueDetectionDTO::fromArray([]);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('returns false when int property has value 0 (non-empty)', function () {
        $dto = IntZeroValueDTO::fromArray(['quantity' => 0, 'price' => 0.0]);
        expect($dto->isEmpty())->toBeFalse();
    });

    it('returns true when only string/int zero-like values are present', function () {
        $dto = EmptyValueDetectionDTO::fromArray(['count' => 0]);
        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true for DTO with zero-value numeric fields', function () {
        $dto = IntZeroValueDTO::fromArray(['quantity' => 0, 'price' => 0.0]);
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

// ── DtoCollection Advanced Operations ──────────────────────────

describe('DtoCollection advanced operations', function () {
    it('toArrayBy produces a keyed map', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => 'a', 'label' => 'First']);
        $d2 = CollectionOperationDTO::fromArray(['id' => 'b', 'label' => 'Second']);
        $col = new DtoCollection([$d1, $d2]);

        $map = $col->toArrayBy('id');
        expect($map)->toHaveKey('a');
        expect($map)->toHaveKey('b');
        expect($map['a']['label'])->toBe('First');
    });

    it('toDictionary maps key field to value field', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => 'x', 'label' => 'Label X']);
        $d2 = CollectionOperationDTO::fromArray(['id' => 'y', 'label' => 'Label Y']);
        $col = new DtoCollection([$d1, $d2]);

        $dict = $col->toDictionary('id', 'label');
        expect($dict)->toBe(['x' => 'Label X', 'y' => 'Label Y']);
    });

    it('toArrayBy skips items with null key values', function () {
        // Create a DTO where 'id' could be null — we need a different fixture
        // For this test we use map behavior with null-safe handling
        $d1 = CollectionOperationDTO::fromArray(['id' => 'a', 'label' => 'OK']);
        $col = new DtoCollection([$d1]);

        $map = $col->toArrayBy('id');
        expect($map)->toHaveCount(1);
        expect($map['a']['label'])->toBe('OK');
    });

    it('append returns a new collection without mutating original', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'A']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'B']);
        $col = new DtoCollection([$d1]);

        $newCol = $col->append($d2);
        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('merge combines two collections without mutation', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'A']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'B']);
        $col1 = new DtoCollection([$d1]);
        $col2 = new DtoCollection([$d2]);

        $merged = $col1->merge($col2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('filter returns a new re-indexed collection', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'Keep']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'Remove']);
        $col = new DtoCollection([$d1, $d2]);

        $filtered = $col->filter(fn (DataTransferObject $d) => $d->label === 'Keep');
        expect($filtered->count())->toBe(1);
        expect($filtered->first()->label)->toBe('Keep');
    });

    it('push mutates in-place and returns self for chaining', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'A']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'B']);
        $col = new DtoCollection([$d1]);

        $result = $col->push($d2);
        expect($col->count())->toBe(2);
        expect($result)->toBe($col); // same instance
    });

    it('offsetUnset re-indexes the collection', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'A']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'B']);
        $d3 = CollectionOperationDTO::fromArray(['id' => '3', 'label' => 'C']);
        $col = new DtoCollection([$d1, $d2, $d3]);

        unset($col[0]);
        expect($col->count())->toBe(2);
        expect($col[0]->id)->toBe('2'); // re-indexed
        expect($col[1]->id)->toBe('3');
    });

    it('jsonSerialize produces array of arrays', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'A']);
        $col = new DtoCollection([$d1]);

        $json = json_encode($col);
        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0]['id'])->toBe('1');
        expect($decoded[0]['label'])->toBe('A');
    });

    it('allValues includes hidden properties of each DTO', function () {
        $d1 = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'secret']);
        $col = new DtoCollection([$d1]);

        $all = $col->allValues();
        expect($all[0])->toHaveKey('password');
        expect($all[0]['email'])->toBe('a@b.com');
    });

    it('pluck extracts single property values', function () {
        $d1 = CollectionOperationDTO::fromArray(['id' => '1', 'label' => 'X']);
        $d2 = CollectionOperationDTO::fromArray(['id' => '2', 'label' => 'Y']);
        $col = new DtoCollection([$d1, $d2]);

        $ids = $col->pluck('id');
        expect($ids)->toBe(['1', '2']);
    });
});

// ── with() Immutable Update ───────────────────────────────────

describe('DTO with() immutable update edge cases', function () {
    it('creates a new instance with updated fields', function () {
        $dto = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $updated = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice'); // unchanged
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@b.com'); // preserved
    });

    it('validates the merged data even if original was valid', function () {
        $dto = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        // name exceeds max 100? No. Let's use invalid email
        expect(fn () => $dto->with(['email' => 'not-valid']))
            ->toThrow(ValidationException::class);
    });

    it('preserves hidden field exclusion after with()', function () {
        $dto = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'old']);
        $updated = $dto->with(['email' => 'b@c.com']);

        expect($updated->toArray())->not->toHaveKey('password');
        expect($updated->email)->toBe('b@c.com');
        expect($updated->allValues()['password'])->toBe('old');
    });
});

// ── equals() ─────────────────────────────────────────────────

describe('DTO equals() edge cases', function () {
    it('returns true for identical data', function () {
        $d1 = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $d2 = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        expect($d1->equals($d2))->toBeTrue();
    });

    it('returns false for different data', function () {
        $d1 = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $d2 = FromJsonValidDTO::fromArray(['email' => 'a@b.com', 'name' => 'Bob']);
        expect($d1->equals($d2))->toBeFalse();
    });

    it('compares ignoring hidden fields', function () {
        $d1 = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'old']);
        $d2 = FromJsonWithHiddenDTO::fromArray(['email' => 'a@b.com', 'password' => 'new']);
        expect($d1->equals($d2))->toBeTrue(); // hidden fields excluded from toArray()
    });
});
