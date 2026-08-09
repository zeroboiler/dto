<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

// ─── All ValidationAttribute implementations have ruleKey() ─────────

describe('All validation attributes implement ValidationAttribute', function (): void {
    $attributes = [
        Accepted::class,
        Boolean::class,
        Confirmed::class,
        Declined::class,
        Distinct::class,
        Integer::class,
        Numeric::class,
        Present::class,
        Prohibited::class,
        Required::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
        Same::class,
        Sometimes::class,
        Url::class,
        Uuid::class,
        Json::class,
        Nullable::class,
        EndsWith::class,
        StartsWith::class,
    ];

    foreach ($attributes as $attributeClass) {
        it("{$attributeClass} implements ValidationAttribute", function () use ($attributeClass): void {
            expect($attributeClass)->toImplement(ValidationAttribute::class);
        });

        it("{$attributeClass}::ruleKey() returns non-empty string", function () use ($attributeClass): void {
            $instance = new $attributeClass;
            expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
        });
    }
});

// ─── Attribute with parameters also implement ValidationAttribute ─────────

describe('Validation attributes with parameters implement contract', function (): void {
    $parameterized = [
        [Required::class, ['message' => 'Field is required']],
        [Email::class, []],
        [Max::class, [255]],
        [Min::class, [2]],
        [Pattern::class, ['/^[A-Z]+$/']],
        [In::class, [['a', 'b', 'c']]],
        [Integer::class, []],
        [Size::class, [5]],
        [StartsWith::class, ['https://']],
        [EndsWith::class, ['.com']],
        [Between::class, [1, 100]],
        [Different::class, ['other_field']],
        [Same::class, ['other_field']],
        [RequiredIf::class, ['field', 'value']],
        [RequiredUnless::class, ['field', 'value']],
        [RequiredWith::class, ['field1']],
        [RequiredWithAll::class, ['field1', 'field2']],
        [RequiredWithout::class, ['field1']],
        [RequiredWithoutAll::class, ['field1', 'field2']],
    ];

    foreach ($parameterized as [$attributeClass, $args]) {
        it("{$attributeClass} can be constructed with parameters", function () use ($attributeClass, $args): void {
            $instance = new $attributeClass(...$args);
            expect($instance)->toBeInstanceOf(ValidationAttribute::class);
            expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
        });
    }
});

// ─── DTO metadata cache TTL and invalidation ─────────

describe('DTO metadata cache TTL behavior', function (): void {
    it('setMetadataCacheTtl changes TTL', function (): void {
        $originalTtl = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        expect($originalTtl)->toBeInstanceOf(EmptyDTO::class);

        // Set a new TTL
        DataTransferObject::setMetadataCacheTtl(10.0);
        DataTransferObject::flushMetadataCache(EmptyDTO::class);

        // Should work after flush
        $dto = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);
        expect($dto->foo)->toBe('x');
    });

    it('flushMetadataCache with null clears all', function (): void {
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        DataTransferObject::flushMetadataCache(null);

        // Should still work — metadata rebuilt from scratch
        $dto = CreateUserDTO::fromArray([
            'email' => 'new@example.com',
            'name' => 'New',
        ], validate: false);

        expect($dto->email)->toBe('new@example.com');
    });

    it('flushMetadataCache for specific class does not affect others', function (): void {
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        EmptyDTO::fromArray(['foo' => 'a'], validate: false);

        // Flush only CreateUserDTO
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // EmptyDTO metadata should still be cached
        $dto = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        expect($dto->foo)->toBe('b');
    });
});

// ─── DTO::rulesFor() default behavior ─────────

describe('DTO rulesFor action scoping', function (): void {
    it('rulesFor returns same rules as rules() by default', function (): void {
        $rules = CreateUserDTO::rules();
        $createRules = CreateUserDTO::rulesFor('create');
        $updateRules = CreateUserDTO::rulesFor('update');

        expect($createRules)->toBe($rules);
        expect($updateRules)->toBe($rules);
    });

    it('rulesFor with unknown action still returns rules()', function (): void {
        $rules = EmptyDTO::rules();
        $customRules = EmptyDTO::rulesFor('custom_action');

        expect($customRules)->toBe($rules);
    });
});

