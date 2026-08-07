<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;

describe('DataTransferObject — fromJson edge cases', function (): void {
    it('creates from valid JSON string', function (): void {
        $json = '{"email":"test@example.com","name":"Doruk"}';

        $dto = MinimalDTO::fromJson($json, validate: false);

        expect($dto->name)->toBe('test@example.com');
        expect($dto->value)->toBe('Doruk');
    });

    it('throws DTOException on invalid JSON', function (): void {
        MinimalDTO::fromJson('{invalid json}', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on sequential JSON array', function (): void {
        MinimalDTO::fromJson('["a","b"]', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on JSON null', function (): void {
        MinimalDTO::fromJson('null', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on JSON boolean', function (): void {
        MinimalDTO::fromJson('true', validate: false);
    })->throws(DTOException::class);

    it('throws DTOException on empty string', function (): void {
        MinimalDTO::fromJson('', validate: false);
    })->throws(DTOException::class);
});

describe('DataTransferObject — fromPartialArray', function (): void {
    it('hydrates only provided fields, uses defaults for rest', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
        ], validatePresent: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->status)->toBe('active'); // default
    });

    it('works with empty data array', function (): void {
        $dto = EmptyDTO::fromPartialArray([], validatePresent: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('nullable fields remain null when not provided', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validatePresent: false);

        expect($dto->phone)->toBeNull();
        expect($dto->password)->toBeNull();
    });

    it('provides type-appropriate empty values for non-nullable fields', function (): void {
        $dto = ScalarConstraintsDTO::fromPartialArray([], validatePresent: false);

        // string → ''
        expect($dto->name)->toBe('');
        // int → 0
        expect($dto->score)->toBe(0);
    });
});

describe('DataTransferObject — selective output', function (): void {
    it('only() returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
            'password' => 'secret',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveKeys(['email', 'name']);
        expect($result)->not->toHaveKey('status');
        expect($result)->not->toHaveKey('password');
    });

    it('only() accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result['email'])->toBe('test@example.com');
    });

    it('only() ignores non-existent keys silently', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveCount(1);
    });

    it('except() excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('except() accepts single string key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $result = $dto->except('name');

        expect($result)->toHaveCount(1);
        expect($result['email'])->toBe('test@example.com');
    });
});

describe('DataTransferObject — isEmpty / isNotEmpty', function (): void {
    it('isEmpty returns true when all properties are empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true when at least one property has value', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isNotEmpty is the negation of isEmpty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isNotEmpty())->toBeFalse();
    });
});

describe('DataTransferObject — with() immutable update', function (): void {
    it('creates new instance with merged data', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $updated = $dto->with(['status' => 'inactive', 'tags' => ['php']]);

        expect($updated->email)->toBe('test@example.com');
        expect($updated->name)->toBe('Doruk');
        expect($updated->status)->toBe('inactive');
        expect($updated->tags)->toBe(['php']);
    });

    it('original instance is unchanged', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => 'active',
        ], validate: false);

        $dto->with(['status' => 'inactive']);

        expect($dto->status)->toBe('active');
    });
});

describe('DataTransferObject — allValues includes hidden fields', function (): void {
    it('allValues includes Hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('toArray excludes Hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $visible = $dto->toArray();

        expect($visible)->not->toHaveKey('password');
    });
});

describe('DataTransferObject — jsonSerialize', function (): void {
    it('returns the same as toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DataTransferObject — rulesFor action scoping', function (): void {
    it('returns default rules for unknown action', function (): void {
        $rules = ActionScopedDTO::rulesFor('unknown');

        expect($rules)->toBe(ActionScopedDTO::rules());
    });

    it('returns create rules (same as base)', function (): void {
        $rules = ActionScopedDTO::rulesFor('create');

        expect($rules['email'])->toContain('required');
        expect($rules['password'])->toContain('required');
    });

    it('returns update rules (relaxed)', function (): void {
        $rules = ActionScopedDTO::rulesFor('update');

        expect($rules['email'])->toContain('sometimes');
        expect($rules['password'])->toContain('sometimes');
    });
});

describe('DataTransferObject — validateArray standalone', function (): void {
    it('returns validated data', function (): void {
        $validated = CreateUserDTO::validateArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        expect($validated['email'])->toBe('test@example.com');
        expect($validated['name'])->toBe('Doruk');
    });

    it('throws ValidationException on invalid data', function (): void {
        CreateUserDTO::validateArray([
            'email' => 'not-an-email',
            'name' => 'D',
        ]);
    })->throws(\Illuminate\Validation\ValidationException::class);
});

describe('DataTransferObject — nested DTO hydration', function (): void {
    it('auto-hydrates nested DTO from array', function (): void {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
                'zipCode' => '34000',
            ],
        ], validate: false);

        expect($order->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($order->shippingAddress->street)->toBe('123 Main St');
        expect($order->shippingAddress->city)->toBe('Istanbul');
        expect($order->shippingAddress->zipCode)->toBe('34000');
    });

    it('serializes nested DTOs recursively', function (): void {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '456 Oak Ave',
                'city' => 'Ankara',
            ],
        ], validate: false);

        $array = $order->toArray();

        expect($array['shippingAddress'])->toBeArray();
        expect($array['shippingAddress']['street'])->toBe('456 Oak Ave');
    });
});

