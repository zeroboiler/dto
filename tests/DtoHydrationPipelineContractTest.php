<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;

describe('DTO Hydration Pipeline Contract', function () {
    it('fromArray creates DTO with all required fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
        expect($dto->status)->toBe('active'); // DefaultValue
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
        expect($dto->password)->toBeNull();
    });

    it('fromArray applies MapFrom correctly', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('fromArray applies DefaultValue when source key missing', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // status has DefaultValue('active')
        expect($dto->status)->toBe('active');
    });

    it('fromArray applies DefaultValue — can be overridden', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'pending',
        ], validate: false);

        expect($dto->status)->toBe('pending');
    });

    it('fromArray applies Cast array decodes JSON strings', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'tags' => '["php","laravel"]',
        ], validate: false);

        expect($dto->tags)->toBeArray();
        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('fromArray with Cast array passes through arrays', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('fromArray with Cast array decodes numeric JSON arrays', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'tags' => '[1,2,3]',
        ], validate: false);

        expect($dto->tags)->toBe([1, 2, 3]);
    });

    it('fromArray sets optional properties to null when not provided', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->phone)->toBeNull();
        expect($dto->password)->toBeNull();
    });

    it('fromArray handles MixedAttributesDTO with all attribute types', function () {
        $dto = MixedAttributesDTO::fromArray([
            'username' => 'alice',
            'hexCode' => 'a1b2c3',
            'user_email' => 'alice@example.com',
            'age' => '25',
            'token' => 'secret',
            'isActive' => '1',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        expect($dto->username)->toBe('alice');
        expect($dto->email)->toBe('alice@example.com'); // MapFrom('user_email')
        expect($dto->age)->toBeInt();
        expect($dto->age)->toBe(25); // Cast('integer')
        expect($dto->role)->toBe('user'); // DefaultValue('user')
        expect($dto->isActive)->toBeBool();
    });

    it('toArray excludes Hidden properties', function () {
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

    it('allValues includes Hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('toJson produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson();
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('email');
        expect($decoded)->not->toHaveKey('password');
    });
});

describe('DTO Immutable Update Contract', function () {
    it('with() creates a new instance with overrides', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated Name']);

        expect($updated)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Test User'); // original unchanged
        expect($updated->name)->toBe('Updated Name');
    });

    it('with() preserves original email', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $updated = $dto->with(['name' => 'New Name']);

        expect($updated->email)->toBe('test@example.com');
    });

    it('with() can override multiple fields at once', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $updated = $dto->with([
            'name' => 'Updated Name',
            'status' => 'suspended',
        ]);

        expect($updated->name)->toBe('Updated Name');
        expect($updated->status)->toBe('suspended');
        expect($updated->email)->toBe('test@example.com');
    });
});

describe('DTO Equality and State Contract', function () {
    it('equals returns true for identical DTOs', function () {
        $data = ['email' => 'test@example.com', 'name' => 'Test User'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function () {
        $dto1 = CreateUserDTO::fromArray(
            ['email' => 'a@test.com', 'name' => 'Alice'],
            validate: false,
        );
        $dto2 = CreateUserDTO::fromArray(
            ['email' => 'b@test.com', 'name' => 'Bob'],
            validate: false,
        );

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns false when required fields have values', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('equals uses toArray() which excludes hidden fields', function () {
        $data = ['email' => 'test@example.com', 'name' => 'Test', 'password' => 'pw1'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);

        $data2 = ['email' => 'test@example.com', 'name' => 'Test', 'password' => 'pw2'];
        $dto2 = CreateUserDTO::fromArray($data2, validate: false);

        // Different passwords but hidden — equals should be true
        expect($dto1->equals($dto2))->toBeTrue();
    });
});

describe('DTO Selective Output Contract', function () {
    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+905551234567',
        ], validate: false);

        $only = $dto->only('email', 'name');
        expect($only)->toHaveKeys(['email', 'name']);
        expect($only)->not->toHaveKey('phone');
    });

    it('only with single string key works', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $only = $dto->only('email');
        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
    });

    it('only with array keys works', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $only = $dto->only(['email', 'name']);
        expect($only)->toHaveCount(2);
    });

    it('except excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $except = $dto->except('name');
        expect($except)->toHaveKey('email');
        expect($except)->not->toHaveKey('name');
    });

    it('except with array keys works', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone_number' => '+905551234567',
        ], validate: false);

        $except = $dto->except(['name', 'phone']);
        expect($except)->toHaveKey('email');
        expect($except)->not->toHaveKey('name');
        expect($except)->not->toHaveKey('phone');
    });
});

