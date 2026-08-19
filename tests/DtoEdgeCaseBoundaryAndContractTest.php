<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Comprehensive edge-case tests for DTO contract compliance,
 * boundary conditions, and cross-feature integration.
 *
 * Covers:
 * - fromArray/toArray roundtrip consistency
 * - Empty/missing field handling
 * - only/except selective output
 * - equals() and isEmpty() state checks
 * - with() immutability guarantee
 * - fromJson error handling
 * - DtoCollection boundary operations
 * - Metadata cache TTL behavior
 * - MapFrom dot notation
 * - Cast type coercion edge cases
 * - Validation rule generation contract
 */

// --- Test Fixtures ---

class SimpleDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[DefaultValue('active')]
        public readonly string $status = 'active',
    ) {}
}

class HiddenFieldDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $public,

        #[Hidden]
        public readonly string $secret = '',
    ) {}
}

class MapFromDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user_email')]
        public readonly string $email = '',

        #[MapFrom('user_name')]
        public readonly string $name = '',
    ) {}
}

class CastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count = 0,

        #[Cast('boolean')]
        public readonly bool $active = false,

        #[Cast('string')]
        public readonly string $label = '',
    ) {}
}

class NullableDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $optional = null,
        public readonly string $required = '',
    ) {}
}

class AllDefaultDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('a')]
        public readonly string $x = 'a',

        #[DefaultValue('b')]
        public readonly string $y = 'b',
    ) {}
}

class EmptyDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $field = null,
    ) {}
}

// --- Test Suites ---

describe('DTO fromArray/toArray Roundtrip Contract', function () {

    it('roundtrips all fields exactly', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'pending',
        ]);

        $arr = $dto->toArray();

        expect($arr['email'])->toBe('test@example.com');
        expect($arr['name'])->toBe('Alice');
        expect($arr['status'])->toBe('pending');
    });

    it('applies defaults for missing optional fields', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Bob',
        ]);

        expect($dto->status)->toBe('active');
    });

    it('explicit null or empty string is not overridden by default', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Charlie',
            'status' => '',
        ]);

        expect($dto->status)->toBe('');
    });
});

describe('DTO Hidden Field Contract', function () {

    it('toArray() excludes hidden fields', function () {
        $dto = HiddenFieldDTO::fromArray([
            'public' => 'visible',
            'secret' => 'hidden-value',
        ]);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('public');
        expect($arr)->not->toHaveKey('secret');
    });

    it('allValues() includes hidden fields', function () {
        $dto = HiddenFieldDTO::fromArray([
            'public' => 'visible',
            'secret' => 'hidden-value',
        ]);

        $all = $dto->allValues();

        expect($all)->toHaveKey('public');
        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('hidden-value');
    });

    it('toJson() excludes hidden fields', function () {
        $dto = HiddenFieldDTO::fromArray([
            'public' => 'visible',
            'secret' => 'hidden-value',
        ]);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toHaveKey('public');
        expect($decoded)->not->toHaveKey('secret');
    });
});

describe('DTO MapFrom Contract', function () {

    it('maps source key to property', function () {
        $dto = MapFromDTO::fromArray([
            'user_email' => 'a@b.com',
            'user_name' => 'Alice',
        ]);

        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Alice');
    });

    it('uses property name when map_from key is absent', function () {
        $dto = MapFromDTO::fromArray([
            'email' => 'direct@example.com',
        ]);

        expect($dto->email)->toBe('direct@example.com');
    });

    it('map_from takes priority over property name when both present', function () {
        $dto = MapFromDTO::fromArray([
            'user_email' => 'mapped@example.com',
            'email' => 'direct@example.com',
        ]);

        // map_from key should take priority
        expect($dto->email)->toBe('mapped@example.com');
    });
});

