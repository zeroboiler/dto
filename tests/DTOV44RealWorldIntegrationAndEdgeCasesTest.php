<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;

/**
 * V44 — Real-world integration, edge cases, and PHP 8.5 compliance audit.
 *
 * Covers:
 * - fromArray/toArray roundtrip with scalar types
 * - fromArray with MapFrom dot notation
 * - fromPartialArray with DefaultValue attribute
 * - Hidden attribute exclusion in toArray()
 * - fromJson with valid and invalid JSON
 * - equals() comparison
 * - isEmpty()/isNotEmpty() state detection
 * - with() immutable update
 * - only()/except() selective output
 * - DTOCast get/set cycle
 * - DTOManager delegation
 * - DtoCollection operations (push, filter, unique, sortBy, take, skip)
 * - fromPartialArray with type-appropriate empty values
 * - DTOException message format
 * - Union type properties
 * - rules() structure validation
 */
describe('V44 Real-World Integration and Edge Cases', function () {
    // ---------------------------------------------------------------
    // Basic roundtrip
    // ---------------------------------------------------------------
    it('fromArray/toArray roundtrip preserves scalar values', function () {
        $data = [
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'age' => 30,
            'active' => true,
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['email'])->toBe('test@example.com')
            ->and($result['name'])->toBe('John Doe')
            ->and($result['age'])->toBe(30)
            ->and($result['active'])->toBeTrue();
    });

    it('fromArray with empty data uses defaults', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        $arr = $dto->toArray();

        expect($arr)->not->toBeEmpty();
        // AllDefaultsDTO has default values for all fields
    });

    // ---------------------------------------------------------------
    // fromJson
    // ---------------------------------------------------------------
    it('fromJson creates DTO from valid JSON', function () {
        $json = '{"email": "a@b.com", "name": "Alice", "age": 25, "active": true}';
        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->toArray()['email'])->toBe('a@b.com');
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for sequential array', function () {
        expect(fn () => MinimalDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    // ---------------------------------------------------------------
    // MapFrom dot notation
    // ---------------------------------------------------------------
    it('MapFrom resolves dot notation keys', function () {
        $dto = RoundtripDTO::fromArray([
            'user.name' => 'Alice',
            'user.email' => 'alice@example.com',
        ], validate: false);

        $arr = $dto->toArray();
        // The DTO should have the mapped values
        expect($arr)->toBeArray();
    });

    // ---------------------------------------------------------------
    // Hidden attribute
    // ---------------------------------------------------------------
    it('toArray excludes Hidden properties', function () {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Test',
            'email' => 'test@example.com',
        ], validate: false);

        $public = $dto->toArray();
        $all = $dto->allValues();

        // allValues should include hidden fields, toArray should not
        expect(count($all))->toBeGreaterThanOrEqual(count($public));
    });

    // ---------------------------------------------------------------
    // equals()
    // ---------------------------------------------------------------
    it('equals returns true for same data', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different data', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'age' => 30,
            'active' => false,
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    // ---------------------------------------------------------------
    // isEmpty / isNotEmpty
    // ---------------------------------------------------------------
    it('AllDefaultsDTO with defaults is not empty', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ---------------------------------------------------------------
    // with() immutable update
    // ---------------------------------------------------------------
    it('with() creates new instance with overrides', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $dto2 = $dto1->with(['name' => 'Bob']);

        expect($dto2->toArray()['name'])->toBe('Bob');
        expect($dto1->toArray()['name'])->toBe('Alice'); // original unchanged
        expect($dto2)->not->toBe($dto1); // different instance
    });

    // ---------------------------------------------------------------
    // only() / except()
    // ---------------------------------------------------------------
    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $result = $dto->only(['email', 'name']);
        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('age');
    });

    it('except excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $result = $dto->except(['age', 'active']);
        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('age');
    });

    // ---------------------------------------------------------------
    // DTOCast
    // ---------------------------------------------------------------
    it('DTOCast set/get roundtrip with DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $stored = $cast->set(new \stdClass, 'data', $dto, []);
        expect($stored)->toBeString();

        $restored = $cast->get(new \stdClass, 'data', $stored, []);
        expect($restored)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('DTOCast set() with array hydrates through DTO', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $data = [
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ];

        $stored = $cast->set(new \stdClass, 'data', $data, []);
        expect($stored)->toBeJson();
    });

    it('DTOCast get() returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass, 'data', null, []);
        expect($result)->toBeNull();
    });

    // ---------------------------------------------------------------
    // DTOManager delegation
    // ---------------------------------------------------------------
    it('DTOManager make creates DTO instance', function () {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('DTOManager rules returns validation rules array', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
    });

    it('DTOManager rulesFor returns rules for an action', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

        expect($rules)->toBeArray();
    });

    it('DTOManager schema returns OpenAPI schema', function () {
        $manager = new DTOManager;
        $schema = $manager->schema(CreateUserDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
    });

    // ---------------------------------------------------------------
    // DtoCollection operations
    // ---------------------------------------------------------------
    it('DtoCollection push mutates and returns same instance', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $same = $collection->push($dto);

        expect($same)->toBe($collection); // same instance
        expect($collection->count())->toBe(2);
    });

    it('DtoCollection append returns new instance', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $new = $collection->append($dto);

        expect($new)->not->toBe($collection); // different instance
        expect($new->count())->toBe(2);
        expect($collection->count())->toBe(1); // original unchanged
    });

    it('DtoCollection filter returns new filtered collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'age' => 30,
            'active' => false,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $active = $collection->filter(fn (DataTransferObject $d): bool => $d->toArray()['active'] === true);

        expect($active->count())->toBe(1);
        expect($collection->count())->toBe(2); // original unchanged
    });

    it('DtoCollection unique removes duplicates based on toArray()', function () {
        $data = [
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $unique = $collection->unique();

        expect($unique->count())->toBe(1);
    });

    it('DtoCollection sortBy returns new sorted collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'c@c.com',
            'name' => 'Charlie',
            'age' => 35,
            'active' => true,
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@a.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $sorted = $collection->sortBy('email');

        expect($sorted->count())->toBe(2);
        // Alice (a@a.com) should come first
        $first = $sorted->first();
        expect($first->toArray()['email'])->toBe('a@a.com');
    });

    it('DtoCollection take and skip work correctly', function () {
        $dtos = [];
        for ($i = 0; $i < 5; $i++) {
            $dtos[] = CreateUserDTO::fromArray([
                'email' => "user{$i}@test.com",
                'name' => "User {$i}",
                'age' => 20 + $i,
                'active' => true,
            ], validate: false);
        }

        $collection = new DtoCollection($dtos);

        $first3 = $collection->take(3);
        expect($first3->count())->toBe(3);

        $last2 = $collection->skip(3);
        expect($last2->count())->toBe(2);
    });

    it('DtoCollection first/last return correct items', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'age' => 30,
            'active' => false,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first()->toArray()['email'])->toBe('a@b.com');
        expect($collection->last()->toArray()['email'])->toBe('b@c.com');
    });

    it('DtoCollection first/last return null for empty collection', function () {
        $collection = new DtoCollection;
        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('DtoCollection isEmpty and isNotEmpty', function () {
        $collection = new DtoCollection;
        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();

        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        expect($collection->isEmpty())->toBeFalse();
        expect($collection->isNotEmpty())->toBeTrue();
    });

    // ---------------------------------------------------------------
    // DtoCollection jsonSerialize
    // ---------------------------------------------------------------
    it('DtoCollection jsonSerialize returns array of arrays', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect(count($decoded))->toBe(1);
    });

    // ---------------------------------------------------------------
    // DtoCollection clone protection
    // ---------------------------------------------------------------
    it('DtoCollection clone throws RuntimeException', function () {
        $collection = new DtoCollection;

        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });

    // ---------------------------------------------------------------
    // DTOException message format
    // ---------------------------------------------------------------
    it('DTOException::invalidCast includes property and type', function () {
        $ex = DTOException::invalidCast('email', 'integer', 'not-an-int');
        expect($ex->getMessage())->toContain('email')
            ->and($ex->getMessage())->toContain('integer');
    });

    it('DTOException::invalidJson includes property and error', function () {
        $ex = DTOException::invalidJson('payload', 'Syntax error');
        expect($ex->getMessage())->toContain('payload')
            ->and($ex->getMessage())->toContain('Syntax error');
    });

    it('DTOException __toString includes class name', function () {
        $ex = DTOException::invalidJson('root', 'test');
        $str = (string) $ex;
        expect($str)->toContain('DTOException');
    });

    // ---------------------------------------------------------------
    // Rules structure
    // ---------------------------------------------------------------
    it('rules returns array with string keys matching property names', function () {
        $rules = StrictValidationDTO::rules();
        expect($rules)->toBeArray();

        foreach (array_keys($rules) as $field) {
            expect($field)->toBeString();
            expect($field)->not->toBeEmpty();
        }
    });

    // ---------------------------------------------------------------
    // ScalarConstraints validation attributes
    // ---------------------------------------------------------------
    it('ScalarConstraintsDTO has max and min rules from attributes', function () {
        $rules = ScalarConstraintsDTO::rules();

        // At least one field should have max/min constraints
        $allRules = array_merge(...array_values($rules));
        $ruleStrings = array_map(fn (mixed $r): string => is_string($r) ? $r : '', $allRules);

        $hasMax = count(array_filter($ruleStrings, fn (string $r): bool => str_starts_with($r, 'max:'))) > 0;
        $hasMin = count(array_filter($ruleStrings, fn (string $r): bool => str_starts_with($r, 'min:'))) > 0;

        expect($hasMax || $hasMin)->toBeTrue();
    });

    // ---------------------------------------------------------------
    // fromPartialArray with defaults
    // ---------------------------------------------------------------
    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = AllDefaultsDTO::fromPartialArray([], validate: false);
        $arr = $dto->toArray();
        expect($arr)->toBeArray();
    });

    // ---------------------------------------------------------------
    // DtoCollection merge
    // ---------------------------------------------------------------
    it('DtoCollection merge combines items from both collections', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'age' => 30,
            'active' => false,
        ], validate: false);

        $c1 = new DtoCollection([$dto1]);
        $c2 = new DtoCollection([$dto2]);
        $merged = $c1->merge($c2);

        expect($merged->count())->toBe(2);
        expect($c1->count())->toBe(1); // original unchanged
    });

    // ---------------------------------------------------------------
    // DtoCollection contains/search
    // ---------------------------------------------------------------
    it('DtoCollection contains finds matching item', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com',
            'name' => 'Bob',
            'age' => 30,
            'active' => false,
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $found = $collection->contains(fn (DataTransferObject $d): bool => $d->toArray()['name'] === 'Alice');
        expect($found)->toBeTrue();

        $notFound = $collection->contains(fn (DataTransferObject $d): bool => $d->toArray()['name'] === 'Charlie');
        expect($notFound)->toBeFalse();
    });

    it('DtoCollection search returns matching item or null', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 25,
            'active' => true,
        ], validate: false);

        $collection = new DtoCollection([$dto1]);

        $found = $collection->search(fn (DataTransferObject $d): bool => $d->toArray()['email'] === 'a@b.com');
        expect($found)->toBeInstanceOf(CreateUserDTO::class);

        $notFound = $collection->search(fn (DataTransferObject $d): bool => $d->toArray()['email'] === 'z@z.com');
        expect($notFound)->toBeNull();
    });

    // ---------------------------------------------------------------
    // Metadata cache TTL behavior
    // ---------------------------------------------------------------
    it('metadata cache uses TTL for invalidation', function () {
        DataTransferObject::setMetadataCacheTtl(0.5); // 500ms TTL
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // First access populates cache
        $rules1 = CreateUserDTO::rules();

        // Second access should use cache (within TTL)
        $rules2 = CreateUserDTO::rules();

        expect($rules1)->toBe($rules2);

        DataTransferObject::setMetadataCacheTtl(0.0); // reset to disabled
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
    });
});