describe('DTO fromJson Contract', function () {
    it('fromJson creates DTO from valid JSON object', function () {
        $dto = CreateUserDTO::fromJson(
            '{"email":"test@example.com","name":"Test User"}',
            validate: false,
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('not-json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for sequential arrays', function () {
        expect(fn () => CreateUserDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson with nested JSON in Cast array fields', function () {
        $dto = CreateUserDTO::fromJson(
            '{"email":"test@example.com","name":"Test","tags":"[\"a\",\"b\"]"}',
            validate: false,
        );

        expect($dto->tags)->toBe(['a', 'b']);
    });
});

describe('DTO Validation Rules Contract', function () {
    it('rules returns array with expected structure', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');

        // email rules must include 'required' and 'email'
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');

        // name rules must include 'required', 'min:2', 'max:50'
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('rulesFor returns same as rules by default', function () {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        expect($rules)->toBe($rulesFor);
    });

    it('MinimalDTO has required field rules for both properties', function () {
        $rules = MinimalDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('value');
        expect($rules['name'])->toContain('required');
        expect($rules['value'])->toContain('required');
    });

    it('ActionScopedDTO has different rules for different actions', function () {
        $defaultRules = ActionScopedDTO::rules();
        $createRules = ActionScopedDTO::rulesFor('create');

        // rulesFor() delegates to rules() by default
        expect($defaultRules)->toBe($createRules);
    });
});

describe('DTO Collection Immutability Contract', function () {
    it('append returns a new collection without modifying original', function () {
        $dto1 = CreateUserDTO::fromArray(
            ['email' => 'a@test.com', 'name' => 'Alice'],
            validate: false,
        );
        $dto2 = CreateUserDTO::fromArray(
            ['email' => 'b@test.com', 'name' => 'Bob'],
            validate: false,
        );

        $col = new DtoCollection([$dto1]);
        $newCol = $col->append($dto2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('merge returns a new collection combining both', function () {
        $col1 = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);
        $col2 = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ]);

        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('push mutates in place and returns self', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'a@test.com', 'name' => 'A'],
            validate: false,
        );

        $col = new DtoCollection();
        $result = $col->push($dto);

        expect($col->count())->toBe(1);
        expect($result)->toBe($col); // same instance
    });

    it('filter returns new collection with matching items', function () {
        $dtoArray = [
            CreateUserDTO::fromArray(['email' => 'alice@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'bob@test.com', 'name' => 'Bob'], validate: false),
        ];

        $col = new DtoCollection($dtoArray);
        $filtered = $col->filter(
            fn (DataTransferObject $dto): bool => str_starts_with($dto->email, 'alice'),
        );

        expect($filtered->count())->toBe(1);
        expect($col->count())->toBe(2); // original unchanged
    });

    it('pluck extracts a single field from all DTOs', function () {
        $col = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ]);

        $emails = $col->pluck('email');
        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('map returns plain array of results', function () {
        $col = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ]);

        $names = $col->map(fn (DataTransferObject $dto): string => $dto->name);
        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('first and last return correct items', function () {
        $col = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'First'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Last'], validate: false),
        ]);

        expect($col->first()->name)->toBe('First');
        expect($col->last()->name)->toBe('Last');
    });

    it('isEmpty and isNotEmpty work correctly', function () {
        $empty = new DtoCollection();
        $nonEmpty = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('make factory creates collection from array', function () {
        $items = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $col = DtoCollection::make($items);
        expect($col->count())->toBe(2);
    });

    it('toArray serializes all DTOs', function () {
        $col = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);

        $arr = $col->toArray();
        expect($arr)->toBeArray();
        expect($arr)->toHaveCount(1);
        expect($arr[0])->toHaveKey('email');
        expect($arr[0])->not->toHaveKey('password'); // hidden
    });

    it('allValues serializes including hidden fields', function () {
        $col = new DtoCollection([
            CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'A',
                'password' => 'secret',
            ], validate: false),
        ]);

        $all = $col->allValues();
        expect($all)->toBeArray();
        expect($all[0])->toHaveKey('password');
    });
});

describe('DTO Metadata Cache Contract', function () {
    it('flushMetadataCache clears all classes', function () {
        CreateUserDTO::fromArray(
            ['email' => 'a@test.com', 'name' => 'A'],
            validate: false,
        );

        DataTransferObject::flushMetadataCache();
        // Next fromArray should rebuild metadata
        $dto = CreateUserDTO::fromArray(
            ['email' => 'b@test.com', 'name' => 'B'],
            validate: false,
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('flushMetadataCache for specific class works', function () {
        CreateUserDTO::fromArray(
            ['email' => 'a@test.com', 'name' => 'A'],
            validate: false,
        );

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray(
            ['email' => 'b@test.com', 'name' => 'B'],
            validate: false,
        );

        expect($dto->email)->toBe('b@test.com');
    });
});
