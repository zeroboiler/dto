<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
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
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * V28 End-to-end DTO hydration, serialization, validation and collection contract tests.
 *
 * Tests the full DTO lifecycle:
 * 1. DTO definition with all attribute types
 * 2. fromArray hydration with type casting
 * 3. fromPartialArray PATCH semantics
 * 4. toArray/toJson serialization with Hidden
 * 5. only/except selective output
 * 6. with() immutable update
 * 7. equals/isEmpty/isNotEmpty state checks
 * 8. rules() auto-generated from attributes
 * 9. DtoCollection operations (pluck, map, filter, append, merge)
 * 10. MapFrom key aliasing
 * 11. Nested DTO hydration
 */

// ── Test Fixtures ──────────────────────────────────────────────────

class V28AddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        #[Required, Max(20)]
        public readonly string $zip,

        #[DefaultValue('US')]
        public readonly string $country = 'US',
    ) {}
}

class V28UserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[MapFrom('user_age')]
        #[Cast('integer')]
        public readonly int $age = 0,

        #[MapFrom('is_active')]
        public readonly bool $active = true,

        #[DefaultValue('user')]
        public readonly string $role = 'user',

        #[Hidden]
        public readonly ?string $password = null,

        #[Url]
        public readonly ?string $website = null,

        #[Nullable]
        public readonly ?string $bio = null,

        #[StartsWith('@')]
        public readonly ?string $handle = null,

        #[EndsWith('.com')]
        public readonly ?string $domain = null,

        #[In(['small', 'medium', 'large'])]
        public readonly string $size = 'medium',

        #[Between(0, 100)]
        public readonly int $score = 0,

        #[Confirmed]
        public readonly ?string $newPassword = null,

        #[Different('email')]
        public readonly ?string $username = null,

        #[Same('city')]
        public readonly ?string $billingCity = null,

        #[Prohibited]
        public readonly ?string $legacyField = null,

        #[Present]
        public readonly ?string $optionalField = null,

        #[RequiredWith('email')]
        public readonly ?string $emailVerifiedAt = null,

        #[RequiredWithAll('email', 'name')]
        public readonly ?string $fullName = null,

        #[RequiredWithout('email')]
        public readonly ?string $guestName = null,

        #[RequiredWithoutAll('email', 'name')]
        public readonly ?string $guestEmail = null,

        #[RequiredIf('role', 'admin')]
        public readonly ?string $adminToken = null,

        #[RequiredUnless('role', 'user')]
        public readonly ?string $roleToken = null,

        #[Accepted]
        public readonly bool $terms = false,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[Pattern('/^[A-Z]{2,4}-\d{4}$/')]
        public readonly ?string $code = null,

        #[Integer]
        public readonly int $priority = 0,

        #[Numeric]
        public readonly float $rating = 0.0,

        #[Boolean]
        public readonly bool $newsletter = false,

        #[Date]
        public readonly ?\Carbon\Carbon $createdAt = null,

        #[Size(255)]
        public readonly string $notes = '',

        #[Distinct]
        public readonly array $tags = [],

        #[Sometimes]
        public readonly ?string $maybeField = null,

        #[ArrayRule(min: 1, max: 10)]
        public readonly array $items = [],
    ) {}
}

// ── fromArray Hydration ────────────────────────────────────────────

test('fromArray hydrates all required fields', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ], validate: false);

    expect($dto->email)->toBe('test@example.com');
    expect($dto->name)->toBe('Test User');
});

test('fromArray applies MapFrom key aliasing', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
        'user_age' => 25,
        'is_active' => false,
    ], validate: false);

    expect($dto->age)->toBe(25);
    expect($dto->active)->toBeFalse();
});

test('fromArray applies Cast type casting', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
        'age' => '30', // string, should be cast to int
    ], validate: false);

    expect($dto->age)->toBe(30);
    expect($dto->age)->toBeInt();
});

test('fromArray uses DefaultValue when key is absent', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
    ], validate: false);

    expect($dto->role)->toBe('user');
    expect($dto->active)->toBeTrue();
    expect($dto->size)->toBe('medium');
    expect($dto->score)->toBe(0);
    expect($dto->terms)->toBe(false);
});

test('fromArray respects explicit null values over defaults', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
        'bio' => null,
        'handle' => null,
    ], validate: false);

    expect($dto->bio)->toBeNull();
    expect($dto->handle)->toBeNull();
});

// ── Serialization ──────────────────────────────────────────────────

test('toArray excludes Hidden properties', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
        'password' => 'secret123',
    ], validate: false);

    $array = $dto->toArray();

    expect($array)->not->toHaveKey('password');
    expect($array)->toHaveKey('email');
    expect($array)->toHaveKey('name');
});

