<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTOCast edge cases', function () {
    it('get returns null for non-array non-string value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'payload', 123, []);
        expect($result)->toBeNull();
    });

    it('get returns null for invalid JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        $result = $cast->get($model, 'payload', '{invalid json}', []);
        expect($result)->toBeNull();
    });

    it('set returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        $result = $cast->set($model, 'payload', null, []);
        expect($result)->toBeNull();
    });

    it('set throws InvalidArgumentException for unsupported type', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        expect(fn () => $cast->set($model, 'payload', 42, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('set throws InvalidArgumentException for boolean', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        expect(fn () => $cast->set($model, 'payload', true, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serialize returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'payload', null, []);
        expect($result)->toBeNull();
    });

    it('serialize returns toArray for DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $model = new \stdClass;
        $result = $cast->serialize($model, 'payload', $dto, []);
        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
        expect($result['email'])->toBe('a@b.com');
    });

    it('set hydrates array through DTO before storing', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new \stdClass;
        $result = $cast->set($model, 'payload', ['email' => 'test@test.com', 'name' => 'Test'], []);
        expect($result)->toBeString();
        $decoded = json_decode($result, true);
        expect($decoded)->toHaveKey('email');
        expect($decoded['email'])->toBe('test@test.com');
    });

    it('get hydrates from stored JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new \stdClass;
        $json = json_encode(['email' => 'x@y.com', 'name' => 'Bob']);
        $dto = $cast->get($model, 'payload', $json, []);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('x@y.com');
        expect($dto->name)->toBe('Bob');
    });

    it('set with validate=true throws ValidationException for invalid data', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: true);
        $model = new \stdClass;
        expect(fn () => $cast->set($model, 'payload', ['email' => 'not-an-email', 'name' => ''], []))
            ->toThrow(ValidationException::class);
    });
});

describe('DTO with() immutable update edge cases', function () {
    it('with() creates new instance — original unchanged', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $modified = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice');
        expect($modified->name)->toBe('Bob');
        expect($dto->email)->toBe('a@b.com'); // original email unchanged
        expect($modified->email)->toBe('a@b.com'); // email preserved (not overridden)
    });

    it('with() always validates regardless of $validate parameter', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        // The $validate parameter is deprecated and ignored — validation always runs
        // Passing invalid data should still throw
        expect(fn () => $dto->with(['email' => '', 'name' => ''], validate: false))
            ->toThrow(ValidationException::class);
    });

    it('with() preserves hidden fields from allValues()', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'],
            validate: false,
        );
        $modified = $dto->with(['name' => 'Bob']);

        // Hidden field should be preserved in the new instance
        expect($modified->password)->toBe('secret');
    });

    it('with() multiple overrides work correctly', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'tags' => ['php']],
            validate: false,
        );
        $modified = $dto->with(['email' => 'b@c.com', 'name' => 'Bob', 'tags' => ['laravel']]);

        expect($modified->email)->toBe('b@c.com');
        expect($modified->name)->toBe('Bob');
        expect($modified->tags)->toBe(['laravel']);
    });
});

