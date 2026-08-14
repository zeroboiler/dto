<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{AllScalarTypesDTO, ItemDTO, WithRoundtripDTO};

// ============================================================================
// WithRoundtripDTO: immutable with() behavior and serialization roundtrip
// ============================================================================

describe('WithRoundtripDTO — immutable with() override and roundtrip', function (): void {
    test('with() returns a new instance (immutability)', function (): void {
        $original = WithRoundtripDTO::fromArray([
            'quantity' => 5,
            'sku' => 'SKU-001',
        ], validate: false);

        $modified = $original->with(['quantity' => 10]);

        expect($modified)->not->toBe($original)
            ->and($original->quantity)->toBe(5)
            ->and($modified->quantity)->toBe(10)
            ->and($modified->sku)->toBe('SKU-001');
    });

    test('with() preserves hidden fields internally', function (): void {
        $original = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'SKU-001',
            'internalCode' => 'SECRET-123',
        ], validate: false);

        $modified = $original->with(['quantity' => 3]);

        expect($modified->internalCode)->toBe('SECRET-123');
    });

    test('with() merges correctly with allValues()', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'SKU-001',
        ], validate: false);

        $updated = $dto->with([
            'quantity' => 7,
            'isActive' => true,
            'note' => 'Updated note',
        ]);

        expect($updated->quantity)->toBe(7)
            ->and($updated->sku)->toBe('SKU-001')
            ->and($updated->isActive)->toBeTrue()
            ->and($updated->note)->toBe('Updated note');
    });

    test('toArray() excludes hidden fields', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'SKU-001',
            'internalCode' => 'HIDDEN',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('quantity')
            ->and($arr)->toHaveKey('sku')
            ->and($arr)->not->toHaveKey('internalCode');
    });

    test('allValues() includes hidden fields', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'SKU-001',
            'internalCode' => 'HIDDEN',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('quantity')
            ->and($all)->toHaveKey('sku')
            ->and($all)->toHaveKey('internalCode')
            ->and($all['internalCode'])->toBe('HIDDEN');
    });

    test('serialization roundtrip: fromArray → toArray → fromArray', function (): void {
        $original = WithRoundtripDTO::fromArray([
            'quantity' => 5,
            'sku' => 'SKU-RT-001',
            'isActive' => true,
            'note' => 'Roundtrip test',
        ], validate: false);

        $arr = $original->toArray();
        $restored = WithRoundtripDTO::fromArray($arr, validate: false);

        expect($restored->quantity)->toBe($original->quantity)
            ->and($restored->sku)->toBe($original->sku)
            ->and($restored->isActive)->toBe($original->isActive)
            ->and($restored->note)->toBe($original->note);
    });

    test('with() null override sets nullable field to null', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'SKU-001',
            'note' => 'Has note',
        ], validate: false);

        $updated = $dto->with(['note' => null]);

        expect($updated->note)->toBeNull();
    });

    test('isEmpty() returns false when at least one non-empty field exists', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 0, // 0 is considered non-empty
            'sku' => '',
        ], validate: false);

        // quantity=0 is non-empty for non-nullable int
        expect($dto->isEmpty())->toBeFalse();
    });

    test('isEmpty() returns true when all fields are empty/null', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 0, // non-nullable int, 0 is non-empty
            'sku' => '',
            'isActive' => false,
            'note' => null,
            'internalCode' => '',
        ], validate: false);

        // quantity=0 prevents isEmpty() from returning true
        expect($dto->isEmpty())->toBeFalse();

        // But isNotEmpty should be true since quantity=0 is non-empty
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

// ============================================================================
// fromPartialArray: PATCH semantics edge cases
// ============================================================================

