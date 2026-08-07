<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Edge-case DTO fixture: all optional properties with defaults and mixed types.
 */
final class AllOptionalDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly string $role = 'guest',
        public readonly int $age = 0,
        public readonly bool $active = false,
        public readonly array $meta = [],
    ) {}
}

describe('AllOptionalDTO edge cases', function () {
    it('creates from empty array using all defaults', function () {
        $dto = AllOptionalDTO::fromArray([], validate: false);
        expect($dto->name)->toBeNull();
        expect($dto->role)->toBe('guest');
        expect($dto->age)->toBe(0);
        expect($dto->active)->toBeFalse();
        expect($dto->meta)->toBe([]);
    });

    it('toArray uses default values', function () {
        $dto = AllOptionalDTO::fromArray([], validate: false);
        $arr = $dto->toArray();
        expect($arr)->toBe([
            'name' => null,
            'role' => 'guest',
            'age' => 0,
            'active' => false,
            'meta' => [],
        ]);
    });

    it('isEmpty returns true for all-default DTO', function () {
        $dto = AllOptionalDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when any property has non-default value', function () {
        $dto = AllOptionalDTO::fromArray(['name' => 'Alice'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('equals returns true for same values', function () {
        $a = AllOptionalDTO::fromArray(['name' => 'Alice', 'role' => 'admin'], validate: false);
        $b = AllOptionalDTO::fromArray(['name' => 'Alice', 'role' => 'admin'], validate: false);
        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different values', function () {
        $a = AllOptionalDTO::fromArray(['name' => 'Alice'], validate: false);
        $b = AllOptionalDTO::fromArray(['name' => 'Bob'], validate: false);
        expect($a->equals($b))->toBeFalse();
    });

    it('with returns a new instance with merged data', function () {
        $original = AllOptionalDTO::fromArray(['name' => 'Alice'], validate: false);
        $updated = $original->with(['role' => 'admin', 'age' => 30]);

        expect($original->role)->toBe('guest');
        expect($updated->role)->toBe('admin');
        expect($updated->age)->toBe(30);
        expect($updated->name)->toBe('Alice'); // preserved
    });

    it('only returns specified fields', function () {
        $dto = AllOptionalDTO::fromArray(['name' => 'Alice', 'role' => 'admin', 'age' => 25], validate: false);
        expect($dto->only(['name', 'role']))->toBe([
            'name' => 'Alice',
            'role' => 'admin',
        ]);
    });

    it('except returns all but specified fields', function () {
        $dto = AllOptionalDTO::fromArray(['name' => 'Alice', 'role' => 'admin', 'age' => 25], validate: false);
        $result = $dto->except('age');
        expect($result)->not->toHaveKey('age');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('role');
    });

    it('fromJson creates DTO from JSON string', function () {
        $json = '{"name":"Alice","role":"admin","active":true}';
        $dto = AllOptionalDTO::fromJson($json, validate: false);
        expect($dto->name)->toBe('Alice');
        expect($dto->role)->toBe('admin');
        expect($dto->active)->toBeTrue();
    });

    it('toJson roundtrips correctly', function () {
        $dto = AllOptionalDTO::fromArray([
            'name' => 'Alice',
            'role' => 'admin',
            'age' => 30,
        ], validate: false);
        $json = $dto->toJson();
        $restored = AllOptionalDTO::fromJson($json, validate: false);
        expect($dto->equals($restored))->toBeTrue();
    });

    it('flushMetadataCache clears cached metadata', function () {
        AllOptionalDTO::flushMetadataCache();
        $dto = AllOptionalDTO::fromArray(['name' => 'Bob'], validate: false);
        expect($dto->name)->toBe('Bob');
        AllOptionalDTO::flushMetadataCache();
    });
});

/**
 * DTO with Hidden + Nullable + Required combination edge cases.
 */
final class HiddenNullableDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public readonly string $name,

        #[Hidden, Nullable]
        public readonly ?string $secret = null,

        #[DefaultValue('user')]
        public readonly string $role = 'user',
    ) {}
}

describe('HiddenNullableDTO', function () {
    it('toArray excludes hidden fields', function () {
        $dto = HiddenNullableDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'my-secret',
        ], validate: false);
        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('secret');
        expect($arr['name'])->toBe('Alice');
    });

    it('allValues includes hidden fields', function () {
        $dto = HiddenNullableDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'my-secret',
        ], validate: false);
        $arr = $dto->allValues();
        expect($arr)->toHaveKey('secret');
        expect($arr['secret'])->toBe('my-secret');
    });

    it('validation fails when required field is missing', function () {
        HiddenNullableDTO::fromArray(['secret' => 'test']);
    })->throws(ValidationException::class);

    it('validation passes with all required fields', function () {
        $dto = HiddenNullableDTO::fromArray([
            'name' => 'Alice',
        ]);
        expect($dto->name)->toBe('Alice');
        expect($dto->secret)->toBeNull();
    });

    it('only on hidden field returns empty', function () {
        $dto = HiddenNullableDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'test',
        ], validate: false);
        // 'only' works on toArray output (hidden excluded), but hidden field
        // should still be accessible via only if it exists in toArray output
        $result = $dto->only('secret');
        expect($result)->toBe([]);
    });

    it('rules generates correct validation array', function () {
        $rules = HiddenNullableDTO::rules();
        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:1');
        expect($rules['name'])->toContain('max:100');
    });
});
