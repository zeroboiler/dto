<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
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
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO Edge Cases — fromJson', function () {
    it('creates DTO from valid JSON object', function () {
        $dto = MinimalDTO::fromJson('{"name":"test","value":"hello"}', validate: false);

        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('hello');
    });

    it('throws DTOException for invalid JSON syntax', function () {
        MinimalDTO::fromJson('{invalid json}', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON array (sequential)', function () {
        MinimalDTO::fromJson('["name","value"]', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for empty string', function () {
        MinimalDTO::fromJson('', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON null', function () {
        MinimalDTO::fromJson('null', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON boolean', function () {
        MinimalDTO::fromJson('true', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException for JSON number', function () {
        MinimalDTO::fromJson('42', validate: false);
    })->throws(DTOException::class);

    it('works with JSON that has extra fields (ignored)', function () {
        $dto = MinimalDTO::fromJson('{"name":"test","value":"hello","extra":"ignored"}', validate: false);

        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('hello');
    });
});

describe('DTO Edge Cases — fromPartialArray', function () {
    it('hydrates only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['email' => 'test@example.com'], validatePresent: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->status)->toBe('active'); // default value
    });

    it('respects explicit null values', function () {
        $dto = CreateUserDTO::fromPartialArray(['email' => 'test@example.com', 'phone' => null], validatePresent: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->phone)->toBeNull();
    });

    it('works with empty data array', function () {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->status)->toBe('active'); // default
        expect($dto->tags)->toEqual([]); // default
    });

    it('overrides defaults with provided values', function () {
        $dto = CreateUserDTO::fromPartialArray(['status' => 'inactive'], validatePresent: false);

        expect($dto->status)->toBe('inactive');
    });
});

describe('DTO Edge Cases — with() immutability', function () {
    it('creates new instance without modifying original', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $updated = $original->with(['status' => 'inactive']);

        expect($original->status)->toBe('active');
        expect($updated->status)->toBe('inactive');
        expect($original->email)->toBe($updated->email);
    });

    it('preserves all original values', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+1234567890',
        ], validate: false);

        $updated = $original->with(['status' => 'banned']);

        expect($updated->email)->toBe('test@example.com');
        expect($updated->name)->toBe('Test User');
        expect($updated->phone)->toBe('+1234567890');
        expect($updated->status)->toBe('banned');
    });
});

describe('DTO Edge Cases — isEmpty / isNotEmpty', function () {
    it('isEmpty returns true when all properties are empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns false when all properties are empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when any property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty returns false when boolean false is set', function () {
        // EmptyDTO doesn't have bool fields, but the logic should handle it
        $dto = EmptyDTO::fromArray(['foo' => '0'], validate: false);

        // '0' is a non-empty string value (it's not 0 as int)
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO Edge Cases — equals()', function () {
    it('returns true for identical DTOs', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for different DTOs', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'A',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'B',
        ], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('ignores hidden fields in comparison', function () {
        $a = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret1',
        ], validate: false);
        $b = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret2',
        ], validate: false);

        // equals uses toArray() which excludes hidden fields
        expect($a->equals($b))->toBeTrue();
    });
});

describe('DTO Edge Cases — only / except', function () {
    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('only with multiple keys returns all specified', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveCount(2);
    });

    it('only ignores non-existent keys silently', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1);
    });

    it('except excludes hidden fields too', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        // password is already hidden in toArray(), except should still work
        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except ignores non-existent keys silently', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveKey('email');
    });
});

describe('DTO Edge Cases — toArray / allValues / toJson', function () {
    it('toArray excludes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
    });

    it('allValues includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('toJson produces valid JSON string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        expect(json_decode($json, true))->toHaveKey('email');
    });

    it('toJson respects JSON options', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("\n");
    });
});

describe('DTO Edge Cases — MapFrom with dot notation', function () {
    it('maps nested dot-notation keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('handles missing mapped key gracefully', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });
});

describe('DTO Edge Cases — Cast attribute', function () {
    it('casts string to array from JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => '["laravel","php"]',
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });

    it('casts empty string to empty array', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });

    it('passes through array as-is', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => ['a', 'b'],
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b']);
    });
});

describe('DTO Edge Cases — DefaultValue', function () {
    it('applies default when key is missing', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('overrides default when key is present with empty string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => '',
        ], validate: false);

        expect($dto->status)->toBe('');
    });

    it('overrides default when key is present with null', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => null,
        ], validate: false);

        // status is string type, not nullable — this would fail validation
        // but without validation it should pass through
        expect($dto->status)->toBe(null);
    });
});

