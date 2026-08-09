<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;
use ZeroBoiler\DTO\DtoCollection;

describe('DTO: fromJson edge cases', function (): void {
    it('creates DTO from valid JSON string', function (): void {
        $json = '{"email":"test@example.com","name":"Alice"}';

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('throws DTOException for invalid JSON', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('not json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function (): void {
        expect(fn (): mixed => CreateUserDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('applies defaults from JSON', function (): void {
        $json = '{"email":"test@example.com","name":"Bob"}';

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
    });
});

describe('DTO: fromPartialArray edge cases', function (): void {
    it('hydrates only provided fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validate: false);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fills missing required fields with type-appropriate empty values', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        // Missing string fields should get empty string
        expect($dto->email)->toBe('');
        expect($dto->name)->toBe('');
    });

    it('preserves defaults for optional fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });
});

describe('DTO: toArray / only / except', function (): void {
    it('only() accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('only() accepts multiple string keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveKeys(['email', 'name']);
    });

    it('only() silently ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('nonexistent');
    });

    it('except() excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('allValues() includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });
});

describe('DTO: isEmpty / isNotEmpty', function (): void {
    it('EmptyDTO with null fields is empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with at least one non-empty field is not empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('zero int is not considered empty', function (): void {
        // ProductDTO has stock (int) — value 0 is valid and non-empty
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'stock' => 0,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO: nested DTO hydration', function (): void {
    it('hydrates nested DTO from array', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
            ],
        ], validate: false);

        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->street)->toBe('123 Main St');
    });

    it('serializes nested DTO recursively', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
            ],
        ], validate: false);

        $array = $dto->toArray();

        expect($array['shippingAddress'])->toBeArray();
        expect($array['shippingAddress']['street'])->toBe('123 Main St');
    });

    it('deep nested DTO hydration works', function (): void {
        $dto = DeepNestedDTO::fromArray([
            'id' => '1',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'zipCode' => '12345',
            ],
            'label' => 'Root',
        ], validate: false);

        expect($dto->address)->toBeInstanceOf(AddressDTO::class);
        expect($dto->address->street)->toBe('123 Main St');
    });
});

describe('DTO: DtoCollection operations', function (): void {
    it('create collection with make()', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect($col->count())->toBe(2);
    });

    it('pluck extracts single field', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect($col->pluck('email'))->toBe(['a@b.com', 'c@d.com']);
    });

    it('pluckKey builds key-value map', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('filter returns new collection', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $filtered = $col->filter(fn ($d) => $d->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('append returns new immutable collection', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $newCol = $col->append($dto2);

        expect($col->count())->toBe(1); // original unchanged
        expect($newCol->count())->toBe(2);
    });

    it('merge combines two collections', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
    });

    it('first() and last() work correctly', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect($col->first()->email)->toBe('a@b.com');
        expect($col->last()->email)->toBe('c@d.com');
    });

    it('jsonSerialize returns array of arrays', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $col = DtoCollection::make([$dto]);

        $json = json_encode($col);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('email');
    });
});

describe('DTO: type casting', function (): void {
    it('casts string to integer', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '29.99',
            'stock' => '42',
        ], validate: false);

        expect($dto->stock)->toBe(42);
    });

    it('casts JSON string to array via Cast attribute', function (): void {
        $dto = ArrayCastDTO::fromArray([
            'name' => 'Test',
            'tags' => '["laravel","php"]',
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });
});

describe('DTO: with() immutability', function (): void {
    it('original DTO is unchanged after with()', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($dto->email)->toBe($updated->email);
    });

    it('with() preserves hidden field exclusion in equals()', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'different',
        ], validate: false);

        // equals() compares toArray() — password is hidden
        expect($dto1->equals($dto2))->toBeTrue();
    });
});