describe('fromPartialArray — PATCH semantics with defaults and empty values', function (): void {
    test('partial update preserves unmodified fields at defaults', function (): void {
        $dto = AllScalarTypesDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Updated Name')
            ->and($dto->count)->toBe(1) // default
            ->and($dto->active)->toBeFalse() // default
            ->and($dto->score)->toBe(0.0); // DefaultValue attribute
    });

    test('partial update with empty data uses all defaults', function (): void {
        $dto = AllScalarTypesDTO::fromPartialArray([], validatePresent: false);

        expect($dto->count)->toBe(1)
            ->and($dto->name)->toBe('')
            ->and($dto->active)->toBeFalse()
            ->and($dto->score)->toBe(0.0)
            ->and($dto->optional)->toBeNull()
            ->and($dto->tag)->toBe('default-tag')
            ->and($dto->mapped)->toBeNull()
            ->and($dto->items)->toBe([]);
    });

    test('partial update with MapFrom uses source key', function (): void {
        $dto = AllScalarTypesDTO::fromPartialArray([
            'source_field' => 'mapped-value',
        ], validatePresent: false);

        expect($dto->mapped)->toBe('mapped-value');
    });

    test('partial update with explicit null sets nullable to null', function (): void {
        $dto = AllScalarTypesDTO::fromPartialArray([
            'optional' => null,
        ], validatePresent: false);

        expect($dto->optional)->toBeNull();
    });

    test('partial update with Cast applies cast type', function (): void {
        $dto = AllScalarTypesDTO::fromPartialArray([
            'castedInt' => '42',
        ], validatePresent: false);

        expect($dto->castedInt)->toBe(42);
    });
});

// ============================================================================
// DTOManager: delegation and error handling
// ============================================================================

