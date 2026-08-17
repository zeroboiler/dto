<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('V47 — DTO Core Contract And Infrastructure Audit', function () {
    it('fromArray() with validate:false skips validation', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => '',
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('not-an-email');
        expect($dto->name)->toBe('');
    });

    it('fromArray() with validate:true throws on invalid data', function () {
        expect(fn () => CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => '',
        ], validate: true))->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('fromPartialArray() merges provided fields with defaults', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Alice');
    });

    it('toArray() excludes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues() includes hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('email');
        expect($all)->toHaveKey('name');
        expect($all)->toHaveKey('password');
    });

    it('equals() returns true for identical DTOs', function () {
        $data = ['email' => 'a@b.com', 'name' => 'Alice'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different DTOs', function () {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('only() returns subset of fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $only = $dto->only('email');
        expect($only)->toBe(['email' => 'a@b.com']);
    });

    it('except() returns all fields except specified', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $except = $dto->except('email');
        expect($except)->toHaveKey('name');
        expect($except)->not->toHaveKey('email');
    });

    it('isEmpty() returns true when all fields are empty/null/default', function () {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        // All fields at their empty/default state
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty() is inverse of isEmpty()', function () {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('toJson() produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $json = $dto->toJson();
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveKey('email');
    });

    it('fromJson() roundtrips correctly', function () {
        $data = ['email' => 'a@b.com', 'name' => 'Alice'];
        $dto = CreateUserDTO::fromArray($data, validate: false);
        $json = $dto->toJson();
        $restored = CreateUserDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe($dto->email);
        expect($restored->name)->toBe($dto->name);
    });

    it('fromJson() throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('not-json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson() throws DTOException for sequential arrays', function () {
        expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('with() creates new instance with overrides', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob'], validate: false);

        expect($updated)->not->toBe($dto);
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@b.com');
        // Original unchanged
        expect($dto->name)->toBe('Alice');
    });

    it('rules() returns non-empty array with correct structure', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();

        foreach ($rules as $field => $fieldRules) {
            expect($field)->toBeString();
            expect($fieldRules)->toBeArray();
        }
    });

    it('rulesFor() returns same as rules() by default', function () {
        expect(CreateUserDTO::rulesFor('create'))
            ->toEqual(CreateUserDTO::rules());
    });

    it('DtoCollection::make() creates typed collection', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false),
        ];

        $col = DtoCollection::make($dtoArray);
        expect($col->count())->toBe(2);
        expect($col->isEmpty())->toBeFalse();
    });

    it('DtoCollection::pluck() extracts property values', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false),
        ];

        $col = DtoCollection::make($dtoArray);
        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@b.com', 'b@c.com']);
    });

    it('DtoCollection::filter() returns new collection', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false),
        ];

        $col = DtoCollection::make($dtoArray);
        $filtered = $col->filter(fn ($dto) => $dto->name === 'Alice');

        expect($filtered->count())->toBe(1);
        // Original unchanged
        expect($col->count())->toBe(2);
    });

    it('DtoCollection::append() returns new collection with added item', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $newCol = $col->append($dto2);

        expect($newCol->count())->toBe(2);
        expect($col->count())->toBe(1); // Original unchanged
    });

    it('DtoCollection::first() and last() return correct items', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'Bob'], validate: false),
        ];

        $col = DtoCollection::make($dtoArray);

        expect($col->first()->name)->toBe('Alice');
        expect($col->last()->name)->toBe('Bob');
    });

    it('DtoCollection::contains() checks via callback', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
        ];

        $col = DtoCollection::make($dtoArray);

        expect($col->contains(fn ($dto) => $dto->name === 'Alice'))->toBeTrue();
        expect($col->contains(fn ($dto) => $dto->name === 'Bob'))->toBeFalse();
    });

    it('DtoCollection rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DTOException::invalidCast() includes property name and type', function () {
        $e = DTOException::invalidCast('status', 'integer', 'hello');

        expect($e->getMessage())->toContain('status');
        expect($e->getMessage())->toContain('integer');
        expect((string) $e)->toContain('DTOException');
    });

    it('DTOException::invalidJson() includes property and error', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('jsonSerialize() returns same as toArray()', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('metadata cache flush clears all classes', function () {
        DataTransferObject::flushMetadataCache();
        // Resolve for one class
        CreateUserDTO::rules();

        // Flush everything
        DataTransferObject::flushMetadataCache();

        // Should re-resolve without error
        expect(CreateUserDTO::rules())->toBeArray();
    });

    it('metadata cache flush targets specific class', function () {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        expect(CreateUserDTO::rules())->toBeArray();
    });
});
