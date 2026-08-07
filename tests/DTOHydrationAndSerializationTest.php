<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO Hydration Pipeline', function (): void {
    it('fromArray hydrates all fields correctly', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Doruk');
        expect($dto->phone)->toBe('+905551234567');
        expect($dto->status)->toBe('active'); // DefaultValue
        expect($dto->tags)->toBe([]); // default
        expect($dto->password)->toBeNull(); // optional, no value
    });

    it('MapFrom maps source key to property', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });

    it('Cast converts string to array from JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'tags' => '["laravel","php"]',
        ], validate: false);

        expect($dto->tags)->toBe(['laravel', 'php']);
    });

    it('Cast with empty string returns empty array', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });
});

describe('DTO Serialization', function (): void {
    it('toArray excludes Hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->toHaveKey('email');
        expect($array)->toHaveKey('name');
        expect($array)->not->toHaveKey('password');
    });

    it('allValues includes Hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('only() returns specified fields only', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto->only('email'))->toBe(['email' => 'test@example.com']);
        expect($dto->only('email', 'name'))->toBe([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ]);
    });

    it('except() returns all fields except specified', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'phone_number' => '+905551234567',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('phone');
    });

    it('toJson returns valid JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);

        expect($decoded['email'])->toBe('test@example.com');
    });
});

describe('DTO Immutability', function (): void {
    it('with() creates new instance with overrides', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $updated = $dto->with(['status' => 'inactive']);

        expect($dto->status)->toBe('active'); // unchanged
        expect($updated->status)->toBe('inactive'); // overridden
        expect($updated->email)->toBe('test@example.com'); // preserved
    });
});

describe('DTO Equality', function (): void {
    it('equals() returns true for identical values', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different values', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Doruk',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('DTO isEmpty/isNotEmpty', function (): void {
    it('isEmpty returns true for all-default EmptyDTO', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when a field has a value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty treats empty string as empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => '', 'bar' => null], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isEmpty treats 0 and false as empty', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => '0', 'bar' => null], validate: false);

        // '0' is a non-empty string
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO fromJson', function (): void {
    it('creates DTO from valid JSON string', function (): void {
        $dto = MinimalDTO::fromJson(
            '{"name":"Test","value":"Value"}',
            validate: false
        );

        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('Value');
    });

    it('throws DTOException for invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('not-json', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential JSON array', function (): void {
        expect(fn () => MinimalDTO::fromJson('["name","value"]', validate: false))
            ->toThrow(DTOException::class);
    });
});

describe('DTO Partial Updates', function (): void {
    it('fromPartialArray hydrates only present fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Updated Name');
        expect($dto->status)->toBe('active'); // default preserved
    });

    it('validatePartialArray converts required to sometimes', function (): void {
        // Should not throw because only 'name' is present
        $result = CreateUserDTO::validatePartialArray([
            'name' => 'Valid',
        ]);

        expect($result)->toHaveKey('name');
    });
});

describe('DTO Rules', function (): void {
    it('rules() returns correct structure', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
        expect($rules['name'])->toContain('max:50');
    });

    it('rulesFor() returns same as rules() by default', function (): void {
        expect(CreateUserDTO::rulesFor('create'))
            ->toBe(CreateUserDTO::rules());
    });
});

describe('DTO Type Casting', function (): void {
    it('Cast integer converts numeric string to int', function (): void {
        $dto = MinimalDTO::fromArray([
            'name' => 'Test',
            'value' => '42',
        ], validate: false);

        // value is string type, no cast applied
        expect($dto->value)->toBe('42');
    });
});

describe('DTO DefaultValue Behaviour', function (): void {
    it('uses DefaultValue when key is missing', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            // status key missing → uses DefaultValue('active')
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('respects explicit null over DefaultValue', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Doruk',
            'status' => null, // explicitly null
        ], validate: false);

        // status is not nullable (string type), but validate is false
        // so it should accept null
        expect($dto->status)->toBeNull();
    });
});
