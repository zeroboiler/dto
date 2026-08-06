<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTOManager facade integration', function (): void {
    it('validates data through manager', function (): void {
        $manager = new DTOManager;
        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        expect($result)->toHaveKey('email');
        expect($result['email'])->toBe('test@example.com');
    });

    it('creates DTO via manager', function (): void {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('creates DTO from JSON via manager', function (): void {
        $manager = new DTOManager;
        $dto = $manager->makeFromJson(CreateUserDTO::class, '{"email":"test@example.com","name":"Doruk"}');

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Doruk');
    });

    it('throws DTOException on invalid JSON', function (): void {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(CreateUserDTO::class, 'not-json'))
            ->toThrow(DTOException::class);
    });

    it('generates OpenAPI schema via manager', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(AddressDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
        expect($schema)->toHaveKey('properties');
        expect($schema['properties'])->toHaveKey('street');
    });
});

describe('DtoCollection advanced operations', function (): void {
    it('creates via static make factory', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
        ];

        $collection = DtoCollection::make($dtos);

        expect($collection->count())->toBe(2);
        expect($collection->first()->name)->toBe('A');
    });

    it('push returns fluent self', function (): void {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $collection = new DtoCollection;
        $result = $collection->push($dto);

        expect($result)->toBe($collection); // same instance (fluent)
        expect($collection->count())->toBe(1);
    });

    it('map returns plain array with keys', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false),
        ];
        $collection = new DtoCollection($dtos);

        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Charlie']);
    });

    it('filter returns new collection instance', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'status' => 'active'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C', 'status' => 'inactive'], validate: false),
        ];
        $collection = new DtoCollection($dtos);

        $active = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->status === 'active');

        expect($active)->not->toBe($collection); // new instance
        expect($active->count())->toBe(1);
        expect($active->first()->name)->toBe('A');
    });

    it('pluckKey builds associative array', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false),
        ];
        $collection = new DtoCollection($dtos);

        $map = $collection->pluckKey('email', 'name');

        expect($map)->toBe([
            'a@b.com' => 'Alice',
            'c@d.com' => 'Charlie',
        ]);
    });

    it('rejects non-DTO items in constructor', function (): void {
        expect(fn () => new DtoCollection(['not', 'a', 'dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes keys', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@c.com', 'name' => 'B'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
        ];
        $collection = new DtoCollection($dtos);

        unset($collection[0]); // removes first

        expect($collection->count())->toBe(2);
        expect($collection->first()->name)->toBe('B'); // re-indexed
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('jsonSerialize matches toArray', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('collection jsonSerialize returns array of arrays', function (): void {
        $dtos = [
            CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
        ];
        $collection = new DtoCollection($dtos);

        $json = $collection->jsonSerialize();

        expect($json)->toBeArray();
        expect($json[0])->toHaveKey('email');
        expect($json[0])->not->toHaveKey('password'); // hidden
    });
});

describe('DTO state checks', function (): void {
    it('isEmpty returns true when all defaults', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when any property has a value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty returns true for DTO with zero/false values only', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});
