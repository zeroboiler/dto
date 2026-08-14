<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
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
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
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
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Fixture: Full attribute coverage DTO ──

class FullAttributeDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[MapFrom('user_phone')]
        public readonly ?string $phone = null,

        #[Cast('integer')]
        public readonly int $age = 0,

        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[Boolean]
        public readonly bool $isActive = true,

        #[Integer]
        public readonly int $score = 0,

        #[Numeric]
        public readonly float $rate = 0.0,

        #[Url]
        public readonly ?string $website = null,

        #[Uuid]
        public readonly ?string $uuid = null,

        #[Pattern('/^[A-Z]{2}-\d{4}$/')]
        public readonly ?string $code = null,

        #[In(['admin', 'editor', 'viewer'])]
        public readonly string $role = 'viewer',

        #[Between(1, 100)]
        public readonly int $percentage = 50,

        #[Size(10)]
        public readonly ?string $username = null,

        #[StartsWith('+')]
        public readonly ?string $internationalPhone = null,

        #[EndsWith('@example.com')]
        public readonly ?string $companyEmail = null,

        #[Json]
        public readonly ?string $metadata = null,

        #[Cast('array')]
        public readonly array $tags = [],

        #[ArrayRule(min: 1, max: 5)]
        public readonly array $favorites = [],

        #[Nullable]
        public readonly ?string $optionalField = null,

        #[Sometimes]
        public readonly ?string $conditionalField = null,

        #[Present]
        public readonly ?string $mustBePresent = null,

        #[Prohibited]
        public readonly ?string $blockedField = null,

        #[Accepted]
        public readonly bool $termsAccepted = false,

        #[Declined]
        public readonly bool $termsDeclined = false,

        #[Confirmed]
        public readonly ?string $passwordConfirmation = null,

        #[Same('name')]
        public readonly ?string $alias = null,

        #[Different('name')]
        public readonly ?string $usernameField = null,

        #[Distinct]
        public readonly array $uniqueItems = [],

        #[RequiredWith('email')]
        public readonly ?string $emailDomain = null,

        #[RequiredWithAll('email', 'name')]
        public readonly ?string $fullName = null,

        #[RequiredWithout('phone')]
        public readonly ?string $mobile = null,

        #[RequiredWithoutAll('phone', 'mobile')]
        public readonly ?string $contactMethod = null,

        #[RequiredIf('role:admin')]
        public readonly ?string $adminNote = null,

        #[RequiredUnless('role:admin')]
        public readonly ?string $userNote = null,

        #[Date]
        public readonly ?string $birthDate = null,

        #[Date(format: 'd-m-Y')]
        public readonly ?string $customDate = null,

        #[Hidden]
        public readonly ?string $secret = null,
    ) {}
}

// ── Fixture: Nested DTO for NestedArray test ──

class NestedItemDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title,

        #[Required, Min(0)]
        public readonly int $quantity = 0,
    ) {}
}

class ParentWithNestedDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderName,

        #[NestedArray(NestedItemDTO::class)]
        public readonly array $items = [],
    ) {}
}