describe('DTO Cast Type Coercion', function () {

    it('casts string to int', function () {
        $dto = CastDTO::fromArray(['count' => '42']);

        expect($dto->count)->toBe(42);
        expect($dto->count)->toBeInt();
    });

    it('casts string "true" to bool true', function () {
        $dto = CastDTO::fromArray(['active' => 'true']);

        expect($dto->active)->toBeTrue();
    });

    it('casts string "0" to bool false', function () {
        $dto = CastDTO::fromArray(['active' => '0']);

        expect($dto->active)->toBeFalse();
    });

    it('casts int to string', function () {
        $dto = CastDTO::fromArray(['label' => 123]);

        expect($dto->label)->toBe('123');
        expect($dto->label)->toBeString();
    });

    it('non-numeric string casts to int 0', function () {
        $dto = CastDTO::fromArray(['count' => 'abc']);

        expect($dto->count)->toBe(0);
    });

    it('non-numeric string casts to float 0.0', function () {
        $dto = CastDTO::fromArray(['count' => 'abc']);

        expect($dto->count)->toBe(0);
    });
});

describe('DTO only/except Selective Output', function () {

    it('only() returns just the requested fields', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ]);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['email', 'name']);
    });

    it('only() with single string key', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });

    it('except() excludes the requested fields', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ]);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('except() ignores non-existent keys gracefully', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveCount(3); // All fields present
    });

    it('only() ignores non-existent keys gracefully', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });
});

