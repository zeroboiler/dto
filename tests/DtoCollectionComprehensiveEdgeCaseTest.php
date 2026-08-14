<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection comprehensive edge cases', function (): void {
    describe('Construction and type safety', function (): void {
        it('rejects non-DTO items in constructor', function (): void {
            new DtoCollection([
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false),
                'not a dto',
            ]);
        })->throws(\InvalidArgumentException::class, 'only accepts DataTransferObject');

        it('accepts empty array', function (): void {
            $col = new DtoCollection([]);

            expect($col->count())->toBe(0);
            expect($col->isEmpty())->toBeTrue();
            expect($col->isNotEmpty())->toBeFalse();
        });

        it('make() creates from array of DTOs', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);

            expect($col->count())->toBe(2);
        });
    });

    describe('ArrayAccess contract', function (): void {
        it('offsetExists returns true for valid indices', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeFalse();
            expect($col->offsetExists(-1))->toBeFalse();
        });

        it('offsetGet returns item or null', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col->offsetGet(0))->toBe($dto);
            expect($col->offsetGet(5))->toBeNull();
        });

        it('offsetSet appends when offset is null', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = new DtoCollection([$dto1]);

            $col->offsetSet(null, $dto2);

            expect($col->count())->toBe(2);
            expect($col->offsetGet(1))->toBe($dto2);
        });

        it('offsetSet replaces at specific offset', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = new DtoCollection([$dto1]);

            $col->offsetSet(0, $dto2);

            expect($col->count())->toBe(1);
            expect($col->offsetGet(0))->toBe($dto2);
        });

        it('offsetSet rejects non-DTO values', function (): void {
            $col = new DtoCollection([]);
            $col->offsetSet(0, 'not a dto');
        })->throws(\InvalidArgumentException::class);

        it('offsetUnset removes and re-indexes', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);
            $col = new DtoCollection([$dto1, $dto2, $dto3]);

            $col->offsetUnset(1);

            expect($col->count())->toBe(2);
            expect($col->offsetGet(0))->toBe($dto1);
            expect($col->offsetGet(1))->toBe($dto3);
        });
    });

    describe('Iterator and Countable', function (): void {
        it('iterates with foreach preserving keys', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            $iterated = [];
            foreach ($col as $key => $item) {
                $iterated[$key] = $item->name;
            }

            expect($iterated)->toBe([0 => 'A', 1 => 'C']);
        });
    });

    describe('Push vs Append (mutability)', function (): void {
        it('push() mutates in place and returns same instance', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = new DtoCollection([$dto1]);

            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $result = $col->push($dto2);

            expect($result)->toBe($col);
            expect($col->count())->toBe(2);
        });

        it('append() returns new instance, original unchanged', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $col = new DtoCollection([$dto1]);

            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
            expect($newCol)->not->toBe($col);
        });

        it('merge() creates new collection combining both', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2, $dto3]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(2);
        });
    });

    describe('Map, filter, pluck', function (): void {
        it('map returns plain array with correct indices', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            $names = $col->map(fn (CreateUserDTO $dto) => $dto->name);

            expect($names)->toBe(['Alice', 'Charlie']);
        });

        it('filter returns new collection with matching items', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            $filtered = $col->filter(fn (CreateUserDTO $dto) => str_starts_with($dto->name, 'A'));

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
            expect($col->count())->toBe(2); // original unchanged
        });

        it('pluck extracts a single property', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            expect($col->pluck('email'))->toBe(['a@b.com', 'c@d.com']);
            expect($col->pluck('name'))->toBe(['Alice', 'Charlie']);
        });

        it('pluckKey returns associative array keyed by one property', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            expect($col->pluckKey('email', 'name'))->toBe([
                'a@b.com' => 'Alice',
                'c@d.com' => 'Charlie',
            ]);
        });

        it('pluckKey skips null keys', function (): void {
            $dto1 = AddressDTO::fromArray(['street' => '123 Main', 'city' => 'NYC'], validate: false);
            $dto2 = AddressDTO::fromArray(['street' => '456 Oak', 'city' => null], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            $result = $col->pluckKey('city', 'street');

            // Null city key is skipped
            expect($result)->toHaveCount(1);
            expect($result['NYC'])->toBe('123 Main');
        });

        it('toDictionary re-keys by one property, extracts another', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
            $col = new DtoCollection([$dto1, $dto2]);

            expect($col->toDictionary('email', 'name'))->toBe([
                'a@b.com' => 'Alice',
                'c@d.com' => 'Charlie',
            ]);
        });

        it('toArrayBy is alias for pluckKey with only key field', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $col = new DtoCollection([$dto1]);

            $byEmail = $col->toArrayBy('email');

            expect($byEmail)->toBe([
                'a@b.com' => ['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active', 'tags' => []],
            ]);
        });
    });

    describe('Serialization', function (): void {
        it('jsonSerialize returns toArray result', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('allValues includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Test', 'password' => 'secret'],
                validate: false,
            );
            $col = new DtoCollection([$dto]);

            expect($col->allValues()[0])->toHaveKey('password');
            expect($col->toArray()[0])->not->toHaveKey('password');
        });

        it('items() returns raw DTO instances', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col->items()[0])->toBe($dto);
            expect($col->items()[0])->toBeInstanceOf(CreateUserDTO::class);
        });
    });
});