describe('DTO attribute contract full coverage', function () {
    describe('FullAttributeDTO rules resolution', function () {
        it('resolves rules for all validation attributes', function () {
            $rules = FullAttributeDTO::rules();

            // Core rules
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');

            expect($rules)->toHaveKey('name');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');

            // Boolean, Integer, Numeric
            expect($rules['isActive'])->toContain('boolean');
            expect($rules['score'])->toContain('integer');
            expect($rules['rate'])->toContain('numeric');

            // Format rules
            expect($rules['website'])->toContain('url');
            expect($rules['uuid'])->toContain('uuid');
            expect($rules['code'])->toContain('regex:/^[A-Z]{2}-\d{4}$/');
            expect($rules['role'])->toContain('in:admin,editor,viewer');
            expect($rules['percentage'])->toContain('between:1,100');
            expect($rules['size'])->toContain('size:10');

            // String matching
            expect($rules['internationalPhone'])->toContain('starts_with:+');
            expect($rules['companyEmail'])->toContain('ends_with:@example.com');

            // JSON, Array
            expect($rules['metadata'])->toContain('json');
            expect($rules['tags'])->toContain('array');
            expect($rules['favorites'])->toContain('array');
            expect($rules['favorites'])->toContain('min:1');
            expect($rules['favorites'])->toContain('max:5');

            // Field presence
            expect($rules['optionalField'])->toContain('nullable');
            expect($rules['conditionalField'])->toContain('sometimes');
            expect($rules['mustBePresent'])->toContain('present');
            expect($rules['blockedField'])->toContain('prohibited');

            // Acceptance
            expect($rules['termsAccepted'])->toContain('accepted');
            expect($rules['termsDeclined'])->toContain('declined');

            // Cross-field
            expect($rules['passwordConfirmation'])->toContain('confirmed');
            expect($rules['alias'])->toContain('same:name');
            expect($rules['usernameField'])->toContain('different:name');

            // Conditional required
            expect($rules['emailDomain'])->toContain('required_with:email');
            expect($rules['fullName'])->toContain('required_with_all:email,name');
            expect($rules['mobile'])->toContain('required_without:phone');
            expect($rules['contactMethod'])->toContain('required_without_all:phone,mobile');

            // Date
            expect($rules['birthDate'])->toContain('date');
            expect($rules['customDate'])->toContain('date_format:d-m-Y');

            // Hidden field should still have rules (only excluded from toArray)
            expect($rules)->not->toHaveKey('secret');
        });

        it('rules do not contain duplicate entries', function () {
            $rules = FullAttributeDTO::rules();

            foreach ($rules as $field => $fieldRules) {
                $stringRules = array_filter($fieldRules, fn (mixed $r): bool => is_string($r));
                expect(array_unique($stringRules))->toHaveCount(count($stringRules));
            }
        });

        it('MapFrom affects source key but not rule key', function () {
            $rules = FullAttributeDTO::rules();

            expect($rules)->toHaveKey('phone');
            expect($rules)->not->toHaveKey('user_phone');
        });
    });

    describe('FullAttributeDTO hydration', function () {
        it('creates from array with MapFrom key mapping', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'user_phone' => '+1234567890',
            ], validate: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->phone)->toBe('+1234567890');
            expect($dto->age)->toBe(0);
            expect($dto->status)->toBe('active');
            expect($dto->isActive)->toBeTrue();
        });

        it('applies Cast attribute for integer type', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => '25',
            ], validate: false);

            expect($dto->age)->toBe(25);
            expect($dto->age)->toBeInt();
        });

        it('applies Cast array from JSON string', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'tags' => '{"laravel":true,"php":true}',
            ], validate: false);

            expect($dto->tags)->toBeArray();
            expect($dto->tags)->toHaveKey('laravel');
        });

        it('uses DefaultValue when source key is absent', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('respects explicit null values without applying defaults', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'phone' => null,
            ], validate: false);

            expect($dto->phone)->toBeNull();
        });

        it('serializes to array excluding hidden fields', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'secret' => 'password123',
            ], validate: false);

            $array = $dto->toArray();
            expect($array)->not->toHaveKey('secret');
        });

        it('allValues includes hidden fields', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'secret' => 'password123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('secret');
            expect($all['secret'])->toBe('password123');
        });

        it('serializes to JSON correctly', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('only() returns specified fields', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $only = $dto->only('email');
            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
        });

        it('except() excludes specified fields', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });

        it('equals() compares two DTOs by toArray output', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Alice'];
            $dto1 = FullAttributeDTO::fromArray($data, validate: false);
            $dto2 = FullAttributeDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('with() creates immutable copy with overrides', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($updated->name)->toBe('Bob');
            expect($dto->name)->toBe('Alice'); // original unchanged
            expect($updated)->not->toBe($dto);
        });
    });

    describe('FullAttributeDTO partial updates', function () {
        it('fromPartialArray hydrates only provided fields', function () {
            $dto = FullAttributeDTO::fromPartialArray([
                'name' => 'Updated Name',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated Name');
            expect($dto->status)->toBe('active'); // default
            expect($dto->age)->toBe(0); // type-appropriate empty
        });

        it('fromJson round-trips correctly', function () {
            $dto = FullAttributeDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $json = $dto->toJson();
            $restored = FullAttributeDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe('test@example.com');
            expect($restored->name)->toBe('Alice');
        });

        it('fromJson rejects sequential arrays', function () {
            expect(fn () => FullAttributeDTO::fromJson('["test","data"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson rejects invalid JSON', function () {
            expect(fn () => FullAttributeDTO::fromJson('{invalid json}', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    describe('NestedArray hydration', function () {
        it('hydrates nested DTOs from array data', function () {
            $dto = ParentWithNestedDTO::fromArray([
                'orderName' => 'Test Order',
                'items' => [
                    ['title' => 'Item 1', 'quantity' => 5],
                    ['title' => 'Item 2', 'quantity' => 3],
                ],
            ], validate: false);

            expect($dto->items)->toHaveCount(2);
            expect($dto->items[0])->toBeInstanceOf(NestedItemDTO::class);
            expect($dto->items[0]->title)->toBe('Item 1');
            expect($dto->items[0]->quantity)->toBe(5);
            expect($dto->items[1]->title)->toBe('Item 2');
        });

        it('serializes nested DTOs recursively in toArray', function () {
            $dto = ParentWithNestedDTO::fromArray([
                'orderName' => 'Test Order',
                'items' => [['title' => 'Item 1', 'quantity' => 5]],
            ], validate: false);

            $array = $dto->toArray();

            expect($array['items'][0])->toBe(['title' => 'Item 1', 'quantity' => 5]);
        });

        it('handles empty nested array', function () {
            $dto = ParentWithNestedDTO::fromArray([
                'orderName' => 'Empty Order',
                'items' => [],
            ], validate: false);

            expect($dto->items)->toBe([]);
        });
    });

    describe('Distinct attribute wildcard rules', function () {
        it('generates wildcard distinct rule for distinct arrays', function () {
            $rules = FullAttributeDTO::rules();

            expect($rules)->toHaveKey('uniqueItems.*');
            expect($rules['uniqueItems.*'])->toContain('distinct');
        });
    });
});
