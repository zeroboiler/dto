<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO fromPartialArray edge cases and type safety', function () {
    it('creates DTO from empty partial array using all defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('fromPartialArray only hydrates fields present in data', function () {
        $dto = CreateUserDTO::fromPartialArray(['status' => 'inactive'], validate: false);

        expect($dto->status)->toBe('inactive');
        // email has no default, should get empty value for string type
        expect($dto->email)->toBe('');
    });

    it('fromPartialArray respects explicit null values', function () {
        $dto = CreateUserDTO::fromPartialArray(['phone' => null], validate: false);

        expect($dto->phone)->toBeNull();
    });

    it('fromPartialArray with MapFrom resolves dot-notation source keys', function () {
        $dto = CreateUserDTO::fromPartialArray(['phone_number' => '+1234567890'], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('fromPartialArray applies Cast attribute to provided values', function () {
        $dto = CreateUserDTO::fromPartialArray(['tags' => '["a","b"]'], validate: false);

        expect($dto->tags)->toBe(['a', 'b']);
    });

    it('isEmpty returns true for DTO with all defaults or empty values', function () {
        $dto = EmptyDTO::fromPartialArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when at least one property has a non-empty value', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty considers zero int/float values as non-empty', function () {
        $dto = ProductDTO::fromPartialArray(['price' => '0'], validate: false);

        // stock=0 (int) is non-empty, price='0' (string) is non-empty
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO fromJson strict validation', function () {
    it('rejects sequential JSON arrays', function () {
        expect(fn () => MinimalDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('rejects non-object JSON (string)', function () {
        expect(fn () => MinimalDTO::fromJson('"hello"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('accepts empty JSON array (treated as empty object)', function () {
        $dto = EmptyDTO::fromJson('[]', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('throws DTOException for invalid JSON', function () {
        expect(fn () => MinimalDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });
});

describe('DTO with() immutability and validation', function () {
    it('with() creates a new instance', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $modified = $dto->with(['name' => 'New Name']);

        expect($modified)->not->toBe($dto);
        expect($dto->name)->toBe('Test User');
        expect($modified->name)->toBe('New Name');
    });

    it('equals() returns true for DTOs with same values', function () {
        $data = ['email' => 'test@example.com', 'name' => 'Test'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for DTOs with different values', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('equals() excludes hidden properties from comparison', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 'secret1'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 'secret2'], validate: false);

        // Hidden property 'password' is excluded from toArray(), so equals returns true
        expect($dto1->equals($dto2))->toBeTrue();
    });
});

describe('DTO Hidden attribute serialization', function () {
    it('toArray() excludes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
    });

    it('allValues() includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('only() filters fields from toArray()', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $only = $dto->only('email');

        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
    });

    it('except() excludes fields from toArray()', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $except = $dto->except('email');

        expect($except)->not->toHaveKey('email');
        expect($except)->toHaveKey('name');
    });
});

describe('DTO rules and rulesFor contract', function () {
    it('rules() returns non-empty array for CreateUserDTO', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor() returns same rules as rules() by default', function () {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        expect($rulesFor)->toBe($rules);
    });

    it('rulesFor() returns same rules for any action', function () {
        $create = CreateUserDTO::rulesFor('create');
        $update = CreateUserDTO::rulesFor('update');
        $patch = CreateUserDTO::rulesFor('patch');

        expect($create)->toBe($update);
        expect($update)->toBe($patch);
    });
});

describe('DTO Cast edge cases', function () {
    it('Cast integer converts string numeric to int', function () {
        $dto = CastEdgeCaseDTO::fromArray(['count' => '42'], validate: false);

        expect($dto->count)->toBe(42);
    });

    it('Cast integer converts float to int', function () {
        $dto = CastEdgeCaseDTO::fromArray(['count' => 3.14], validate: false);

        expect($dto->count)->toBe(3);
    });

    it('Cast integer defaults non-numeric to 0', function () {
        $dto = CastEdgeCaseDTO::fromArray(['count' => 'abc'], validate: false);

        expect($dto->count)->toBe(0);
    });

    it('Cast boolean handles string "true"', function () {
        $dto = CastEdgeCaseDTO::fromArray(['active' => 'true'], validate: false);

        expect($dto->active)->toBeTrue();
    });

    it('Cast boolean handles string "false"', function () {
        $dto = CastEdgeCaseDTO::fromArray(['active' => 'false'], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('Cast boolean handles int 0 as false', function () {
        $dto = CastEdgeCaseDTO::fromArray(['active' => 0], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('Cast boolean handles int 1 as true', function () {
        $dto = CastEdgeCaseDTO::fromArray(['active' => 1], validate: false);

        expect($dto->active)->toBeTrue();
    });
});

/**
 * DTO fixture for testing cast edge cases.
 */
final class CastEdgeCaseDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count = 0,

        #[Cast('boolean')]
        public readonly bool $active = false,
    ) {}
}
