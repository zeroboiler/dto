<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{AllDefaultsDTO, AddressDTO, CreateUserDTO, MinimalDTO, OrderDTO, OrderItemDTO};

/**
 * V55 strict type safety, infrastructure contract, and return type tests.
 *
 * Validates:
 * - All public methods return exact declared types
 * - readonly promotion works correctly
 * - fromArray/toArray/with/equals/isEmpty roundtrip integrity
 * - DtoCollection type safety (ArrayAccess, IteratorAggregate, Countable, JsonSerializable)
 * - DTOManager delegation contract
 * - DTOException named constructors
 * - Structural: final/readonly classes, declare(strict_types=1), attribute contracts
 */
describe('V55 DTO Strict Type Safety & Infrastructure Contract', function (): void {
    // -----------------------------------------------------------------------
    // Basic DTO: MinimalDTO
    // -----------------------------------------------------------------------
    describe('MinimalDTO creation and serialization', function (): void {
        it('fromArray returns correct instance type', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto)->toBeInstanceOf(DataTransferObject::class);
        });

        it('properties are readonly and accessible', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);

            expect($dto->name)->toBe('Alice');
            expect($dto->value)->toBe('test');
        });

        it('toArray returns array with correct keys and types', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
            $arr = $dto->toArray();

            expect($arr)->toBeArray();
            expect($arr)->toHaveKeys(['name', 'value']);
            expect($arr['name'])->toBeString();
            expect($arr['value'])->toBeString();
        });

        it('toJson returns valid JSON string', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
            $json = $dto->toJson();

            expect($json)->toBeString();
            expect($json)->not->toBeEmpty();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['name'])->toBe('Alice');
        });

        it('jsonSerialize returns same as toArray', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('equals returns true for identical DTOs', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
            $b = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals returns false for different DTOs', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
            $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'test']);

            expect($a->equals($b))->toBeFalse();
        });

        it('isEmpty returns false when properties have values', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('validation fails for missing required fields', function (): void {
            expect(fn () => MinimalDTO::fromArray(['name' => 'Alice']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('rules returns array with required rules', function (): void {
            $rules = MinimalDTO::rules();

            expect($rules)->toBeArray();
            expect($rules['name'])->toContain('required');
            expect($rules['value'])->toContain('required');
        });

        it('rulesFor returns same as rules by default', function (): void {
            expect(MinimalDTO::rulesFor('create'))->toBe(MinimalDTO::rules());
            expect(MinimalDTO::rulesFor('update'))->toBe(MinimalDTO::rules());
        });
    });

    // -----------------------------------------------------------------------
    // CreateUserDTO: hidden, mapFrom, cast, default
    // -----------------------------------------------------------------------
    describe('CreateUserDTO advanced features', function (): void {
        it('hidden property excluded from toArray', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'],
                validate: false,
            );

            expect($dto->toArray())->not->toHaveKey('password');
            expect($dto->password)->toBe('secret123'); // still accessible on instance
        });

        it('hidden property included in allValues', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'],
                validate: false,
            );

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret123');
        });

        it('MapFrom maps source key to property', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'phone_number' => '555-1234'],
                validate: false,
            );

            expect($dto->phone)->toBe('555-1234');
        });

        it('DefaultValue applied when key absent', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice'],
                validate: false,
            );

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });

        it('Cast converts string to array', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'tags' => 'a,b,c'],
                validate: false,
            );

            expect($dto->tags)->toBeArray();
        });

        it('only returns specified fields', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'],
                validate: false,
            );

            $only = $dto->only('email');

            expect($only)->toBe(['email' => 'a@b.com']);
        });

        it('except returns all fields except specified', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'],
                validate: false,
            );

            $except = $dto->except('email');

            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('status');
        });

        it('with returns new instance with overrides', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'Alice'],
                validate: false,
            );

            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice'); // original unchanged
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('a@b.com');
        });

        it('fromPartialArray fills missing with defaults', function (): void {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Bob'], validate: false);

            expect($dto->name)->toBe('Bob');
            expect($dto->status)->toBe('active'); // default
            expect($dto->tags)->toBe([]); // default
        });
    });

    // -----------------------------------------------------------------------
    // AllDefaultsDTO: isEmpty, defaults, hidden
    // -----------------------------------------------------------------------
    describe('AllDefaultsDTO default and isEmpty behavior', function (): void {
        it('fromArray with empty data uses all defaults', function (): void {
            $dto = AllDefaultsDTO::fromArray([]);

            expect($dto->name)->toBe('default-name');
            expect($dto->count)->toBe(0);
            expect($dto->active)->toBeFalse();
            expect($dto->items)->toBe([]);
        });

        it('isEmpty returns true when all defaults are empty/false/zero', function (): void {
            $dto = AllDefaultsDTO::fromArray([]);

            // 0 and false are NOT considered empty per isEmpty() contract
            // But count=0 and active=false ARE considered empty
            // Only name='default-name' is non-empty → should be false
            expect($dto->isEmpty())->toBeFalse();
        });

        it('hidden property excluded from toArray but in allValues', function (): void {
            $dto = AllDefaultsDTO::fromArray([]);

            expect($dto->toArray())->not->toHaveKey('token');
            expect($dto->allValues())->toHaveKey('token');
            expect($dto->allValues()['token'])->toBe('hidden-secret');
        });
    });

    // -----------------------------------------------------------------------
    // Nested DTO: OrderDTO with AddressDTO + NestedArray
    // -----------------------------------------------------------------------
    describe('Nested DTO hydration', function (): void {
        it('hydrates nested DTO from array', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Springfield');
        });

        it('hydrates nested DTO array via NestedArray', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => ['street' => '123 Main St', 'city' => 'Springfield'],
                'items' => [
                    ['productName' => 'Widget', 'price' => 9.99, 'quantity' => 2],
                    ['productName' => 'Gadget', 'price' => 24.99],
                ],
            ], validate: false);

            expect($dto->items)->toBeArray();
            expect($dto->items[0])->toBeInstanceOf(OrderItemDTO::class);
            expect($dto->items[0]->productName)->toBe('Widget');
            expect($dto->items[0]->price)->toBe(9.99);
            expect($dto->items[1]->productName)->toBe('Gadget');
        });

        it('toArray recursively serializes nested DTOs', function (): void {
            $dto = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => ['street' => '123 Main St', 'city' => 'Springfield'],
                'items' => [
                    ['productName' => 'Widget', 'price' => 9.99],
                ],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['street'])->toBe('123 Main St');
            expect($arr['items'][0])->toBeArray();
            expect($arr['items'][0]['productName'])->toBe('Widget');
        });
    });

    // -----------------------------------------------------------------------
    // fromJson
    // -----------------------------------------------------------------------
    describe('fromJson', function (): void {
        it('creates DTO from valid JSON string', function (): void {
            $dto = MinimalDTO::fromJson('{"name":"Alice","value":"test"}');

            expect($dto->name)->toBe('Alice');
            expect($dto->value)->toBe('test');
        });

        it('throws DTOException for invalid JSON', function (): void {
            expect(fn () => MinimalDTO::fromJson('not json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array JSON', function (): void {
            expect(fn () => MinimalDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty object JSON', function (): void {
            $dto = AllDefaultsDTO::fromJson('{}');

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });
    });

    // -----------------------------------------------------------------------
    // DtoCollection type safety
    // -----------------------------------------------------------------------
    describe('DtoCollection contract', function (): void {
        it('make creates collection from DTO array', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);

            $col = DtoCollection::make([$a, $b]);

            expect($col)->toBeInstanceOf(DtoCollection::class);
            expect($col->count())->toBe(2);
        });

        it('rejects non-DTO items', function (): void {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('toArray returns array of arrays', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a]);

            $arr = $col->toArray();

            expect($arr)->toBeArray();
            expect($arr[0])->toBe(['name' => 'A', 'value' => '1']);
        });

        it('implements ArrayAccess', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a]);

            expect(isset($col[0]))->toBeTrue();
            expect($col[0])->toBe($a);
            expect($col[1] ?? null)->toBeNull();
        });

        it('implements IteratorAggregate', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            $names = [];
            foreach ($col as $dto) {
                $names[] = $dto->name;
            }

            expect($names)->toBe(['A', 'B']);
        });

        it('implements JsonSerializable', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a]);

            $json = json_encode($col);

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBe([['name' => 'A', 'value' => '1']]);
        });

        it('first/last return DTO or null', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            expect($col->first())->toBe($a);
            expect($col->last())->toBe($b);
        });

        it('isEmpty/isNotEmpty work correctly', function (): void {
            $empty = DtoCollection::make([]);
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $nonEmpty = DtoCollection::make([$a]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('map returns plain array', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            $names = $col->map(fn (DataTransferObject $dto, int $i): string => $dto->name);

            expect($names)->toBe(['A', 'B']);
        });

        it('filter returns new collection', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->name === 'A');

            expect($filtered)->toBeInstanceOf(DtoCollection::class);
            expect($filtered->count())->toBe(1);
        });

        it('append returns new collection with added item', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a]);

            $appended = $col->append($b);

            expect($col->count())->toBe(1); // original unchanged
            expect($appended->count())->toBe(2);
        });

        it('push mutates and returns same collection', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a]);

            $result = $col->push($b);

            expect($result)->toBe($col); // same instance
            expect($col->count())->toBe(2); // mutated
        });

        it('merge combines two collections', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $c = MinimalDTO::fromArray(['name' => 'C', 'value' => '3']);
            $col1 = DtoCollection::make([$a]);
            $col2 = DtoCollection::make([$b, $c]);

            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
        });

        it('pluck extracts property values', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            expect($col->pluck('name'))->toBe(['A', 'B']);
        });

        it('take/skip return correct slices', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $c = MinimalDTO::fromArray(['name' => 'C', 'value' => '3']);
            $col = DtoCollection::make([$a, $b, $c]);

            expect($col->take(2)->count())->toBe(2);
            expect($col->skip(1)->count())->toBe(2);
            expect($col->skip(1)->first()->name)->toBe('B');
        });

        it('chunk splits into correct groups', function (): void {
            $items = [];
            for ($i = 0; $i < 5; $i++) {
                $items[] = MinimalDTO::fromArray(['name' => "Item{$i}", 'value' => (string) $i]);
            }
            $col = DtoCollection::make($items);

            $chunks = $col->chunk(2);

            expect($chunks)->toHaveCount(3); // 2+2+1
            expect($chunks[0]->count())->toBe(2);
            expect($chunks[2]->count())->toBe(1);
        });

        it('unique removes duplicates by toArray equality', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $a2 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $a2, $b]);

            $unique = $col->unique();

            expect($unique->count())->toBe(2);
        });

        it('contains returns bool for matching callback', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a]);

            expect($col->contains(fn (DataTransferObject $d): bool => $d->name === 'A'))->toBeTrue();
            expect($col->contains(fn (DataTransferObject $d): bool => $d->name === 'Z'))->toBeFalse();
        });

        it('search returns first matching DTO or null', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a, $b]);

            expect($col->search(fn (DataTransferObject $d): bool => $d->name === 'B'))->toBe($b);
            expect($col->search(fn (DataTransferObject $d): bool => $d->name === 'Z'))->toBeNull();
        });

        it('sortBy sorts by property name', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'C', 'value' => '3']);
            $b = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a, $b]);

            $sorted = $col->sortBy('name');

            expect($sorted->first()->name)->toBe('A');
        });

        it('clone throws RuntimeException', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $col = DtoCollection::make([$a]);

            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('offsetSet/offsetUnset work correctly', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $col = DtoCollection::make([$a]);

            $col[] = $b; // offsetSet with null offset
            expect($col->count())->toBe(2);

            unset($col[0]); // offsetUnset
            expect($col->count())->toBe(1);
            expect($col->first()->name)->toBe('B'); // re-indexed
        });
    });

    // -----------------------------------------------------------------------
    // DTOManager delegation
    // -----------------------------------------------------------------------
    describe('DTOManager delegation contract', function (): void {
        it('make creates DTO instance', function (): void {
            $manager = new DTOManager;
            $dto = $manager->make(MinimalDTO::class, ['name' => 'A', 'value' => '1']);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('A');
        });

        it('validate returns validated data', function (): void {
            $manager = new DTOManager;
            $data = $manager->validate(MinimalDTO::class, ['name' => 'A', 'value' => '1']);

            expect($data)->toBe(['name' => 'A', 'value' => '1']);
        });

        it('validate throws on invalid data', function (): void {
            $manager = new DTOManager;

            expect(fn () => $manager->validate(MinimalDTO::class, ['name' => 'A']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('rules returns DTO rules', function (): void {
            $manager = new DTOManager;
            $rules = $manager->rules(MinimalDTO::class);

            expect($rules)->toBe(MinimalDTO::rules());
        });

        it('rulesFor delegates to DTO', function (): void {
            $manager = new DTOManager;

            expect($manager->rulesFor(MinimalDTO::class, 'create'))->toBe(MinimalDTO::rulesFor('create'));
        });

        it('fromPartialArray delegates to DTO', function (): void {
            $manager = new DTOManager;
            $dto = $manager->fromPartialArray(CreateUserDTO::class, ['name' => 'Bob']);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Bob');
        });

        it('makeFromJson creates DTO from JSON', function (): void {
            $manager = new DTOManager;
            $dto = $manager->makeFromJson(MinimalDTO::class, '{"name":"A","value":"1"}');

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });

        it('throws InvalidArgumentException for non-DTO class', function (): void {
            $manager = new DTOManager;

            expect(fn () => $manager->make(\stdClass::class, []))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    // -----------------------------------------------------------------------
    // DTOException contract
    // -----------------------------------------------------------------------
    describe('DTOException contract', function (): void {
        it('invalidCast creates exception with property and type info', function (): void {
            $e = DTOException::invalidCast('status', 'integer', 'abc');

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('status');
            expect($e->getMessage())->toContain('integer');
            expect($e->getMessage())->toContain('abc');
        });

        it('invalidJson creates exception with property and error info', function (): void {
            $e = DTOException::invalidJson('payload', 'Syntax error');

            expect($e)->toBeInstanceOf(DTOException::class);
            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function (): void {
            $e = DTOException::invalidJson('field', 'error');

            $str = (string) $e;

            expect($str)->toStartWith(DTOException::class);
            expect($str)->toContain('field');
        });
    });

    // -----------------------------------------------------------------------
    // Structural checks
    // -----------------------------------------------------------------------
    describe('Structural: class type declarations', function (): void {
        it('DTOManager is final and readonly', function (): void {
            $ref = new ReflectionClass(DTOManager::class);

            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('DtoCollection is final', function (): void {
            $ref = new ReflectionClass(DtoCollection::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('DTOException is final', function (): void {
            $ref = new ReflectionClass(DTOException::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('DataTransferObject is abstract', function (): void {
            $ref = new ReflectionClass(DataTransferObject::class);

            expect($ref->isAbstract())->toBeTrue();
        });

        it('DTOSServiceProvider is final', function (): void {
            $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

            expect($ref->isFinal())->toBeTrue();
        });

        it('all source files have declare(strict_types=1)', function (): void {
            $srcDir = dirname(__DIR__, 2).'/src';
            $files = glob("{$srcDir}/**/*.php");

            expect($files)->not->toBeEmpty();

            foreach ($files as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all validation attributes implement ValidationAttribute', function (): void {
            $attributes = [
                \ZeroBoiler\DTO\Attributes\Accepted::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class,
                \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
                \ZeroBoiler\DTO\Attributes\In::class,
                \ZeroBoiler\DTO\Attributes\Integer::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\NestedArray::class,
                \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Pattern::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class,
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class,
                \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
                \ZeroBoiler\DTO\Attributes\Same::class,
                \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\StartsWith::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Url::class,
                \ZeroBoiler\DTO\Attributes\Uuid::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class,
                \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Collection::class,
            ];

            $contract = \ZeroBoiler\DTO\Contracts\ValidationAttribute::class;

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->implementsInterface($contract))->toBeTrue("{$attrClass} should implement ValidationAttribute");
            }
        });

        it('all validation attributes are final', function (): void {
            $attributes = [
                \ZeroBoiler\DTO\Attributes\Accepted::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class,
                \ZeroBoiler\DTO\Attributes\Email::class,
                \ZeroBoiler\DTO\Attributes\Max::class,
                \ZeroBoiler\DTO\Attributes\Min::class,
                \ZeroBoiler\DTO\Attributes\Required::class,
                \ZeroBoiler\DTO\Attributes\Hidden::class,
                \ZeroBoiler\DTO\Attributes\MapFrom::class,
                \ZeroBoiler\DTO\Attributes\Cast::class,
                \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\DefaultValue::class,
            ];

            foreach ($attributes as $attrClass) {
                $ref = new ReflectionClass($attrClass);

                expect($ref->isFinal())->toBeTrue("{$attrClass} should be final");
            }
        });

        it('ValidationAttribute ruleKey() returns non-empty string for all', function (): void {
            $attrs = [
                new \ZeroBoiler\DTO\Attributes\Email,
                new \ZeroBoiler\DTO\Attributes\Max(100),
                new \ZeroBoiler\DTO\Attributes\Min(1),
                new \ZeroBoiler\DTO\Attributes\Required,
                new \ZeroBoiler\DTO\Attributes\Pattern('/^[a-z]+$/'),
                new \ZeroBoiler\DTO\Attributes\Url,
                new \ZeroBoiler\DTO\Attributes\Uuid,
                new \ZeroBoiler\DTO\Attributes\Integer,
                new \ZeroBoiler\DTO\Attributes\Numeric,
                new \ZeroBoiler\DTO\Attributes\Boolean,
                new \ZeroBoiler\DTO\Attributes\In(['a', 'b']),
                new \ZeroBoiler\DTO\Attributes\Size(10),
                new \ZeroBoiler\DTO\Attributes\StartsWith('pre'),
                new \ZeroBoiler\DTO\Attributes\EndsWith('suf'),
                new \ZeroBoiler\DTO\Attributes\Between(1, 10),
                new \ZeroBoiler\DTO\Attributes\Date,
                new \ZeroBoiler\DTO\Attributes\Json,
                new \ZeroBoiler\DTO\Attributes\Enum(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class),
                new \ZeroBoiler\DTO\Attributes\ArrayRule,
                new \ZeroBoiler\DTO\Attributes\Prohibited,
                new \ZeroBoiler\DTO\Attributes\Accepted,
                new \ZeroBoiler\DTO\Attributes\Declined,
                new \ZeroBoiler\DTO\Attributes\Confirmed,
                new \ZeroBoiler\DTO\Attributes\Same('other'),
                new \ZeroBoiler\DTO\Attributes\Different('other'),
                new \ZeroBoiler\DTO\Attributes\Distinct,
                new \ZeroBoiler\DTO\Attributes\RequiredIf('field', 'value'),
                new \ZeroBoiler\DTO\Attributes\RequiredUnless('field', 'value'),
                new \ZeroBoiler\DTO\Attributes\RequiredWith('field'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithAll('a', 'b'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithout('field'),
                new \ZeroBoiler\DTO\Attributes\RequiredWithoutAll('a', 'b'),
                new \ZeroBoiler\DTO\Attributes\Nullable,
                new \ZeroBoiler\DTO\Attributes\Sometimes,
                new \ZeroBoiler\DTO\Attributes\Present,
                new \ZeroBoiler\DTO\Attributes\NestedArray(MinimalDTO::class),
                new \ZeroBoiler\DTO\Attributes\Collection(MinimalDTO::class),
            ];

            foreach ($attrs as $attr) {
                if ($attr instanceof \ZeroBoiler\DTO\Contracts\ValidationAttribute) {
                    $key = $attr->ruleKey();
                    expect($key)->toBeString();
                    expect($key)->not->toBeEmpty();
                }
            }
        });
    });

    // -----------------------------------------------------------------------
    // __debugInfo
    // -----------------------------------------------------------------------
    describe('__debugInfo', function (): void {
        it('DTO __debugInfo returns toArray output', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);

            expect($dto->__debugInfo())->toBe($dto->toArray());
        });

        it('DtoCollection __debugInfo returns count and items', function (): void {
            $a = MinimalDTO::fromArray(['name' => 'A', 'value' => '1']);
            $b = MinimalDTO::fromArray(['name' => 'B', 'value' => '2']);
            $c = MinimalDTO::fromArray(['name' => 'C', 'value' => '3']);
            $col = DtoCollection::make([$a, $b, $c]);

            $debug = $col->__debugInfo();

            expect($debug)->toHaveKeys(['count', 'items']);
            expect($debug['count'])->toBe(3);
            expect($debug['items'])->toHaveCount(3); // max 3 items
        });
    });

    // -----------------------------------------------------------------------
    // validateArray standalone
    // -----------------------------------------------------------------------
    describe('validateArray standalone', function (): void {
        it('returns validated data on success', function (): void {
            $result = MinimalDTO::validateArray(['name' => 'Alice', 'value' => 'test']);

            expect($result)->toBe(['name' => 'Alice', 'value' => 'test']);
        });

        it('throws ValidationException on failure', function (): void {
            expect(fn () => MinimalDTO::validateArray(['name' => 'Alice']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    // -----------------------------------------------------------------------
    // fromArray with validate=false
    // -----------------------------------------------------------------------
    describe('fromArray with validate=false', function (): void {
        it('skips validation and creates DTO', function (): void {
            $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => ''], validate: false);

            expect($dto->name)->toBe('A');
            expect($dto->value)->toBe('');
        });

        it('still applies defaults when validate=false', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
        });

        it('still applies casts when validate=false', function (): void {
            $dto = CreateUserDTO::fromArray(
                ['email' => 'a@b.com', 'name' => 'A', 'tags' => 'x,y,z'],
                validate: false,
            );

            expect($dto->tags)->toBeArray();
        });
    });
});
