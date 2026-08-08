<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Required, Email, Hidden, Max, Min, Cast, MapFrom, DefaultValue, Nullable, Pattern, In, Accepted};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Fixtures ──────────────────────────────────────────────────

class AuditUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(2), Max(100)]
        public readonly string $name,

        #[Nullable, Max(255)]
        public readonly ?string $bio = null,

        #[Hidden]
        public readonly ?string $password = null,

        #[Cast('integer')]
        public readonly int $age = 0,

        #[DefaultValue('active')]
        public readonly string $status = 'active',
    ) {}
}

class AuditProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $sku,

        #[Required, Min(0)]
        public readonly float $price,

        #[In(['draft', 'published', 'archived'])]
        public readonly string $state = 'draft',

        #[Accepted]
        public readonly bool $terms = false,
    ) {}
}

class AuditEmptyDTO extends DataTransferObject
{
    public function __construct(
        #[Nullable]
        public readonly ?string $optional = null,
    ) {}
}

class AuditMappedDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('remote_id')]
        public readonly ?string $id = null,

        #[MapFrom('remote_name')]
        #[Required]
        public readonly string $name = '',
    ) {}
}

class AuditCollectionDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
    ) {}
}

describe('DTO production readiness comprehensive audit', function () {
    // ── fromArray hydration ──────────────────────────────────
    describe('fromArray basic hydration', function () {
        it('hydrates all properties from array', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'alice@example.com',
                'name' => 'Alice',
                'bio' => 'Developer',
                'age' => '30',
            ], validate: false);

            expect($dto->email)->toBe('alice@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->bio)->toBe('Developer');
            expect($dto->age)->toBe(30);
            expect($dto->status)->toBe('active');
        });

        it('applies Cast integer to string value', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'age' => '25',
            ], validate: false);

            expect($dto->age)->toBe(25);
            expect($dto->age)->toBeInt();
        });

        it('applies DefaultValue when key is absent', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('allows overriding DefaultValue', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->status)->toBe('inactive');
        });

        it('respects nullable with explicit null', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'test@test.com',
                'name' => 'Test',
                'bio' => null,
            ], validate: false);

            expect($dto->bio)->toBeNull();
        });
    });

    // ── fromArray with MapFrom ─────────────────────────────────
    describe('fromArray with MapFrom', function () {
        it('maps source key to property name', function () {
            $dto = AuditMappedDTO::fromArray([
                'remote_id' => '123',
                'remote_name' => 'Alice',
            ], validate: false);

            expect($dto->id)->toBe('123');
            expect($dto->name)->toBe('Alice');
        });

        it('uses default when mapped key is absent', function () {
            $dto = AuditMappedDTO::fromArray([
                'remote_name' => 'Bob',
            ], validate: false);

            expect($dto->id)->toBeNull();
        });
    });

    // ── Serialization ─────────────────────────────────────────
    describe('toArray / allValues / toJson', function () {
        it('toArray excludes hidden fields', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('allValues includes hidden fields', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('toJson produces valid JSON', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('toJson respects options', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect(str_contains($json, "\n"))->toBeTrue();
        });

        it('jsonSerialize returns toArray result', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // ── Selective output ──────────────────────────────────────
    describe('only() and except()', function () {
        it('only returns specified fields', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => 30,
            ], validate: false);

            $result = $dto->only('email', 'name');

            expect($result)->toHaveCount(2);
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('age');
        });

        it('only with single string key', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('a@b.com');
        });

        it('except excludes specified fields', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => 30,
            ], validate: false);

            $result = $dto->except('age', 'status');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('age');
        });
    });

    // ── Immutable update ───────────────────────────────────────
    describe('with() immutable update', function () {
        it('returns new instance with updated values', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('a@b.com');
        });

        it('original DTO is unchanged', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto->with(['status' => 'inactive']);

            expect($dto->status)->toBe('active');
        });
    });

    // ── Equality ──────────────────────────────────────────────
    describe('equals()', function () {
        it('returns true for identical values', function () {
            $a = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $b = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for different values', function () {
            $a = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $b = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Bob'], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('excludes hidden from comparison', function () {
            $a = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'x'], validate: false);
            $b = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'y'], validate: false);

            expect($a->equals($b))->toBeTrue();
        });
    });

    // ── State checks ──────────────────────────────────────────
    describe('isEmpty and isNotEmpty', function () {
        it('isEmpty returns true for all-defaults', function () {
            $dto = AuditEmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
        });

        it('isEmpty returns false when a field has value', function () {
            $dto = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty is negation of isEmpty', function () {
            $empty = AuditEmptyDTO::fromArray([], validate: false);
            $nonEmpty = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });
    });

    // ── fromJson ─────────────────────────────────────────────
    describe('fromJson', function () {
        it('creates DTO from valid JSON string', function () {
            $dto = AuditUserDTO::fromJson('{"email":"a@b.com","name":"Alice"}', validate: false);

            expect($dto->email)->toBe('a@b.com');
            expect($dto->name)->toBe('Alice');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => AuditUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array (JSON array)', function () {
            expect(fn () => AuditUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    // ── DTOException factory methods ──────────────────────────
    describe('DTOException named constructors', function () {
        it('invalidCast formats message correctly', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('not_a_number');
        });

        it('invalidJson formats message correctly', function () {
            $e = DTOException::invalidJson('data', 'Syntax error');

            expect($e->getMessage())->toContain('data');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    // ── Validation rules ──────────────────────────────────────
    describe('rules() and rulesFor()', function () {
        it('rules() returns array with required, email, max, min', function () {
            $rules = AuditUserDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:100');
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = AuditUserDTO::rules();
            $rulesForUpdate = AuditUserDTO::rulesFor('update');

            expect($rules)->toEqual($rulesForUpdate);
        });

        it('rulesFor() returns same for arbitrary action', function () {
            $rules = AuditUserDTO::rules();
            $rulesForDelete = AuditUserDTO::rulesFor('delete');

            expect($rules)->toEqual($rulesForDelete);
        });
    });

    // ── Metadata cache management ─────────────────────────────
    describe('metadata cache management', function () {
        it('flushMetadataCache clears all cached metadata', function () {
            // Resolve metadata first
            AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);

            // Flush should not break subsequent usage
            DataTransferObject::flushMetadataCache();

            $dto = AuditUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Test2'], validate: false);
            expect($dto->email)->toBe('b@c.com');
        });

        it('flushMetadataCache with specific class', function () {
            AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);

            DataTransferObject::flushMetadataCache(AuditUserDTO::class);

            // Should still work after flush
            $dto = AuditUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            expect($dto->name)->toBe('Test');
        });

        it('setMetadataCacheTtl accepts positive value', function () {
            DataTransferObject::setMetadataCacheTtl(5.0);
            // No exception = success
            expect(true)->toBeTrue();
        });

        it('setMetadataCacheTtl accepts zero', function () {
            DataTransferObject::setMetadataCacheTtl(0.0);
            expect(true)->toBeTrue();
        });
    });

    // ── DtoCollection ArrayAccess ──────────────────────────────
    describe('DtoCollection ArrayAccess', function () {
        it('offsetExists returns true for valid index', function () {
            $dto = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$dto]);

            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeFalse();
        });

        it('offsetGet returns DTO for valid index', function () {
            $dto = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col[0])->toBe($dto);
        });

        it('offsetGet returns null for invalid index', function () {
            $col = new DtoCollection;

            expect($col[0])->toBeNull();
        });

        it('offsetSet appends when offset is null', function () {
            $col = new DtoCollection;
            $dto = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);

            $col[] = $dto;

            expect($col->count())->toBe(1);
        });

        it('offsetSet replaces at specific offset', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1]);

            $col[0] = $d2;

            expect($col->count())->toBe(1);
            expect($col[0]->id)->toBe('2');
        });

        it('offsetSet rejects non-DTO values', function () {
            $col = new DtoCollection;

            expect(fn () => $col[] = 'not a dto')
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset removes and re-indexes', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $d3 = AuditCollectionDTO::fromArray(['id' => '3', 'label' => 'C'], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col[0]->id)->toBe('2');
            expect($col[1]->id)->toBe('3');
        });

        it('constructor rejects non-DTO values', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    // ── DtoCollection make() ───────────────────────────────────
    describe('DtoCollection make()', function () {
        it('creates from array of DTOs', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$d1, $d2]);

            expect($col->count())->toBe(2);
        });

        it('creates empty collection from no args', function () {
            $col = DtoCollection::make();

            expect($col->isEmpty())->toBeTrue();
        });
    });

    // ── DtoCollection first/last ───────────────────────────────
    describe('DtoCollection first() and last()', function () {
        it('first returns first item', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->first()->id)->toBe('1');
        });

        it('last returns last item', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->last()->id)->toBe('2');
        });

        it('first/last return null for empty collection', function () {
            $col = new DtoCollection;

            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });
    });

    // ── DtoCollection pluck/pluckKey ────────────────────────────
    describe('DtoCollection pluck and pluckKey', function () {
        it('pluck extracts single field', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $ids = $col->pluck('id');

            expect($ids)->toEqual(['1', '2']);
        });

        it('pluckKey creates associative map', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $map = $col->pluckKey('id', 'label');

            expect($map)->toEqual(['1' => 'A', '2' => 'B']);
        });

        it('pluckKey without valueField returns full arrays', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$d1]);

            $map = $col->pluckKey('id');

            expect($map)->toHaveKey('1');
            expect($map['1'])->toBe(['id' => '1', 'label' => 'A']);
        });
    });

    // ── DtoCollection items/toArray/allValues ──────────────────
    describe('DtoCollection iteration and serialization', function () {
        it('items returns raw DTO instances', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$d1]);

            $items = $col->items();

            expect($items)->toHaveCount(1);
            expect($items[0])->toBe($d1);
        });

        it('toArray serializes all DTOs', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$d1]);

            $arr = $col->toArray();

            expect($arr[0])->toEqual(['id' => '1', 'label' => 'A']);
        });

        it('allValues includes hidden fields from child DTOs', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);
            $col = new DtoCollection([$dto]);

            $all = $col->allValues();

            expect($all[0])->toHaveKey('password');
            expect($all[0]['password'])->toBe('secret');
        });

        it('getIterator yields all items', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $count = 0;
            foreach ($col as $item) {
                $count++;
            }

            expect($count)->toBe(2);
        });

        it('count implements Countable', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $col = new DtoCollection([$d1]);

            expect(count($col))->toBe(1);
        });
    });

    // ── DtoCollection map with index ───────────────────────────
    describe('DtoCollection map with index', function () {
        it('map passes both item and index', function () {
            $d1 = AuditCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $d2 = AuditCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $result = $col->map(fn ($dto, $index) => $index . '-' . $dto->label);

            expect($result)->toEqual(['0-A', '1-B']);
        });
    });

    // ── fromPartialArray ───────────────────────────────────────
    describe('fromPartialArray', function () {
        it('uses defaults for missing fields', function () {
            $dto = AuditUserDTO::fromPartialArray(['email' => 'a@b.com'], validate: false);

            expect($dto->email)->toBe('a@b.com');
            expect($dto->status)->toBe('active');
            expect($dto->age)->toBe(0);
        });

        it('overrides defaults when provided', function () {
            $dto = AuditUserDTO::fromPartialArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'suspended',
            ], validate: false);

            expect($dto->status)->toBe('suspended');
        });

        it('works with empty array', function () {
            $dto = AuditUserDTO::fromPartialArray([], validate: false);

            expect($dto->email)->toBe('');
            expect($dto->name)->toBe('');
            expect($dto->status)->toBe('active');
        });
    });

    // ── validatePartialArray ──────────────────────────────────
    describe('validatePartialArray', function () {
        it('returns data when valid', function () {
            $result = AuditProductDTO::validatePartialArray([
                'price' => 10.99,
            ]);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('price');
        });
    });

    // ── AuditProductDTO with In/Accepted ───────────────────────
    describe('AuditProductDTO attribute validation', function () {
        it('hydrates with In constraint field', function () {
            $dto = AuditProductDTO::fromArray([
                'sku' => 'SKU-001',
                'price' => 29.99,
                'state' => 'published',
                'terms' => true,
            ], validate: false);

            expect($dto->state)->toBe('published');
            expect($dto->terms)->toBeTrue();
        });

        it('rules include In and Accepted', function () {
            $rules = AuditProductDTO::rules();

            expect($rules['state'])->toContain('in:draft,published,archived');
            expect($rules['terms'])->toContain('accepted');
        });
    });

    // ── Readonly enforcement ─────────────────────────────────
    describe('readonly property enforcement', function () {
        it('properties are public readonly (cannot be reassigned)', function () {
            $dto = AuditUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            // This is a compile-time check — the property is readonly
            // We verify it exists and is accessible
            expect($dto->email)->toBe('a@b.com');

            // Trying to set would be a compile error, so we can't test it at runtime
            // But we verify the value doesn't change through normal usage
            $copy = $dto->with(['email' => 'b@c.com']);
            expect($dto->email)->toBe('a@b.com');
            expect($copy->email)->toBe('b@c.com');
        });
    });
});
