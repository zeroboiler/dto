<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('fromPartialArray with DefaultValue attribute', function () {
    it('uses DefaultValue attribute when field is absent from partial data', function () {
        $dto = TestPartialWithDefaultDTO::fromPartialArray(['name' => 'John']);

        expect($dto->name)->toBe('John');
        expect($dto->role)->toBe('user'); // from DefaultValue attribute
    });

    it('DefaultValue attribute overrides type-inferred empty value', function () {
        $dto = TestPartialWithDefaultDTO::fromPartialArray(['name' => 'Jane']);

        expect($dto->role)->toBe('user');
    });

    it('explicit null overrides DefaultValue attribute', function () {
        $dto = TestPartialWithDefaultDTO::fromPartialArray(['name' => 'Bob', 'role' => null]);

        expect($dto->role)->toBeNull();
    });

    it('explicit value overrides DefaultValue attribute', function () {
        $dto = TestPartialWithDefaultDTO::fromPartialArray(['name' => 'Alice', 'role' => 'admin']);

        expect($dto->role)->toBe('admin');
    });

    it('all fields absent uses all DefaultValue attributes', function () {
        $dto = TestPartialWithDefaultDTO::fromPartialArray([]);

        expect($dto->name)->toBe('guest');
        expect($dto->role)->toBe('user');
    });
});

describe('fromPartialArray edge cases', function () {
    it('preserves zero values from partial data', function () {
        $dto = TestPartialWithZeroDTO::fromPartialArray(['count' => 0]);

        expect($dto->count)->toBe(0);
    });

    it('preserves empty string from partial data', function () {
        $dto = TestPartialWithZeroDTO::fromPartialArray(['label' => '']);

        expect($dto->label)->toBe('');
    });

    it('preserves false from partial data', function () {
        $dto = TestPartialWithZeroDTO::fromPartialArray(['active' => false]);

        expect($dto->active)->toBeFalse();
    });

    it('treats zero as non-empty in isEmpty check after partial', function () {
        $dto = TestPartialWithZeroDTO::fromPartialArray(['count' => 0]);

        // count = 0 is non-empty (valid numeric value), label defaults to empty
        expect($dto->isEmpty())->toBeTrue(); // only label is empty string
    });

    it('treats non-zero as non-empty in isEmpty check', function () {
        $dto = TestPartialWithZeroDTO::fromPartialArray(['count' => 5]);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('DtoCollection immutable operations', function () {
    it('append returns new collection without mutating original', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'secret123'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => 'secret456'], validate: false);

        $original = DtoCollection::make([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended->first()->name)->toBe('Alice');
    });

    it('merge combines two collections without mutation', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@test.com', 'password' => 'pass'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@test.com', 'password' => 'pass'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['name' => 'C', 'email' => 'c@test.com', 'password' => 'pass'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter returns new collection with matching items', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'pass'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => 'pass'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $filtered = $collection->filter(fn ($dto) => $dto->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
        expect($collection->count())->toBe(2); // original unchanged
    });

    it('map returns plain array of results', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'pass'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => 'pass'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->map(fn ($dto) => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('push mutates and returns same instance for chaining', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@test.com', 'password' => 'pass'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@test.com', 'password' => 'pass'], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2); // mutated in-place
        expect($result)->toBe($collection); // same instance
    });
});

describe('DtoCollection serialization', function () {
    it('jsonSerialize returns array of arrays', function () {
        $dto = CreateUserDTO::fromArray(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'pass'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $serialized = $collection->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized)->toHaveCount(1);
        expect($serialized[0])->toBeArray();
        expect($serialized[0])->toHaveKey('name');
        expect($serialized[0]['name'])->toBe('Test');
    });

    it('toArray excludes hidden fields from nested DTOs', function () {
        $dto = CreateUserDTO::fromArray(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'secret'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $arr = $collection->toArray();

        expect($arr[0])->not->toHaveKey('password');
    });

    it('allValues includes hidden fields from nested DTOs', function () {
        $dto = CreateUserDTO::fromArray(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'secret'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
    });
});

describe('DtoCollection access edge cases', function () {
    it('offsetUnset re-indexes array', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@t.com', 'password' => 'p'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@t.com', 'password' => 'p'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['name' => 'C', 'email' => 'c@t.com', 'password' => 'p'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($collection[0]); // offsetUnset

        expect($collection->count())->toBe(2);
        expect($collection->first()->name)->toBe('B');
    });

    it('last returns last item correctly after re-index', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@t.com', 'password' => 'p'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@t.com', 'password' => 'p'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        unset($collection[0]);

        expect($collection->last()->name)->toBe('B');
    });

    it('pluck extracts single field from all DTOs', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'a@t.com', 'password' => 'p'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'b@t.com', 'password' => 'p'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('pluckKey creates associative array from two fields', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@t.com', 'password' => 'p'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['name' => 'Bob', 'email' => 'bob@t.com', 'password' => 'p'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'alice@t.com' => 'Alice',
            'bob@t.com' => 'Bob',
        ]);
    });

    it('pluckKey without valueField uses full toArray', function () {
        $dto1 = CreateUserDTO::fromArray(['name' => 'Alice', 'email' => 'alice@t.com', 'password' => 'p'], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $map = $collection->pluckKey('email');

        expect($map)->toHaveKey('alice@t.com');
        expect($map['alice@t.com'])->toBeArray();
    });
});

// ─── Fixtures for this test file ────────────────────────────────────────────

/** @internal Test fixture — partial DTO with DefaultValue attribute */
class TestPartialWithDefaultDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('guest')]
        public readonly string $name,

        #[DefaultValue('user')]
        public readonly string $role,
    ) {}
}

/** @internal Test fixture — partial DTO with zero/false/empty edge cases */
class TestPartialWithZeroDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue(0)]
        public readonly int $count,

        #[DefaultValue('')]
        public readonly string $label,

        #[DefaultValue(false)]
        public readonly bool $active,
    ) {}
}

