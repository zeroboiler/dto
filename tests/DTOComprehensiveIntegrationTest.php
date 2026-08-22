<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Fixtures ──────────────────────────────────────────────────

class IntegrationUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(2), Max(100)]
        public readonly string $name,

        #[Nullable, Max(500)]
        public ?string $bio = null,

        #[Hidden]
        public ?string $password = null,

        #[Cast('integer')]
        public int $age = 0,

        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[Boolean]
        public readonly bool $isAdmin = false,
    ) {}
}

class IntegrationMappedDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('remote_id')]
        public readonly ?string $id = null,

        #[MapFrom('remote_name')]
        #[Required]
        public readonly string $name = '',

        #[MapFrom('remote_email')]
        #[Email]
        public readonly ?string $email = null,
    ) {}
}

class IntegrationProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(0)]
        #[Numeric]
        public readonly float $price,

        #[Required, In(['draft', 'published', 'archived'])]
        public readonly string $state = 'draft',

        #[Size(13)]
        public readonly string $isbn = '',

        #[Accepted]
        public readonly bool $terms = false,

        #[Present]
        public readonly ?string $note = null,
    ) {}
}

class IntegrationAddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        #[Pattern('/^[A-Z]{2}\d{4}[A-Z]{2}$/')]
        public readonly string $postalCode = '',

        #[StartsWith('https://')]
        public readonly ?string $website = null,
    ) {}
}

class IntegrationOrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderNumber,

        public readonly IntegrationAddressDTO $shippingAddress,

        #[Required, Same('shippingAddress.city')]
        public readonly string $billingCity = '',
    ) {}
}

class IntegrationCollectionDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $id,

        public readonly string $label,

        #[DefaultValue(0)]
        #[Cast('integer')]
        public readonly int $score = 0,
    ) {}
}

class IntegrationNullableDTO extends DataTransferObject
{
    public function __construct(
        #[Sometimes]
        public readonly ?string $optional = null,

        #[Nullable]
        public readonly ?int $count = null,

        #[DefaultValue('default-value')]
        public readonly string $configured = 'default-value',
    ) {}
}

class IntegrationUuidDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Uuid]
        public readonly string $id,
    ) {}
}

class IntegrationCrossFieldDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $password,

        #[Same('password')]
        public readonly string $passwordConfirmation = '',

        #[Different('password')]
        public readonly string $username = '',
    ) {}
}

class IntegrationEmptyDTO extends DataTransferObject
{
    public function __construct(
        #[Nullable]
        public readonly ?string $optional = null,
    ) {}
}

// ── Tests ──────────────────────────────────────────────────────