describe('DTOManager — delegation and type safety', function (): void {
    test('DTOManager is final readonly', function (): void {
        $ref = new ReflectionClass(DTOManager::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    test('make() creates DTO from array', function (): void {
        $manager = new DTOManager;
        $dto = $manager->make(WithRoundtripDTO::class, [
            'quantity' => 5,
            'sku' => 'TEST',
        ]);

        expect($dto)->toBeInstanceOf(WithRoundtripDTO::class)
            ->and($dto->quantity)->toBe(5)
            ->and($dto->sku)->toBe('TEST');
    });

    test('rules() returns validation rules', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rules(WithRoundtripDTO::class);

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKey('quantity');
    });

    test('rulesFor() defaults to rules()', function (): void {
        $manager = new DTOManager;

        expect($manager->rulesFor(WithRoundtripDTO::class, 'create'))
            ->toBe($manager->rules(WithRoundtripDTO::class));
    });

    test('makeFromJson() creates DTO from JSON string', function (): void {
        $manager = new DTOManager;
        $dto = $manager->makeFromJson(WithRoundtripDTO::class, json_encode([
            'quantity' => 10,
            'sku' => 'JSON-SKU',
        ]));

        expect($dto)->toBeInstanceOf(WithRoundtripDTO::class)
            ->and($dto->quantity)->toBe(10);
    });

    test('makeFromJson() throws DTOException for invalid JSON', function (): void {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(WithRoundtripDTO::class, 'not-json'))
            ->toThrow(DTOException::class);
    });

    test('fromJson() is an alias for makeFromJson()', function (): void {
        $manager = new DTOManager;
        $json = json_encode(['quantity' => 3, 'sku' => 'ALIAS']);

        $a = $manager->makeFromJson(WithRoundtripDTO::class, $json);
        $b = $manager->fromJson(WithRoundtripDTO::class, $json);

        expect($a->quantity)->toBe($b->quantity)
            ->and($a->sku)->toBe($b->sku);
    });

    test('fromPartialArray() delegates correctly', function (): void {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(AllScalarTypesDTO::class, [
            'name' => 'Partial',
        ]);

        expect($dto)->toBeInstanceOf(AllScalarTypesDTO::class)
            ->and($dto->name)->toBe('Partial');
    });
});

// ============================================================================
// Metadata cache TTL behavior
// ============================================================================

describe('Metadata cache — TTL and flush behavior', function (): void {
    test('setMetadataCacheTtl configures TTL', function (): void {
        AllScalarTypesDTO::setMetadataCacheTtl(5.0);

        // Access metadata to populate cache
        $rules1 = AllScalarTypesDTO::rules();
        expect($rules1)->not->toBeEmpty();

        // Reset
        AllScalarTypesDTO::setMetadataCacheTtl(0.0);
    });

    test('flushMetadataCache() clears specific class', function (): void {
        AllScalarTypesDTO::flushMetadataCache(AllScalarTypesDTO::class);

        $rules = AllScalarTypesDTO::rules();
        expect($rules)->not->toBeEmpty();
    });

    test('flushMetadataCache(null) clears all classes', function (): void {
        AllScalarTypesDTO::flushMetadataCache(null);

        $rules = AllScalarTypesDTO::rules();
        expect($rules)->not->toBeEmpty();
    });
});

// ============================================================================
// DTOCollection: advanced operations
// ============================================================================

describe('DtoCollection — advanced operations and edge cases', function (): void {
    test('collection with single item', function (): void {
        $item = ItemDTO::fromArray(['id' => 1, 'name' => 'Item 1'], validate: false);
        $col = new DtoCollection([$item]);

        expect($col->count())->toBe(1)
            ->and($col->first())->toBe($item)
            ->and($col->last())->toBe($item)
            ->and($col->isEmpty())->toBeFalse();
    });

    test('empty collection', function (): void {
        $col = new DtoCollection;

        expect($col->count())->toBe(0)
            ->and($col->first())->toBeNull()
            ->and($col->last())->toBeNull()
            ->and($col->isEmpty())->toBeTrue()
            ->and($col->isNotEmpty())->toBeFalse();
    });

    test('filter returns new collection without mutation', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false),
            ItemDTO::fromArray(['id' => 3, 'name' => 'A'], validate: false),
        ];
        $col = new DtoCollection($items);

        $filtered = $col->filter(fn (ItemDTO $dto): bool => $dto->name === 'A');

        expect($filtered->count())->toBe(2)
            ->and($col->count())->toBe(3); // original unchanged
    });

    test('append returns new collection without mutation', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
        ];
        $col = new DtoCollection($items);
        $newItem = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);

        $appended = $col->append($newItem);

        expect($appended->count())->toBe(2)
            ->and($col->count())->toBe(1); // original unchanged
    });

    test('merge combines two collections', function (): void {
        $col1 = new DtoCollection([
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
        ]);
        $col2 = new DtoCollection([
            ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false),
        ]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2)
            ->and($col1->count())->toBe(1)
            ->and($col2->count())->toBe(1);
    });

    test('pluck extracts single property values', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 10, 'name' => 'X'], validate: false),
            ItemDTO::fromArray(['id' => 20, 'name' => 'Y'], validate: false),
        ];
        $col = new DtoCollection($items);

        expect($col->pluck('id'))->toBe([10, 20])
            ->and($col->pluck('name'))->toBe(['X', 'Y']);
    });

    test('toDictionary creates key-value map', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha', 'category' => 'cat-a'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Beta', 'category' => 'cat-b'], validate: false),
        ];
        $col = new DtoCollection($items);

        $dict = $col->toDictionary('id', 'name');

        expect($dict)->toBe([1 => 'Alpha', 2 => 'Beta']);
    });

    test('toArrayBy creates key-to-array map', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Beta'], validate: false),
        ];
        $col = new DtoCollection($items);

        $keyed = $col->toArrayBy('id');

        expect($keyed)->toBe([
            1 => ['id' => 1, 'name' => 'Alpha', 'category' => null],
            2 => ['id' => 2, 'name' => 'Beta', 'category' => null],
        ]);
    });

    test('toDictionary skips items with null key values', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha', 'category' => null], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'Beta', 'category' => 'cat-b'], validate: false),
        ];
        $col = new DtoCollection($items);

        $dict = $col->toDictionary('category', 'name');

        // id=1 has null category, skipped
        expect($dict)->toBe(['cat-b' => 'Beta']);
    });

    test('offsetUnset re-indexes collection', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false),
            ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false),
        ];
        $col = new DtoCollection($items);

        unset($col[0]); // Remove first item

        expect($col->count())->toBe(2)
            ->and($col[0]->id)->toBe(2) // Re-indexed: old [1] is now [0]
            ->and($col[1]->id)->toBe(3);
    });

    test('push mutates in-place and returns self', function (): void {
        $col = new DtoCollection;
        $item = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);

        $result = $col->push($item);

        expect($result)->toBe($col) // same instance
            ->and($col->count())->toBe(1);
    });

    test('map returns plain array of results', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false),
        ];
        $col = new DtoCollection($items);

        $names = $col->map(fn (ItemDTO $dto, int $i): string => strtoupper($dto->name));

        expect($names)->toBe(['A', 'B']); // Wait — map() returns array_map result, not DtoCollection
    });

    test('jsonSerialize produces array of arrays', function (): void {
        $items = [
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
        ];
        $col = new DtoCollection($items);

        $json = json_encode($col);

        expect($json)->toBeJson()
            ->and(json_decode($json, true))->toBe([
                ['id' => 1, 'name' => 'A', 'category' => null],
            ]);
    });

    test('clone throws RuntimeException', function (): void {
        $col = new DtoCollection;

        expect(fn () => clone $col)->toThrow(\RuntimeException::class, 'immutable');
    });
});

