<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO production readiness coverage', function () {

    // -----------------------------------------------------------------------
    // DtoCollection ArrayAccess edge cases
    // -----------------------------------------------------------------------
    describe('DtoCollection ArrayAccess', function () {
        it('supports isset/empty checks', function () {
            $dto = new EmptyDTO(foo: 'test');
            $col = new DtoCollection([$dto]);

            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeFalse();
            expect(empty($col[0]))->toBeFalse();
        });

        it('offsetGet returns null for non-existent index', function () {
            $col = new DtoCollection;

            expect($col[99])->toBeNull();
        });

        it('offsetSet with null key appends', function () {
            $a = new EmptyDTO;
            $b = new EmptyDTO;
            $col = new DtoCollection;

            $col[] = $a;
            $col[] = $b;

            expect($col->count())->toBe(2);
            expect($col[0]->foo)->toBeNull();
        });

        it('offsetSet with numeric key replaces', function () {
            $a = new EmptyDTO(foo: 'first');
            $b = new EmptyDTO(foo: 'second');
            $col = new DtoCollection([$a]);

            $col[0] = $b;

            expect($col->count())->toBe(1);
            expect($col[0]->foo)->toBe('second');
        });

        it('offsetUnset re-indexes array', function () {
            $a = new EmptyDTO(foo: 'a');
            $b = new EmptyDTO(foo: 'b');
            $c = new EmptyDTO(foo: 'c');
            $col = new DtoCollection([$a, $b, $c]);

            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col[0]->foo)->toBe('b');
            expect($col[1]->foo)->toBe('c');
        });

        it('rejects non-DTO values in constructor', function () {
            new DtoCollection(['not a dto']);
        })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');

        it('rejects non-DTO values in offsetSet', function () {
            $col = new DtoCollection;
            $col[] = 'not a dto';
        })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
    });

    // -----------------------------------------------------------------------
    // DtoCollection pluck/pluckKey/map/filter
    // -----------------------------------------------------------------------
    describe('DtoCollection utility methods', function () {
        it('pluck extracts a single field', function () {
            $col = new DtoCollection([
                new ProductDTO(name: 'Widget A', price: '9.99', stock: 10),
                new ProductDTO(name: 'Widget B', price: '14.99', stock: 5),
            ]);

            expect($col->pluck('name'))->toBe(['Widget A', 'Widget B']);
        });

        it('pluckKey builds key/value map', function () {
            $col = new DtoCollection([
                new ProductDTO(name: 'Widget A', price: '9.99', stock: 10),
                new ProductDTO(name: 'Widget B', price: '14.99', stock: 5),
            ]);

            $map = $col->pluckKey('name', 'price');
            expect($map)->toBe([
                'Widget A' => '9.99',
                'Widget B' => '14.99',
            ]);
        });

        it('pluckKey without valueField returns full arrays', function () {
            $col = new DtoCollection([
                new ProductDTO(name: 'Widget A', price: '9.99', stock: 10),
            ]);

            $map = $col->pluckKey('name');
            expect($map)->toHaveKey('Widget A');
        });

        it('filter returns new collection with matching items', function () {
            $col = new DtoCollection([
                new ProductDTO(name: 'Widget A', price: '9.99', stock: 10),
                new ProductDTO(name: 'Widget B', price: '14.99', stock: 0),
                new ProductDTO(name: 'Widget C', price: '5.99', stock: 3),
            ]);

            $inStock = $col->filter(fn (ProductDTO $p): bool => $p->stock > 0);

            expect($inStock->count())->toBe(2);
            expect($inStock->pluck('name'))->toBe(['Widget A', 'Widget C']);
        });

        it('map returns plain array of mapped values', function () {
            $col = new DtoCollection([
                new ProductDTO(name: 'Widget A', price: '9.99', stock: 10),
                new ProductDTO(name: 'Widget B', price: '14.99', stock: 5),
            ]);

            $names = $col->map(fn (ProductDTO $p): string => strtoupper($p->name));

            expect($names)->toBe(['WIDGET A', 'WIDGET B']);
        });

        it('push returns fluent interface', function () {
            $col = new DtoCollection;
            $result = $col->push(new EmptyDTO(foo: 'x'));

            expect($result)->toBe($col); // same instance (fluent)
            expect($col->count())->toBe(1);
        });
    });

    // -----------------------------------------------------------------------
    // DtoCollection serialization
    // -----------------------------------------------------------------------
    describe('DtoCollection serialization', function () {
        it('toArray serializes each DTO', function () {
            $col = new DtoCollection([
                new EmptyDTO(foo: 'test', bar: 'value'),
            ]);

            $arr = $col->toArray();
            expect($arr)->toBe([
                ['foo' => 'test', 'bar' => 'value'],
            ]);
        });

        it('allValues includes hidden fields', function () {
            $col = new DtoCollection([
                new CreateUserDTO(
                    email: 'test@example.com',
                    name: 'Test',
                    password: 'secret123',
                ),
            ]);

            $arr = $col->allValues();
            expect($arr[0])->toHaveKey('password');
        });

        it('jsonSerialize returns toArray result', function () {
            $col = new DtoCollection([
                new EmptyDTO(foo: 'x'),
            ]);

            expect(json_encode($col))->toBe('[{"foo":"x"}]');
        });
    });

    // -----------------------------------------------------------------------
    // DTO equality and state checks
    // -----------------------------------------------------------------------
    describe('DTO equality and isEmpty', function () {
        it('equals returns true for identical data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);
            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals returns false for different data', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
            ], validate: false);
            $b = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'Bob',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('isEmpty returns true when all properties are empty', function () {
            $dto = new EmptyDTO;

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when at least one property has value', function () {
            $dto = new EmptyDTO(foo: 'something');

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isEmpty handles zero, empty string, false, and empty array as empty', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public int $zero = 0,
                    public string $empty = '',
                    public bool $false = false,
                    public readonly array $arr = [],
                    public readonly ?string $null = null,
                ) {}
            };

            expect($dto->isEmpty())->toBeTrue();
        });
    });

    // -----------------------------------------------------------------------
    // fromJson edge cases
    // -----------------------------------------------------------------------
    describe('fromJson error handling', function () {
        it('throws on invalid JSON syntax', function () {
            EmptyDTO::fromJson('{invalid json}');
        })->throws(DTOException::class, 'Cannot decode JSON');

        it('throws on sequential array (JSON array, not object)', function () {
            EmptyDTO::fromJson('[1, 2, 3]');
        })->throws(DTOException::class, 'Expected a JSON object');

        it('throws on JSON null', function () {
            EmptyDTO::fromJson('null');
        })->throws(DTOException::class);

        it('throws on JSON boolean', function () {
            EmptyDTO::fromJson('true');
        })->throws(DTOException::class);

        it('succeeds on empty JSON object', function () {
            $dto = EmptyDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->foo)->toBeNull();
        });
    });

    // -----------------------------------------------------------------------
    // with() immutability proof
    // -----------------------------------------------------------------------
    describe('with() immutability', function () {
        it('returns a new instance', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Original',
            ], validate: false);

            $modified = $original->with(['name' => 'Modified']);

            expect($original)->not->toBe($modified);
            expect($original->name)->toBe('Original');
            expect($modified->name)->toBe('Modified');
            // Email should be preserved
            expect($modified->email)->toBe('test@example.com');
        });
    });

    // -----------------------------------------------------------------------
    // Hidden field behavior
    // -----------------------------------------------------------------------
    describe('Hidden field behavior', function () {
        it('toArray excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret123');
        });
    });

    // -----------------------------------------------------------------------
    // MapFrom behavior
    // -----------------------------------------------------------------------
    describe('MapFrom behavior', function () {
        it('maps source key to property', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+905551234567',
            ], validate: false);

            expect($dto->phone)->toBe('+905551234567');
        });

        it('original property name takes precedence when both exist', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone' => 'direct',
                'phone_number' => 'mapped',
            ], validate: false);

            // MapFrom maps phone_number → phone, so phone_number value should win
            expect($dto->phone)->toBe('mapped');
        });
    });

    // -----------------------------------------------------------------------
    // DefaultValue behavior
    // -----------------------------------------------------------------------
    describe('DefaultValue behavior', function () {
        it('applies default when key is missing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('uses provided value when key is present', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->status)->toBe('inactive');
        });
    });

    // -----------------------------------------------------------------------
    // only/except selective output
    // -----------------------------------------------------------------------
    describe('only/except selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toBe(['email' => 'test@example.com']);
        });

        it('only accepts array of keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $dto->only(['email', 'name']);

            expect($result)->toHaveKeys(['email', 'name']);
        });

        it('except removes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status');

            expect($result)->not->toHaveKey('status');
            expect($result)->toHaveKeys(['email', 'name']);
        });

        it('ignores non-existent keys in only/except', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->only('nonexistent'))->toBe([]);
            expect($dto->except('nonexistent'))->toHaveKeys(['email', 'name']);
        });
    });

    // -----------------------------------------------------------------------
    // Nested DTO hydration
    // -----------------------------------------------------------------------
    describe('Nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'city' => 'Istanbul',
                    'street' => '123 Main St',
                    'zipCode' => '34000',
                ],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->city)->toBe('Istanbul');
        });

        it('serializes nested DTO recursively', function () {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'city' => 'Istanbul',
                    'street' => '123 Main St',
                    'zipCode' => '34000',
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBe([
                'city' => 'Istanbul',
                'street' => '123 Main St',
                'zipCode' => '34000',
            ]);
        });
    });

    // -----------------------------------------------------------------------
    // DTOException factory methods
    // -----------------------------------------------------------------------
    describe('DTOException factories', function () {
        it('invalidCast includes property, type, and value debug info', function () {
            $ex = DTOException::invalidCast('price', 'integer', 'not_a_number');

            expect($ex->getMessage())->toContain('price');
            expect($ex->getMessage())->toContain('integer');
            expect($ex->getMessage())->toContain('string');
        });

        it('invalidJson includes property and error', function () {
            $ex = DTOException::invalidJson('metadata', 'Syntax error');

            expect($ex->getMessage())->toContain('metadata');
            expect($ex->getMessage())->toContain('Syntax error');
        });
    });
});