describe('DTO comprehensive integration and edge cases', function () {

    // ── Basic hydration ──────────────────────────────────────
    describe('fromArray basic hydration', function () {
        it('hydrates all typed properties correctly', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'bio' => 'Hello world',
                'age' => '30',
                'isAdmin' => '1',
            ], validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->bio)->toBe('Hello world');
            expect($dto->age)->toBe(30);
            expect($dto->status)->toBe('active');
            expect($dto->isAdmin)->toBeTrue();
        });

        it('applies defaults for missing keys', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->bio)->toBeNull();
            expect($dto->password)->toBeNull();
            expect($dto->age)->toBe(0);
            expect($dto->status)->toBe('active');
            expect($dto->isAdmin)->toBeFalse();
        });

        it('Cast integer converts string to int', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Bob',
                'age' => '42',
            ], validate: false);

            expect($dto->age)->toBe(42);
            expect($dto->age)->toBeInt();
        });

        it('Cast integer defaults to 0 for non-numeric', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Bob',
                'age' => 'not-a-number',
            ], validate: false);

            expect($dto->age)->toBe(0);
        });
    });

    // ── MapFrom ──────────────────────────────────────────────
    describe('MapFrom attribute', function () {
        it('maps source key to property name', function () {
            $dto = IntegrationMappedDTO::fromArray([
                'remote_id' => '12345',
                'remote_name' => 'Alice',
                'remote_email' => 'alice@example.com',
            ], validate: false);

            expect($dto->id)->toBe('12345');
            expect($dto->name)->toBe('Alice');
            expect($dto->email)->toBe('alice@example.com');
        });

        it('uses default when mapped source key is missing', function () {
            $dto = IntegrationMappedDTO::fromArray([
                'remote_name' => 'Bob',
            ], validate: false);

            expect($dto->id)->toBeNull();
            expect($dto->email)->toBeNull();
        });
    });

    // ── Serialization ──────────────────────────────────────
    describe('serialization', function () {
        it('toArray() excludes hidden fields', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues() includes hidden fields', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('toJson() returns valid JSON string', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeString();
            expect(json_decode($json, true))->toBeArray();
        });

        it('only() returns only specified fields', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => '30',
            ], validate: false);

            $only = $dto->only('email', 'name');
            expect($only)->toHaveCount(2);
            expect($only)->toHaveKey('email');
            expect($only)->toHaveKey('name');
            expect($only)->not->toHaveKey('age');
        });

        it('only() accepts single string key', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $only = $dto->only('email');
            expect($only)->toHaveCount(1);
            expect($only['email'])->toBe('a@b.com');
        });

        it('except() excludes specified fields', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => '30',
            ], validate: false);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
            expect($except)->toHaveKey('age');
        });

        it('except() accepts single string key', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $except = $dto->except('name');
            expect($except)->not->toHaveKey('name');
            expect($except)->toHaveKey('email');
        });
    });

    // ── Immutability ──────────────────────────────────────
    describe('immutability', function () {
        it('with() creates new instance with overrides', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);
            expect($updated->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // original unchanged
        });

        it('with() preserves unchanged fields', function () {
            $dto = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'age' => '30',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);
            expect($updated->email)->toBe('a@b.com');
            expect($updated->age)->toBe(30);
        });

        it('equals() compares serialized output', function () {
            $dto1 = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals() returns false for different values', function () {
            $dto1 = IntegrationUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = IntegrationUserDTO::fromArray([
                'email' => 'b@c.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    // ── State checks ──────────────────────────────────────
    describe('state checks', function () {
        it('isEmpty() returns true when all properties are empty/default', function () {
            $dto = IntegrationEmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isEmpty() returns false when a property has a non-empty value', function () {
            $dto = IntegrationEmptyDTO::fromArray([
                'optional' => 'something',
            ], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() is logical negation of isEmpty()', function () {
            $empty = IntegrationEmptyDTO::fromArray([], validate: false);
            $nonEmpty = IntegrationEmptyDTO::fromArray(['optional' => 'x'], validate: false);

            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('isEmpty() treats 0 int as non-empty', function () {
            $dto = IntegrationCollectionDTO::fromArray([
                'id' => '1',
                'label' => '',
                'score' => '0',
            ], validate: false);
            // score=0 is a non-nullable int with value 0 → non-empty
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ── fromJson ──────────────────────────────────────────
    describe('fromJson', function () {
        it('creates DTO from JSON string', function () {
            $json = '{"email":"test@example.com","name":"Alice"}';
            $dto = IntegrationUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => IntegrationUserDTO::fromJson('not-json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON array', function () {
            expect(fn () => IntegrationUserDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });
    });

    // ── Partial updates ──────────────────────────────────────
    describe('partial updates', function () {
        it('fromPartialArray() hydrates only present fields', function () {
            $dto = IntegrationUserDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated Name');
            expect($dto->status)->toBe('active'); // default
            expect($dto->age)->toBe(0); // default
        });

        it('fromPartialArray() uses empty values for missing non-nullable fields', function () {
            $dto = IntegrationUserDTO::fromPartialArray([], validatePresent: false);

            // email is required and non-nullable string → empty string fallback
            expect($dto->email)->toBe('');
        });
    });

    // ── Nested DTO ──────────────────────────────────────
    describe('nested DTO hydration', function () {
        it('auto-hydrates nested DTO from array', function () {
            $dto = IntegrationOrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Istanbul',
                ],
                'billingCity' => 'Istanbul',
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(IntegrationAddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
            expect($dto->shippingAddress->city)->toBe('Istanbul');
            expect($dto->billingCity)->toBe('Istanbul');
        });

        it('nested DTO serializes recursively in toArray()', function () {
            $dto = IntegrationOrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => [
                    'street' => '123 Main St',
                    'city' => 'Ankara',
                ],
                'billingCity' => 'Ankara',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['street'])->toBe('123 Main St');
        });
    });

    // ── DtoCollection ──────────────────────────────────────
    describe('DtoCollection', function () {
        it('make() creates collection from DTOs', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
        });

        it('pluck() extracts single field', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A', 'score' => '10'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B', 'score' => '20'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $labels = $col->pluck('label');

            expect($labels)->toEqual(['A', 'B']);
        });

        it('map() transforms items to plain array', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $ids = $col->map(fn (DataTransferObject $dto): string => $dto->id);

            expect($ids)->toEqual(['1', '2']);
        });

        it('filter() returns new collection', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A', 'score' => '5'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B', 'score' => '15'], validate: false);
            $dto3 = IntegrationCollectionDTO::fromArray(['id' => '3', 'label' => 'C', 'score' => '3'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2, $dto3]);
            $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->score > 4);

            expect($filtered->count())->toBe(2);
        });

        it('append() returns new collection (immutable)', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1); // original unchanged
            expect($newCol->count())->toBe(2);
        });

        it('merge() combines two collections', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);
            $dto3 = IntegrationCollectionDTO::fromArray(['id' => '3', 'label' => 'C'], validate: false);

            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2, $dto3]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
        });

        it('first() and last() return correct items', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            expect($col->first()->id)->toBe('1');
            expect($col->last()->id)->toBe('2');
        });

        it('toArray() serializes all DTOs', function () {
            $dto1 = IntegrationCollectionDTO::fromArray(['id' => '1', 'label' => 'A'], validate: false);
            $dto2 = IntegrationCollectionDTO::fromArray(['id' => '2', 'label' => 'B'], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $arr = $col->toArray();

            expect($arr)->toBeArray();
            expect($arr[0])->toBeArray();
            expect($arr[0]['id'])->toBe('1');
        });

        it('empty collection isEmpty() returns true', function () {
            $col = DtoCollection::make([]);
            expect($col->isEmpty())->toBeTrue();
            expect($col->count())->toBe(0);
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });
    });

    // ── Validation rules generation ──────────────────────────────
    describe('validation rules generation', function () {
        it('rules() generates correct attribute-derived rules', function () {
            $rules = IntegrationUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:100');
        });

        it('rules() handles Nullable attribute', function () {
            $rules = IntegrationNullableDTO::rules();

            expect($rules['optional'])->toContain('sometimes');
            expect($rules['count'])->toContain('nullable');
        });

        it('rules() includes Same and Different cross-field rules', function () {
            $rules = IntegrationCrossFieldDTO::rules();

            expect($rules['passwordConfirmation'])->toContain('same:password');
            expect($rules['username'])->toContain('different:password');
        });

        it('rules() handles Pattern attribute', function () {
            $rules = IntegrationAddressDTO::rules();

            expect($rules['postalCode'])->toContain('regex:/^[A-Z]{2}\d{4}[A-Z]{2}$/');
        });

        it('rules() handles StartsWith attribute', function () {
            $rules = IntegrationAddressDTO::rules();

            expect($rules['website'])->toContain('starts_with:https://');
        });

        it('rules() handles In attribute', function () {
            $rules = IntegrationProductDTO::rules();

            expect($rules['state'])->toContain('in:draft,published,archived');
        });

        it('rules() handles Size attribute', function () {
            $rules = IntegrationProductDTO::rules();

            expect($rules['isbn'])->toContain('size:13');
        });

        it('rules() handles Accepted and Present', function () {
            $rules = IntegrationProductDTO::rules();

            expect($rules['terms'])->toContain('accepted');
            expect($rules['note'])->toContain('present');
        });
    });

    // ── Type system verification ──────────────────────────────
    describe('PHPStan L9 type safety verification', function () {
        it('all properties are public readonly', function () {
            $ref = new ReflectionClass(IntegrationUserDTO::class);
            foreach ($ref->getProperties() as $prop) {
                expect($prop->isPublic())->toBeTrue();
                expect($prop->isReadOnly())->toBeTrue();
            }
        });

        it('all public methods have return type declarations', function () {
            $ref = new ReflectionClass(IntegrationUserDTO::class);
            $baseRef = new ReflectionClass(DataTransferObject::class);

            foreach ($baseRef->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() === DataTransferObject::class) {
                    expect($method->hasReturnType())->toBeTrue();
                }
            }
        });

        it('DataTransferObject implements expected interfaces', function () {
            expect(DataTransferObject::class)->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class);
            expect(DataTransferObject::class)->implementsInterface(\JsonSerializable::class);
        });

        it('DtoCollection implements expected interfaces', function () {
            expect(DtoCollection::class)->implementsInterface(\Countable::class);
            expect(DtoCollection::class)->implementsInterface(\ArrayAccess::class);
            expect(DtoCollection::class)->implementsInterface(\IteratorAggregate::class);
            expect(DtoCollection::class)->implementsInterface(\JsonSerializable::class);
        });
    });
});
