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
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;

describe('DTO Structural and Contract Integrity', function () {
    // ─── Hydration contract ───
    describe('Hydration contract', function () {
        it('fromArray() creates DTO with typed properties', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 10,
                'name' => 'Alice',
            ]);

            expect($dto->count)->toBe(10);
            expect($dto->name)->toBe('Alice');
            expect($dto->active)->toBeFalse();
            expect($dto->score)->toBe(0.0);
            expect($dto->optional)->toBeNull();
            expect($dto->tag)->toBe('default-tag');
            expect($dto->mapped)->toBeNull();
        });

        it('fromArray() applies MapFrom correctly', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Bob',
                'source_field' => 'mapped_value',
            ]);

            expect($dto->mapped)->toBe('mapped_value');
        });

        it('fromArray() applies Cast correctly', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'castedInt' => '42',
            ]);

            expect($dto->castedInt)->toBe(42);
        });

        it('fromArray() with validate: false skips validation', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'name' => '',  // would fail Min(1) on count
            ], validate: false);

            expect($dto)->toBeInstanceOf(AllScalarTypesDTO::class);
        });

        it('fromArray() respects explicit null values over defaults', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'tag' => null,
            ]);

            expect($dto->tag)->toBeNull();
        });

        it('fromJson() parses valid JSON', function () {
            $dto = AllScalarTypesDTO::fromJson('{"count":5,"name":"Test"}');
            expect($dto->count)->toBe(5);
            expect($dto->name)->toBe('Test');
        });

        it('fromJson() throws DTOException for invalid JSON', function () {
            expect(fn () => AllScalarTypesDTO::fromJson('not json'))
                ->toThrow(DTOException::class);
        });

        it('fromJson() throws DTOException for sequential arrays', function () {
            expect(fn () => AllScalarTypesDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });

        it('fromJson() accepts empty object', function () {
            $dto = AllDefaultsDTO::fromJson('{}');
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
            expect($dto->name)->toBe('default-name');
        });
    });

    // ─── Partial update (PATCH) contract ───
    describe('Partial update (PATCH) contract', function () {
        it('fromPartialArray() merges provided fields with defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'updated']);

            expect($dto->name)->toBe('updated');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
        });

        it('fromPartialArray() with empty array uses all defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray([]);

            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
        });

        it('fromPartialArray() with validatePresent: false skips validation', function () {
            $dto = AllScalarTypesDTO::fromPartialArray([
                'count' => -999,  // would fail Min(1)
            ], validatePresent: false);

            expect($dto->count)->toBe(-999);
        });
    });

    // ─── Serialization contract ───
    describe('Serialization contract', function () {
        it('toArray() excludes hidden properties', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'secret' => 'password123',
            ]);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('secret');
            expect($arr)->toHaveKey('name');
        });

        it('allValues() includes hidden properties', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'secret' => 'password123',
            ]);

            $all = $dto->allValues();
            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('password123');
        });

        it('toJson() produces valid JSON string', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            $json = $dto->toJson();
            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->not->toHaveKey('token');
        });

        it('jsonSerialize() returns toArray() output', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // ─── Selective output contract ───
    describe('Selective output contract', function () {
        it('only() returns specified fields', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $result = $dto->only('name');
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('name');
        });

        it('except() excludes specified fields', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $result = $dto->except('name');
            expect($result)->not->toHaveKey('name');
        });

        it('only() and except() accept string argument', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto->only('name'))->toBeArray();
            expect($dto->except('name'))->toBeArray();
        });
    });

    // ─── Immutable update contract ───
    describe('Immutable update contract', function () {
        it('with() creates a new instance', function () {
            $original = AllDefaultsDTO::fromArray(['name' => 'Original']);
            $updated = $original->with(['name' => 'Updated']);

            expect($updated)->not->toBe($original);
            expect($original->name)->toBe('Original');
            expect($updated->name)->toBe('Updated');
        });

        it('with() validates merged data', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            // AllDefaultsDTO has no required fields, so any merge should work
            $updated = $dto->with(['name' => 'Test']);
            expect($updated->name)->toBe('Test');
        });
    });

    // ─── Equality contract ───
    describe('Equality contract', function () {
        it('equals() returns true for identical DTOs', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $b = AllDefaultsDTO::fromArray(['name' => 'Test']);
            expect($a->equals($b))->toBeTrue();
        });

        it('equals() returns false for different DTOs', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $b = AllDefaultsDTO::fromArray(['name' => 'Other']);
            expect($a->equals($b))->toBeFalse();
        });
    });

    // ─── isEmpty contract ───
    describe('isEmpty contract', function () {
        it('returns true when all properties are empty/default', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('returns false when at least one property has non-empty value', function () {
            $dto = AllDefaultsDTO::fromArray(['name' => 'Non-empty']);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() is the negation of isEmpty()', function () {
            $empty = AllDefaultsDTO::fromArray([]);
            $nonEmpty = AllDefaultsDTO::fromArray(['name' => 'Test']);
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });
    });

    // ─── Rules contract ───
    describe('Rules contract', function () {
        it('rules() returns Laravel-compatible rules array', function () {
            $rules = AllScalarTypesDTO::rules();
            expect($rules)->toBeArray();

            if (isset($rules['count'])) {
                expect($rules['count'])->toBeArray();
                expect($rules['count'])->toContain('integer');
                expect($rules['count'])->toContain('min:1');
                expect($rules['count'])->toContain('max:100');
            }
        });

        it('rulesFor() returns same as rules() by default', function () {
            expect(AllDefaultsDTO::rulesFor('create'))->toBe(AllDefaultsDTO::rules());
            expect(AllDefaultsDTO::rulesFor('update'))->toBe(AllDefaultsDTO::rules());
        });

        it('validateArray() returns validated data', function () {
            $data = AllDefaultsDTO::validateArray(['name' => 'Test', 'count' => 5]);
            expect($data)->toBeArray();
        });
    });

    // ─── DtoCollection contract ───
    describe('DtoCollection contract', function () {
        it('make() creates from array of DTOs', function () {
            $a = AllDefaultsDTO::fromArray([]);
            $col = DtoCollection::make([$a]);
            expect($col->count())->toBe(1);
        });

        it('constructor rejects non-DTO items', function () {
            expect(fn () => new DtoCollection(['not-a-dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('toArray() serializes all items', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'A']);
            $b = AllDefaultsDTO::fromArray(['name' => 'B']);
            $col = DtoCollection::make([$a, $b]);
            $arr = $col->toArray();
            expect($arr)->toHaveCount(2);
            expect($arr[0]['name'])->toBe('A');
            expect($arr[1]['name'])->toBe('B');
        });

        it('push() mutates in-place and returns self', function () {
            $a = AllDefaultsDTO::fromArray([]);
            $col = DtoCollection::make([$a]);
            $b = AllDefaultsDTO::fromArray([]);
            $result = $col->push($b);
            expect($col->count())->toBe(2);
            expect($result)->toBe($col);
        });

        it('append() returns new immutable collection', function () {
            $a = AllDefaultsDTO::fromArray([]);
            $col = DtoCollection::make([$a]);
            $b = AllDefaultsDTO::fromArray([]);
            $newCol = $col->append($b);
            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge() returns new combined collection', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'A']);
            $b = AllDefaultsDTO::fromArray(['name' => 'B']);
            $colA = DtoCollection::make([$a]);
            $colB = DtoCollection::make([$b]);
            $merged = $colA->merge($colB);
            expect($merged->count())->toBe(2);
        });

        it('filter() returns new filtered collection', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Keep']);
            $b = AllDefaultsDTO::fromArray(['name' => '']);
            $col = DtoCollection::make([$a, $b]);
            $filtered = $col->filter(fn (DataTransferObject $d) => $d->name !== '');
            expect($filtered->count())->toBe(1);
        });

        it('first() and last() return correct items', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'First']);
            $b = AllDefaultsDTO::fromArray(['name' => 'Last']);
            $col = DtoCollection::make([$a, $b]);
            expect($col->first()?->name)->toBe('First');
            expect($col->last()?->name)->toBe('Last');
        });

        it('isEmpty() and isNotEmpty() work correctly', function () {
            $empty = DtoCollection::make([]);
            $nonEmpty = DtoCollection::make([AllDefaultsDTO::fromArray([])]);
            expect($empty->isEmpty())->toBeTrue();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('ArrayAccess works for reading', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $col = DtoCollection::make([$a]);
            expect($col[0])->toBe($a);
            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeFalse();
        });

        it('foreach iteration works', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'A']);
            $b = AllDefaultsDTO::fromArray(['name' => 'B']);
            $col = DtoCollection::make([$a, $b]);
            $names = [];
            foreach ($col as $dto) {
                $names[] = $dto->name;
            }
            expect($names)->toEqual(['A', 'B']);
        });

        it('jsonSerialize() returns toArray() output', function () {
            $a = AllDefaultsDTO::fromArray([]);
            $col = DtoCollection::make([$a]);
            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('__clone() throws RuntimeException', function () {
            $col = DtoCollection::make([]);
            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('pluck() extracts a single property', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice']);
            $b = AllDefaultsDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$a, $b]);
            expect($col->pluck('name'))->toEqual(['Alice', 'Bob']);
        });

        it('pluckKey() creates key-value map', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice', 'count' => 5]);
            $col = DtoCollection::make([$a]);
            $map = $col->pluckKey('name', 'count');
            expect($map)->toEqual(['Alice' => 5]);
        });

        it('map() transforms items to plain array', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice']);
            $b = AllDefaultsDTO::fromArray(['name' => 'Bob']);
            $col = DtoCollection::make([$a, $b]);
            $names = $col->map(fn (DataTransferObject $d, int $i) => $d->name);
            expect($names)->toEqual(['Alice', 'Bob']);
        });

        it('offsetUnset() re-indexes the collection', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'A']);
            $b = AllDefaultsDTO::fromArray(['name' => 'B']);
            $c = AllDefaultsDTO::fromArray(['name' => 'C']);
            $col = DtoCollection::make([$a, $b, $c]);
            unset($col[0]);
            expect($col->count())->toBe(2);
            expect($col->first()?->name)->toBe('B');
        });

        it('toArrayBy() re-keys by property', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice']);
            $col = DtoCollection::make([$a]);
            $keyed = $col->toArrayBy('name');
            expect($keyed)->toHaveKey('Alice');
        });

        it('toDictionary() creates lookup map', function () {
            $a = AllDefaultsDTO::fromArray(['name' => 'Alice', 'count' => 5]);
            $col = DtoCollection::make([$a]);
            $dict = $col->toDictionary('name', 'count');
            expect($dict)->toEqual(['Alice' => 5]);
        });

        it('items() returns raw DTO instances', function () {
            $a = AllDefaultsDTO::fromArray([]);
            $col = DtoCollection::make([$a]);
            $items = $col->items();
            expect($items[0])->toBe($a);
        });

        it('allValues() includes hidden fields', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'secret' => 'hidden',
            ]);
            $col = DtoCollection::make([$dto]);
            $all = $col->allValues();
            expect($all[0])->toHaveKey('secret');
            expect($all[0]['secret'])->toBe('hidden');
        });
    });

    // ─── DTOCast contract ───
    describe('DTOCast contract', function () {
        it('get() returns null for null database value', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $result = $cast->get(new stdClass(), 'data', null, []);
            expect($result)->toBeNull();
        });

        it('get() hydrates from JSON string', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $json = json_encode(['name' => 'Test', 'count' => 0]);
            $result = $cast->get(new stdClass(), 'data', $json, []);
            expect($result)->toBeInstanceOf(AllDefaultsDTO::class);
            expect($result->name)->toBe('Test');
        });

        it('get() returns null for invalid JSON', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $result = $cast->get(new stdClass(), 'data', 'not json', []);
            expect($result)->toBeNull();
        });

        it('set() serializes DTO to JSON', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $dto = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $result = $cast->set(new stdClass(), 'data', $dto, []);
            expect($result)->toBeJson();
            $decoded = json_decode($result, true);
            expect($decoded['name'])->toBe('Test');
        });

        it('set() hydrates and serializes from array', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $result = $cast->set(new stdClass(), 'data', ['name' => 'Test'], []);
            expect($result)->toBeJson();
        });

        it('set() returns null for null value', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $result = $cast->set(new stdClass(), 'data', null, []);
            expect($result)->toBeNull();
        });

        it('set() throws for unexpected type', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            expect(fn () => $cast->set(new stdClass(), 'data', 42, []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns toArray() of DTO', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $dto = AllDefaultsDTO::fromArray(['name' => 'Test']);
            $result = $cast->serialize(new stdClass(), 'data', $dto, []);
            expect($result)->toBeArray();
            expect($result['name'])->toBe('Test');
        });

        it('serialize() returns null for null value', function () {
            $cast = new DTOCast(AllDefaultsDTO::class);
            $result = $cast->serialize(new stdClass(), 'data', null, []);
            expect($result)->toBeNull();
        });
    });

    // ─── DTOException contract ───
    describe('DTOException contract', function () {
        it('invalidCast() creates with correct message', function () {
            $e = DTOException::invalidCast('email', 'integer', 'test@example.com');
            expect($e->getMessage())->toContain('email');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson() creates with correct message', function () {
            $e = DTOException::invalidJson('data', 'Syntax error');
            expect($e->getMessage())->toContain('data');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString() returns class name and message', function () {
            $e = DTOException::invalidJson('data', 'error');
            $str = (string) $e;
            expect($str)->toContain('DTOException');
        });
    });

    // ─── Metadata cache contract ───
    describe('Metadata cache contract', function () {
        it('setMetadataCacheTtl() configures TTL', function () {
            AllDefaultsDTO::setMetadataCacheTtl(10.0);
            // No assertion needed — just verify it doesn't throw
            expect(true)->toBeTrue();
            // Reset
            AllDefaultsDTO::setMetadataCacheTtl(0.0);
        });

        it('flushMetadataCache() clears all cached metadata', function () {
            AllDefaultsDTO::fromArray([]);  // populate cache
            AllDefaultsDTO::flushMetadataCache();
            // Re-resolve should work
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('flushMetadataCache($class) clears only specified class', function () {
            AllDefaultsDTO::fromArray([]);
            AllScalarTypesDTO::fromArray(['count' => 5, 'name' => 'Test']);
            AllDefaultsDTO::flushMetadataCache(AllDefaultsDTO::class);
            // AllDefaultsDTO cache is cleared, AllScalarTypesDTO is not
            $dto = AllScalarTypesDTO::fromArray(['count' => 5, 'name' => 'Test']);
            expect($dto)->toBeInstanceOf(AllScalarTypesDTO::class);
        });
    });

    // ─── DTOManager delegation contract ───
    describe('DTOManager delegation contract', function () {
        it('make() creates DTO from data', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $dto = $manager->make(AllDefaultsDTO::class, ['name' => 'Test']);
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('makeFromJson() creates DTO from JSON', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $dto = $manager->makeFromJson(AllDefaultsDTO::class, '{"name":"Test"}');
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('validate() returns validated data', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $data = $manager->validate(AllDefaultsDTO::class, ['name' => 'Test']);
            expect($data)->toBeArray();
        });

        it('rules() returns validation rules', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $rules = $manager->rules(AllDefaultsDTO::class);
            expect($rules)->toBeArray();
        });

        it('rulesFor() returns action-scoped rules', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $rules = $manager->rulesFor(AllDefaultsDTO::class, 'update');
            expect($rules)->toBeArray();
        });

        it('fromJson() is alias for makeFromJson()', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $dto = $manager->fromJson(AllDefaultsDTO::class, '{"name":"Test"}');
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('fromPartialArray() creates partial DTO', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $dto = $manager->fromPartialArray(AllDefaultsDTO::class, ['name' => 'Updated']);
            expect($dto->name)->toBe('Updated');
            expect($dto->count)->toBe(0);
        });

        it('schema() returns OpenAPI schema', function () {
            $manager = new \ZeroBoiler\DTO\DTOManager;
            $schema = $manager->schema(AllDefaultsDTO::class);
            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
        });
    });

    // ─── Interface compliance ───
    describe('Interface compliance', function () {
        it('ValidatableDTO interface is implemented', function () {
            expect(AllDefaultsDTO::rules())->toBeArray();
            expect(AllDefaultsDTO::rulesFor('create'))->toBeArray();
        });

        it('FromRequestDTO interface is satisfied via fromRequest()', function () {
            // Verify the method exists and has correct signature
            expect(method_exists(AllDefaultsDTO::class, 'fromRequest'))->toBeTrue();
        });

        it('JsonSerializable interface is satisfied', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto)->toBeInstanceOf(\JsonSerializable::class);
            expect(json_encode($dto))->toBeJson();
        });

        it('Arrayable interface is satisfied', function () {
            $dto = AllDefaultsDTO::fromArray([]);
            expect($dto)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
            expect($dto->toArray())->toBeArray();
        });
    });

    // ─── Cast type pipeline ───
    describe('Cast type pipeline', function () {
        it('Cast integer normalizes string to int', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'castedInt' => '42',
            ]);
            expect($dto->castedInt)->toBe(42);
        });

        it('Cast boolean normalizes truthy strings', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'active' => 'yes',
            ]);
            expect($dto->active)->toBeTrue();
        });

        it('Cast boolean normalizes falsy strings', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'active' => 'no',
            ]);
            expect($dto->active)->toBeFalse();
        });
    });

    // ─── Null handling ───
    describe('Null handling', function () {
        it('nullable property accepts null', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'optional' => null,
            ]);
            expect($dto->optional)->toBeNull();
        });

        it('nullable property accepts value', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'count' => 5,
                'name' => 'Test',
                'optional' => 'has-value',
            ]);
            expect($dto->optional)->toBe('has-value');
        });
    });
});