test('allValues includes Hidden properties', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
        'password' => 'secret123',
    ], validate: false);

    $all = $dto->allValues();

    expect($all)->toHaveKey('password');
    expect($all['password'])->toBe('secret123');
});

test('toJson produces valid JSON string', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
    ], validate: false);

    $json = $dto->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray();
    expect($decoded['email'])->toBe('test@example.com');
    expect($decoded['name'])->toBe('Test');
});

// ── Selective Output ────────────────────────────────────────────────

test('only returns specified fields', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'role' => 'admin',
    ], validate: false);

    $result = $dto->only('email', 'name');

    expect($result)->toHaveKeys(['email', 'name']);
    expect($result)->not->toHaveKey('role');
});

test('except excludes specified fields', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'role' => 'admin',
    ], validate: false);

    $result = $dto->except('role');

    expect($result)->not->toHaveKey('role');
    expect($result)->toHaveKey('email');
});

// ── Immutable Update ───────────────────────────────────────────────

test('with returns a new instance with updated fields', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
    ], validate: false);

    $updated = $dto->with(['name' => 'Updated Name'], validate: false);

    expect($dto->name)->toBe('Test'); // original unchanged
    expect($updated->name)->toBe('Updated Name');
    expect($updated->email)->toBe('test@example.com');
});

// ── State Checks ────────────────────────────────────────────────────

test('isEmpty detects all-default DTO', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
    ], validate: false);

    expect($dto->isEmpty())->toBeFalse(); // email and name are not empty
});

test('equals compares toArray output', function (): void {
    $data = ['email' => 'test@example.com', 'name' => 'Test'];
    $dto1 = V28UserDTO::fromArray($data, validate: false);
    $dto2 = V28UserDTO::fromArray($data, validate: false);

    expect($dto1->equals($dto2))->toBeTrue();
});

test('isNotEmpty is negation of isEmpty', function (): void {
    $dto = V28UserDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test',
    ], validate: false);

    expect($dto->isNotEmpty())->toBeTrue();
});

// ── Rules Generation ───────────────────────────────────────────────

test('rules generates correct validation rules from attributes', function (): void {
    $rules = V28UserDTO::rules();

    // Required + Email
    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('email');

    // Required + Min + Max
    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('min:2');
    expect($rules['name'])->toContain('max:50');

    // Integer validation
    expect($rules['priority'])->toContain('integer');

    // Numeric validation
    expect($rules['rating'])->toContain('numeric');

    // Boolean validation
    expect($rules['newsletter'])->toContain('boolean');

    // In constraint
    expect($rules['size'])->toContain('in:small,medium,large');

    // Between
    expect($rules['score'])->toContain('between:0,100');

    // Distinct
    expect($rules['tags'])->toContain('distinct');

    // Confirmed
    expect($rules['newPassword'])->toContain('confirmed');

    // Url
    expect($rules['website'])->toContain('url');

    // Uuid
    expect($rules['uuid'])->toContain('uuid');

    // Hidden should NOT generate a rule
    expect($rules)->not->toHaveKey('password');
});

test('rulesFor returns same rules by default', function (): void {
    $rules = V28UserDTO::rules();
    $rulesForCreate = V28UserDTO::rulesFor('create');

    expect($rules)->toEqual($rulesForCreate);
});

// ── fromPartialArray ───────────────────────────────────────────────

test('fromPartialArray only hydrates provided fields', function (): void {
    $dto = V28UserDTO::fromPartialArray([
        'name' => 'Partial Update',
    ], validatePresent: false);

    expect($dto->name)->toBe('Partial Update');
});

test('fromPartialArray uses defaults for missing fields', function (): void {
    $dto = V28UserDTO::fromPartialArray([], validatePresent: false);

    expect($dto->role)->toBe('user');
    expect($dto->active)->toBeTrue();
});

// ── DtoCollection ─────────────────────────────────────────────────

test('DtoCollection make and count', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    expect($col->count())->toBe(2);
    expect($col->isEmpty())->toBeFalse();
    expect($col->isNotEmpty())->toBeTrue();
});

test('DtoCollection pluck extracts single field', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    expect($col->pluck('city'))->toBe(['NYC', 'LA']);
});

test('DtoCollection pluckKey creates keyed map', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    $keyed = $col->pluckKey('zip', 'city');
    expect($keyed['10001'])->toBe('NYC');
    expect($keyed['90001'])->toBe('LA');
});

test('DtoCollection map returns plain array', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    $cities = $col->map(fn (V28AddressDTO $dto): string => $dto->city);
    expect($cities)->toBe(['NYC', 'LA']);
});