describe('DataTransferObject — nested array DTOs', function (): void {
    it('auto-hydrates array of DTOs', function (): void {
        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => [
                'street' => '789 Elm',
                'city' => 'Izmir',
            ],
            'items' => [
                ['productName' => 'Widget A', 'price' => 9.99, 'quantity' => 2],
                ['productName' => 'Widget B', 'price' => 14.99, 'quantity' => 1],
            ],
        ], validate: false);

        expect($order->items)->toBeArray();
        expect($order->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($order->items[0]->productName)->toBe('Widget A');
        expect($order->items[1]->productName)->toBe('Widget B');
    });

    it('throws on non-array element in nested array', function (): void {
        OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => ['street' => 'X', 'city' => 'Y'],
            'items' => ['not-an-array'],
        ], validate: false);
    })->throws(\InvalidArgumentException::class);
});

describe('DataTransferObject — MapFrom with dot notation', function (): void {
    it('maps from different source key', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });
});

describe('DataTransferObject — Cast types', function (): void {
    it('casts string to integer via type inference', function (): void {
        $dto = ScalarConstraintsDTO::fromArray([
            'name' => 'Test',
            'score' => '42',
        ], validate: false);

        expect($dto->score)->toBe(42);
    });

    it('respects boolean property type', function (): void {
        $dto = ScalarConstraintsDTO::fromArray([
            'name' => 'Test',
            'is_admin' => true,
        ], validate: false);

        expect($dto->is_admin)->toBe(true);
    });
});

describe('DtoCollection — type safety', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        new DtoCollection(['not-a-dto']);
    })->throws(\InvalidArgumentException::class);

    it('accepts DTO instances', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe($dto);
    });

    it('push appends and returns self (fluent)', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'Charlie',
        ], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $result = $collection->push($dto2);

        expect($result)->toBe($collection); // fluent
        expect($collection->count())->toBe(2);
    });

    it('offsetUnset re-indexes', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        $collection->offsetUnset(0);

        // Should be re-indexed: [0 => $dto2, 1 => $dto3]
        expect($collection->count())->toBe(2);
        expect($collection[0]->email)->toBe('b@c.com');
        expect($collection[1]->email)->toBe('c@d.com');
    });

    it('offsetSet with null appends', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'X'], validate: false);
        $collection = DtoCollection::make();
        $collection[] = $dto;

        expect($collection->count())->toBe(1);
    });

    it('offsetSet with key replaces', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection[0]->email)->toBe('c@d.com');
    });

    it('offsetGet returns null for missing key', function (): void {
        $collection = DtoCollection::make();

        expect($collection[99])->toBeNull();
    });

    it('map returns plain array with correct types', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $d): string => $d->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('filter returns new collection', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(fn (CreateUserDTO $d): bool => str_starts_with($d->name, 'A'));

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('pluckKey builds associative map', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');

        expect($map['a@b.com'])->toBe('Alice');
        expect($map['c@d.com'])->toBe('Charlie');
    });

    it('isEmpty and isNotEmpty', function (): void {
        $collection = DtoCollection::make();

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->isNotEmpty())->toBeFalse();
    });

    it('jsonSerialize returns array of arrays', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $collection = new DtoCollection([$dto]);

        $json = json_encode($collection);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('email');
    });

    it('items() returns raw DTO instances', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $collection = new DtoCollection([$dto]);

        expect($collection->items()[0])->toBe($dto);
    });

    it('allValues includes hidden fields in collection serialization', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
    });
});

describe('DataTransferObject — equals edge cases', function (): void {
    it('equal DTOs with all same values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'status' => 'active'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'status' => 'active'], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('different DTOs with different values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Test'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('different DTOs with different hidden values', function (): void {
        $a = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);
        $b = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'y'], validate: false);

        // equals() uses toArray() which excludes hidden — so they're equal
        expect($a->equals($b))->toBeTrue();
    });
});

describe('DataTransferObject — metadata cache', function (): void {
    it('flushMetadataCache clears class-specific cache', function (): void {
        // Access metadata to populate cache
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();

        // Flush and re-access should still work
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);
        $rules2 = CreateUserDTO::rules();

        expect($rules2)->toBe($rules);
    });

    it('flushMetadataCache with no argument clears all', function (): void {
        CreateUserDTO::rules();
        EmptyDTO::rules();

        CreateUserDTO::flushMetadataCache();

        // Should still work — just clears cache
        expect(CreateUserDTO::rules())->toBeArray();
        expect(EmptyDTO::rules())->toBeArray();
    });
});

describe('DataTransferObject — DTOException factory methods', function (): void {
    it('invalidCast creates exception with property info', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson creates exception with JSON error', function (): void {
        $e = DTOException::invalidJson('metadata', 'Syntax error');

        expect($e->getMessage())->toContain('metadata');
        expect($e->getMessage())->toContain('Syntax error');
    });
});

describe('DataTransferObject — fromArray with explicit null values respected', function (): void {
    it('respects explicit null over default', function (): void {
        // phone has no default, is nullable — explicit null should be null
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone' => null,
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });

    it('respects explicit empty string over default', function (): void {
        // status has default 'active' — explicit '' should become ''
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => '',
        ], validate: false);

        expect($dto->status)->toBe('');
    });

    it('respects explicit 0 over default', function (): void {
        // tags has default [] — explicit array should be kept
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'tags' => ['laravel'],
        ], validate: false);

        expect($dto->tags)->toBe(['laravel']);
    });
});
