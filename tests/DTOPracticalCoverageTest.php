<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('fromJson() error paths', function (): void {
    it('throws DTOException on invalid JSON syntax', function (): void {
        CreateUserDTO::fromJson('{invalid json}');
    })->throws(DTOException::class);

    it('throws DTOException on sequential JSON array', function (): void {
        CreateUserDTO::fromJson('["email@test.com", "Test"]');
    })->throws(DTOException::class);

    it('throws DTOException with context in message', function (): void {
        try {
            CreateUserDTO::fromJson('{bad}');
            expect(true)->toBeFalse();
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('JSON');
        }
    });
});

describe('only() selective output', function (): void {
    it('accepts a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');
        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('accepts multiple string keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
            'tags' => ['php'],
        ], validate: false);

        $result = $dto->only('email', 'name');
        expect($result)->toBe(['email' => 'a@b.com', 'name' => 'Alice']);
    });

    it('ignores non-existent keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');
        expect($result)->toBe(['email' => 'a@b.com']);
    });

    it('excludes hidden fields even when explicitly requested', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        // only() delegates to toArray() which excludes hidden
        $result = $dto->only('email', 'password');
        expect($result)->toBe(['email' => 'a@b.com']);
    });
});

describe('except() selective output', function (): void {
    it('excludes specified keys', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('accepts a single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $dto->except('email');
        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

describe('allValues() includes hidden fields', function (): void {
    it('returns hidden field that toArray excludes', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues()['password'])->toBe('secret123');
    });
});

describe('isEmpty() and isNotEmpty()', function (): void {
    it('returns true for DTO with all defaults/nulls', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        // email and name are non-empty, so isEmpty should be false
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('EmptyDTO with no data is empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('EmptyDTO with data is not empty', function (): void {
        $dto = EmptyDTO::fromArray([
            'foo' => 'value',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('jsonSerialize() contract', function (): void {
    it('returns toArray() output', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('validateArray() standalone', function (): void {
    it('returns validated data for valid input', function (): void {
        $result = CreateUserDTO::validateArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('throws ValidationException for invalid input', function (): void {
        CreateUserDTO::validateArray([
            'email' => 'not-an-email',
        ]);
    })->throws(ValidationException::class);
});

describe('rulesFor() defaults to rules()', function (): void {
    it('returns same rules as rules() by default', function (): void {
        expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        expect(CreateUserDTO::rulesFor('delete'))->toBe(CreateUserDTO::rules());
    });
});

describe('DtoCollection push mutability', function (): void {
    it('push mutates in place and returns self', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 10], validate: false);

        $col = new DtoCollection([$dto1]);
        $result = $col->push($dto2);

        expect($result)->toBe($col); // same instance
        expect($col->count())->toBe(2);
        expect($col->last())->toBe($dto2);
    });
});

describe('DtoCollection append immutability', function (): void {
    it('append returns new collection without mutating original', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 10], validate: false);

        $original = new DtoCollection([$dto1]);
        $new = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($new->count())->toBe(2);
        expect($new->last())->toBe($dto2);
    });
});

describe('DtoCollection merge immutability', function (): void {
    it('merge returns new collection combining both', function (): void {
        $dto1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $dto2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 10], validate: false);
        $dto3 = ProductDTO::fromArray(['name' => 'C', 'price' => '30', 'stock' => 15], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });
});

describe('DtoCollection filter and map', function (): void {
    it('filter returns new collection with matching items', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 0], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $inStock = $col->filter(fn (ProductDTO $p): bool => $p->stock > 0);

        expect($inStock->count())->toBe(1);
        expect($inStock->first()->name)->toBe('A');
    });

    it('map returns plain array of results', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 10], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $names = $col->map(fn (ProductDTO $p): string => $p->name);

        expect($names)->toBe(['A', 'B']);
    });
});