describe('DTO Edge Cases — DtoCollection', function () {
    it('creates empty collection', function () {
        $collection = DtoCollection::make([]);

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    it('filters items correctly', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $filtered = $collection->filter(fn (CreateUserDTO $dto): bool => str_starts_with($dto->name, 'A'));

        expect($filtered->count())->toBe(1);
    });

    it('plucks single property', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $emails = $collection->pluck('email');

        expect($emails)->toEqual(['a@test.com', 'b@test.com']);
    });

    it('offsetUnset re-indexes array', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        unset($collection[0]);

        expect($collection->count())->toBe(1);
        expect($collection[0]->email)->toBe('b@test.com'); // re-indexed
    });

    it('map returns plain array of results', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($dtoArray);
        $names = $collection->map(fn (CreateUserDTO $dto, int $index): string => $dto->name);

        expect($names)->toEqual(['Alice', 'Bob']);
    });

    it('push returns same collection instance', function () {
        $collection = DtoCollection::make([]);
        $dto = CreateUserDTO::fromArray(['email' => 'test@test.com', 'name' => 'Test'], validate: false);

        $result = $collection->push($dto);

        expect($result)->toBe($collection); // same instance
        expect($result->count())->toBe(1);
    });

    it('rejects non-DTO items in constructor', function () {
        new DtoCollection(['not_a_dto']);
    })->throws(\InvalidArgumentException::class);
});

describe('DTO Edge Cases — rulesFor action scoping', function () {
    it('returns default rules for unknown action', function () {
        $createRules = ActionScopedDTO::rulesFor('create');
        $unknownRules = ActionScopedDTO::rulesFor('unknown_action');

        expect($createRules)->toEqual($unknownRules);
    });

    it('create rules have required email and password', function () {
        $rules = ActionScopedDTO::rulesFor('create');

        expect($rules['email'])->toContain('required');
        expect($rules['password'])->toContain('required');
    });

    it('update rules have sometimes instead of required', function () {
        $rules = ActionScopedDTO::rulesFor('update');

        expect($rules['email'])->toContain('sometimes');
        expect($rules['email'])->not->toContain('required');
        expect($rules['password'])->toContain('sometimes');
    });
});

describe('DTO Edge Cases — metadata cache', function () {
    it('flushMetadataCache clears class-specific cache', function () {
        // Resolve metadata to populate cache
        CreateUserDTO::fromArray(['email' => 't@t.com', 'name' => 'T'], validate: false);

        // Flush specific class
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // Re-resolve should work without error
        $dto = CreateUserDTO::fromArray(['email' => 't2@t.com', 'name' => 'T2'], validate: false);

        expect($dto->email)->toBe('t2@t.com');
    });

    it('flushMetadataCache with null clears everything', function () {
        CreateUserDTO::fromArray(['email' => 't@t.com', 'name' => 'T'], validate: false);

        CreateUserDTO::flushMetadataCache(null);

        $dto = CreateUserDTO::fromArray(['email' => 't2@t.com', 'name' => 'T2'], validate: false);

        expect($dto->email)->toBe('t2@t.com');
    });

    it('setMetadataCacheTtl accepts zero', function () {
        CreateUserDTO::setMetadataCacheTtl(0.0);

        // Should not throw
        $dto = CreateUserDTO::fromArray(['email' => 't@t.com', 'name' => 'T'], validate: false);

        expect($dto->email)->toBe('t@t.com');

        // Reset
        CreateUserDTO::setMetadataCacheTtl(0.0);
    });
});

describe('DTO Edge Cases — DTOCast Eloquent cast', function () {
    it('get returns null for null database value', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('set returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('set serializes DTO to JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $cast->set(new \stdClass, 'data', $dto, []);

        expect($result)->toBeJson();
        $decoded = json_decode((string) $result, true);
        expect($decoded['email'])->toBe('test@example.com');
    });

    it('set rejects non-DTO non-array values', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $cast->set(new \stdClass, 'data', 42, []);
    })->throws(\InvalidArgumentException::class);

    it('serialize returns array representation', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $cast->serialize(new \stdClass, 'data', $dto, []);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('serialize returns null for null', function () {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->serialize(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('get hydrates from JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $json = json_encode(['email' => 'test@example.com', 'name' => 'Test']);

        $result = $cast->get(new \stdClass, 'data', $json, []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
        expect($result->email)->toBe('test@example.com');
    });

    it('get hydrates from array', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $data = ['email' => 'test@example.com', 'name' => 'Test'];

        $result = $cast->get(new \stdClass, 'data', $data, []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
        expect($result->email)->toBe('test@example.com');
    });
});

describe('DTO Edge Cases — DTOException', function () {
    it('invalidCast includes property name and type', function () {
        $exception = DTOException::invalidCast('age', 'int', 'not_a_number');

        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('int');
    });

    it('invalidJson includes property name and error', function () {
        $exception = DTOException::invalidJson('metadata', 'Syntax error');

        expect($exception->getMessage())->toContain('metadata');
        expect($exception->getMessage())->toContain('Syntax error');
    });
});
