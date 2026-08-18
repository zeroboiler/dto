<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('fromJson() edge cases', function () {
    it('creates DTO from valid JSON object', function () {
        $dto = MinimalDTO::fromJson('{"name": "test", "value": "123"}');
        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('123');
    });

    it('creates DTO from empty JSON object for optional-only DTO', function () {
        $dto = EmptyDTO::fromJson('{}');
        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('creates DTO from empty JSON object {}', function () {
        $dto = EmptyDTO::fromJson('{}');
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('creates DTO from empty array JSON []', function () {
        $dto = EmptyDTO::fromJson('[]');
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('rejects non-empty sequential JSON array', function () {
        expect(fn () => EmptyDTO::fromJson('["a", "b"]'))
            ->toThrow(DTOException::class, 'sequential array');
    });

    it('rejects invalid JSON', function () {
        expect(fn () => MinimalDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('rejects JSON string (non-object)', function () {
        expect(fn () => MinimalDTO::fromJson('"hello"'))
            ->toThrow(DTOException::class, 'JSON object');
    });

    it('rejects JSON number (non-object)', function () {
        expect(fn () => MinimalDTO::fromJson('42'))
            ->toThrow(DTOException::class, 'JSON object');
    });

    it('rejects JSON boolean (non-object)', function () {
        expect(fn () => MinimalDTO::fromJson('true'))
            ->toThrow(DTOException::class, 'JSON object');
    });

    it('rejects JSON null (non-object)', function () {
        expect(fn () => MinimalDTO::fromJson('null'))
            ->toThrow(DTOException::class, 'JSON object');
    });

    it('creates DTO with validation disabled', function () {
        // name is required but we skip validation
        $dto = CreateUserDTO::fromJson('{"email": "a@b.com", "name": "Test"}', validate: false);
        expect($dto->email)->toBe('a@b.com');
    });

    it('handles JSON with extra fields gracefully', function () {
        $dto = MinimalDTO::fromJson('{"name": "test", "value": "v", "extra": "ignored"}');
        expect($dto->name)->toBe('test');
        expect($dto->value)->toBe('v');
    });

    it('handles JSON with whitespace', function () {
        $json = '  {  "name"  :  "test"  ,  "value"  :  "v"  }  ';
        $dto = MinimalDTO::fromJson($json);
        expect($dto->name)->toBe('test');
    });

    it('result is serializable back to JSON via toJson()', function () {
        $dto = MinimalDTO::fromJson('{"name": "test", "value": "123"}');
        $json = $dto->toJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBe(['name' => 'test', 'value' => '123']);
    });
});

describe('fromArray() validation with hidden fields', function () {
    it('excludes hidden fields from toArray()', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
    });

    it('includes hidden fields in allValues()', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('MapFrom maps source key to property', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('DefaultValue applies when key is absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('Cast applies array cast on strings', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'tags' => '[]',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });
});

describe('DTO equality and state', function () {
    it('equals returns true for identical DTOs', function () {
        $a = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different DTOs', function () {
        $a = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'baz'], validate: false);
        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty returns true when all properties are empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when a property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('only() returns selected fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => 'v'], validate: false);
        $result = $dto->only('name');
        expect($result)->toBe(['name' => 'test']);
    });

    it('except() returns all except specified fields', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => 'v'], validate: false);
        $result = $dto->except('value');
        expect($result)->toBe(['name' => 'test']);
    });
});