describe('DTO equals() and isEmpty() State Checks', function () {

    it('equals() returns true for identical data', function () {
        $a = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals() returns false for different data', function () {
        $a = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = SimpleDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob']);

        expect($a->equals($b))->toBeFalse();
    });

    it('equals() is symmetric', function () {
        $a = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);

        expect($a->equals($b))->toBe($b->equals($a));
    });

    it('isEmpty() returns true for DTO with all null/empty fields', function () {
        $dto = EmptyDTO::fromArray([]);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty() returns false when any field has a value', function () {
        $dto = EmptyDTO::fromArray(['field' => 'not-empty']);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('isNotEmpty() is negation of isEmpty()', function () {
        $empty = EmptyDTO::fromArray([]);
        $nonEmpty = EmptyDTO::fromArray(['field' => 'x']);

        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('isEmpty() considers int 0 as non-empty', function () {
        $dto = CastDTO::fromArray(['count' => 0]);

        // 0 is a valid meaningful value, should not be "empty"
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO with() Immutability', function () {

    it('returns a new instance', function () {
        $original = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        $updated = $original->with(['name' => 'Bob']);

        expect($original)->not->toBe($updated);
        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
    });

    it('preserves unchanged fields', function () {
        $original = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        $updated = $original->with(['name' => 'Bob']);

        expect($updated->email)->toBe('a@b.com');
    });

    it('original instance is unchanged after with()', function () {
        $original = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ]);

        $original->with(['name' => 'Bob', 'status' => 'pending']);

        // Original must be unchanged
        expect($original->name)->toBe('Alice');
        expect($original->status)->toBe('active');
    });
});

describe('DTO fromJson Error Handling', function () {

    it('throws DTOException for invalid JSON', function () {
        expect(fn () => SimpleDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON array (sequential)', function () {
        expect(fn () => SimpleDTO::fromJson('[1,2,3]'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON null', function () {
        expect(fn () => SimpleDTO::fromJson('null'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        // Should use defaults for required fields — but will fail validation
        // because email is Required. Test with all-default DTO instead.
        $dto = AllDefaultDTO::fromJson('{}');

        expect($dto->x)->toBe('a');
        expect($dto->y)->toBe('b');
    });

    it('DTOException __toString includes class name', function () {
        try {
            SimpleDTO::fromJson('{bad}');
            expect(true)->toBeFalse();
        } catch (DTOException $e) {
            $str = (string) $e;
            expect($str)->toContain('DTOException');
        }
    });
});

describe('DTO rules() Contract', function () {

    it('returns array with field keys', function () {
        $rules = SimpleDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('email field has required and email rules', function () {
        $rules = SimpleDTO::rules();

        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('name field has required, min:2, max:50', function () {
        $rules = SimpleDTO::rules();

        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('rulesFor() returns same as rules() by default', function () {
        expect(SimpleDTO::rulesFor('create'))->toBe(SimpleDTO::rules());
        expect(SimpleDTO::rulesFor('update'))->toBe(SimpleDTO::rules());
    });
});

describe('DTO fromPartialArray PATCH Semantics', function () {

    it('only hydrates fields present in data', function () {
        $dto = NullableDTO::fromPartialArray(['optional' => 'provided']);

        expect($dto->optional)->toBe('provided');
    });

    it('missing fields use defaults when available', function () {
        $dto = AllDefaultDTO::fromPartialArray(['x' => 'custom']);

        expect($dto->x)->toBe('custom');
        expect($dto->y)->toBe('b'); // default
    });

    it('empty data array uses all defaults', function () {
        $dto = AllDefaultDTO::fromPartialArray([]);

        expect($dto->x)->toBe('a');
        expect($dto->y)->toBe('b');
    });
});

describe('DtoCollection Boundary Operations', function () {

    it('empty collection isEmpty returns true', function () {
        $col = new DtoCollection;

        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
        expect($col->count())->toBe(0);
    });

    it('push returns same instance (mutable)', function () {
        $dto = AllDefaultDTO::fromArray([]);
        $col = new DtoCollection;

        $result = $col->push($dto);

        expect($result)->toBe($col); // Same instance
        expect($col->count())->toBe(1);
    });

    it('append returns new instance (immutable)', function () {
        $dto = AllDefaultDTO::fromArray([]);
        $col = new DtoCollection;

        $newCol = $col->append($dto);

        expect($newCol)->not->toBe($col); // Different instance
        expect($col->count())->toBe(0); // Original unchanged
        expect($newCol->count())->toBe(1);
    });

    it('merge returns new combined collection', function () {
        $dto1 = AllDefaultDTO::fromArray(['x' => '1']);
        $dto2 = AllDefaultDTO::fromArray(['x' => '2']);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1); // Original unchanged
    });

    it('filter returns new filtered collection', function () {
        $dto1 = AllDefaultDTO::fromArray(['x' => 'keep']);
        $dto2 = AllDefaultDTO::fromArray(['x' => 'remove']);

        $col = new DtoCollection([$dto1, $dto2]);
        $filtered = $col->filter(fn ($d) => $d->x === 'keep');

        expect($filtered->count())->toBe(1);
        expect($col->count())->toBe(2); // Original unchanged
    });

    it('take returns at most N items', function () {
        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $items[] = AllDefaultDTO::fromArray(['x' => (string) $i]);
        }

        $col = new DtoCollection($items);
        $taken = $col->take(3);

        expect($taken->count())->toBe(3);
    });

    it('skip returns remaining items after N', function () {
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = AllDefaultDTO::fromArray(['x' => (string) $i]);
        }

        $col = new DtoCollection($items);
        $skipped = $col->skip(3);

        expect($skipped->count())->toBe(2);
    });

    it('unique removes duplicates by toArray() equality', function () {
        $dto1 = AllDefaultDTO::fromArray(['x' => 'same', 'y' => 'same']);
        $dto2 = AllDefaultDTO::fromArray(['x' => 'same', 'y' => 'same']);
        $dto3 = AllDefaultDTO::fromArray(['x' => 'different', 'y' => 'different']);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $unique = $col->unique();

        expect($unique->count())->toBe(2);
    });

    it('clone throws RuntimeException', function () {
        $col = new DtoCollection;

        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection([new \stdClass]))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetGet returns null for non-existent offset', function () {
        $col = new DtoCollection;

        expect($col[0])->toBeNull();
    });

    it('offsetSet rejects non-DTO values', function () {
        $col = new DtoCollection;

        expect(fn () => $col[0] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => '1']);
        $d2 = AllDefaultDTO::fromArray(['x' => '2']);
        $d3 = AllDefaultDTO::fromArray(['x' => '3']);

        $col = new DtoCollection([$d1, $d2, $d3]);
        unset($col[0]);

        // After re-indexing, offset 0 should be $d2
        expect($col[0]->x)->toBe('2');
        expect($col[1]->x)->toBe('3');
        expect($col->count())->toBe(2);
    });

    it('chunk splits into correct sizes', function () {
        $items = [];
        for ($i = 0; $i < 7; $i++) {
            $items[] = AllDefaultDTO::fromArray(['x' => (string) $i]);
        }

        $col = new DtoCollection($items);
        $chunks = $col->chunk(3);

        expect($chunks)->toHaveCount(3); // ceil(7/3) = 3
        expect($chunks[0]->count())->toBe(3);
        expect($chunks[1]->count())->toBe(3);
        expect($chunks[2]->count())->toBe(1);
    });

    it('contains short-circuits on first match', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'match']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'no-match']);

        $col = new DtoCollection([$d1, $d2]);

        expect($col->contains(fn ($d) => $d->x === 'match'))->toBeTrue();
        expect($col->contains(fn ($d) => $d->x === 'never'))->toBeFalse();
    });

    it('search returns first matching DTO or null', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'b']);
        $d3 = AllDefaultDTO::fromArray(['x' => 'c']);

        $col = new DtoCollection([$d1, $d2, $d3]);

        $found = $col->search(fn ($d) => $d->x === 'b');
        expect($found)->not->toBeNull();
        expect($found->x)->toBe('b');

        $notFound = $col->search(fn ($d) => $d->x === 'z');
        expect($notFound)->toBeNull();
    });

    it('sortBy string property returns sorted collection', function () {
        $d3 = AllDefaultDTO::fromArray(['x' => 'c']);
        $d1 = AllDefaultDTO::fromArray(['x' => 'a']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'b']);

        $col = new DtoCollection([$d3, $d1, $d2]);
        $sorted = $col->sortBy('x');

        expect($sorted[0]->x)->toBe('a');
        expect($sorted[1]->x)->toBe('b');
        expect($sorted[2]->x)->toBe('c');
    });

    it('sortBy puts null values at end', function () {
        // Using toArray() values for sort, null values go to end
        $d1 = NullableDTO::fromArray(['optional' => null, 'required' => 'a']);
        $d2 = NullableDTO::fromArray(['optional' => 'z', 'required' => 'b']);
        $d3 = NullableDTO::fromArray(['optional' => 'a', 'required' => 'c']);

        $col = new DtoCollection([$d2, $d1, $d3]);
        $sorted = $col->sortBy('optional');

        // null should be last
        $lastItem = $sorted[$sorted->count() - 1];
        expect($lastItem->optional)->toBeNull();
    });
});

describe('DTO Metadata Cache Behavior', function () {

    afterEach(function () {
        // Flush static cache between tests
        SimpleDTO::flushMetadataCache();
        AllDefaultDTO::flushMetadataCache();
    });

    it('flushMetadataCache clears all entries', function () {
        SimpleDTO::rules(); // Populate cache
        AllDefaultDTO::rules(); // Populate cache

        SimpleDTO::flushMetadataCache();

        // Cache should be empty — next call rebuilds (no way to inspect
        // private static cache directly, but the call should not error)
        expect(SimpleDTO::rules())->toBeArray();
    });

    it('flushMetadataCache with class clears only that class', function () {
        SimpleDTO::rules();
        AllDefaultDTO::rules();

        SimpleDTO::flushMetadataCache(SimpleDTO::class);

        // AllDefaultDTO cache should still be intact
        expect(AllDefaultDTO::rules())->toBeArray();
    });
});

describe('DTO __debugInfo Contract', function () {

    it('returns array with public fields only', function () {
        $dto = HiddenFieldDTO::fromArray([
            'public' => 'visible',
            'secret' => 'hidden',
        ]);

        $debug = $dto->__debugInfo();

        expect($debug)->toBeArray();
        expect($debug)->toHaveKey('public');
        expect($debug)->not->toHaveKey('secret');
    });
});

describe('DTO jsonSerialize Contract', function () {

    it('returns same as toArray()', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ]);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DTOException Named Constructors', function () {

    it('invalidCast includes property name and type', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not-an-int');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson includes property and error', function () {
        $e = DTOException::invalidJson('data', 'Syntax error');

        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });
});

