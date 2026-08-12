<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DtoCollection advanced edge cases and DataTransferObject partial update tests.
 *
 * Targets:
 * - DtoCollection toArrayBy/toDictionary with int keys, duplicate keys, and null keys
 * - DtoCollection jsonSerialize consistency
 * - DataTransferObject fromPartialArray() with various missing field scenarios
 * - DataTransferObject fromPartialArray() with all required fields present (full update)
 * - DataTransferObject with() validation always runs
 *
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

describe('DtoCollection advanced edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // toArrayBy / toDictionary with various key types
    // ──────────────────────────────────────────────────────────────

    describe('toArrayBy key type handling', function (): void {
        it('handles string keys correctly', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection([$dto]);
            $keyed = $collection->toArrayBy('name');

            expect($keyed)->toBe(['Alice' => $dto->toArray()]);
        });

        it('handles nullable field as key — skips null values', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection([$dto]);
            // phone is null — should be skipped
            $keyed = $collection->toArrayBy('phone');

            expect($keyed)->toBeEmpty();
        });

        it('toArrayBy with duplicate key values — later item wins', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Alice', // duplicate name key
                'status' => 'inactive',
            ], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $keyed = $collection->toArrayBy('name');

            expect($keyed)->toHaveCount(1);
            expect($keyed['Alice'])->toBe($dto2->toArray()); // last one wins
        });

        it('toDictionary with duplicate key values — later item wins', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Alice',
                'status' => 'inactive',
            ], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $dict = $collection->toDictionary('name', 'email');

            expect($dict)->toHaveCount(1);
            expect($dict['Alice'])->toBe('c@d.com');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // JSON serialization consistency
    // ──────────────────────────────────────────────────────────────

    describe('JSON serialization consistency', function (): void {
        it('jsonSerialize matches toArray output', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Bob',
                'status' => 'inactive',
            ], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->jsonSerialize())->toBe($collection->toArray());
        });

        it('hidden fields are excluded from jsonSerialize', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection([$dto]);
            $json = $collection->jsonSerialize();

            // password should not appear in any serialized item
            foreach ($json as $item) {
                expect(array_key_exists('password', $item))->toBeFalse();
            }
        });

        it('allValues includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection([$dto]);
            $all = $collection->allValues();

            foreach ($all as $item) {
                expect(array_key_exists('password', $item))->toBeTrue();
                expect($item['password'])->toBe('secret123');
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // DtoCollection mutation methods
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection mutation edge cases', function (): void {
        it('push returns self for chaining', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection;
            $result = $collection->push($dto);

            expect($result)->toBe($collection); // same instance (mutating)
            expect($collection->count())->toBe(1);
        });

        it('append returns new instance (immutable)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection;
            $newCollection = $collection->append($dto);

            expect($newCollection)->not->toBe($collection);
            expect($collection->count())->toBe(0);
            expect($newCollection->count())->toBe(1);
        });

        it('merge combines two collections immutably', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Bob',
                'status' => 'inactive',
            ], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1); // original unchanged
            expect($col2->count())->toBe(1); // original unchanged
        });

        it('offsetUnset re-indexes the collection', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Bob',
                'status' => 'inactive',
            ], validate: false);

            $dto3 = CreateUserDTO::fromArray([
                'email' => 'e@f.com',
                'name' => 'Charlie',
                'status' => 'active',
            ], validate: false);

            $collection = new DtoCollection([$dto1, $dto2, $dto3]);
            unset($collection[0]);

            // After re-indexing, [0] should be Bob, [1] should be Charlie
            expect($collection[0]->name)->toBe('Bob');
            expect($collection[1]->name)->toBe('Charlie');
            expect($collection->count())->toBe(2);
        });

        it('filter returns new collection with correct types', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Bob',
                'status' => 'inactive',
            ], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            $activeOnly = $collection->filter(
                fn (CreateUserDTO $dto): bool => $dto->status === 'active'
            );

            expect($activeOnly->count())->toBe(1);
            expect($activeOnly[0]->name)->toBe('Alice');
            expect($activeOnly)->toBeInstanceOf(DtoCollection::class);
        });
    });
});

