<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DataTransferObject::rulesFor() — action scoping', function (): void {
    it('returns same as rules() by default', function (): void {
        $rules = CreateUserDTO::rules();
        $rulesForCreate = CreateUserDTO::rulesFor('create');
        $rulesForUpdate = CreateUserDTO::rulesFor('update');

        expect($rules)->toBe($rulesForCreate);
        expect($rules)->toBe($rulesForUpdate);
    });

    it('returns empty rules for EmptyDTO', function (): void {
        $rules = EmptyDTO::rulesFor('any');
        expect($rules)->toBeArray();
    });
});

describe('DtoCollection::pluckKey() with null valueField', function (): void {
    it('returns full toArray() when valueField is null', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'x'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'b', 'bar' => 'y'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $map = $collection->pluckKey('foo');

        expect($map)->toBe([
            'a' => ['foo' => 'a', 'bar' => 'x'],
            'b' => ['foo' => 'b', 'bar' => 'y'],
        ]);
    });

    it('uses last value when key duplicates', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'same', 'bar' => 'first'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'same', 'bar' => 'second'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $map = $collection->pluckKey('foo', 'bar');

        expect($map)->toBe([
            'same' => 'second', // Last value wins
        ]);
    });
});

describe('DataTransferObject::jsonSerialize contract', function (): void {
    it('implements JsonSerializable correctly', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto)->toBeInstanceOf(\JsonSerializable::class);
        $serialized = $dto->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized)->toHaveKey('email');
        expect($serialized)->not->toHaveKey('password');
    });

    it('jsonSerialize equals toArray output', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DataTransferObject equality edge cases', function (): void {
    it('two different DTO classes are never equal', function (): void {
        $empty = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $user = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

        // They have the same toArray() keys but different classes
        // equals() only compares toArray() output, so they could be equal
        // if the arrays happen to match — let's verify the behavior
        $result = $empty->equals($user);
        // empty has 'foo' => 'a', user has 'email' => 'a@b.com'
        expect($result)->toBeFalse();
    });

    it('equals is reflexive', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false);
        expect($dto->equals($dto))->toBeTrue();
    });
});

describe('DtoCollection iteration', function (): void {
    it('is iterable via foreach', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        $values = [];
        foreach ($collection as $dto) {
            $values[] = $dto->foo;
        }

        expect($values)->toBe(['a', 'b']);
    });

    it('supports array access via offsetGet', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $collection = new DtoCollection([$d1, $d2]);

        expect($collection[0]->foo)->toBe('a');
        expect($collection[1]->foo)->toBe('b');
        expect($collection[99])->toBeNull(); // Non-existent returns null
    });

    it('supports offsetExists', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $collection = new DtoCollection([$d1]);

        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeFalse();
    });

    it('supports offsetSet at specific index', function (): void {
        $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $collection = new DtoCollection([$d1]);

        $collection[5] = $d2;

        expect($collection[5]->foo)->toBe('b');
        expect($collection->count())->toBe(2);
    });
});

describe('DataTransferObject::fromArray with explicit null', function (): void {
    it('respects explicit null for optional fields (#678)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone' => null, // Explicit null — should NOT fall back to default
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });
});

describe('DataTransferObject metadata cache', function (): void {
    it('caches metadata across multiple creations', function (): void {
        // Create two DTOs — second should use cached metadata
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['bar' => 'b'], validate: false);

        expect($dto1->foo)->toBe('a');
        expect($dto2->bar)->toBe('b');
    });

    it('flushMetadataCache works per-class', function (): void {
        EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        DataTransferObject::flushMetadataCache(EmptyDTO::class);

        // Should still work after flush
        $dto = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        expect($dto->foo)->toBe('b');
    });
});

describe('Nested DTO hydration', function (): void {
    it('hydrates nested DTO from array', function (): void {
        $item = OrderItemDTO::fromArray([
            'productName' => 'Widget',
            'price' => 9.99,
        ], validate: false);

        expect($item)->toBeInstanceOf(OrderItemDTO::class);
        expect($item->productName)->toBe('Widget');
        expect($item->price)->toBe(9.99);
        expect($item->quantity)->toBe(1); // Default
    });

    it('serializes nested DTO to array', function (): void {
        $item = OrderItemDTO::fromArray([
            'productName' => 'Widget',
            'price' => 9.99,
            'quantity' => 3,
        ], validate: false);

        $array = $item->toArray();

        expect($array)->toBe([
            'productName' => 'Widget',
            'price' => 9.99,
            'quantity' => 3,
        ]);
    });

    it('roundtrips nested DTO through json', function (): void {
        $item = OrderItemDTO::fromArray([
            'productName' => 'Widget',
            'price' => 9.99,
        ], validate: false);

        $json = $item->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBe([
            'productName' => 'Widget',
            'price' => 9.99,
            'quantity' => 1,
        ]);
    });
});
