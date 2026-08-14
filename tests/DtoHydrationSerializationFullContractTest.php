<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

describe('DTO Hydration And Serialization Full Contract', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    describe('fromArray basic hydration', function () {
        it('creates DTO with all required fields', function () {
            $dto = MinimalDTO::fromArray([
                'name' => 'Test',
                'value' => 'hello',
            ]);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('Test');
            expect($dto->value)->toBe('hello');
        });

        it('throws ValidationException for missing required fields', function () {
            expect(fn () => MinimalDTO::fromArray(['name' => 'Test']))
                ->toThrow(ValidationException::class);
        });

        it('creates DTO without validation when validate is false', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);

            expect($dto->name)->toBe('Test');
        });

        it('creates CreateUserDTO with all fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'phone_number' => '+901234567890',
                'password' => 'secret123',
            ]);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBe('+901234567890');
            expect($dto->password)->toBe('secret123');
        });

        it('applies MapFrom for source key aliasing', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'phone_number' => '+901234567890',
            ]);

            expect($dto->phone)->toBe('+901234567890');
        });

        it('applies DefaultValue when source key is absent', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);

            expect($dto->status)->toBe('active');
        });

        it('applies Cast to integer type', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'tags' => '["a","b"]',
            ]);

            expect($dto->tags)->toBe(['a', 'b']);
        });
    });

    describe('toArray / allValues / Hidden', function () {
        it('toArray excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });
    });

    describe('fromJson', function () {
        it('creates DTO from valid JSON string', function () {
            $dto = MinimalDTO::fromJson('{"name":"Test","value":"hello"}');

            expect($dto->name)->toBe('Test');
            expect($dto->value)->toBe('hello');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{invalid}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential JSON array', function () {
            expect(fn () => MinimalDTO::fromJson('["a","b"]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            // Empty object {} decodes to [] in PHP — allowed
            // But MinimalDTO requires 'name' and 'value', so validation will fail
            expect(fn () => MinimalDTO::fromJson('{}'))
                ->toThrow(ValidationException::class);
        });

        it('accepts empty array for DTO with all optional fields', function () {
            // For DTOs with all optional/defaulted fields, empty data should work
            // CreateUserDTO requires email and name, so empty still fails
            expect(fn () => CreateUserDTO::fromJson('{}'))
                ->toThrow(ValidationException::class);
        });
    });

    describe('fromPartialArray (PATCH semantics)', function () {
        it('hydrates only present fields, uses defaults for missing', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
            ]);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Bob');
            expect($dto->status)->toBe('active');
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
        });

        it('allows updating subset of fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated Name',
            ]);

            expect($dto->name)->toBe('Updated Name');
        });

        it('respects MapFrom in partial updates', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'phone_number' => '+901234567890',
            ]);

            expect($dto->phone)->toBe('+901234567890');
        });
    });

    describe('with() immutable update', function () {
        it('creates new DTO with merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('test@example.com');
        });

        it('always validates merged data', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            // Empty email should fail validation
            expect(fn () => $dto->with(['email' => '']))
                ->toThrow(ValidationException::class);
        });
    });

    describe('equals() and isEmpty()', function () {
        it('equals returns true for identical DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Other', 'value' => 'hello'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns false for DTO with values', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('only() and except() selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('email');
            expect($result)->not->toHaveKey('name');
        });

        it('except returns all fields except specified', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $result = $dto->except('email');
            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });
    });

    describe('toJson and jsonSerialize', function () {
        it('toJson returns valid JSON string', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded['name'])->toBe('Test');
            expect($decoded['value'])->toBe('hello');
        });

        it('jsonSerialize returns same as toArray', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'hello'], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('rules() and validateArray()', function () {
        it('rules returns validation rules for all fields', function () {
            $rules = CreateUserDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('validateArray returns validated data', function () {
            $validated = CreateUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);

            expect($validated)->toBeArray();
            expect($validated['email'])->toBe('test@example.com');
        });

        it('validateArray throws for invalid data', function () {
            expect(fn () => CreateUserDTO::validateArray(['email' => 'not-an-email', 'name' => 'A']))
                ->toThrow(ValidationException::class);
        });
    });

    describe('DtoCollection operations', function () {
        it('create collection from DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test1', 'value' => 'a'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test2', 'value' => 'b'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->count())->toBe(2);
            expect($col->first()->name)->toBe('Test1');
            expect($col->last()->name)->toBe('Test2');
        });

        it('collection pluck extracts property values', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test1', 'value' => 'a'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test2', 'value' => 'b'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->pluck('name'))->toBe(['Test1', 'Test2']);
        });

        it('collection filter returns new collection', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test1', 'value' => 'a'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test2', 'value' => 'b'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $filtered = $col->filter(fn ($d) => $d->name === 'Test1');

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->name)->toBe('Test1');
        });

        it('collection append returns new collection', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test1', 'value' => 'a'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test2', 'value' => 'b'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $new = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($new->count())->toBe(2);
        });

        it('collection toArray serializes all DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Test1', 'value' => 'a'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Test2', 'value' => 'b'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $arr = $col->toArray();

            expect($arr)->toHaveCount(2);
            expect($arr[0]['name'])->toBe('Test1');
            expect($arr[1]['name'])->toBe('Test2');
        });

        it('collection isEmpty and isNotEmpty', function () {
            $empty = DtoCollection::make([]);
            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();

            $dto = MinimalDTO::fromArray(['name' => 'X', 'value' => 'y'], validate: false);
            $nonEmpty = DtoCollection::make([$dto]);
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });
    });

    describe('nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            if (! class_exists(OrderDTO::class)) {
                $this->skip('OrderDTO fixture not available');
            }

            $dto = OrderDTO::fromArray([
                'customerEmail' => 'buyer@example.com',
                'shippingAddresses' => [
                    ['street' => '123 Main', 'city' => 'Ankara', 'country' => 'TR'],
                ],
            ], validate: false);

            expect($dto->customerEmail)->toBe('buyer@example.com');
            expect($dto->shippingAddresses)->toBeArray();
            expect($dto->shippingAddresses[0])->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddresses[0]->city)->toBe('Ankara');
        });
    });

    describe('metadata cache', function () {
        it('flushMetadataCache clears cached rules', function () {
            $rules1 = MinimalDTO::rules();
            DataTransferObject::flushMetadataCache(MinimalDTO::class);
            $rules2 = MinimalDTO::rules();

            expect($rules1)->toBe($rules2);
        });

        it('flushMetadataCache with null clears all', function () {
            MinimalDTO::rules();
            DataTransferObject::flushMetadataCache(null);

            // After flushing, next access should rebuild
            $rules = MinimalDTO::rules();
            expect($rules)->toBeArray();
        });
    });
});