test('DtoCollection filter returns new collection', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $filtered = $col->filter(fn (V28AddressDTO $dto): bool => $dto->city === 'NYC');

    expect($filtered->count())->toBe(1);
    expect($filtered->first()->city)->toBe('NYC');
    expect($col->count())->toBe(2); // original unchanged
});

test('DtoCollection append returns new immutable collection', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1]);
    $newCol = $col->append($dto2);

    expect($col->count())->toBe(1); // original unchanged
    expect($newCol->count())->toBe(2);
});

test('DtoCollection push mutates in place', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1]);
    $result = $col->push($dto2);

    expect($col->count())->toBe(2); // mutated in-place
    expect($result)->toBe($col); // returns same instance
});

test('DtoCollection merge combines two collections', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col1 = DtoCollection::make([$dto1]);
    $col2 = DtoCollection::make([$dto2]);
    $merged = $col1->merge($col2);

    expect($merged->count())->toBe(2);
    expect($col1->count())->toBe(1); // original unchanged
});

test('DtoCollection first and last', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    expect($col->first()->city)->toBe('NYC');
    expect($col->last()->city)->toBe('LA');
});

test('DtoCollection toArray serializes all DTOs', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);

    $col = DtoCollection::make([$dto1]);
    $array = $col->toArray();

    expect($array)->toBe([[
        'street' => 'A',
        'city' => 'NYC',
        'zip' => '10001',
        'country' => 'US',
    ]]);
});

test('DtoCollection isEmpty on empty collection', function (): void {
    $col = DtoCollection::make([]);
    expect($col->isEmpty())->toBeTrue();
    expect($col->isNotEmpty())->toBeFalse();
    expect($col->count())->toBe(0);
    expect($col->first())->toBeNull();
    expect($col->last())->toBeNull();
});

// ── Conditional Validation Attributes ──────────────────────────────

test('RequiredWith generates correct rule', function (): void {
    $rules = V28UserDTO::rules();
    expect($rules['emailVerifiedAt'])->toContain('required_with:email');
});

test('RequiredWithAll generates correct rule', function (): void {
    $rules = V28UserDTO::rules();
    expect($rules['fullName'])->toContain('required_with_all:email,name');
});

test('RequiredWithout generates correct rule', function (): void {
    $rules = V28UserDTO::rules();
    expect($rules['guestName'])->toContain('required_without:email');
});

test('RequiredWithoutAll generates correct rule', function (): void {
    $rules = V28UserDTO::rules();
    expect($rules['guestEmail'])->toContain('required_without_all:email,name');
});

test('Cross-field validation rules are present', function (): void {
    $rules = V28UserDTO::rules();

    expect($rules['newPassword'])->toContain('confirmed');
    expect($rules['username'])->toContain('different:email');
    expect($rules['billingCity'])->toContain('same:city');
    expect($rules['legacyField'])->toContain('prohibited');
});

test('Accepted generates correct rule', function (): void {
    $rules = V28UserDTO::rules();
    expect($rules['terms'])->toContain('accepted');
});

// ── Nested DTO ──────────────────────────────────────────────────────

test('nested address DTO hydration and serialization', function (): void {
    $address = V28AddressDTO::fromArray([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '62701',
        'country' => 'US',
    ], validate: false);

    expect($address->street)->toBe('123 Main St');
    expect($address->city)->toBe('Springfield');
    expect($address->zip)->toBe('62701');
    expect($address->country)->toBe('US');

    $array = $address->toArray();
    expect($array)->toBe([
        'street' => '123 Main St',
        'city' => 'Springfield',
        'zip' => '62701',
        'country' => 'US',
    ]);
});

// ── ArrayAccess ─────────────────────────────────────────────────────

test('DtoCollection supports array access', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);

    expect($col[0]->city)->toBe('NYC');
    expect($col[1]->city)->toBe('LA');
    expect(isset($col[0]))->toBeTrue();
    expect(isset($col[5]))->toBeFalse();
});

// ── Iterator ────────────────────────────────────────────────────────

test('DtoCollection supports foreach iteration', function (): void {
    $dto1 = V28AddressDTO::fromArray(['street' => 'A', 'city' => 'NYC', 'zip' => '10001'], validate: false);
    $dto2 = V28AddressDTO::fromArray(['street' => 'B', 'city' => 'LA', 'zip' => '90001'], validate: false);

    $col = DtoCollection::make([$dto1, $dto2]);
    $cities = [];

    foreach ($col as $dto) {
        $cities[] = $dto->city;
    }

    expect($cities)->toBe(['NYC', 'LA']);
});