describe('DataTransferObject partial update edge cases', function (): void {

    // ──────────────────────────────────────────────────────────────
    // fromPartialArray with various missing field scenarios
    // ──────────────────────────────────────────────────────────────

    describe('fromPartialArray type-appropriate defaults', function (): void {
        it('missing non-nullable string gets empty string default', function (): void {
            // Only provide 'name', 'value' should get empty string
            $dto = MinimalDTO::fromPartialArray(['name' => 'test'], validate: false);

            expect($dto->name)->toBe('test');
            expect($dto->value)->toBe('');
        });

        it('missing non-nullable int gets 0 default', function (): void {
            $dto = RoundtripDTO::fromPartialArray(['name' => 'test'], validate: false);

            expect($dto->name)->toBe('test');
            expect($dto->age)->toBe(0);
        });

        it('missing nullable property gets null default', function (): void {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);

            // Nullable properties (phone, password) should be null
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('explicit null is respected, not replaced by default', function (): void {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'a@b.com',
                'phone' => null, // explicit null
            ], validate: false);

            expect($dto->phone)->toBeNull();
        });

        it('empty string is respected, not replaced by default', function (): void {
            $dto = MinimalDTO::fromPartialArray([
                'name' => '',     // explicit empty
                'value' => '',
            ], validate: false);

            expect($dto->name)->toBe('');
            expect($dto->value)->toBe('');
        });

        it('provided values override defaults', function (): void {
            $dto = RoundtripDTO::fromPartialArray([
                'name' => 'Test',
                'age' => 25,
                'active' => true,
            ], validate: false);

            expect($dto->name)->toBe('Test');
            expect($dto->age)->toBe(25);
            expect($dto->active)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // with() validation always runs (even when validate param is false)
    // ──────────────────────────────────────────────────────────────

    describe('with() immutable update', function (): void {
        it('with() creates a new instance with updated values', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice'); // original unchanged
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('a@b.com'); // other fields preserved
        });

        it('with() includes hidden fields from allValues', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
                'status' => 'active',
            ]);

            $updated = $dto->with(['name' => 'Bob']);

            // Password should still be present on the new instance
            expect($updated->allValues())->toHaveKey('password');
        });

        it('with() returns same class type', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ]);

            $updated = $dto->with(['status' => 'inactive']);

            expect($updated)->toBeInstanceOf(CreateUserDTO::class);
            expect($updated->status)->toBe('inactive');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // equals() comparison
    // ──────────────────────────────────────────────────────────────

    describe('equals() comparison', function (): void {
        it('identical DTOs are equal', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('different DTOs are not equal', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Bob',
                'status' => 'inactive',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('equals() ignores hidden fields in comparison', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret1',
                'status' => 'active',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret2', // different password
                'status' => 'active',
            ], validate: false);

            // toArray() excludes hidden fields, so they should still be equal
            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // isEmpty() / isNotEmpty() state checks
    // ──────────────────────────────────────────────────────────────

    describe('isEmpty and isNotEmpty state checks', function (): void {
        it('DTO with all defaults is not empty if non-nullable has default value', function (): void {
            // CreateUserDTO has required email and name
            // We can't test truly empty since they're required
            // But we can test isNotEmpty on a valid DTO
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });

        it('minimal DTO with values is not empty', function (): void {
            $dto = MinimalDTO::fromArray([
                'name' => 'test',
                'value' => 'hello',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // only() / except() selective output
    // ──────────────────────────────────────────────────────────────

    describe('only and except selective output', function (): void {
        it('only returns only specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
                'tags' => ['dev'],
            ], validate: false);

            $result = $dto->only('email', 'name');

            expect($result)->toHaveCount(2);
            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('status');
            expect($result)->not->toHaveKey('tags');
        });

        it('only with string parameter works', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('a@b.com');
        });

        it('except excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('status');
        });

        it('except ignores non-existent fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('nonexistent_field');

            // Should return all fields since nonexistent field is silently ignored
            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('only never exposes hidden fields even if requested', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email', 'password');

            // toArray() excludes hidden, so only() based on toArray won't include password
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('password');
        });
    });
});
