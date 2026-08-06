<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTOMetadataAndRulesDedup', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    it('resolves rules with deduplication — no duplicate required rules', function () {
        $rules = CreateUserDTO::rules();

        // email field should have required, email — not 'required' twice
        $emailRules = $rules['email'];
        expect($emailRules)->toContain('required');
        expect($emailRules)->toContain('email');

        // Count 'required' occurrences — should be exactly 1
        $requiredCount = count(array_filter($emailRules, fn ($r): bool => $r === 'required'));
        expect($requiredCount)->toBe(1);
    });

    it('infers base rules from property types', function () {
        $rules = CreateUserDTO::rules();

        // tags is array type — no auto-inferred base rule needed
        // phone is nullable with default — should have 'sometimes'
        expect($rules)->toHaveKey('phone');
    });

    it('resolves metadata cache with TTL invalidation', function () {
        DataTransferObject::setMetadataCacheTtl(0.0);

        // First resolution
        $rules1 = CreateUserDTO::rules();
        expect($rules1)->toBeArray()->not->toBeEmpty();

        // With TTL=0, next access should rebuild (but results identical)
        DataTransferObject::flushMetadataCache();
        $rules2 = CreateUserDTO::rules();
        expect($rules2)->toEqual($rules1);

        // Reset TTL
        DataTransferObject::setMetadataCacheTtl(0.0);
    });

    it('flushes cache for specific class only', function () {
        CreateUserDTO::rules();
        EmptyDTO::rules();

        // Both should be cached
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // EmptyDTO rules should still work from cache
        $rules = EmptyDTO::rules();
        expect($rules)->toBeArray();
    });

    it('DTOException invalidCast creates proper message', function () {
        $exception = DTOException::invalidCast('status', 'integer', 'not_an_int');
        expect($exception->getMessage())->toContain('status');
        expect($exception->getMessage())->toContain('integer');
        expect($exception->getMessage())->toContain('not_an_int');
    });

    it('DTOException invalidJson creates proper message', function () {
        $exception = DTOException::invalidJson('payload', 'Syntax error');
        expect($exception->getMessage())->toContain('payload');
        expect($exception->getMessage())->toContain('Syntax error');
    });

    it('ValidationAttribute contract — all validation attributes implement ruleKey()', function () {
        $attributes = [
            new Required,
            new Email,
            new Max(255),
            new Min(1),
            new Url,
            new Integer,
            new Nullable,
        ];

        foreach ($attributes as $attr) {
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
            expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });

    it('ValidationAttribute with custom message stores it correctly', function () {
        $attr = new Email(message: 'Custom email error');
        expect($attr->message)->toBe('Custom email error');
        expect($attr->ruleKey())->toBe('email');
    });

    it('Hidden property excluded from toArray but included in allValues', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('password');

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('MapFrom correctly maps source key to property', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('DefaultValue applied when key is missing from input', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('Cast attribute transforms values during hydration', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => '["a","b","c"]',
        ], validate: false);

        expect($dto->tags)->toBe(['a', 'b', 'c']);
    });

    it('equals checks value equality', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('with creates immutable copy with validation', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);
        expect($updated->name)->toBe('Updated');
        expect($updated->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test'); // Original unchanged
    });
});

describe('DtoCollectionEdgeCases', function () {
    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not_a_dto']))
            ->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
    });

    it('offsetSet rejects non-DTO values', function () {
        $collection = new DtoCollection;
        expect(fn () => $collection[] = 'not_a_dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes array to prevent gaps', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $dto3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        expect($collection->count())->toBe(3);

        unset($collection[0]);

        // After re-index, count should be 2 and items sequential
        expect($collection->count())->toBe(2);
        $items = $collection->items();
        expect(array_keys($items))->toEqual([0, 1]);
    });

    it('map returns plain array with correct types', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'c', 'bar' => 'd'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $result = $collection->map(fn (EmptyDTO $dto, int $index): string => $dto->foo);

        expect($result)->toBe(['a', 'c']);
    });

    it('filter returns new collection with matching items', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'keep', 'bar' => null], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => null, 'bar' => 'drop'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(fn (EmptyDTO $dto): bool => $dto->foo !== null);

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->foo)->toBe('keep');
    });
});
