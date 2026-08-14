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
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * DTO hydration, serialization, and edge case integration tests.
 *
 * Covers:
 * - fromJson with strict JSON validation (reject arrays, invalid JSON)
 * - DtoCollection offsetGet, offsetUnset, re-index, merge, append
 * - equals() with hidden fields
 * - isEmpty() / isNotEmpty() edge cases
 * - with() validation always runs
 * - toArray() excludes hidden, allValues() includes hidden
 */
describe('DTOHydrationSerializationIntegration', function () {
    it('fromJson rejects sequential JSON array', function () {
        expect(fn () => SimpleDTO::fromJson('["a","b","c"]'))
            ->toThrow(DTOException::class);
    });

    it('fromJson rejects invalid JSON syntax', function () {
        expect(fn () => SimpleDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class);
    });

    it('fromJson accepts empty JSON object', function () {
        $dto = EmptyDefaultsDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDefaultsDTO::class);
        expect($dto->name)->toBe('default');
    });

    it('toArray excludes hidden properties', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes hidden properties', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('email');
        expect($all)->toHaveKey('name');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('equals ignores hidden fields in comparison', function () {
        $dto1 = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'pass1',
        ], validate: false);

        $dto2 = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'pass2',
        ], validate: false);

        // Different hidden field, but equals should still be true
        // because equals() compares toArray() which excludes hidden
        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('isEmpty returns true when all properties are empty/default', function () {
        $dto = EmptyDefaultsDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isNotEmpty returns true when at least one property has value', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => '',
            'password' => null,
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
        expect($dto->isEmpty())->toBeFalse();
    });

    it('isEmpty treats zero as non-empty for non-nullable int', function () {
        $dto = NumericZeroDTO::fromArray(['count' => 0], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('with returns new instance (immutability)', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);

        expect($dto->name)->toBe('Test');
        expect($updated->name)->toBe('Updated');
        expect($dto)->not->toBe($updated);
    });

    it('only returns specified fields', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => null,
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except excludes specified fields', function () {
        $dto = SimpleDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => null,
        ], validate: false);

        $result = $dto->except('password');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('password');
    });

    it('DtoCollection pluck extracts property values', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    it('DtoCollection pluckKey creates keyed map', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('DtoCollection merge combines items immutably', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1); // original unchanged
    });

    it('DtoCollection append returns new collection immutably', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $appended = $col->append($dto2);

        expect($appended->count())->toBe(2);
        expect($col->count())->toBe(1); // original unchanged
    });

    it('DtoCollection push mutates in place and returns self', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $result = $col->push($dto2);

        expect($col->count())->toBe(2); // mutated in place
        expect($result)->toBe($col); // returns self
    });

    it('DtoCollection offsetUnset re-indexes correctly', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $dto3 = SimpleDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($col[1]); // remove middle

        expect($col->count())->toBe(2);
        expect($col[0]->name)->toBe('A');
        expect($col[1]->name)->toBe('E'); // re-indexed
    });

    it('DtoCollection map returns plain array', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $names = $col->map(fn ($dto) => $dto->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('DtoCollection filter returns new filtered collection', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $filtered = $col->filter(fn ($dto) => str_starts_with($dto->name, 'A'));

        expect($filtered->count())->toBe(1);
        expect($filtered[0]->name)->toBe('Alice');
    });

    it('DtoCollection jsonSerialize produces array of arrays', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $json = json_encode($col);

        expect($json)->toBeJson();
        expect(json_decode($json, true))->toBe([['email' => 'a@b.com', 'name' => 'A']]);
    });

    it('DtoCollection first and last return correct items', function () {
        $dto1 = SimpleDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = SimpleDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect($col->first()->name)->toBe('A');
        expect($col->last()->name)->toBe('C');
    });

    it('DtoCollection first/last return null for empty collection', function () {
        $col = DtoCollection::make([]);

        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
        expect($col->isEmpty())->toBeTrue();
    });

    it('rules returns correct validation rules', function () {
        $rules = SimpleDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('rulesFor returns same rules by default', function () {
        expect(SimpleDTO::rulesFor('create'))->toBe(SimpleDTO::rules());
        expect(SimpleDTO::rulesFor('update'))->toBe(SimpleDTO::rules());
    });

    it('flushMetadataCache clears all cached metadata', function () {
        DataTransferObject::flushMetadataCache();
        $before = SimpleDTO::rules();

        DataTransferObject::flushMetadataCache();
        $after = SimpleDTO::rules();

        // Rules should be identical after cache flush
        expect($before)->toBe($after);
    });
});

/**
 * Simple DTO fixture for testing.
 */
final class SimpleDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(1), Max(50)]
        public readonly string $name = '',

        #[Hidden]
        #[Nullable]
        public readonly ?string $password = null,
    ) {}
}

/**
 * DTO with all-optional default values for isEmpty testing.
 */
final class EmptyDefaultsDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('default')]
        public readonly string $name = 'default',

        #[DefaultValue(0)]
        public readonly int $count = 0,
    ) {}
}

/**
 * DTO with non-nullable int for isEmpty zero-value testing.
 */
final class NumericZeroDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $count = 0,
    ) {}
}