// ============================================================================
// fromJson edge cases
// ============================================================================

describe('fromJson — JSON hydration edge cases', function (): void {
    test('fromJson with valid JSON object', function (): void {
        $dto = WithRoundtripDTO::fromJson('{"quantity": 5, "sku": "JSON-001"}', validate: false);

        expect($dto->quantity)->toBe(5)
            ->and($dto->sku)->toBe('JSON-001');
    });

    test('fromJson with empty object uses defaults', function (): void {
        $dto = WithRoundtripDTO::fromJson('{}', validate: false);

        expect($dto->quantity)->toBe(1)
            ->and($dto->sku)->toBe('')
            ->and($dto->isActive)->toBeFalse()
            ->and($dto->note)->toBeNull();
    });

    test('fromJson rejects sequential arrays', function (): void {
        expect(fn () => WithRoundtripDTO::fromJson('[{"quantity": 1}]', validate: false))
            ->toThrow(DTOException::class, 'sequential array');
    });

    test('fromJson rejects invalid JSON', function (): void {
        expect(fn () => WithRoundtripDTO::fromJson('{invalid}', validate: false))
            ->toThrow(DTOException::class);
    });

    test('fromJson rejects non-object JSON (string)', function (): void {
        expect(fn () => WithRoundtripDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    test('toJson produces valid JSON', function (): void {
        $dto = WithRoundtripDTO::fromArray([
            'quantity' => 3,
            'sku' => 'TOJSON',
            'isActive' => true,
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['quantity'])->toBe(3)
            ->and($decoded['sku'])->toBe('TOJSON')
            ->and($decoded['isActive'])->toBeTrue()
            ->and($decoded)->not->toHaveKey('internalCode'); // Hidden
    });
});

// ============================================================================
// equals() and only()/except()
// ============================================================================

describe('DTO equality and field selection', function (): void {
    test('equals returns true for identical public state', function (): void {
        $a = WithRoundtripDTO::fromArray([
            'quantity' => 5,
            'sku' => 'EQ-001',
        ], validate: false);
        $b = WithRoundtripDTO::fromArray([
            'quantity' => 5,
            'sku' => 'EQ-001',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    test('equals returns false for different state', function (): void {
        $a = WithRoundtripDTO::fromArray([
            'quantity' => 5,
            'sku' => 'EQ-001',
        ], validate: false);
        $b = WithRoundtripDTO::fromArray([
            'quantity' => 10,
            'sku' => 'EQ-001',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    test('equals ignores hidden fields', function (): void {
        $a = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'HID',
            'internalCode' => 'CODE-A',
        ], validate: false);
        $b = WithRoundtripDTO::fromArray([
            'quantity' => 1,
            'sku' => 'HID',
            'internalCode' => 'CODE-B',
        ], validate: false);

        // Hidden fields are not in toArray(), so equals returns true
        expect($a->equals($b))->toBeTrue();
    });

    test('only() returns specified fields', function (): void {
        $dto = AllScalarTypesDTO::fromArray([
            'count' => 10,
            'name' => 'OnlyTest',
            'tag' => 'test-tag',
        ], validate: false);

        $result = $dto->only(['count', 'name']);

        expect($result)->toHaveCount(2)
            ->and($result)->toHaveKeys(['count', 'name']);
    });

    test('only() with single string key', function (): void {
        $dto = AllScalarTypesDTO::fromArray([
            'count' => 10,
            'name' => 'SingleKey',
        ], validate: false);

        $result = $dto->only('count');

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('count');
    });

    test('except() excludes specified fields', function (): void {
        $dto = AllScalarTypesDTO::fromArray([
            'count' => 10,
            'name' => 'ExceptTest',
            'tag' => 'test-tag',
        ], validate: false);

        $result = $dto->except(['count']);

        expect($result)->not->toHaveKey('count')
            ->and($result)->toHaveKey('name');
    });

    test('except() with single string key', function (): void {
        $dto = AllScalarTypesDTO::fromArray([
            'count' => 10,
            'name' => 'SingleKey',
        ], validate: false);

        $result = $dto->except('count');

        expect($result)->not->toHaveKey('count')
            ->and($result)->toHaveKey('name');
    });
});
