<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('allValues includes hidden fields', function (): void {
    it('returns hidden fields that toArray excludes', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $public = $dto->toArray();
        $all = $dto->allValues();

        expect($public)->not->toHaveKey('password');
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('allValues and toArray share non-hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $public = $dto->toArray();
        $all = $dto->allValues();

        expect($public['email'])->toBe($all['email']);
        expect($public['name'])->toBe($all['name']);
    });
});

describe('isEmpty and isNotEmpty edge cases', function (): void {
    it('empty DTO with all nulls is empty', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with a non-empty string is not empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('int 0 is NOT empty (valid meaningful value)', function (): void {
        $dto = ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '0',
        ], validate: false);

        // stock defaults to 0, which should NOT make the DTO "empty"
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('fromJson parsing', function (): void {
    it('creates DTO from valid JSON object', function (): void {
        $json = '{"name":"Test","value":"hello"}';
        $dto = MinimalDTO::fromJson($json, validate: false);

        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('hello');
    });

    it('creates DTO from empty JSON object', function (): void {
        $json = '{}';
        $dto = EmptyDTO::fromJson($json, validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('accepts empty array as valid JSON object', function (): void {
        $json = '[]';
        $dto = EmptyDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('throws DTOException for non-object JSON arrays', function (): void {
        expect(fn () => MinimalDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON scalar', function (): void {
        expect(fn () => MinimalDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON number', function (): void {
        expect(fn () => MinimalDTO::fromJson('42', validate: false))
            ->toThrow(DTOException::class);
    });
});

describe('DTOException named constructors', function (): void {
    it('creates invalidCast exception', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
        expect($e->getMessage())->toContain('not_a_number');
    });

    it('creates invalidJson exception', function (): void {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class and message', function (): void {
        $e = DTOException::invalidJson('field', 'bad json');

        $str = (string) $e;

        expect($str)->toBe(DTOException::class.': Cannot decode JSON for property [field]: bad json');
    });
});

describe('DtoCollection core operations', function (): void {
    it('creates empty collection', function (): void {
        $collection = DtoCollection::make();

        expect($collection->isEmpty())->toBeTrue();
        expect($collection->count())->toBe(0);
    });

    it('throws on non-DTO items', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('push adds and returns same instance', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection;
        $result = $collection->push($dto);

        expect($result)->toBe($collection);
        expect($collection->count())->toBe(1);
    });

    it('append returns new instance', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection;
        $new = $collection->append($dto);

        expect($new)->not->toBe($collection);
        expect($collection->isEmpty())->toBeTrue();
        expect($new->count())->toBe(1);
    });

    it('filter returns new filtered collection', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $d3 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $collection = new DtoCollection([$d1, $d2, $d3]);

        $filtered = $collection->filter(function (DataTransferObject $dto): bool {
            return $dto->foo === 'a';
        });

        expect($filtered)->not->toBe($collection);
        expect($filtered->count())->toBe(2);
    });

    it('merge combines two collections', function (): void {
        $d1 = EmptyDTO::fromArray([], validate: false);
        $d2 = EmptyDTO::fromArray([], validate: false);
        $c1 = new DtoCollection([$d1]);
        $c2 = new DtoCollection([$d2]);

        $merged = $c1->merge($c2);

        expect($merged->count())->toBe(2);
        expect($c1->count())->toBe(1);
        expect($c2->count())->toBe(1);
    });

    it('first and last return correct items', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        expect($collection->first())->toBe($d1);
        expect($collection->last())->toBe($d2);
    });

    it('first and last return null on empty', function (): void {
        $collection = new DtoCollection;

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('offsetUnset re-indexes collection', function (): void {
        $d1 = EmptyDTO::fromArray([], validate: false);
        $d2 = EmptyDTO::fromArray([], validate: false);
        $d3 = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection([$d1, $d2, $d3]);

        unset($collection[0]);

        expect($collection->count())->toBe(2);
        expect($collection[0])->toBe($d2);
        expect($collection[1])->toBe($d3);
    });

    it('map returns array of results', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $emails = $collection->map(fn (DataTransferObject $dto) => $dto->email);

        expect($emails)->toBe(['a@b.com', 'b@c.com']);
    });

    it('jsonSerialize produces array of arrays', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $collection = new DtoCollection([$dto]);

        $json = json_encode($collection);
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect(count($decoded))->toBe(1);
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->not->toHaveKey('password');
    });
});

describe('DtoCollection toArrayBy and toDictionary', function (): void {
    it('toArrayBy re-keys by property value', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'A', 'price' => '10', 'stock' => 5], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'B', 'price' => '20', 'stock' => 3], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toHaveKey('A');
        expect($keyed)->toHaveKey('B');
    });

    it('toDictionary maps one property to another', function (): void {
        $d1 = ProductDTO::fromArray(['name' => 'Widget', 'price' => '10', 'stock' => 5], validate: false);
        $d2 = ProductDTO::fromArray(['name' => 'Gadget', 'price' => '20', 'stock' => 3], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $map = $collection->toDictionary('name', 'price');

        expect($map['Widget'])->toBe('10');
        expect($map['Gadget'])->toBe('20');
    });
});

describe('Nested DTO hydration', function (): void {
    it('hydrates nested DTO from array', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '62701',
            ],
            'items' => [
                ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
            ],
            'rawTotal' => 100,
        ], validate: false);

        expect($dto->orderNumber)->toBe('ORD-001');
        expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
        expect($dto->shippingAddress->city)->toBe('Springfield');
        expect($dto->items)->toBeArray();
        expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
        expect($dto->items[0]->productName)->toBe('Widget');
    });

    it('serializes nested DTOs to arrays', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
            'rawTotal' => '50',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['shippingAddress'])->toBeArray();
        expect($array['shippingAddress']['city'])->toBe('Springfield');
    });

    it('allValues includes nested DTOs', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ], validate: false);

        $all = $dto->allValues();

        expect($all['shippingAddress'])->toBeArray();
        expect($all['shippingAddress']['street'])->toBe('123 Main St');
    });
});

describe('Metadata cache management', function (): void {
    it('flushMetadataCache clears specific class', function (): void {
        // Trigger metadata resolution
        ProductDTO::rules();

        // Flush only ProductDTO
        ProductDTO::flushMetadataCache(ProductDTO::class);

        // Rules should still work (re-resolves on next call)
        $rules = ProductDTO::rules();
        expect($rules)->toHaveKey('name');
    });

    it('flushMetadataCache clears all with null', function (): void {
        ProductDTO::rules();
        CreateUserDTO::rules();

        DataTransferObject::flushMetadataCache();

        // Should re-resolve correctly
        expect(ProductDTO::rules())->toHaveKey('name');
        expect(CreateUserDTO::rules())->toHaveKey('email');
    });
});
