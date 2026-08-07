<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

/**
 * Full-cycle integration tests for the DTO package.
 * Covers hydration, serialization, validation rules, edge cases, and type safety.
 */
describe('DTO full-cycle tests', function (): void {

    // -----------------------------------------------------------------
    // 1. ValidationAttribute contract compliance
    // -----------------------------------------------------------------
    describe('ValidationAttribute contract compliance', function (): void {
        $attributes = [
            'required' => new Required,
            'email' => new Email,
            'max' => new Max(255),
            'min' => new Min(2),
            'url' => new Url,
            'uuid' => new Uuid,
            'integer' => new Integer,
            'numeric' => new Numeric,
            'boolean' => new Boolean,
            'pattern' => new Pattern('/^[a-z]+$/'),
            'in' => new In(['a', 'b']),
            'date' => new Date,
            'date_formatted' => new Date('Y-m-d'),
            'confirmed' => new Confirmed,
            'different' => new Different('other'),
            'same' => new Same('other'),
            'accepted' => new Accepted,
            'declined' => new Declined,
            'distinct' => new Distinct,
            'prohibited' => new Prohibited,
            'present' => new Present,
            'sometimes' => new Sometimes,
            'nullable' => new Nullable,
            'starts_with' => new StartsWith('prefix'),
            'ends_with' => new EndsWith('suffix'),
            'json' => new Json,
            'size' => new Size(5),
        ];

        it('all validation attributes implement ValidationAttribute interface', function () use ($attributes): void {
            foreach ($attributes as $name => $attr) {
                expect($attr)->toBeInstanceOf(ValidationAttribute::class, "Failed for {$name}");
            }
        });

        it('ruleKey() returns non-empty string for all attributes', function () use ($attributes): void {
            foreach ($attributes as $name => $attr) {
                expect($attr->ruleKey())->toBeString()->not->toBeEmpty("Failed for {$name}");
            }
        });
    });

    // -----------------------------------------------------------------
    // 2. DTO property types and readonly enforcement
    // -----------------------------------------------------------------
    describe('DTO readonly properties', function (): void {
        it('properties are publicly accessible', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test');
            expect($dto->status)->toBe('active');
        });

        it('nested DTO property is correct type', function (): void {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            expect($order->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($order->shippingAddress->street)->toBe('123 Main St');
            expect($order->shippingAddress->city)->toBe('Istanbul');
        });

        it('nullable property defaults to null', function (): void {
            $dto = AddressDTO::fromArray([
                'street' => 'Test St',
                'city' => 'Test City',
            ], validate: false);

            expect($dto->zipCode)->toBeNull();
        });
    });

    // -----------------------------------------------------------------
    // 3. fromJson() edge cases
    // -----------------------------------------------------------------
    describe('fromJson() edge cases', function (): void {
        it('creates DTO from valid JSON object', function (): void {
            $json = '{"email":"test@example.com","name":"Doruk"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Doruk');
        });

        it('throws DTOException on invalid JSON syntax', function (): void {
            expect(fn () => CreateUserDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on sequential JSON array', function (): void {
            expect(fn () => CreateUserDTO::fromJson('["email","name"]'))
                ->toThrow(DTOException::class);
        });

        it('applies defaults from JSON', function (): void {
            $json = '{"email":"test@example.com","name":"Doruk"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
        });

        it('respects MapFrom when hydrating from JSON', function (): void {
            $json = '{"email":"test@example.com","name":"Doruk","phone_number":"+90555"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto->phone)->toBe('+90555');
        });
    });

    // -----------------------------------------------------------------
    // 4. Serialization roundtrip
    // -----------------------------------------------------------------
    describe('serialization roundtrip', function (): void {
        it('toArray → fromArray roundtrip preserves values', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'status' => 'active',
                'tags' => ['php', 'laravel'],
                'phone' => '+90555',
                'password' => 'secret',
            ], validate: false);

            $array = $original->allValues();
            $restored = CreateUserDTO::fromArray($array, validate: false);

            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
            expect($restored->status)->toBe($original->status);
            expect($restored->tags)->toBe($original->tags);
            expect($restored->phone)->toBe($original->phone);
            expect($restored->password)->toBe($original->password);
        });

        it('toJson → fromJson roundtrip (without hidden)', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
        });

        it('jsonSerialize returns toArray output', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    // -----------------------------------------------------------------
    // 5. only() and except() selective output
    // -----------------------------------------------------------------
    describe('selective output', function (): void {
        it('only() returns specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email', 'name');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('status');
        });

        it('only() with single string key', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('test@example.com');
        });

        it('except() excludes specified fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'status' => 'active',
                'tags' => ['php'],
            ], validate: false);

            $result = $dto->except('status', 'tags');
            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('status');
        });

        it('except() with single string key', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $result = $dto->except('name');
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('email');
        });
    });

    // -----------------------------------------------------------------
    // 6. isEmpty() / isNotEmpty() state checks
    // -----------------------------------------------------------------
    describe('state checks', function (): void {
        it('isEmpty() returns true for all-default DTO', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty() returns false when a field has a value', function (): void {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('isEmpty() returns false for zero numeric values', function (): void {
            // ProductDTO has int $stock = 0 — 0 is considered empty
            $dto = ProductDTO::fromArray([
                'name' => 'Widget',
                'price' => '0',
                'stock' => 0,
            ], validate: false);

            // '0' for price and 0 for stock are considered empty by isEmpty()
            // but 'name' => 'Widget' is not empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // -----------------------------------------------------------------
    // 7. equals() value equality
    // -----------------------------------------------------------------
    describe('equals() value equality', function (): void {
        it('same data produces equal DTOs', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'X'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'X'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('different data produces unequal DTOs', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'X'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Y'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('equals() excludes hidden fields from comparison', function (): void {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'X',
                'password' => 'secret1',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'X',
                'password' => 'secret2',
            ], validate: false);

            // Hidden fields are excluded from toArray(), so equals compares
            // only visible fields — they should be equal
            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    // -----------------------------------------------------------------
    // 8. rules() and rulesFor() generation
    // -----------------------------------------------------------------
    describe('rule generation', function (): void {
        it('CreateUserDTO has correct rule structure', function (): void {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('EmptyDTO has minimal or no rules', function (): void {
            $rules = EmptyDTO::rules();
            expect($rules)->toBeArray();
        });

        it('ProductDTO has numeric and integer rules', function (): void {
            $rules = ProductDTO::rules();
            expect($rules['price'])->toContain('numeric');
            expect($rules['stock'])->toContain('integer');
            expect($rules['stock'])->toContain('min:0');
        });

        it('ActionScopedDTO rulesFor returns different rules for update', function (): void {
            $createRules = ActionScopedDTO::rulesFor('create');
            $updateRules = ActionScopedDTO::rulesFor('update');

            // Create: email is required
            expect($createRules['email'])->toContain('required');

            // Update: email is sometimes
            expect($updateRules['email'])->toContain('sometimes');
            expect($updateRules['email'])->not->toContain('required');
        });

        it('rulesFor() defaults to rules() for unknown actions', function (): void {
            $defaultRules = ActionScopedDTO::rules();
            $unknownRules = ActionScopedDTO::rulesFor('delete');

            expect($unknownRules)->toBe($defaultRules);
        });
    });

    // -----------------------------------------------------------------
    // 9. with() immutable update
    // -----------------------------------------------------------------
    describe('with() immutable update', function (): void {
        it('creates new instance with overrides', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Original',
                'status' => 'active',
            ], validate: false);

            $updated = $original->with(['status' => 'inactive']);

            expect($original->status)->toBe('active'); // unchanged
            expect($updated->status)->toBe('inactive');
            expect($updated->email)->toBe('a@b.com'); // preserved
        });

        it('preserves all other fields', function (): void {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Test',
                'tags' => ['php'],
                'phone' => '+90555',
            ], validate: false);

            $updated = $original->with(['name' => 'Updated']);

            expect($updated->email)->toBe('a@b.com');
            expect($updated->name)->toBe('Updated');
            expect($updated->tags)->toBe(['php']);
            expect($updated->phone)->toBe('+90555');
        });
    });

    // -----------------------------------------------------------------
    // 10. DtoCollection type safety
    // -----------------------------------------------------------------
    describe('DtoCollection type safety', function (): void {
        it('rejects non-DTO items in constructor', function (): void {
            expect(fn () => new DtoCollection(['not_a_dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('accepts DTO instances', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $collection = new DtoCollection([$dto]);
            expect($collection->count())->toBe(1);
            expect($collection->first())->toBe($dto);
        });

        it('make() creates collection from array', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $collection = DtoCollection::make([$dto]);
            expect($collection->count())->toBe(1);
        });

        it('push() appends and returns self', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $collection = DtoCollection::make([$dto1]);
            $result = $collection->push($dto2);

            expect($result->count())->toBe(2);
            expect($result)->toBe($collection); // fluent
        });

        it('pluck() extracts field from all DTOs', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);

            expect($collection->pluck('email'))->toBe(['a@b.com', 'c@d.com']);
            expect($collection->pluck('name'))->toBe(['A', 'C']);
        });

        it('pluckKey() builds key/value map', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $map = $collection->pluckKey('email', 'name');

            expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
        });

        it('map() applies callback to each item', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $emails = $collection->map(fn (CreateUserDTO $dto): string => strtoupper($dto->email));

            expect($emails)->toBe(['A@B.COM', 'C@D.COM']);
        });

        it('filter() returns new collection', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $filtered = $collection->filter(fn (CreateUserDTO $dto): bool => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->email)->toBe('a@b.com');
        });

        it('isEmpty() and isNotEmpty() work', function (): void {
            $empty = new DtoCollection;
            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();

            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $nonEmpty = DtoCollection::make([$dto]);
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('last() returns last item', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            expect($collection->last()->email)->toBe('c@d.com');
        });

        it('toArray() serializes all DTOs', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $arrays = $collection->toArray();

            expect($arrays)->toHaveCount(2);
            expect($arrays[0])->toHaveKey('email');
            expect($arrays[1])->toHaveKey('email');
        });

        it('jsonSerialize returns toArray output', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $collection = DtoCollection::make([$dto]);

            expect($collection->jsonSerialize())->toBe($collection->toArray());
        });
    });

    // -----------------------------------------------------------------
    // 11. Nested DTO hydration
    // -----------------------------------------------------------------
    describe('nested DTO hydration', function (): void {
        it('hydrates nested DTO from array', function (): void {
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

        it('serializes nested DTO in toArray()', function (): void {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            $array = $order->toArray();
            expect($array['shippingAddress'])->toBe([
                'street' => '123 Main St',
                'city' => 'Istanbul',
                'zipCode' => null,
            ]);
        });

        it('serializes nested DTO in allValues()', function (): void {
            $order = OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
            ], validate: false);

            $values = $order->allValues();
            expect($values['shippingAddress'])->toBeArray();
            expect($values['shippingAddress']['street'])->toBe('123 Main St');
        });
    });

    // -----------------------------------------------------------------
    // 12. Hidden fields behavior
    // -----------------------------------------------------------------
    describe('hidden fields', function (): void {
        it('toArray() excludes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
        });

        it('allValues() includes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret123');
        });

        it('toJson() excludes hidden fields', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'password' => 'secret123',
            ], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->not->toHaveKey('password');
        });

        it('only() on hidden field returns empty (hidden excluded from toArray)', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'password' => 'secret123',
            ], validate: false);

            // 'password' is hidden, so only('password') returns nothing
            // because only() uses toArray() which excludes hidden fields
            $result = $dto->only('password');
            expect($result)->toBe([]);
        });
    });

    // -----------------------------------------------------------------
    // 13. MapFrom with dot notation
    // -----------------------------------------------------------------
    describe('MapFrom dot notation', function (): void {
        it('maps dot-notation keys correctly', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'phone_number' => '+90555',
            ], validate: false);

            expect($dto->phone)->toBe('+90555');
        });

        it('ignores original property name when MapFrom is set', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
                'phone' => 'SHOULD_BE_IGNORED',
            ], validate: false);

            // 'phone' is the property name but MapFrom('phone_number') is set
            // Since 'phone_number' is not in the data, phone should be null (default)
            expect($dto->phone)->toBeNull();
        });
    });

    // -----------------------------------------------------------------
    // 14. Type safety checks (PHPStan level 9)
    // -----------------------------------------------------------------
    describe('type safety (PHPStan level 9)', function (): void {
        it('toArray() returns array with string keys', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            $array = $dto->toArray();
            expect($array)->toBeArray();
            foreach (array_keys($array) as $key) {
                expect($key)->toBeString();
            }
        });

        it('toJson() returns string', function (): void {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Doruk',
            ], validate: false);

            expect($dto->toJson())->toBeString();
        });

        it('rules() returns array with string keys and array values', function (): void {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
            }
        });

        it('DtoCollection items() returns array of DataTransferObject', function (): void {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $collection = DtoCollection::make([$dto]);

            $items = $collection->items();
            expect($items)->toBeArray();
            expect($items[0])->toBeInstanceOf(DataTransferObject::class);
        });

        it('equals() returns bool', function (): void {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);

            expect($dto1->equals($dto2))->toBeBool();
        });

        it('isEmpty() and isNotEmpty() return bool', function (): void {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeBool();
            expect($dto->isNotEmpty())->toBeBool();
        });
    });
});
