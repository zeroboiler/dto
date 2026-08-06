<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DtoCollection offsetSet with explicit key', function () {
    it('sets value at explicit integer key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection[5] = $dto2;

        expect($collection->count())->toBe(2);
        expect($collection[5]->email)->toBe('b@test.com');
    });

    it('replaces existing value at key', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect($collection[0]->email)->toBe('b@test.com');
    });

    it('rejects non-DTO values in offsetSet', function () {
        $collection = new DtoCollection;

        $collection[0] = 'not a dto';
    })->throws(\InvalidArgumentException::class, 'DtoCollection only accepts DataTransferObject instances');
});

describe('DtoCollection pluckKey edge cases', function () {
    it('returns full DTO array when valueField is null', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email');

        expect($map)->toHaveCount(2);
        expect($map['a@test.com'])->toHaveKey('name');
        expect($map['a@test.com']['name'])->toBe('Alice');
    });

    it('returns single field values when valueField is specified', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');

        expect($map)->toEqual([
            'a@test.com' => 'Alice',
            'b@test.com' => 'Bob',
        ]);
    });
});

describe('DtoCollection fluent operations', function () {
    it('push returns same collection instance', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection;
        $result = $collection->push($dto);

        expect($result)->toBe($collection); // Same instance
        expect($collection->count())->toBe(1);
    });

    it('filter returns new collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => $dto->name === 'Alice'
        );

        expect($filtered)->not->toBe($collection); // Different instance
        expect($filtered->count())->toBe(1);
        expect($collection->count())->toBe(2); // Original unchanged
    });

    it('map returns plain array', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
    });
});

describe('DtoCollection iteration', function () {
    it('iterates with foreach', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $count = 0;

        foreach ($collection as $dto) {
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            $count++;
        }

        expect($count)->toBe(2);
    });
});

describe('DataTransferObject equals edge cases', function () {
    it('returns true for identical DTOs', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('returns false for different DTOs', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('DataTransferObject only/except', function () {
    it('only returns subset of fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $subset = $dto->only('email', 'name');

        expect($subset)->toHaveCount(2);
        expect($subset)->toHaveKeys(['email', 'name']);
    });

    it('only ignores non-existent keys silently', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $subset = $dto->only('email', 'nonexistent');

        expect($subset)->toHaveCount(1);
        expect($subset)->toHaveKey('email');
    });

    it('except returns all but specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $subset = $dto->except('email');

        expect($subset)->not->toHaveKey('email');
        expect($subset)->toHaveKey('name');
        expect($subset)->toHaveKey('status');
    });

    it('hidden fields are excluded from only/except', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        expect($dto->only('password'))->toEqual([]);
        expect($dto->except('email'))->not->toHaveKey('password');
    });
});

describe('DataTransferObject with() immutable update', function () {
    it('creates new instance with override', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $updated = $dto->with(['name' => 'Bob']);

        expect($updated)->not->toBe($dto);
        expect($dto->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@test.com');
    });
});

describe('DataTransferObject allValues includes hidden', function () {
    it('returns all fields including hidden', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        $visible = $dto->toArray();

        expect($all)->toHaveKey('password');
        expect($visible)->not->toHaveKey('password');
    });
});

describe('DataTransferObject fromPartialArray', function () {
    it('applies provided fields and uses defaults for rest', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validatePresent: false);

        expect($dto->email)->toBe('a@test.com');
        expect($dto->name)->toBe('Alice');
        expect($dto->status)->toBe('active'); // default
    });

    it('returns DTO with defaults when empty array passed with validatePresent=false', function () {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        expect($dto->status)->toBe('active'); // default
    });

    it('handles explicit null values correctly', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'phone' => null,
        ], validatePresent: false);

        expect($dto->phone)->toBeNull();
    });
});

describe('DataTransferObject with MapFrom dot notation', function () {
    it('maps from nested array key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'phone_number' => '+905551234567',
        ], validate: false);

        expect($dto->phone)->toBe('+905551234567');
    });
});

describe('DataTransferObject with EmptyDTO', function () {
    it('creates empty DTO', function () {
        $dto = EmptyDTO::fromArray([]);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
        expect($dto->toArray())->toEqual([]);
    });

    it('rules returns empty array for DTO with no properties', function () {
        expect(EmptyDTO::rules())->toEqual([]);
    });
});

describe('DataTransferObject toJson', function () {
    it('serializes to valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('a@test.com');
        expect($decoded)->not->toHaveKey('password');
    });

    it('returns empty JSON object for empty DTO', function () {
        $dto = EmptyDTO::fromArray([]);

        expect($dto->toJson())->toBe('{}');
    });
});

describe('DataTransferObject jsonSerialize', function () {
    it('implements JsonSerializable correctly', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $encoded = json_encode($dto);

        expect($encoded)->not->toBeFalse();
        expect(json_decode($encoded, true)['email'])->toBe('a@test.com');
    });
});

describe('DataTransferObject flushMetadataCache', function () {
    it('flushes cache for all classes', function () {
        DataTransferObject::flushMetadataCache();

        // This should not throw
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });
});

describe('Compound validation attributes', function () {
    it('infers rules from Integer attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['age'])->toContain('integer');
    });

    it('infers rules from Boolean attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['active'])->toContain('boolean');
    });

    it('infers rules from Uuid attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['id'])->toContain('uuid');
    });

    it('infers rules from Url attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['website'])->toContain('url');
    });

    it('infers rules from Pattern attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['code'])->toContain('regex:/^[A-Z]{3}-\d{4}$/');
    });

    it('infers rules from Size attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['pin'])->toContain('size:4');
    });

    it('infers rules from StartsWith attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['prefix'])->toContain('starts_with:+90');
    });

    it('infers rules from Confirmed attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['password'])->toContain('confirmed');
    });

    it('infers rules from Prohibited attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['internal'])->toContain('prohibited');
    });

    it('infers rules from Nullable attribute', function () {
        $rules = CompoundRulesDTO::rules();

        expect($rules['optional'])->toContain('nullable');
    });
});

describe('rulesFor returns same as rules by default', function () {
    it('returns identical rules for all actions', function () {
        $rules = CompoundRulesDTO::rules();
        $createRules = CompoundRulesDTO::rulesFor('create');
        $updateRules = CompoundRulesDTO::rulesFor('update');
        $patchRules = CompoundRulesDTO::rulesFor('patch');

        expect($rules)->toBe($createRules);
        expect($rules)->toBe($updateRules);
        expect($rules)->toBe($patchRules);
    });
});

// ─── Test Fixtures ───────────────────────────────────────────────

class CompoundRulesDTO extends DataTransferObject
{
    public function __construct(
        #[Uuid]
        public readonly string $id = '550e8400-e29b-41d4-a716-446655440000',

        #[Integer, Min(0), Max(150)]
        public readonly int $age = 25,

        #[Boolean]
        public readonly bool $active = true,

        #[Url]
        public readonly ?string $website = null,

        #[Pattern('/^[A-Z]{3}-\d{4}$/')]
        public readonly ?string $code = null,

        #[Size(4)]
        public readonly ?string $pin = null,

        #[StartsWith('+90')]
        public readonly ?string $prefix = null,

        #[Confirmed]
        public readonly ?string $password = null,

        #[Prohibited]
        public readonly ?string $internal = null,

        #[Nullable]
        public readonly ?string $optional = null,
    ) {}
}