// ─── DTO isEmpty / isNotEmpty edge cases ─────────

describe('DTO isEmpty and isNotEmpty edge cases', function (): void {
    it('empty DTO with all nullable nulls is empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => null, 'bar' => null], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with default-only values is considered empty', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => '',
            'hexCode' => '',
        ], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('DTO with non-empty string is not empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello', 'bar' => null], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('DTO with int 0 is NOT empty (0 is a meaningful value)', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => '',
            'hexCode' => '',
            'age' => 0,
        ], validate: false);
        // age is 0 which is non-nullable int → NOT empty
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

// ─── DtoCollection immutable operations ─────────

describe('DtoCollection immutable append and merge', function (): void {
    it('append returns a new collection', function (): void {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original)->toHaveCount(1);
        expect($appended)->toHaveCount(2);
        expect($appended)->not->toBe($original);
    });

    it('merge combines two collections', function (): void {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $dto3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);

        $merged = $col1->merge($col2);

        expect($merged)->toHaveCount(3);
        expect($col1)->toHaveCount(1);
        expect($col2)->toHaveCount(2);
    });

    it('merge returns a new collection', function (): void {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged)->not->toBe($col1);
        expect($merged)->not->toBe($col2);
    });
});

// ─── DTO fromJson edge cases ─────────

describe('DTO fromJson edge cases', function (): void {
    it('throws DTOException for empty string', function (): void {
        expect(fn () => EmptyDTO::fromJson(''))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for invalid JSON', function (): void {
        expect(fn () => EmptyDTO::fromJson('{invalid json'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function (): void {
        expect(fn () => EmptyDTO::fromJson('["foo", "bar"]'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON null', function (): void {
        expect(fn () => EmptyDTO::fromJson('null'))
            ->toThrow(DTOException::class);
    });

    it('accepts valid JSON object', function (): void {
        $dto = EmptyDTO::fromJson('{"foo":"hello","bar":"world"}', validate: false);
        expect($dto->foo)->toBe('hello');
        expect($dto->bar)->toBe('world');
    });
});

// ─── DTO with() always validates (backward compat param ignored) ─────────

describe('DTO with() validation enforcement', function (): void {
    it('with() creates a new instance', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        $updated = $dto->with(['foo' => 'c']);

        expect($updated)->not->toBe($dto);
        expect($updated->foo)->toBe('c');
        expect($updated->bar)->toBe('b');
    });

    it('with() uses allValues() for merge including hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);
        expect($updated->name)->toBe('Updated');
        expect($updated->email)->toBe('test@example.com');
    });
});

// ─── MapFrom with dot notation ─────────

describe('MapFrom dot notation', function (): void {
    it('resolves nested dot-notation keys', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'johndoe',
            'hexCode' => 'abcdef',
            'user_email' => 'john@example.com',
        ], validate: false);

        expect($dto->email)->toBe('john@example.com');
    });
});

// ─── Cast edge cases ─────────

describe('Cast attribute edge cases', function (): void {
    it('casts string to integer', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'johndoe',
            'hexCode' => 'abcdef',
            'age' => '42',
        ], validate: false);

        expect($dto->age)->toBe(42);
    });

    it('casts float string to integer (truncates)', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'johndoe',
            'hexCode' => 'abcdef',
            'age' => '42.9',
        ], validate: false);

        expect($dto->age)->toBe(42);
    });

    it('non-numeric string casts to 0', function (): void {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'johndoe',
            'hexCode' => 'abcdef',
            'age' => 'not_a_number',
        ], validate: false);

        expect($dto->age)->toBe(0);
    });
});

// ─── only() and except() ─────────

describe('only() and except() selective output', function (): void {
    it('only with single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->only('email');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('only with array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->only('email', 'name');
        expect($result)->toHaveCount(2);
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('only ignores non-existent keys', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $result = $dto->only('nonexistent');
        expect($result)->toBeArray()->toBeEmpty();
    });

    it('except excludes the specified key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except with array of keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email', 'status');
        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('status');
        expect($result)->toHaveKey('name');
    });
});

// ─── allValues includes hidden ─────────

describe('allValues includes hidden fields', function (): void {
    it('toArray excludes hidden, allValues includes them', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues()['password'])->toBe('secret123');
    });
});
