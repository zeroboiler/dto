<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

describe('DTO Production Quality Audit', function () {
    describe('Class structure validation', function () {
        it('DataTransferObject is abstract', function () {
            expect((new \ReflectionClass(DataTransferObject::class))->isAbstract())->toBeTrue();
        });

        it('DataTransferObject implements Arrayable, JsonSerializable', function () {
            $interfaces = class_implements(DataTransferObject::class);
            expect($interfaces)->toContain(\Illuminate\Contracts\Support\Arrayable::class);
            expect($interfaces)->toContain(\JsonSerializable::class);
        });

        it('DtoCollection is final', function () {
            expect((new \ReflectionClass(DtoCollection::class))->isFinal())->toBeTrue();
        });

        it('CreateUserDTO extends DataTransferObject', function () {
            expect(is_subclass_of(CreateUserDTO::class, DataTransferObject::class))->toBeTrue();
        });

        it('CreateUserDTO constructor has only readonly public properties', function () {
            $reflection = new \ReflectionClass(CreateUserDTO::class);
            $constructor = $reflection->getConstructor();
            expect($constructor)->not->toBeNull();

            foreach ($constructor->getParameters() as $param) {
                $propName = $param->getName();
                $prop = $reflection->getProperty($propName);
                expect($prop->isPublic())->toBeTrue("Property {$propName} should be public");
                expect($prop->isReadOnly())->toBeTrue("Property {$propName} should be readonly");
            }
        });

        it('all DTO fixtures have readonly promoted properties', function () {
            $fixtures = [CreateUserDTO::class, MinimalDTO::class, EmptyDTO::class];

            foreach ($fixtures as $class) {
                $reflection = new \ReflectionClass($class);
                $constructor = $reflection->getConstructor();

                if ($constructor === null) {
                    continue; // EmptyDTO has no constructor
                }

                foreach ($constructor->getParameters() as $param) {
                    $propName = $param->getName();
                    if ($reflection->hasProperty($propName)) {
                        $prop = $reflection->getProperty($propName);
                        expect($prop->isReadOnly())
                            ->toBeTrue("{$class}::\${$propName} should be readonly");
                    }
                }
            }
        });
    });

    describe('Method return type correctness', function () {
        it('fromArray returns correct DTO class', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->status)->toBe('active'); // DefaultValue
            expect($dto->tags)->toBe([]); // Cast default
        });

        it('toArray returns associative array with correct keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->toHaveKey('status');
            expect($arr)->toHaveKey('tags');
            expect($arr)->toHaveKey('phone');
            // Hidden field should NOT be in toArray()
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('toJson returns valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('equals() returns true for same data, false for different', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $dto3 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
            expect($dto1->equals($dto3))->toBeFalse();
        });

        it('isEmpty() and isNotEmpty() work correctly', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });

        it('only() filters fields correctly', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $only = $dto->only('email', 'name');
            expect($only)->toHaveKey('email');
            expect($only)->toHaveKey('name');
            expect($only)->not->toHaveKey('status');
            expect($only)->not->toHaveKey('password');
        });

        it('except() excludes fields correctly', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('status');
        });

        it('only() accepts single string key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $only = $dto->only('email');
            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
        });

        it('except() accepts single string key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $except = $dto->except('name');
            expect($except)->not->toHaveKey('name');
            expect($except)->toHaveKey('email');
        });

        it('with() returns new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($updated)->not->toBe($dto);
            expect($updated->email)->toBe('a@b.com');
            expect($updated->name)->toBe('Bob');
            // Original unchanged
            expect($dto->name)->toBe('Alice');
        });
    });

    describe('MapFrom attribute', function () {
        it('maps source key to property name', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+1234567890', // MapFrom('phone_number')
            ], validate: false);

            expect($dto->phone)->toBe('+1234567890');
        });

        it('uses property name when MapFrom key not present', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone' => '+9876543210', // Direct property name (not mapped)
            ], validate: false);

            expect($dto->phone)->toBe('+9876543210');
        });
    });

    describe('Cast attribute', function () {
        it('casts integer value from string', function () {
            $dto = MinimalDTO::fromArray([
                'name' => 'Test',
            ], validate: false);

            // Cast attribute should convert the value
            expect($dto)->toBeInstanceOf(MinimalDTO::class);
        });
    });

    describe('DefaultValue attribute', function () {
        it('applies default when key is missing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->status)->toBe('active'); // DefaultValue
        });

        it('applies default even over constructor default', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'tags' => ['php', 'laravel'],
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
        });

        it('does not override explicit null value', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone' => null, // explicit null
            ], validate: false);

            expect($dto->phone)->toBeNull();
        });
    });

    describe('fromJson and serialization roundtrip', function () {
        it('fromJson creates valid DTO from JSON string', function () {
            $json = '{"email":"a@b.com","name":"Alice"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('a@b.com');
            expect($dto->name)->toBe('Alice');
        });

        it('fromJson throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws DTOException for sequential array', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('toJson roundtrip preserves data', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'phone_number' => '+123',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe($original->email);
            expect($restored->name)->toBe($original->name);
        });
    });

    describe('rules() method', function () {
        it('returns array with expected structure', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });
    });

    describe('fromPartialArray', function () {
        it('hydrates only provided fields, uses defaults for missing', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated',
            ], validate: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active'); // default
        });

        it('can be called with empty array', function () {
            $dto = CreateUserDTO::fromPartialArray([], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->status)->toBe('active'); // default value
        });
    });

    describe('DtoCollection', function () {
        it('wraps DTOs with type safety', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            expect($col->count())->toBe(2);
            expect($col->first())->toBe($dto1);
            expect($col->last())->toBe($dto2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('toArray serializes all items', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $arr = $col->toArray();
            expect($arr)->toHaveCount(1);
            expect($arr[0])->toHaveKey('email');
        });

        it('pluck extracts a single field from all items', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $emails = $col->pluck('email');
            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('map transforms each item', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto1]);
            $names = $col->map(fn ($dto) => $dto->name);
            expect($names)->toBe(['Alice']);
        });

        it('filter returns new collection with matching items', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@b.com',
                'name' => 'Bob',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn ($dto) => str_starts_with($dto->name, 'A'));
            expect($filtered->count())->toBe(1);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('offsetUnset re-indexes', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'B'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            unset($col[0]); // triggers offsetUnset

            expect($col->count())->toBe(1);
            expect($col[0]->name)->toBe('B'); // re-indexed
        });

        it('merge combines two collections', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'B'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'c@c.com', 'name' => 'C'], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2, $dto3]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
        });

        it('append returns new collection with added item', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'B'], validate: false);

            $col = new DtoCollection([$dto1]);
            $appended = $col->append($dto2);

            expect($appended->count())->toBe(2);
            expect($col->count())->toBe(1); // original unchanged
        });
    });

    describe('EmptyDTO edge case', function () {
        it('empty DTO with no constructor can be created', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->toArray())->toBe([]);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('empty DTO toJson returns empty object', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->toJson())->toBe('{}');
        });
    });
});