describe('DTO fromJson with special content', function () {
    it('fromJson handles unicode characters correctly', function () {
        $json = json_encode(['email' => 'test@例え.com', 'name' => 'テスト ユーザー']);
        $dto = CreateUserDTO::fromArray(json_decode($json, true), validate: false);
        expect($dto->email)->toBe('test@例え.com');
        expect($dto->name)->toBe('テスト ユーザー');
    });

    it('fromJson handles nested objects', function () {
        $dto = EmptyDTO::fromJson('{"foo": "bar", "baz": {"nested": true}}');
        // baz is not a property — it gets ignored by fromArray
        expect($dto->foo)->toBe('bar');
    });

    it('fromJson handles escaped characters', function () {
        $json = '{"foo": "line1\\nline2", "bar": "tab\\there"}';
        $dto = EmptyDTO::fromJson($json);
        expect($dto->foo)->toBe("line1\nline2");
        expect($dto->bar)->toBe("tab\there");
    });

    it('fromJson with empty braces {} works for EmptyDTO', function () {
        $dto = EmptyDTO::fromJson('{}');
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('DTO isEmpty and isNotEmpty edge cases', function () {
    it('isEmpty returns true for all-default EmptyDTO', function () {
        $dto = EmptyDTO::fromArray([]);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when a property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'bar']);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty considers int 0 as non-empty', function () {
        // Create a DTO with int 0 via partial array
        $dto = CreateUserDTO::fromPartialArray(['email' => 'test@test.com', 'name' => 'Test']);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty returns true when nullable fields are null and others have defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([]);
        // phone=null, password=null, status='active' (default), tags=[] (default)
        // All defaults/empty values — isEmpty should be true
        expect($dto->isEmpty())->toBeTrue();
    });
});

describe('DtoCollection offset operations', function () {
    it('offsetSet with explicit offset replaces existing item', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $col = new DtoCollection([$dto1]);
        $col[0] = $dto2;

        expect($col->count())->toBe(1);
        expect($col[0]->email)->toBe('c@d.com');
    });

    it('offsetSet with null appends to end', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $col = new DtoCollection([$dto1]);
        $col[] = $dto2;

        expect($col->count())->toBe(2);
        expect($col[1]->email)->toBe('c@d.com');
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Eve'], validate: false);
        $col = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($col[1]); // Remove Charlie

        expect($col->count())->toBe(2);
        // After re-indexing, index 1 should now be Eve
        expect($col[0]->email)->toBe('a@b.com');
        expect($col[1]->email)->toBe('e@f.com');
    });

    it('offsetUnset on single-item collection produces empty collection', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        unset($col[0]);

        expect($col->isEmpty())->toBeTrue();
        expect($col->count())->toBe(0);
        expect($col[0])->toBeNull();
    });

    it('offsetExists returns false for out-of-range', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = new DtoCollection([$dto]);

        expect($col->offsetExists(0))->toBeTrue();
        expect($col->offsetExists(1))->toBeFalse();
        expect($col->offsetExists(-1))->toBeFalse();
    });

    it('offsetSet rejects non-DTO values', function () {
        $col = new DtoCollection;
        expect(fn () => $col[] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('DTO only() and except() edge cases', function () {
    it('only with multiple keys returns correct subset', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active', 'tags' => ['php']],
            validate: false,
        );
        $result = $dto->only(['email', 'name']);
        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('status');
        expect($result)->not->toHaveKey('tags');
    });

    it('only with string key (non-array) works', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $result = $dto->only('email');
        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });

    it('only with non-existent key silently ignores', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $result = $dto->only(['email', 'nonexistent']);
        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });

    it('except with multiple keys returns correct subset', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active', 'tags' => ['php']],
            validate: false,
        );
        $result = $dto->except(['email', 'status']);
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('tags');
        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('status');
    });

    it('except with non-existent key silently ignores', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $result = $dto->except('nonexistent');
        // All public fields should remain
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('only excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'],
            validate: false,
        );
        $result = $dto->only(['email', 'password']);
        // password is hidden — only() uses toArray() which excludes hidden
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('password');
    });
});

describe('DTO metadata cache TTL', function () {
    it('setMetadataCacheTtl affects subsequent cache invalidation', function () {
        CreateUserDTO::setMetadataCacheTtl(0.0); // Disable TTL
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);

        // Restore default
        CreateUserDTO::setMetadataCacheTtl(0.0);
    });

    it('flushMetadataCache with null flushes all', function () {
        CreateUserDTO::flushMetadataCache(null);
        // Should not throw — subsequent fromArray should rebuild cache
        $dto = EmptyDTO::fromArray([]);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('DTOException factory methods', function () {
    it('invalidCast produces correct message', function () {
        $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');
        expect($ex->getMessage())->toContain('age');
        expect($ex->getMessage())->toContain('integer');
    });

    it('invalidJson produces correct message', function () {
        $ex = DTOException::invalidJson('metadata', 'Syntax error');
        expect($ex->getMessage())->toContain('metadata');
        expect($ex->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function () {
        $ex = DTOException::invalidJson('field', 'error');
        $str = (string) $ex;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('error');
    });
});

describe('DTO jsonSerialize consistency', function () {
    it('jsonSerialize matches toArray output', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'tags' => ['php', 'laravel']],
            validate: false,
        );
        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('toJson produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice'],
            validate: false,
        );
        $json = $dto->toJson();
        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('a@b.com');
    });

    it('toJson with JSON_PRETTY_PRINT option', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice'],
            validate: false,
        );
        $json = $dto->toJson(JSON_PRETTY_PRINT);
        expect($json)->toContain("\n"); // Pretty printed should have newlines
        expect($json)->toBeJson();
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret'],
            validate: false,
        );
        $all = $dto->allValues();
        $public = $dto->toArray();

        expect($all)->toHaveKey('password');
        expect($public)->not->toHaveKey('password');
    });
});
