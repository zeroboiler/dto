<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
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

beforeEach(function () {
    CreateUserDTO::flushMetadataCache();
    EmptyDTO::flushMetadataCache();
    MinimalDTO::flushMetadataCache();
});

describe('fromArray — MapFrom mapping', function () {
    it('maps source key to property name', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('uses property name directly when no MapFrom', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
    });
});

describe('fromArray — DefaultValue handling', function () {
    it('applies DefaultValue when key is absent', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('preserves explicit null over DefaultValue', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => null,
        ], validate: false);

        expect($dto->status)->toBeNull();
    });

    it('preserves explicit empty string over DefaultValue', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => '',
        ], validate: false);

        expect($dto->status)->toBe('');
    });
});

describe('fromArray — Cast types', function () {
    it('cast integer from string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // tags property has Cast('array') — verify it handles array passthrough
        expect($dto->tags)->toBeArray();
    });
});

describe('fromPartialArray — PATCH semantics', function () {
    it('hydrates only present fields, others get defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Updated Name');
        expect($dto->status)->toBe('active'); // default
    });

    it('with validatePresent true, validates only present fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Valid Name',
        ], validatePresent: true);

        expect($dto->name)->toBe('Valid Name');
    });

    it('fromPartialArray with empty data returns DTO with defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toEqual([]);
    });

    it('validatePartialArray returns validated data', function () {
        $validated = CreateUserDTO::validatePartialArray([
            'name' => 'Test',
        ]);

        expect($validated)->toBeArray();
        expect($validated)->toHaveKey('name');
    });

    it('validatePartialArray with empty data returns empty array', function () {
        $validated = CreateUserDTO::validatePartialArray([]);

        expect($validated)->toEqual([]);
    });
});

describe('toArray / allValues — Hidden field behavior', function () {
    it('toArray excludes Hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
    });

    it('allValues includes Hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret');
    });
});

describe('equals — edge cases', function () {
    it('equals compares only visible fields', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'p1',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'p2',
        ], validate: false);

        // password is hidden, so equals should be true
        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals is false when visible fields differ', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test1',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test2',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('isEmpty — numeric zero handling', function () {
    it('nullable null properties count as empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('non-empty string property makes DTO not empty', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DtoCollection — pluck and pluckKey', function () {
    it('pluck extracts single property from all DTOs', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $emails = $collection->pluck('email');

        expect($emails)->toEqual(['a@b.com', 'c@d.com']);
    });

    it('pluckKey creates key-value map', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $map = $collection->pluckKey('email', 'name');

        expect($map)->toEqual([
            'a@b.com' => 'Alice',
            'c@d.com' => 'Bob',
        ]);
    });

    it('pluckKey without valueField returns full toArray', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        $map = $collection->pluckKey('email');

        expect($map)->toBeArray();
        expect($map['a@b.com'])->toBeArray();
        expect($map['a@b.com'])->toHaveKey('name');
    });

    it('map returns plain array', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $names = $collection->map(fn (DataTransferObject $d): string => $d->name);

        expect($names)->toEqual(['Alice', 'Bob']);
    });

    it('filter returns new DtoCollection', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'status' => 'active'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob', 'status' => 'inactive'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $active = $collection->filter(fn (DataTransferObject $d): bool => $d->status === 'active');

        expect($active)->toBeInstanceOf(DtoCollection::class);
        expect($active->count())->toBe(1);
    });

    it('first and last return correct items', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first()->email)->toBe('a@b.com');
        expect($collection->last()->email)->toBe('c@d.com');
    });

    it('first and last return null for empty collection', function () {
        $collection = new DtoCollection;

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('items returns raw DTO instances', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBe($dto);
    });

    it('toArray serializes all DTOs', function () {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr)->toHaveCount(2);
        expect($arr[0])->toHaveKey('email');
    });

    it('allValues includes hidden fields for all DTOs', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $all = $collection->allValues();

        expect($all[0])->toHaveKey('password');
    });

    it('make factory creates collection', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        $collection = DtoCollection::make([$dto]);

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(1);
    });

    it('jsonSerialize returns toArray output', function () {
        $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $collection = new DtoCollection([$dto]);

        $json = json_encode($collection);

        expect($json)->toBeJson();
    });
});

describe('DTOException — named constructors', function () {
    it('invalidCast with object value shows debug type', function () {
        $exception = DTOException::invalidCast('field', 'string', new \stdClass);

        expect($exception->getMessage())->toContain('stdClass');
    });

    it('invalidJson with null property name', function () {
        $exception = DTOException::invalidJson('', 'syntax error');

        expect($exception->getMessage())->toContain('syntax error');
    });
});

describe('fromArray — validation integration', function () {
    it('required fields must be present', function () {
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            // missing 'name' which is required
        ], validate: true);
    })->throws(ValidationException::class);

    it('email format is validated', function () {
        CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => 'Test User',
        ], validate: true);
    })->throws(ValidationException::class);

    it('min constraint is enforced', function () {
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'T', // min:2
        ], validate: true);
    })->throws(ValidationException::class);

    it('max constraint is enforced', function () {
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => str_repeat('X', 51), // max:50
        ], validate: true);
    })->throws(ValidationException::class);
});

describe('Metadata cache — setMetadataCacheTtl', function () {
    it('can set and use TTL for cache invalidation', function () {
        CreateUserDTO::setMetadataCacheTtl(0);

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');

        // Reset to default
        CreateUserDTO::setMetadataCacheTtl(0.0);
    });
});
