<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
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
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

// ---------------------------------------------------------------------------
// Runtime fixtures for deep edge case testing
// ---------------------------------------------------------------------------

/** DTO with all constraint types for validation rule coverage testing */
final class AllConstraintsDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(2), Max(100), Pattern('/^[a-zA-Z\s]+$/')]
        public readonly string $fullName,

        #[Required, Integer, Between(18, 120)]
        public readonly string $age,

        #[Boolean]
        public readonly bool $isActive = true,

        #[Numeric, Min(0)]
        public readonly ?float $score = null,

        #[Url]
        public readonly ?string $website = null,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[In(['admin', 'editor', 'viewer'])]
        public readonly string $role = 'viewer',

        #[StartsWith('https://')]
        public readonly ?string $apiUrl = null,

        #[EndsWith('.com')]
        public readonly ?string $domain = null,

        #[Size(3)]
        #[ArrayRule(min: 1, max: 10)]
        public readonly array $tags = [],

        #[Confirmed]
        public readonly ?string $password = null,

        #[Different('email')]
        public readonly ?string $username = null,

        #[Same('password')]
        public readonly ?string $passwordConfirmation = null,

        #[Accepted]
        public readonly bool $terms = false,

        #[Declined]
        public readonly bool $doNotContact = false,

        #[Distinct]
        public readonly array $uniqueIds = [],

        #[Present]
        public readonly ?string $optionalField = null,

        #[Prohibited]
        public readonly ?string $blockedField = null,

        #[Sometimes]
        public readonly ?string $occasionalField = null,

        #[Json]
        public readonly ?string $metadata = null,

        #[Nullable]
        public readonly ?string $notes = null,

        #[RequiredIf('role', 'admin')]
        public readonly ?string $adminToken = null,

        #[MapFrom('contact_no')]
        public readonly ?string $phone = null,

        #[Hidden]
        public readonly ?string $secretKey = null,

        #[Cast('int')]
        public readonly int $counter = 0,

        #[DefaultValue('draft')]
        public readonly string $status = 'draft',
    ) {}
}

/** DTO with nested array of DTOs for hydration pipeline testing */
final class ContainerDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[NestedArray(AddressDTO::class)]
        public readonly array $addresses = [],
    ) {}
}

/** DTO with collection for DtoCollection edge case testing */
final class TeamDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $teamName,

        #[Collection(MinimalDTO::class)]
        public readonly array $members = [],
    ) {}
}

describe('DTO Validation Rule Coverage', function () {
    it('Email attribute generates email rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['email'])->toContain('email');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('max:255');
    });

    it('Pattern attribute generates regex rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['fullName'])->toContain('regex:/^[a-zA-Z\s]+$/');
    });

    it('Boolean attribute generates boolean rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['isActive'])->toContain('boolean');
    });

    it('Uuid attribute generates uuid rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['uuid'])->toContain('uuid');
    });

    it('In attribute generates in rule with comma-separated values', function () {
        $rules = AllConstraintsDTO::rules();

        $inRule = array_first($rules['role'], fn (mixed $r): bool => str_starts_with((string) $r, 'in:'));
        expect($inRule)->not->toBeNull();
        expect((string) $inRule)->toContain('admin');
        expect((string) $inRule)->toContain('editor');
        expect((string) $inRule)->toContain('viewer');
    });

    it('StartsWith attribute generates starts_with rule', function () {
        $rules = AllConstraintsDTO::rules();

        $rule = array_first($rules['apiUrl'], fn (mixed $r): bool => str_starts_with((string) $r, 'starts_with:'));
        expect($rule)->not->toBeNull();
        expect((string) $rule)->toContain('https://');
    });

    it('EndsWith attribute generates ends_with rule', function () {
        $rules = AllConstraintsDTO::rules();

        $rule = array_first($rules['domain'], fn (mixed $r): bool => str_starts_with((string) $r, 'ends_with:'));
        expect($rule)->not->toBeNull();
        expect((string) $rule)->toContain('.com');
    });

    it('Size attribute generates size rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['tags'])->toContain('size:3');
    });

    it('ArrayRule generates array + min/max rules', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['tags'])->toContain('array');
        expect($rules['tags'])->toContain('min:1');
        expect($rules['tags'])->toContain('max:10');
    });

    it('Distinct attribute generates distinct rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['uniqueIds'])->toContain('distinct');
    });

    it('Distinct registers wildcard rule for array elements', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules)->toHaveKey('uniqueIds.*');
        expect($rules['uniqueIds.*'])->toContain('distinct');
    });

    it('Present attribute generates present rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['optionalField'])->toContain('present');
    });

    it('Prohibited attribute generates prohibited rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['blockedField'])->toContain('prohibited');
    });

    it('Sometimes attribute generates sometimes rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['occasionalField'])->toContain('sometimes');
    });

    it('Json attribute generates json rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['metadata'])->toContain('json');
    });

    it('Nullable attribute generates nullable rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['notes'])->toContain('nullable');
    });

    it('Confirmed attribute generates confirmed rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['password'])->toContain('confirmed');
    });

    it('Different attribute generates different rule', function () {
        $rules = AllConstraintsDTO::rules();

        $rule = array_first($rules['username'], fn (mixed $r): bool => str_starts_with((string) $r, 'different:'));
        expect($rule)->not->toBeNull();
        expect((string) $rule)->toContain('email');
    });

    it('Same attribute generates same rule', function () {
        $rules = AllConstraintsDTO::rules();

        $rule = array_first($rules['passwordConfirmation'], fn (mixed $r): bool => str_starts_with((string) $r, 'same:'));
        expect($rule)->not->toBeNull();
        expect((string) $rule)->toContain('password');
    });

    it('Accepted attribute generates accepted rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['terms'])->toContain('accepted');
    });

    it('Declined attribute generates declined rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['doNotContact'])->toContain('declined');
    });

    it('RequiredIf attribute generates required_if rule', function () {
        $rules = AllConstraintsDTO::rules();

        $rule = array_first($rules['adminToken'], fn (mixed $r): bool => str_starts_with((string) $r, 'required_if:'));
        expect($rule)->not->toBeNull();
        expect((string) $rule)->toContain('role');
        expect((string) $rule)->toContain('admin');
    });

    it('MapFrom is reflected in metadata but not in rule keys', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules)->toHaveKey('phone');
        expect($rules)->not->toHaveKey('contact_no');
    });

    it('Hidden property has metadata flag', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test User',
            'age' => '25',
            'secretKey' => 'super-secret',
        ], validate: false);

        $array = $dto->toArray();
        expect($array)->not->toHaveKey('secretKey');

        $all = $dto->allValues();
        expect($all)->toHaveKey('secretKey');
        expect($all['secretKey'])->toBe('super-secret');
    });

    it('Cast attribute applies type casting', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test',
            'age' => '30',
            'counter' => '42',
        ], validate: false);

        expect($dto->counter)->toBe(42);
        expect($dto->counter)->toBeInt();
    });

    it('DefaultValue attribute provides default when key absent', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test',
            'age' => '25',
        ], validate: false);

        expect($dto->status)->toBe('draft');
    });
});