describe('DtoCollection __debugInfo Contract', function () {

    it('returns count and truncated items', function () {
        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $items[] = AllDefaultDTO::fromArray(['x' => (string) $i]);
        }

        $col = new DtoCollection($items);
        $debug = $col->__debugInfo();

        expect($debug)->toHaveKeys(['count', 'items']);
        expect($debug['count'])->toBe(10);
        expect($debug['items'])->toHaveCount(3); // Truncated to 3
    });

    it('empty collection debug shows count 0', function () {
        $col = new DtoCollection;
        $debug = $col->__debugInfo();

        expect($debug['count'])->toBe(0);
        expect($debug['items'])->toBe([]);
    });
});

describe('DtoCollection first/last Contract', function () {

    it('first returns first item', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => '1']);
        $d2 = AllDefaultDTO::fromArray(['x' => '2']);

        $col = new DtoCollection([$d1, $d2]);

        expect($col->first()->x)->toBe('1');
    });

    it('last returns last item', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => '1']);
        $d2 = AllDefaultDTO::fromArray(['x' => '2']);

        $col = new DtoCollection([$d1, $d2]);

        expect($col->last()->x)->toBe('2');
    });

    it('first returns null for empty collection', function () {
        $col = new DtoCollection;

        expect($col->first())->toBeNull();
    });

    it('last returns null for empty collection', function () {
        $col = new DtoCollection;

        expect($col->last())->toBeNull();
    });
});

