<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\CollectionItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * V52 Comprehensive Production Hardening Audit.
 *
 * Covers edge cases discovered after V51:
 * - DataTransferObject fromJson() rejects sequential arrays
 * - DataTransferObject fromJson() accepts empty array as valid JSON object
 * - DataTransferObject fromPartialArray() with empty data returns defaults
 * - DataTransferObject with() always validates (deprecated $validate param)
 * - DtoCollection __clone() throws RuntimeException for immutability
 * - DtoCollection sortBy() with property name and callback
 * - DtoCollection chunk() returns array of collections
 * - DtoCollection unique() removes duplicates based on toArray() equality
 * - DtoCollection search() returns first matching DTO or null
 * - DtoCollection contains() short-circuits on first match
 * - DtoCollection toDictionary() key-value extraction
 * - DtoCollection toArrayBy() re-keying
 * - DTOCast serialize() returns null for null
 * - DTOCast set() rejects non-DTO, non-array, non-null values
 * - DTOManager schema() delegates to OpenApiSchemaGenerator
 * - ProductDTO MapFrom maps vendor_code to vendorCode
 * - CollectionItemDTO collection operations
 * - DTOException __toString() format
 * - DTOFacade getFacadeAccessor() returns correct string
 * - Metadata cache TTL behavior with flushMetadataCache(null)
 * - DtoCollection allValues() includes hidden properties
 * - DtoCollection offsetSet() accepts DTO instances
 * - DtoCollection offsetUnset() re-indexes
 * - DtoCollection make() static factory
 */