describe('DTO Hydration Pipeline Edge Cases', function () {
    it('fromArray with validate=false skips validation', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'not-an-email',
            'fullName' => 'T',
            'age' => '999',
        ], validate: false);

        expect($dto)->toBeInstanceOf(AllConstraintsDTO::class);
        expect($dto->email)->toBe('not-an-email');
    });

    it('fromJson with invalid JSON throws DTOException', function () {
        expect(fn () => AllConstraintsDTO::fromJson('{not valid json'))
            ->toThrow(DTOException::class);
    });

    it('fromJson with sequential array throws DTOException', function () {
        expect(fn () => AllConstraintsDTO::fromJson('[1,2,3]'))
            ->toThrow(DTOException::class);
    });

    it('fromJson with valid JSON creates DTO', function () {
        $dto = AllConstraintsDTO::fromJson(json_encode([
            'email' => 'test@example.com',
            'fullName' => 'Test User',
            'age' => '25',
        ]), validate: false);

        expect($dto)->toBeInstanceOf(AllConstraintsDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('MapFrom maps source key to property', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test',
            'age' => '25',
            'contact_no' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('nested array hydration creates DTO instances', function () {
        $dto = ContainerDTO::fromArray([
            'name' => 'Main Office',
            'addresses' => [
                ['street' => '123 Main St', 'city' => 'NYC', 'zipCode' => '10001'],
                ['street' => '456 Side Ave', 'city' => 'LA'],
            ],
        ], validate: false);

        expect($dto->addresses)->toHaveCount(2);
        expect($dto->addresses[0])->toBeInstanceOf(AddressDTO::class);
        expect($dto->addresses[0]->city)->toBe('NYC');
        expect($dto->addresses[1]->zipCode)->toBeNull();
    });

    it('collection hydration wraps in DtoCollection', function () {
        $dto = TeamDTO::fromArray([
            'teamName' => 'Alpha',
            'members' => [
                ['name' => 'Alice', 'value' => 'dev'],
                ['name' => 'Bob', 'value' => 'design'],
            ],
        ], validate: false);

        expect($dto->members)->toBeInstanceOf(DtoCollection::class);
        expect($dto->members->count())->toBe(2);
        expect($dto->members->first())->toBeInstanceOf(MinimalDTO::class);
    });
});

describe('DTO Partial Update Edge Cases', function () {
    it('fromPartialArray hydrates only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validate: false);

        expect($dto->name)->toBe('Updated Name');
    });

    it('fromPartialArray preserves defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('fromPartialArray with empty data uses all defaults', function () {
        $dto = EmptyDTO::fromPartialArray([], validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('fromPartialArray handles explicit null values', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'phone' => null,
        ], validate: false);

        expect($dto->phone)->toBeNull();
    });
});