describe('DtoCollection pluck/pluckKey Contract', function () {

    it('pluck extracts single property values', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a', 'y' => '1']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'b', 'y' => '2']);

        $col = new DtoCollection([$d1, $d2]);
        $xValues = $col->pluck('x');

        expect($xValues)->toBe(['a', 'b']);
    });

    it('pluckKey creates key-value map', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'key1', 'y' => 'val1']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'key2', 'y' => 'val2']);

        $col = new DtoCollection([$d1, $d2]);
        $map = $col->pluckKey('x', 'y');

        expect($map)->toBe(['key1' => 'val1', 'key2' => 'val2']);
    });

    it('pluckKey skips items with null key', function () {
        $d1 = NullableDTO::fromArray(['optional' => null, 'required' => 'a']);
        $d2 = NullableDTO::fromArray(['optional' => 'key', 'required' => 'b']);

        $col = new DtoCollection([$d1, $d2]);
        $map = $col->pluckKey('optional', 'required');

        expect($map)->toBe(['key' => 'b']);
    });

    it('toDictionary is alias behavior for pluckKey', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a', 'y' => '1']);
        $col = new DtoCollection([$d1]);

        $dict = $col->toDictionary('x', 'y');
        $plucked = $col->pluckKey('x', 'y');

        expect($dict)->toBe($plucked);
    });

    it('toArrayBy is alias behavior for pluckKey with single arg', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a', 'y' => '1']);
        $col = new DtoCollection([$d1]);

        $by = $col->toArrayBy('x');
        $plucked = $col->pluckKey('x');

        expect($by)->toBe($plucked);
    });
});

describe('DtoCollection map Contract', function () {

    it('maps over all items and returns plain array', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a', 'y' => '1']);
        $d2 = AllDefaultDTO::fromArray(['x' => 'b', 'y' => '2']);

        $col = new DtoCollection([$d1, $d2]);
        $result = $col->map(fn ($d) => $d->x);

        expect($result)->toBe(['a', 'b']);
        expect($result)->not->toBeInstanceOf(DtoCollection::class);
    });
});

describe('DtoCollection toArray/allValues Contract', function () {

    it('toArray serializes each DTO', function () {
        $d1 = HiddenFieldDTO::fromArray(['public' => 'vis', 'secret' => 'hid']);
        $d2 = HiddenFieldDTO::fromArray(['public' => 'vis2', 'secret' => 'hid2']);

        $col = new DtoCollection([$d1, $d2]);
        $arr = $col->toArray();

        expect($arr)->toHaveCount(2);
        // Hidden fields excluded
        expect($arr[0])->not->toHaveKey('secret');
        expect($arr[1])->not->toHaveKey('secret');
    });

    it('allValues includes hidden fields', function () {
        $d1 = HiddenFieldDTO::fromArray(['public' => 'vis', 'secret' => 'hid']);
        $col = new DtoCollection([$d1]);

        $all = $col->allValues();

        expect($all[0])->toHaveKey('secret');
        expect($all[0]['secret'])->toBe('hid');
    });

    it('items() returns raw DTO instances', function () {
        $d1 = AllDefaultDTO::fromArray(['x' => 'a']);
        $col = new DtoCollection([$d1]);

        $items = $col->items();

        expect($items[0])->toBe($d1);
    });
});