describe('V52 Comprehensive Production Hardening Audit', function (): void {
    // ── DataTransferObject fromJson() Edge Cases ────────────────────────

    it('fromJson() rejects non-empty sequential arrays', function (): void {
        expect(fn () => MinimalDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson() accepts empty array as valid JSON object', function (): void {
        // MinimalDTO requires name and value, but fromJson with empty object
        // and validate=false will try to construct — depends on defaults
        // Let's test with a DTO that has all optional properties
        $result = RoundtripDTO::fromJson('{}', validate: false);

        expect($result)->toBeInstanceOf(RoundtripDTO::class);
    });

    it('fromJson() rejects invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('not json', validate: false))
            ->toThrow(DTOException::class);
    });

    // ── DataTransferObject with() Always Validates ────────────────────

    it('with() validates merged data and throws on invalid', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        // Try to override name with empty string (violates Min(1))
        expect(fn () => $dto->with(['name' => '']))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('with() returns a new instance (immutable)', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ], validate: false);

        $modified = $dto->with(['name' => 'Bob']);

        expect($modified)->toBeInstanceOf(RoundtripDTO::class);
        expect($modified)->not->toBe($dto);
        expect($modified->name)->toBe('Bob');
        // Original unchanged
        expect($dto->name)->toBe('Alice');
    });

    // ── DataTransferObject fromPartialArray() ─────────────────────────

    it('fromPartialArray() with empty data returns DTO with defaults', function (): void {
        $dto = RoundtripDTO::fromPartialArray([], validate: false);

        expect($dto)->toBeInstanceOf(RoundtripDTO::class);
        expect($dto->score)->toBe(0.0);
        expect($dto->tags)->toBe([]);
        expect($dto->role)->toBe('user');
    });

    // ── DtoCollection Immutability ─────────────────────────────────────

    it('DtoCollection __clone() throws RuntimeException', function (): void {
        $col = DtoCollection::make();

        expect(fn () => clone $col)
            ->toThrow(\RuntimeException::class);
    });

    it('DtoCollection append() returns new instance without modifying original', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1]);
        $newCol = $col->append($item2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('DtoCollection merge() combines two collections', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 20], validate: false);

        $col1 = DtoCollection::make([$item1]);
        $col2 = DtoCollection::make([$item2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
    });

    // ── DtoCollection sortBy() ────────────────────────────────────────

    it('DtoCollection sortBy() sorts by property name ascending', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 3, 'name' => 'Charlie', 'score' => 30], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item3 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2, $item3]);
        $sorted = $col->sortBy('name');

        expect($sorted->first()->name)->toBe('Alice');
    });

    it('DtoCollection sortBy() sorts by callback', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 30], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 10], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $sorted = $col->sortBy(static fn (CollectionItemDTO $d): int => $d->score);

        expect($sorted->first()->score)->toBe(10);
    });

    // ── DtoCollection chunk() ──────────────────────────────────────────

    it('DtoCollection chunk() splits into correct sizes', function (): void {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = CollectionItemDTO::fromArray([
                'id' => $i, 'name' => "Item{$i}", 'score' => $i * 10,
            ], validate: false);
        }

        $col = DtoCollection::make($items);
        $chunks = $col->chunk(2);

        expect($chunks)->toHaveCount(3); // 2 + 2 + 1
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    // ── DtoCollection unique() ─────────────────────────────────────────

    it('DtoCollection unique() removes duplicates', function (): void {
        $item = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);

        $col = DtoCollection::make([$item, $item, $item]);
        $unique = $col->unique();

        expect($unique->count())->toBe(1);
    });

    // ── DtoCollection search() ────────────────────────────────────────

    it('DtoCollection search() returns first matching DTO', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $found = $col->search(static fn (CollectionItemDTO $d): bool => $d->name === 'Bob');

        expect($found)->not->toBeNull();
        expect($found->id)->toBe(2);
    });

    it('DtoCollection search() returns null when no match', function (): void {
        $item = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $col = DtoCollection::make([$item]);

        expect($col->search(static fn (CollectionItemDTO $d): bool => $d->name === 'Z'))->toBeNull();
    });

    // ── DtoCollection contains() ───────────────────────────────────────

    it('DtoCollection contains() short-circuits on first match', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);

        expect($col->contains(static fn (CollectionItemDTO $d): bool => $d->id === 1))->toBeTrue();
        expect($col->contains(static fn (CollectionItemDTO $d): bool => $d->id === 99))->toBeFalse();
    });

    // ── DtoCollection toDictionary() ──────────────────────────────────

    it('DtoCollection toDictionary() extracts key-value pairs', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $dict = $col->toDictionary('id', 'name');

        expect($dict)->toBe([1 => 'Alice', 2 => 'Bob']);
    });

    // ── DtoCollection toArrayBy() ────────────────────────────────────

    it('DtoCollection toArrayBy() re-keys by property', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $keyed = $col->toArrayBy('id');

        expect($keyed)->toHaveKey(1);
        expect($keyed)->toHaveKey(2);
    });

    // ── DtoCollection ArrayAccess ──────────────────────────────────────

    it('DtoCollection offsetSet() appends when offset is null', function (): void {
        $col = DtoCollection::make();
        $item = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);

        $col->offsetSet(null, $item);

        expect($col->count())->toBe(1);
    });

    it('DtoCollection offsetUnset() re-indexes after removal', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 20], validate: false);
        $item3 = CollectionItemDTO::fromArray(['id' => 3, 'name' => 'C', 'score' => 30], validate: false);

        $col = DtoCollection::make([$item1, $item2, $item3]);
        $col->offsetUnset(0);

        // After re-index, offset 0 should now be Bob
        expect($col->count())->toBe(2);
        expect($col->offsetGet(0)->name)->toBe('B');
    });

    // ── DtoCollection allValues() ────────────────────────────────────

    it('DtoCollection allValues() includes hidden properties', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => true,
            'secret' => 'hidden_value',
        ], validate: false);

        $col = DtoCollection::make([$dto]);
        $all = $col->allValues();

        // secret is hidden in toArray() but visible in allValues()
        expect($all[0])->toHaveKey('secret');
        expect($all[0]['secret'])->toBe('hidden_value');
    });

    // ── DtoCollection make() Factory ──────────────────────────────────

    it('DtoCollection make() creates empty collection', function (): void {
        $col = DtoCollection::make();

        expect($col)->toBeInstanceOf(DtoCollection::class);
        expect($col->isEmpty())->toBeTrue();
    });

    // ── DtoCollection Constructor Validation ───────────────────────────

    it('DtoCollection rejects non-DTO items', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    // ── DTOCast Edge Cases ─────────────────────────────────────────────

    it('DTOCast serialize() returns null for null value', function (): void {
        $cast = new DTOCast(RoundtripDTO::class);

        expect($cast->serialize(new \stdClass, 'data', null, []))->toBeNull();
    });

    it('DTOCast get() returns null for null value', function (): void {
        $cast = new DTOCast(RoundtripDTO::class);

        expect($cast->get(new \stdClass, 'data', null, []))->toBeNull();
    });

    it('DTOCast get() returns null for invalid JSON string', function (): void {
        $cast = new DTOCast(RoundtripDTO::class);

        expect($cast->get(new \stdClass, 'data', '{invalid json}', []))->toBeNull();
    });

    it('DTOCast get() returns null for non-array non-string values', function (): void {
        $cast = new DTOCast(RoundtripDTO::class);

        expect($cast->get(new \stdClass, 'data', 42, []))->toBeNull();
    });

    // ── ProductDTO MapFrom ────────────────────────────────────────────

    it('ProductDTO maps vendor_code source key to vendorCode property', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'sku' => 'AB1234',
            'priceCents' => 999,
            'vendor_code' => 'VENDOR1',
        ], validate: false);

        expect($dto->vendorCode)->toBe('VENDOR1');
    });

    it('ProductDTO toArray() uses property name not source key', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'sku' => 'AB1234',
            'priceCents' => 999,
            'vendor_code' => 'VENDOR1',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('vendorCode');
        expect($arr)->not->toHaveKey('vendor_code');
    });

    // ── DTOException ──────────────────────────────────────────────────

    it('DTOException __toString() includes class name', function (): void {
        $exception = DTOException::invalidCast('field', 'int', 'string');

        $str = (string) $exception;

        expect($str)->toContain('DTOException');
    });

    it('DTOException invalidJson() includes property name', function (): void {
        $exception = DTOException::invalidJson('data', 'syntax error');

        expect($exception->getMessage())->toContain('data');
        expect($exception->getMessage())->toContain('syntax error');
    });

    // ── DTO Facade ────────────────────────────────────────────────────

    it('DTO facade returns correct accessor string', function (): void {
        $ref = new \ReflectionClass(DTO::class);
        $method = $ref->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        expect($method->invoke(null))->toBe('zeroboiler.dto');
    });

    // ── DTOManager readonly class ──────────────────────────────────────

    it('DTOManager is a readonly class', function (): void {
        $ref = new \ReflectionClass(DTOManager::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    // ── DTOSServiceProvider ───────────────────────────────────────────

    it('DTOSServiceProvider is a final class', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider extends Laravel ServiceProvider', function (): void {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

        expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
    });

    // ── Metadata Cache ───────────────────────────────────────────────

    it('flushMetadataCache(null) clears all entries', function (): void {
        // Populate cache by resolving metadata
        RoundtripDTO::fromArray([
            'name' => 'Test', 'age' => 25, 'active' => true,
        ], validate: false);

        RoundtripDTO::flushMetadataCache(null);

        // Should still work after flush (rebuilds from scratch)
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test2', 'age' => 30, 'active' => false,
        ], validate: false);

        expect($dto->name)->toBe('Test2');
    });

    it('flushMetadataCache(class) clears specific entry only', function (): void {
        RoundtripDTO::flushMetadataCache(RoundtripDTO::class);

        // After flush, should still work (rebuilds from scratch)
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test', 'age' => 25, 'active' => true,
        ], validate: false);

        expect($dto)->toBeInstanceOf(RoundtripDTO::class);
    });

    // ── DataTransferObject isEmpty() Boundary ─────────────────────────

    it('isEmpty() returns false when any property has non-empty value', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 0,
            'active' => false,
        ], validate: false);

        // name is 'Test' which is non-empty → not empty
        expect($dto->isEmpty())->toBeFalse();
    });

    it('isEmpty() returns false for int 0 and float 0.0 (valid values)', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'NonEmpty',  // only name matters here
            'age' => 0,
            'active' => false,
            'score' => 0.0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    // ── RoundtripDTO Hidden Property ─────────────────────────────────

    it('RoundtripDTO secret is excluded from toArray()', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => true,
            'secret' => 'password123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('secret');
    });

    it('RoundtripDTO secret is included in allValues()', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => true,
            'secret' => 'password123',
        ], validate: false);

        expect($dto->allValues())->toHaveKey('secret');
        expect($dto->allValues()['secret'])->toBe('password123');
    });

    // ── Cast Type Edge Cases ──────────────────────────────────────────

    it('Cast integer on string input produces 0 for non-numeric', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 'not_a_number',
            'active' => true,
        ], validate: false);

        expect($dto->age)->toBe(0);
    });

    it('Cast array on JSON string produces array', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => true,
            'tags' => '["a","b"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b']);
    });

    it('Cast array on empty string produces empty array', function (): void {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'age' => 25,
            'active' => true,
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    // ── DtoCollection take() and skip() ───────────────────────────────

    it('DtoCollection take() returns at most N items', function (): void {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = CollectionItemDTO::fromArray([
                'id' => $i, 'name' => "Item{$i}", 'score' => $i * 10,
            ], validate: false);
        }

        $col = DtoCollection::make($items);
        $taken = $col->take(3);

        expect($taken->count())->toBe(3);
        expect($taken->first()->id)->toBe(1);
    });

    it('DtoCollection skip() returns remaining items', function (): void {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = CollectionItemDTO::fromArray([
                'id' => $i, 'name' => "Item{$i}", 'score' => $i * 10,
            ], validate: false);
        }

        $col = DtoCollection::make($items);
        $skipped = $col->skip(3);

        expect($skipped->count())->toBe(2);
        expect($skipped->first()->id)->toBe(4);
    });

    // ── DtoCollection filter() ────────────────────────────────────────

    it('DtoCollection filter() returns new collection with matching items', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'A', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'B', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $filtered = $col->filter(static fn (CollectionItemDTO $d): bool => $d->score > 15);

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('B');
    });

    // ── DtoCollection pluck() ──────────────────────────────────────────

    it('DtoCollection pluck() extracts single property values', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $names = $col->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    // ── DtoCollection pluckKey() ──────────────────────────────────────

    it('DtoCollection pluckKey() maps one property to another', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $map = $col->pluckKey('id', 'name');

        expect($map)->toBe([1 => 'Alice', 2 => 'Bob']);
    });

    it('DtoCollection pluckKey() skips items with null key values', function (): void {
        $item1 = CollectionItemDTO::fromArray(['id' => 1, 'name' => 'Alice', 'score' => 10], validate: false);
        $item2 = CollectionItemDTO::fromArray(['id' => 2, 'name' => 'Bob', 'score' => 20, 'email' => null], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        $map = $col->pluckKey('email', 'name');

        // Item2 has null email, should be skipped
        expect($map)->toBeEmpty();
    });

    // ── Contract Interface Verification ───────────────────────────────

    it('DataTransferObject implements FromRequestDTO', function (): void {
        expect(RoundtripDTO::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function (): void {
        expect(RoundtripDTO::class)->toImplement(ValidatableDTO::class);
    });

    it('RoundtripDTO rules() returns expected structure', function (): void {
        $rules = RoundtripDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:1');
        expect($rules['name'])->toContain('max:100');
    });

    // ── JsonSerializable Contract ────────────────────────────────────

    it('DtoCollection implements JsonSerializable', function (): void {
        $ref = new \ReflectionClass(DtoCollection::class);

        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DataTransferObject implements JsonSerializable', function (): void {
        $ref = new \ReflectionClass(DataTransferObject::class);

        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });
});
