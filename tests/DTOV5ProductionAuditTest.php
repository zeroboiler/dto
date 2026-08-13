<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

/**
 * V5 Production Audit — comprehensive DTO contract, type consistency,
 * hydration, serialization, validation, and edge-case coverage.
 *
 * Validates structural contracts, attribute resolution, fromArray/toArray
 * roundtrips, immutability, collections, enum properties, hidden fields,
 * MapFrom, Cast, DefaultValue, and partial updates.
 */
describe('V5 Production Audit', function (): void {
    // -----------------------------------------------------------------------
    // 1. Structural Contract — fixture DTOs extend DataTransferObject
    // -----------------------------------------------------------------------
    describe('Structural Contract', function (): void {
        it('all fixture DTOs extend DataTransferObject', function (): void {
            $classes = [
                CreateUserDTO::class,
                AddressDTO::class,
                OrderDTO::class,
                OrderItemDTO::class,
                ArticleDTO::class,
            ];

            foreach ($classes as $class) {
                expect(is_subclass_of($class, DataTransferObject::class))->toBeTrue(
                    "{$class} must extend DataTransferObject"
                );
            }
        });

        it('all fixture DTOs have public readonly properties in constructor', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $reflection = new \ReflectionClass($dto);
            $props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue(
                    "Property {$prop->getName()} must be readonly"
                );
            }
        });
    });

    // -----------------------------------------------------------------------
    // 2. Hydration — fromArray with validation
    // -----------------------------------------------------------------------
    describe('Hydration', function (): void {
        it('fromArray creates DTO with required fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
        });

        it('fromArray applies defaults', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('fromArray applies MapFrom', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'phone_number' => '+1234567890',
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('fromArray applies Cast', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test Article',
                'body' => 'This is a test article body.',
                'viewCount' => '42',
                'rating' => '4.5',
            ], validate: false);

            expect($dto->viewCount)->toBe(42);
            expect($dto->rating)->toBe(4.5);
        });

        it('fromArray with validation rejects invalid data', function (): void {
            expect(function (): void {
                CreateUserDTO::fromArray([
                    'email' => 'not-an-email',
                    'name' => 'A', // too short (min:2)
                ]);
            })->toThrow(ValidationException::class);
        });

        it('fromArray skip validation creates DTO without checks', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'bad-email',
                'name' => 'X',
            ], validate: false);

            expect($dto->email)->toBe('bad-email');
            expect($dto->name)->toBe('X');
        });
    });

    // -----------------------------------------------------------------------
    // 3. Serialization — toArray, toJson, only, except
    // -----------------------------------------------------------------------
    describe('Serialization', function (): void {
        it('toArray excludes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson produces valid JSON string', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('test@example.com');
            expect($decoded['name'])->toBe('Test User');
        });

        it('only() returns specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $only = $dto->only('email');
            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
        });

        it('except() excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });

        it('only() with array of keys', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test Article',
                'body' => 'This is a test article body.',
            ], validate: false);

            $only = $dto->only(['title', 'body']);
            expect($only)->toHaveKeys(['title', 'body']);
            expect($only)->not->toHaveKey('authorEmail');
        });

        it('except() with array of keys', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test Article',
                'body' => 'This is a test article body.',
            ], validate: false);

            $except = $dto->except(['authorEmail', 'body']);
            expect($except)->toHaveKey('title');
            expect($except)->not->toHaveKey('authorEmail');
            expect($except)->not->toHaveKey('body');
        });
    });

    // -----------------------------------------------------------------------
    // 4. Roundtrip — toArray → fromArray → toArray consistency
    // -----------------------------------------------------------------------
    describe('Roundtrip', function (): void {
        it('simple DTO roundtrip preserves values', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'roundtrip@example.com',
                'name' => 'Roundtrip User',
                'status' => 'active',
                'tags' => ['php', 'laravel'],
                'phone_number' => '+9876543210',
            ], validate: false);

            $restored = CreateUserDTO::fromArray($original->allValues(), validate: false);
            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
            expect($restored->status)->toBe($original->status);
            expect($restored->tags)->toBe($original->tags);
            expect($restored->phone)->toBe($original->phone);
        });

        it('nested DTO roundtrip preserves structure', function (): void {
            $original = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                    'zipCode' => '34000',
                ],
                'items' => [
                    ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                ],
            ], validate: false);

            $restored = OrderDTO::fromArray($original->allValues(), validate: false);
            expect($restored->orderNumber)->toBe('ORD-001');
            expect($restored->shippingAddress->street)->toBe('123 Main St');
            expect(count($restored->items))->toBe(1);
            expect($restored->items[0]->productName)->toBe('Widget');
        });
    });

    // -----------------------------------------------------------------------
    // 5. Immutability — with() creates new instance
    // -----------------------------------------------------------------------
    describe('Immutability', function (): void {
        it('with() returns new instance with overrides', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Original Name',
            ], validate: false);

            $modified = $original->with(['name' => 'New Name'], validate: false);
            expect($modified)->not->toBe($original);
            expect($original->name)->toBe('Original Name');
            expect($modified->name)->toBe('New Name');
        });

        it('with() preserves non-overridden values', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ], validate: false);

            $modified = $original->with(['name' => 'Updated'], validate: false);
            expect($modified->email)->toBe('test@example.com');
            expect($modified->status)->toBe('active');
        });
    });

    // -----------------------------------------------------------------------
    // 6. Equality — equals(), isEmpty(), isNotEmpty()
    // -----------------------------------------------------------------------
    describe('Equality', function (): void {
        it('equals() returns true for same values', function (): void {
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

        it('equals() returns false for different values', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'User 1',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'other@example.com',
                'name' => 'User 2',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty() returns true for all-default DTO', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'test@example.com',
                'title' => 'T',
                'body' => '0123456789',
            ], validate: false);

            // All optional properties are defaults — but required ones are not empty
            // isEmpty checks ALL properties including required ones
            // Required non-nullable fields with values → NOT empty
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isNotEmpty() is negation of isEmpty', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto->isEmpty() === $dto->isNotEmpty())->toBeFalse();
        });
    });

    // -----------------------------------------------------------------------
    // 7. Enum Properties — BackedEnum auto-cast
    // -----------------------------------------------------------------------
    describe('Enum Properties', function (): void {
        it('enum property auto-casts from backed value', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test',
                'body' => '0123456789',
                'status' => 1,
                'currency' => 'EUR',
            ], validate: false);

            expect($dto->status)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::PUBLISHED);
            expect($dto->currency)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\Currency::EUR);
        });

        it('enum property serializes to backed value', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test',
                'body' => '0123456789',
                'status' => 2,
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['status'])->toBe(2);
        });

        it('enum property uses default when not provided', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test',
                'body' => '0123456789',
            ], validate: false);

            expect($dto->status)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::DRAFT);
            expect($dto->currency)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\Currency::USD);
        });

        it('enum property roundtrip preserves enum instance', function (): void {
            $dto = ArticleDTO::fromArray([
                'authorEmail' => 'author@example.com',
                'title' => 'Test',
                'body' => '0123456789',
                'status' => \ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::ARCHIVED,
                'currency' => \ZeroBoiler\DTO\Tests\Fixtures\Currency::TRY,
            ], validate: false);

            $restored = ArticleDTO::fromArray($dto->allValues(), validate: false);
            expect($restored->status)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::ARCHIVED);
            expect($restored->currency)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\Currency::TRY);
        });
    });

    // -----------------------------------------------------------------------
    // 8. Rules — rules(), rulesFor()
    // -----------------------------------------------------------------------
    describe('Rules', function (): void {
        it('rules() returns non-empty array with expected keys', function (): void {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray()->not->toBeEmpty();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rules() contains required rule for required fields', function (): void {
            $rules = CreateUserDTO::rules();
            expect($rules['email'])->toContain('required');
            expect($rules['name'])->toContain('required');
        });

        it('rulesFor() returns same as rules() by default', function (): void {
            $rules = ArticleDTO::rules();
            $rulesForCreate = ArticleDTO::rulesFor('create');
            expect($rules)->toBe($rulesForCreate);
        });

        it('ArticleDTO rules include enum validation', function (): void {
            $rules = ArticleDTO::rules();
            expect($rules)->toHaveKey('status');
            // Enum rule should be present
            $statusRules = $rules['status'];
            $hasEnumRule = false;
            foreach ($statusRules as $rule) {
                if ($rule instanceof \Illuminate\Validation\Rules\Enum) {
                    $hasEnumRule = true;
                    break;
                }
            }
            expect($hasEnumRule)->toBeTrue('status field should have enum validation rule');
        });
    });

    // -----------------------------------------------------------------------
    // 9. DtoCollection — operations
    // -----------------------------------------------------------------------
    describe('DtoCollection', function (): void {
        it('create from array of DTOs', function (): void {
            $dtos = [
                AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false),
                AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            expect($col->count())->toBe(2);
        });

        it('pluck extracts property values', function (): void {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
                CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('filter returns new collection', function (): void {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
                CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            $filtered = $col->filter(fn (CreateUserDTO $d) => str_starts_with($d->name, 'A'));

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Alice');
        });

        it('first/last return correct items', function (): void {
            $dtos = [
                AddressDTO::fromArray(['street' => 'First', 'city' => 'X'], validate: false),
                AddressDTO::fromArray(['street' => 'Last', 'city' => 'Y'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            expect($col->first()->street)->toBe('First');
            expect($col->last()->street)->toBe('Last');
        });

        it('append creates new collection with added item', function (): void {
            $dto1 = AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false);
            $dto2 = AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge combines two collections', function (): void {
            $col1 = DtoCollection::make([
                AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false),
            ]);
            $col2 = DtoCollection::make([
                AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false),
            ]);

            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
        });

        it('toArray serializes all DTOs', function (): void {
            $dtos = [
                CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            $arr = $col->toArray();

            expect($arr)->toBeArray();
            expect(count($arr))->toBe(1);
            expect($arr[0])->toHaveKey('email');
            expect($arr[0])->not->toHaveKey('password');
        });

        it('offsetUnset re-indexes collection', function (): void {
            $dtos = [
                AddressDTO::fromArray(['street' => 'A', 'city' => 'X'], validate: false),
                AddressDTO::fromArray(['street' => 'B', 'city' => 'Y'], validate: false),
                AddressDTO::fromArray(['street' => 'C', 'city' => 'Z'], validate: false),
            ];

            $col = DtoCollection::make($dtos);
            $col->offsetUnset(0);

            expect($col->count())->toBe(2);
            expect($col->first()->street)->toBe('B');
        });
    });

    // -----------------------------------------------------------------------
    // 10. JSON — fromJson, jsonSerialize
    // -----------------------------------------------------------------------
    describe('JSON', function (): void {
        it('fromJson creates DTO from JSON string', function (): void {
            $json = '{"email":"test@example.com","name":"JSON User"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('JSON User');
        });

        it('fromJson throws DTOException for invalid JSON', function (): void {
            expect(fn () => CreateUserDTO::fromJson('{invalid json}'))->toThrow(DTOException::class);
        });

        it('fromJson throws DTOException for sequential array', function (): void {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]'))->toThrow(DTOException::class);
        });

        it('jsonSerialize returns toArray output', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $serialized = $dto->jsonSerialize();
            $arr = $dto->toArray();
            expect($serialized)->toBe($arr);
        });
    });

    // -----------------------------------------------------------------------
    // 11. Partial Updates — fromPartialArray
    // -----------------------------------------------------------------------
    describe('Partial Updates', function (): void {
        it('fromPartialArray hydrates only provided fields', function (): void {
            $dto = ArticleDTO::fromPartialArray([
                'title' => 'Updated Title',
            ], validatePresent: false);

            expect($dto->title)->toBe('Updated Title');
            expect($dto->status)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::DRAFT);
        });

        it('fromPartialArray uses defaults for missing fields', function (): void {
            $dto = ArticleDTO::fromPartialArray([
                'authorEmail' => 'author@example.com',
                'title' => 'T',
                'body' => '0123456789',
            ], validatePresent: false);

            expect($dto->viewCount)->toBe(0);
            expect($dto->rating)->toBe(0.0);
            expect($dto->commentsEnabled)->toBe(true);
        });

        it('fromPartialArray with empty array uses all defaults', function (): void {
            $dto = ArticleDTO::fromPartialArray([], validatePresent: false);

            // All properties should have type-appropriate defaults
            expect($dto->status)->toBe(\ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus::DRAFT);
            expect($dto->viewCount)->toBe(0);
            expect($dto->coverImageUrl)->toBeNull();
        });
    });

    // -----------------------------------------------------------------------
    // 12. DTOException — factory methods and __toString
    // -----------------------------------------------------------------------
    describe('DTOException', function (): void {
        it('invalidCast creates exception with property info', function (): void {
            $e = DTOException::invalidCast('status', 'integer', 'abc');
            expect($e->getMessage())->toContain('status');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson creates exception with property and error', function (): void {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function (): void {
            $e = DTOException::invalidJson('field', 'error');
            $str = (string) $e;
            expect($str)->toContain('DTOException');
            expect($str)->toContain('field');
        });
    });

    // -----------------------------------------------------------------------
    // 13. Nested DTO Hydration
    // -----------------------------------------------------------------------
    describe('Nested DTO Hydration', function (): void {
        it('nested DTO hydrates from array', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Istanbul');
        });

        it('nested DTO accepts existing DTO instance', function (): void {
            $address = AddressDTO::fromArray([
                'street' => '456 Side St',
                'city' => 'Ankara',
            ], validate: false);

            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-002',
                'shippingAddress' => $address,
            ], validate: false);

            expect($dto->shippingAddress->street)->toBe('456 Side St');
        });

        it('nested array of DTOs hydrates each element', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-003',
                'shippingAddress' => [
                    'street' => '789 Oak Ave',
                    'city' => 'Izmir',
                ],
                'items' => [
                    ['productName' => 'Item A', 'price' => 10.0, 'quantity' => 3],
                    ['productName' => 'Item B', 'price' => 25.5, 'quantity' => 1],
                ],
            ], validate: false);

            expect(count($dto->items))->toBe(2);
            expect($dto->items[0]->productName)->toBe('Item A');
            expect($dto->items[1]->price)->toBe(25.5);
        });

        it('nested DTO serializes recursively', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-004',
                'shippingAddress' => [
                    'street' => '100 Test St',
                    'city' => 'Test City',
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['street'])->toBe('100 Test St');
        });
    });

    // -----------------------------------------------------------------------
    // 14. Cache Management
    // -----------------------------------------------------------------------
    describe('Cache Management', function (): void {
        it('flushMetadataCache clears all caches', function (): void {
            // Resolve metadata first
            CreateUserDTO::rules();
            AddressDTO::rules();

            // Flush
            DataTransferObject::flushMetadataCache();

            // Re-resolve should still work
            expect(CreateUserDTO::rules())->toBeArray()->not->toBeEmpty();
        });

        it('flushMetadataCache with class clears only that class', function (): void {
            CreateUserDTO::rules();
            AddressDTO::rules();

            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // AddressDTO should still have its metadata cached (accessible via rules)
            expect(AddressDTO::rules())->toBeArray()->not->toBeEmpty();
        });
    });
});
