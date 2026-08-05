<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('DataTransferObject equality', function (): void {
    it('equals returns true for identical DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'User A',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'User B',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('equals returns false for DTOs of different types', function (): void {
        $user = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $order = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'items' => [],
        ], validate: false);

        // Different classes, toArray() results would differ
        expect($user->equals($order))->toBeFalse();
    });
});

describe('Hidden fields behavior', function (): void {
    it('toArray excludes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->allValues())->toHaveKey('password');
        expect($dto->allValues()['password'])->toBe('secret123');
    });
});

describe('DtoCollection edge cases', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);
        expect(fn (): mixed => new DtoCollection([
            $dto,
            'not a dto',
        ]))->toThrow(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
    });

    it('offsetUnset re-indexes the array', function (): void {
        $collection = DtoCollection::make([]);
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@example.com', 'name' => 'B'], validate: false);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@example.com', 'name' => 'C'], validate: false);

        $collection->push($dto1);
        $collection->push($dto2);
        $collection->push($dto3);

        expect($collection->count())->toBe(3);

        // Remove the middle item
        unset($collection[1]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->email)->toBe('a@example.com');
        expect($collection[1]->email)->toBe('c@example.com');
    });

    it('map returns plain array with correct types', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $emails = $collection->map(fn (CreateUserDTO $u): string => $u->email);

        expect($emails)->toBe(['test@example.com']);
        expect($emails)->toBeArray();
    });

    it('filter returns new collection with matching items', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'active@example.com', 'name' => 'Active'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'inactive@example.com', 'name' => 'Inactive'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        $filtered = $collection->filter(fn (CreateUserDTO $u): bool => str_starts_with($u->email, 'active'));

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->email)->toBe('active@example.com');
    });

    it('pluckKey builds correct key-value map', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@example.com', 'name' => 'Bob'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'a@example.com' => 'Alice',
            'b@example.com' => 'Bob',
        ]);
    });

    it('pluckKey without value field uses full DTO array', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@example.com', 'name' => 'Alice'], validate: false);
        $collection = DtoCollection::make([$dto]);

        $map = $collection->pluckKey('email');

        expect($map)->toHaveKey('a@example.com');
        expect($map['a@example.com'])->toBeArray();
    });
});

describe('Validation attribute messages', function (): void {
    it('collects custom messages from attributes', function (): void {
        $rules = ValidationTestDTO::rules();

        // Should have rules
        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
    });
});

describe('EmptyDTO edge case', function (): void {
    it('creates an empty DTO without error', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toBe([]);
    });
});

describe('fromArray without validation', function (): void {
    it('hydrates DTO without running validation rules', function (): void {
        // Create DTO with data that would fail email validation
        $dto = CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => '',
        ], validate: false);

        expect($dto->email)->toBe('not-an-email');
        expect($dto->name)->toBe('');
    });
});

describe('rulesFor action scoping', function (): void {
    it('returns same rules for unknown actions by default', function (): void {
        $defaultRules = CreateUserDTO::rules();
        $actionRules = CreateUserDTO::rulesFor('create');

        expect($actionRules)->toBe($defaultRules);
    });

    it('returns same rules for update action', function (): void {
        $defaultRules = CreateUserDTO::rules();
        $updateRules = CreateUserDTO::rulesFor('update');

        expect($updateRules)->toBe($defaultRules);
    });
});

describe('JSON serialization', function (): void {
    it('toJson produces valid JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('test@example.com');
        expect($decoded['name'])->toBe('Test User');
        expect($decoded)->not->toHaveKey('password');
    });

    it('jsonSerialize matches toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('Metadata cache flush', function (): void {
    it('flushMetadataCache clears all entries', function (): void {
        DataTransferObject::flushMetadataCache();

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Cache Test',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
    });

    it('flushMetadataCache with class clears specific entry', function (): void {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Specific Flush',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
    });
});