describe('DTO Serialization Edge Cases', function () {
    it('toJson produces valid JSON string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('test@example.com');
    });

    it('jsonSerialize returns toArray output', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('equals compares toArray output', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different data', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@example.com',
            'name' => 'A',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@example.com',
            'name' => 'B',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('only returns specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        $result = $dto->only(['email', 'name']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
        expect($result)->not->toHaveKey('tags');
    });

    it('only accepts string key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('email');
    });

    it('except excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except(['status']);

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('with creates immutable copy with overrides', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Original',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);

        expect($dto->name)->toBe('Original');
        expect($updated->name)->toBe('Updated');
        expect($updated->email)->toBe('test@example.com');
    });
});

describe('DTO isEmpty / isNotEmpty Edge Cases', function () {
    it('EmptyDTO with all nulls is empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with at least one non-empty string is not empty', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('DTO with zero int value is not empty (int 0 is meaningful)', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test',
            'age' => '25',
            'counter' => '0',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('DTO with empty array is empty', function () {
        $dto = AllConstraintsDTO::fromArray([
            'email' => 'test@example.com',
            'fullName' => 'Test',
            'age' => '25',
            'tags' => [],
        ], validate: false);

        // tags is empty array → empty
        // email has value → not empty
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO Rules Inference', function () {
    it('integer type infers integer rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['age'])->toContain('integer');
    });

    it('float type infers numeric rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['score'])->toContain('numeric');
    });

    it('required fields have required rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['email'])->toContain('required');
        expect($rules['fullName'])->toContain('required');
    });

    it('fields with default value do not have required rule', function () {
        $rules = AllConstraintsDTO::rules();

        expect($rules['status'])->not->toContain('required');
    });

    it('nullable fields have sometimes rule when no default', function () {
        $rules = AllConstraintsDTO::rules();

        // notes is nullable with no default — gets 'sometimes'
        expect($rules['notes'])->toContain('sometimes');
    });

    it('rulesFor returns same as rules by default', function () {
        expect(AllConstraintsDTO::rulesFor('create'))
            ->toBe(AllConstraintsDTO::rules());
    });

    it('rulesFor with any action returns same as rules', function () {
        expect(AllConstraintsDTO::rulesFor('update'))
            ->toBe(AllConstraintsDTO::rules());
    });
});

describe('DTO Metadata Cache', function () {
    it('flushMetadataCache clears all caches', function () {
        // Resolve metadata to populate cache
        AllConstraintsDTO::rules();

        // Flush
        AllConstraintsDTO::flushMetadataCache();

        // Re-resolve should work (no stale state)
        $rules = AllConstraintsDTO::rules();
        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
    });

    it('flushMetadataCache for specific class only', function () {
        AllConstraintsDTO::rules();
        CreateUserDTO::rules();

        AllConstraintsDTO::flushMetadataCache(AllConstraintsDTO::class);

        // CreateUserDTO should still work
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });
});

describe('DTO Exception Factory Methods', function () {
    it('invalidCast produces descriptive message', function () {
        $e = DTOException::invalidCast('fieldName', 'integer', 'not-an-int');

        expect($e->getMessage())->toContain('fieldName');
        expect($e->getMessage())->toContain('integer');
        expect($e->getMessage())->toContain('string'); // get_debug_type
    });

    it('invalidJson produces descriptive message', function () {
        $e = DTOException::invalidJson('data', 'Syntax error');

        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });
});

describe('DTO DtoCollection Edge Cases', function () {
    it('empty collection is empty', function () {
        $col = DtoCollection::make([]);

        expect($col->isEmpty())->toBeTrue();
        expect($col->count())->toBe(0);
        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('push returns self for chaining', function () {
        $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $col = DtoCollection::make();

        $result = $col->push($dto);

        expect($result)->toBe($col);
        expect($col->count())->toBe(1);
    });

    it('filter returns new collection', function () {
        $d1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $d2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->name === 'A');

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->name)->toBe('A');
    });

    it('map returns plain array', function () {
        $d1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'dev'], validate: false);
        $d2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'design'], validate: false);

        $col = DtoCollection::make([$d1, $d2]);
        $names = $col->map(fn (DataTransferObject $dto): string => $dto->name);

        expect($names)->toEqual(['Alice', 'Bob']);
    });

    it('offsetUnset re-indexes array', function () {
        $d1 = MinimalDTO::fromArray(['name' => 'A', 'value' => '1'], validate: false);
        $d2 = MinimalDTO::fromArray(['name' => 'B', 'value' => '2'], validate: false);
        $d3 = MinimalDTO::fromArray(['name' => 'C', 'value' => '3'], validate: false);

        $col = DtoCollection::make([$d1, $d2, $d3]);
        unset($col[0]);

        // After re-index, index 0 should be 'B'
        expect($col[0]->name)->toBe('B');
        expect($col->count())->toBe(2);
    });

    it('constructor rejects non-DTO items', function () {
        expect(fn () => DtoCollection::make(['not a dto']))
            ->toThrow(InvalidArgumentException::class);
    });

    it('offsetSet rejects non-DTO items', function () {
        $col = DtoCollection::make();

        expect(fn () => $col[] = 'invalid')
            ->toThrow(InvalidArgumentException::class);
    });
});
