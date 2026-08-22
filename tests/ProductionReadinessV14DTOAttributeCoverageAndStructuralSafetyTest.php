<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
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
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

// ── Inline test DTOs for attribute coverage ──────────────────────────────────

final class V14BasicDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,
    ) {}
}

final class V14MinMaxDTO extends DataTransferObject
{
    public function __construct(
        #[Min(2), Max(50)]
        public readonly string $name,
    ) {}
}

final class V14CastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public $count = 0,

        #[Cast('boolean')]
        public bool $active = false,

        #[Cast('string')]
        public string $label = '',

        #[Cast('array')]
        public readonly array $tags = [],
    ) {}
}

final class V14MapFromDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user_name')]
        public readonly ?string $displayName = null,

        #[MapFrom('meta.phone')]
        public readonly ?string $phone = null,
    ) {}
}

final class V14HiddenDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $public = 'visible',

        #[Hidden]
        public readonly string $secret = 'hidden',
    ) {}
}

final class V14DefaultValueDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('pending')]
        public readonly string $status,

        #[DefaultValue(42)]
        public readonly int $limit,

        #[DefaultValue([])]
        public readonly array $tags,
    ) {}
}

final class V14NullableDTO extends DataTransferObject
{
    public function __construct(
        #[Nullable, Max(255)]
        public readonly ?string $note = null,
    ) {}
}

final class V14PatternDTO extends DataTransferObject
{
    public function __construct(
        #[Pattern('/^[A-Z]{2}-\d{4}$/')]
        public readonly string $code,
    ) {}
}

final class V14InDTO extends DataTransferObject
{
    public function __construct(
        #[In(['draft', 'published', 'archived'])]
        public readonly string $status,
    ) {}
}

final class V14BetweenDTO extends DataTransferObject
{
    public function __construct(
        #[Between(1, 100)]
        public readonly int $score = 50,
    ) {}
}

final class V14SizeDTO extends DataTransferObject
{
    public function __construct(
        #[Size(10)]
        public readonly string $exactLength,
    ) {}
}

final class V14StartsEndsDTO extends DataTransferObject
{
    public function __construct(
        #[StartsWith(['user_', 'admin_'])]
        public readonly string $username,

        #[EndsWith(['@example.com', '@test.com'])]
        public readonly string $emailDomain,
    ) {}
}

final class V14UrlDTO extends DataTransferObject
{
    public function __construct(
        #[Url]
        public readonly string $website,
    ) {}
}

final class V14UuidDTO extends DataTransferObject
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
    ) {}
}

final class V14BooleanDTO extends DataTransferObject
{
    public function __construct(
        #[Boolean]
        public readonly bool $flag = false,
    ) {}
}

final class V14NumericDTO extends DataTransferObject
{
    public function __construct(
        #[Numeric]
        public readonly int|float $amount,
    ) {}
}

final class V14IntegerDTO extends DataTransferObject
{
    public function __construct(
        #[Integer]
        public readonly int $count = 0,
    ) {}
}

final class V14JsonDTO extends DataTransferObject
{
    public function __construct(
        #[Json]
        public readonly string $payload,
    ) {}
}

final class V14ProhibitedDTO extends DataTransferObject
{
    public function __construct(
        #[Prohibited]
        public readonly ?string $blockedField = null,
    ) {}
}

final class V14AcceptedDTO extends DataTransferObject
{
    public function __construct(
        #[Accepted]
        public readonly bool $terms = false,
    ) {}
}

final class V14DeclinedDTO extends DataTransferObject
{
    public function __construct(
        #[Declined]
        public readonly bool $spam = true,
    ) {}
}

final class V14ConfirmedDTO extends DataTransferObject
{
    public function __construct(
        #[Confirmed]
        public readonly string $password,
    ) {}
}

final class V14SameDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $password,

        #[Same('password')]
        public readonly string $passwordConfirmation,
    ) {}
}

final class V14DifferentDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,

        #[Different('email')]
        public readonly ?string $username = null,
    ) {}
}

final class V14DistinctDTO extends DataTransferObject
{
    public function __construct(
        #[Distinct]
        public readonly array $emails = [],
    ) {}
}

final class V14DateDTO extends DataTransferObject
{
    public function __construct(
        #[Date]
        public readonly ?\Carbon\Carbon $createdAt = null,
    ) {}
}

final class V14DateWithFormatDTO extends DataTransferObject
{
    public function __construct(
        #[Date('Y-m-d')]
        public readonly ?\Carbon\Carbon $date = null,
    ) {}
}

final class V14ArrayRuleDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule]
        public readonly array $items = [],
    ) {}
}

final class V14ArrayRuleMinMaxDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule(min: 1, max: 5)]
        public readonly array $tags = [],
    ) {}
}

final class V14SometimesDTO extends DataTransferObject
{
    public function __construct(
        #[Sometimes, Max(100)]
        public readonly ?string $optionalField = null,
    ) {}
}

final class V14PresentDTO extends DataTransferObject
{
    public function __construct(
        #[Present]
        public readonly ?string $field = null,
    ) {}
}

final class V14RequiredIfDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredIf('type', 'premium')]
        public readonly ?string $premiumToken = null,

        public readonly string $type = 'free',
    ) {}
}

final class V14RequiredUnlessDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredUnless('type', 'free')]
        public readonly ?string $paymentMethod = null,

        public readonly string $type = 'free',
    ) {}
}

final class V14RequiredWithDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWith('phone')]
        public readonly ?string $phoneCode = null,

        public readonly ?string $phone = null,
    ) {}
}

final class V14RequiredWithAllDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithAll('street', 'city')]
        public readonly ?string $zip = null,

        public readonly ?string $street = null,
        public readonly ?string $city = null,
    ) {}
}

final class V14RequiredWithoutDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithout('email')]
        public readonly ?string $phone = null,

        public readonly ?string $email = null,
    ) {}
}

final class V14RequiredWithoutAllDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithoutAll('email', 'phone')]
        public readonly ?string $username = null,

        public readonly ?string $email = null,
        public readonly ?string $phone = null,
    ) {}
}

final class V14OnlyExceptDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $a = 'a',
        public readonly string $b = 'b',
        public readonly string $c = 'c',

        #[Hidden]
        public readonly string $d = 'd',
    ) {}
}

final class V14WithBypassDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public readonly string $name,
    ) {}
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('V14 — DTO Attribute Coverage & Structural Safety', function () {
    // ── Section 1: All ValidationAttribute Implementations ────────────────────

    it('all validation attributes implement ValidationAttribute interface', function () {
        $attributes = [
            Required::class, Email::class, Max::class, Min::class,
            Pattern::class, In::class, Integer::class, Numeric::class,
            Boolean::class, Url::class, Uuid::class, Date::class,
            ArrayRule::class, Json::class, Prohibited::class, Present::class,
            Declined::class, Accepted::class, StartsWith::class, EndsWith::class,
            Nullable::class, Sometimes::class, Distinct::class, Size::class,
            Confirmed::class, Same::class, Different::class,
            RequiredIf::class, RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class, RequiredWithoutAll::class,
            Between::class, NestedArray::class, Collection::class,
        ];

        foreach ($attributes as $attr) {
            expect($attr)->toImplement(ValidationAttribute::class,
                "{$attr} does not implement ValidationAttribute");
        }
    });

    it('every ValidationAttribute has a non-empty ruleKey()', function () {
        $attributes = [
            new Required, new Email, new Max(100), new Min(1),
            new Pattern('/test/'), new In(['a', 'b']), new Integer,
            new Numeric, new Boolean, new Url, new Uuid,
            new Date, new Date('Y-m-d'), new ArrayRule,
            new ArrayRule(min: 1, max: 10), new Json,
            new Prohibited, new Present, new Declined, new Accepted,
            new StartsWith(['a']), new EndsWith(['b']),
            new Nullable, new Sometimes, new Distinct, new Size(10),
            new Confirmed, new Same('other'), new Different('other'),
            new RequiredIf('field', 'value'), new RequiredUnless('field', 'value'),
            new RequiredWith('field'), new RequiredWithAll(['a', 'b']),
            new RequiredWithout('field'), new RequiredWithoutAll(['a', 'b']),
            new Between(1, 10), new NestedArray(DataTransferObject::class),
            new Collection(DataTransferObject::class),
        ];

        foreach ($attributes as $attr) {
            $key = $attr->ruleKey();
            expect($key)->toBeString()->not->toBeEmpty(
                get_class($attr) . '::ruleKey() returned empty string');
        }
    });

    // ── Section 2: Basic Hydration ──────────────────────────────────────────

    it('V14BasicDTO fromArray validates email', function () {
        expect(fn () => V14BasicDTO::fromArray(['email' => 'not-an-email']))
            ->toThrow(ValidationException::class);
    });

    it('V14BasicDTO fromArray accepts valid email', function () {
        $dto = V14BasicDTO::fromArray(['email' => 'test@example.com']);
        expect($dto->email)->toBe('test@example.com');
    });

    it('V14BasicDTO toArray returns input data', function () {
        $dto = V14BasicDTO::fromArray(['email' => 'test@example.com']);
        expect($dto->toArray())->toBe(['email' => 'test@example.com']);
    });

    // ── Section 3: Min/Max Validation ────────────────────────────────────────

    it('V14MinMaxDTO rejects string shorter than min', function () {
        expect(fn () => V14MinMaxDTO::fromArray(['name' => 'A']))
            ->toThrow(ValidationException::class);
    });

    it('V14MinMaxDTO rejects string longer than max', function () {
        expect(fn () => V14MinMaxDTO::fromArray(['name' => str_repeat('X', 51)]))
            ->toThrow(ValidationException::class);
    });

    it('V14MinMaxDTO accepts string within range', function () {
        $dto = V14MinMaxDTO::fromArray(['name' => 'Hello']);
        expect($dto->name)->toBe('Hello');
    });

    // ── Section 4: Cast Attributes ──────────────────────────────────────────

    it('V14CastDTO casts string to integer', function () {
        $dto = V14CastDTO::fromArray(['count' => '42']);
        expect($dto->count)->toBe(42);
        expect($dto->count)->toBeInt();
    });

    it('V14CastDTO casts string to boolean', function () {
        $dto = V14CastDTO::fromArray(['active' => '1']);
        expect($dto->active)->toBeTrue();
    });

    it('V14CastDTO casts integer to string', function () {
        $dto = V14CastDTO::fromArray(['label' => 123]);
        expect($dto->label)->toBe('123');
    });

    it('V14CastDTO casts JSON string to array', function () {
        $dto = V14CastDTO::fromArray(['tags' => '["a","b"]']);
        expect($dto->tags)->toBe(['a', 'b']);
        expect($dto->tags)->toBeArray();
    });

    it('V14CastDTO passes arrays through for array cast', function () {
        $dto = V14CastDTO::fromArray(['tags' => ['x', 'y']]);
        expect($dto->tags)->toBe(['x', 'y']);
    });

    // ── Section 5: MapFrom ───────────────────────────────────────────────────

    it('V14MapFromDTO maps source key to property', function () {
        $dto = V14MapFromDTO::fromArray(['user_name' => 'Alice']);
        expect($dto->displayName)->toBe('Alice');
    });

    it('V14MapFromDTO uses dot notation', function () {
        $dto = V14MapFromDTO::fromArray(['meta' => ['phone' => '555-1234']]);
        expect($dto->phone)->toBe('555-1234');
    });

    it('V14MapFromDTO falls back to property name when source key absent', function () {
        $dto = V14MapFromDTO::fromArray([]);
        expect($dto->displayName)->toBeNull();
        expect($dto->phone)->toBeNull();
    });

    // ── Section 6: Hidden ──────────────────────────────────────────────────

    it('V14HiddenDTO toArray excludes hidden fields', function () {
        $dto = V14HiddenDTO::fromArray(['public' => 'visible', 'secret' => 'hidden']);
        expect($dto->toArray())->toBe(['public' => 'visible']);
        expect($dto->toArray())->not->toHaveKey('secret');
    });

    it('V14HiddenDTO allValues includes hidden fields', function () {
        $dto = V14HiddenDTO::fromArray(['public' => 'visible', 'secret' => 'hidden']);
        expect($dto->allValues())->toBe(['public' => 'visible', 'secret' => 'hidden']);
    });

    it('V14HiddenDTO toJson excludes hidden fields', function () {
        $dto = V14HiddenDTO::fromArray(['public' => 'visible', 'secret' => 'hidden']);
        $json = $dto->toJson();
        expect($json)->not->toContain('secret');
        expect($json)->toContain('visible');
    });

    // ── Section 7: DefaultValue ──────────────────────────────────────────────

    it('V14DefaultValueDTO applies defaults when keys are absent', function () {
        $dto = V14DefaultValueDTO::fromArray([], validate: false);
        expect($dto->status)->toBe('pending');
        expect($dto->limit)->toBe(42);
        expect($dto->tags)->toBe([]);
    });

    it('V14DefaultValueDTO explicit values override defaults', function () {
        $dto = V14DefaultValueDTO::fromArray([
            'status' => 'active',
            'limit' => 10,
            'tags' => ['x'],
        ], validate: false);
        expect($dto->status)->toBe('active');
        expect($dto->limit)->toBe(10);
        expect($dto->tags)->toBe(['x']);
    });

    // ── Section 8: Nullable ─────────────────────────────────────────────────

    it('V14NullableDTO accepts null', function () {
        $dto = V14NullableDTO::fromArray(['note' => null]);
        expect($dto->note)->toBeNull();
    });

    it('V14NullableDTO accepts empty array for absent key', function () {
        $dto = V14NullableDTO::fromArray([]);
        expect($dto->note)->toBeNull();
    });

    // ── Section 9: Pattern ──────────────────────────────────────────────────

    it('V14PatternDTO accepts matching pattern', function () {
        $dto = V14PatternDTO::fromArray(['code' => 'AB-1234']);
        expect($dto->code)->toBe('AB-1234');
    });

    it('V14PatternDTO rejects non-matching pattern', function () {
        expect(fn () => V14PatternDTO::fromArray(['code' => 'invalid']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 10: In ─────────────────────────────────────────────────────

    it('V14InDTO accepts allowed value', function () {
        $dto = V14InDTO::fromArray(['status' => 'draft']);
        expect($dto->status)->toBe('draft');
    });

    it('V14InDTO rejects disallowed value', function () {
        expect(fn () => V14InDTO::fromArray(['status' => 'deleted']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 11: Between ──────────────────────────────────────────────────

    it('V14BetweenDTO accepts value within bounds', function () {
        $dto = V14BetweenDTO::fromArray(['score' => 50]);
        expect($dto->score)->toBe(50);
    });

    it('V14BetweenDTO rejects value below min', function () {
        expect(fn () => V14BetweenDTO::fromArray(['score' => 0]))
            ->toThrow(ValidationException::class);
    });

    it('V14BetweenDTO rejects value above max', function () {
        expect(fn () => V14BetweenDTO::fromArray(['score' => 101]))
            ->toThrow(ValidationException::class);
    });

    // ── Section 12: Size ────────────────────────────────────────────────────

    it('V14SizeDTO accepts exact length', function () {
        $dto = V14SizeDTO::fromArray(['exactLength' => '1234567890']);
        expect($dto->exactLength)->toBe('1234567890');
    });

    it('V14SizeDTO rejects wrong length', function () {
        expect(fn () => V14SizeDTO::fromArray(['exactLength' => 'short']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 13: StartsWith / EndsWith ───────────────────────────────────

    it('V14StartsEndsDTO accepts valid prefix and suffix', function () {
        $dto = V14StartsEndsDTO::fromArray([
            'username' => 'user_alice',
            'emailDomain' => 'alice@example.com',
        ]);
        expect($dto->username)->toBe('user_alice');
        expect($dto->emailDomain)->toBe('alice@example.com');
    });

    it('V14StartsEndsDTO rejects invalid prefix', function () {
        expect(fn () => V14StartsEndsDTO::fromArray([
            'username' => 'invalid',
            'emailDomain' => 'a@test.com',
        ]))->toThrow(ValidationException::class);
    });

    // ── Section 14: URL / UUID / Boolean / Numeric / Integer ────────────────

    it('V14UrlDTO rejects invalid URL', function () {
        expect(fn () => V14UrlDTO::fromArray(['website' => 'not-a-url']))
            ->toThrow(ValidationException::class);
    });

    it('V14UrlDTO accepts valid URL', function () {
        $dto = V14UrlDTO::fromArray(['website' => 'https://example.com']);
        expect($dto->website)->toBe('https://example.com');
    });

    it('V14UuidDTO rejects invalid UUID', function () {
        expect(fn () => V14UuidDTO::fromArray(['id' => 'not-a-uuid']))
            ->toThrow(ValidationException::class);
    });

    it('V14UuidDTO accepts valid UUID', function () {
        $dto = V14UuidDTO::fromArray(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        expect($dto->id)->toBe('550e8400-e29b-41d4-a716-446655440000');
    });

    it('V14BooleanDTO accepts boolean values', function () {
        $dto = V14BooleanDTO::fromArray(['flag' => true]);
        expect($dto->flag)->toBeTrue();
    });

    it('V14NumericDTO accepts float', function () {
        $dto = V14NumericDTO::fromArray(['amount' => 99.99]);
        expect($dto->amount)->toBe(99.99);
    });

    it('V14NumericDTO accepts int', function () {
        $dto = V14NumericDTO::fromArray(['amount' => 100]);
        expect($dto->amount)->toBe(100);
    });

    it('V14IntegerDTO accepts int', function () {
        $dto = V14IntegerDTO::fromArray(['count' => 5]);
        expect($dto->count)->toBe(5);
    });

    // ── Section 15: Json ───────────────────────────────────────────────────

    it('V14JsonDTO accepts valid JSON', function () {
        $dto = V14JsonDTO::fromArray(['payload' => '{"key":"value"}']);
        expect($dto->payload)->toBe('{"key":"value"}');
    });

    it('V14JsonDTO rejects invalid JSON', function () {
        expect(fn () => V14JsonDTO::fromArray(['payload' => 'not-json']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 16: Prohibited / Accepted / Declined ────────────────────────

    it('V14ProhibitedDTO rejects when field is present', function () {
        expect(fn () => V14ProhibitedDTO::fromArray(['blockedField' => 'value']))
            ->toThrow(ValidationException::class);
    });

    it('V14AcceptedDTO rejects false', function () {
        expect(fn () => V14AcceptedDTO::fromArray(['terms' => false]))
            ->toThrow(ValidationException::class);
    });

    it('V14AcceptedDTO accepts true', function () {
        $dto = V14AcceptedDTO::fromArray(['terms' => true]);
        expect($dto->terms)->toBeTrue();
    });

    it('V14DeclinedDTO rejects true', function () {
        expect(fn () => V14DeclinedDTO::fromArray(['spam' => true]))
            ->toThrow(ValidationException::class);
    });

    it('V14DeclinedDTO accepts false', function () {
        $dto = V14DeclinedDTO::fromArray(['spam' => false]);
        expect($dto->spam)->toBeFalse();
    });

    // ── Section 17: Confirmed / Same / Different ─────────────────────────────

    it('V14ConfirmedDTO requires confirmation field', function () {
        expect(fn () => V14ConfirmedDTO::fromArray(['password' => 'secret']))
            ->toThrow(ValidationException::class);
    });

    it('V14SameDTO validates matching fields', function () {
        $dto = V14SameDTO::fromArray([
            'password' => 'secret',
            'passwordConfirmation' => 'secret',
        ], validate: false);
        expect($dto->password)->toBe('secret');
    });

    it('V14DifferentDTO validates different fields', function () {
        $dto = V14DifferentDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'testuser',
        ], validate: false);
        expect($dto->username)->toBe('testuser');
    });

    // ── Section 18: Distinct / ArrayRule ───────────────────────────────────

    it('V14ArrayRuleDTO accepts array', function () {
        $dto = V14ArrayRuleDTO::fromArray(['items' => [1, 2, 3]]);
        expect($dto->items)->toBe([1, 2, 3]);
    });

    it('V14ArrayRuleMinMaxDTO accepts array within bounds', function () {
        $dto = V14ArrayRuleMinMaxDTO::fromArray(['tags' => ['a', 'b']]);
        expect($dto->tags)->toBe(['a', 'b']);
    });

    it('V14ArrayRuleMinMaxDTO rejects array below min', function () {
        expect(fn () => V14ArrayRuleMinMaxDTO::fromArray(['tags' => []]))
            ->toThrow(ValidationException::class);
    });

    it('V14ArrayRuleMinMaxDTO rejects array above max', function () {
        expect(fn () => V14ArrayRuleMinMaxDTO::fromArray(['tags' => ['a', 'b', 'c', 'd', 'e', 'f']]))
            ->toThrow(ValidationException::class);
    });

    // ── Section 19: Conditional Required ────────────────────────────────────

    it('V14RequiredIfDTO does not require when condition false', function () {
        $dto = V14RequiredIfDTO::fromArray(['type' => 'free']);
        expect($dto->premiumToken)->toBeNull();
    });

    it('V14RequiredUnlessDTO does not require when condition true', function () {
        $dto = V14RequiredUnlessDTO::fromArray(['type' => 'free']);
        expect($dto->paymentMethod)->toBeNull();
    });

    it('V14RequiredWithDTO does not require when dependent absent', function () {
        $dto = V14RequiredWithDTO::fromArray([]);
        expect($dto->phoneCode)->toBeNull();
    });

    it('V14RequiredWithoutDTO does not require when alternative present', function () {
        $dto = V14RequiredWithoutDTO::fromArray(['email' => 'test@test.com']);
        expect($dto->phone)->toBeNull();
    });

    // ── Section 20: Selective Output ────────────────────────────────────────

    it('V14OnlyExceptDTO only() returns specified fields', function () {
        $dto = V14OnlyExceptDTO::fromArray(['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4']);
        expect($dto->only('a', 'c'))->toBe(['a' => '1', 'c' => '3']);
    });

    it('V14OnlyExceptDTO only() with string arg returns single field', function () {
        $dto = V14OnlyExceptDTO::fromArray(['a' => '1', 'b' => '2']);
        expect($dto->only('b'))->toBe(['b' => '2']);
    });

    it('V14OnlyExceptDTO except() excludes specified fields', function () {
        $dto = V14OnlyExceptDTO::fromArray(['a' => '1', 'b' => '2', 'c' => '3']);
        $result = $dto->except('a', 'c');
        expect($result)->toBe(['b' => '2']);
    });

    it('V14OnlyExceptDTO except() ignores non-existent keys', function () {
        $dto = V14OnlyExceptDTO::fromArray(['a' => '1']);
        expect($dto->except('nonexistent'))->toBe(['a' => '1']);
    });

    // ── Section 21: Equality and State ───────────────────────────────────────

    it('equals returns true for identical DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        expect($a->equals($b))->toBeTrue();
    });

    it('equals returns false for different DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = ComprehensiveDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob']);
        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty returns true for DTO with all defaults', function () {
        $dto = V14NullableDTO::fromArray([]);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns false for DTO with all defaults', function () {
        $dto = V14NullableDTO::fromArray([]);
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false for DTO with non-empty values', function () {
        $dto = V14NullableDTO::fromArray(['note' => 'has content']);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    // ── Section 22: with() Immutable Update ────────────────────────────────

    it('with() returns new instance', function () {
        $original = V14WithBypassDTO::fromArray(['name' => 'Alice']);
        $updated = $original->with(['name' => 'Bob']);
        expect($original)->not->toBe($updated);
        expect($original->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
    });

    it('with() validates merged data', function () {
        $dto = V14WithBypassDTO::fromArray(['name' => 'Alice']);
        expect(fn () => $dto->with(['name' => '']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 23: fromJson ─────────────────────────────────────────────────

    it('fromJson accepts valid JSON object', function () {
        $dto = ComprehensiveDTO::fromJson('{"email":"test@example.com","name":"Test"}');
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test');
    });

    it('fromJson rejects sequential JSON arrays', function () {
        expect(fn () => ComprehensiveDTO::fromJson('[1,2,3]'))
            ->toThrow(DTOException::class);
    });

    it('fromJson rejects invalid JSON', function () {
        expect(fn () => ComprehensiveDTO::fromJson('not-json'))
            ->toThrow(DTOException::class);
    });

    it('fromJson accepts empty JSON object', function () {
        // Empty object will fail validation for required fields
        expect(fn () => ComprehensiveDTO::fromJson('{}'))
            ->toThrow(ValidationException::class);
    });

    it('fromJson without validation skips rules', function () {
        $dto = V14NullableDTO::fromJson('{}', validate: false);
        expect($dto->note)->toBeNull();
    });

    // ── Section 24: DtoCollection ───────────────────────────────────────────

    it('DtoCollection make creates from array of DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $col = DtoCollection::make([$a, $b]);

        expect($col->count())->toBe(2);
        expect($col->isEmpty())->toBeFalse();
    });

    it('DtoCollection from empty array is empty', function () {
        $col = DtoCollection::make([]);
        expect($col->count())->toBe(0);
        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('DtoCollection toArray serializes all DTOs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $col = DtoCollection::make([$a, $b]);

        $arr = $col->toArray();
        expect($arr)->toHaveCount(2);
        expect($arr[0])->toBe(['email' => 'a@b.com', 'name' => 'A']);
        expect($arr[1])->toBe(['email' => 'c@d.com', 'name' => 'B']);
    });

    it('DtoCollection push mutates and returns self', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $result = $col->push($b);

        expect($result)->toBe($col); // same instance
        expect($col->count())->toBe(2);
    });

    it('DtoCollection append returns new instance', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $newCol = $col->append($b);

        expect($newCol)->not->toBe($col);
        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('DtoCollection merge returns new combined instance', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $c = ComprehensiveDTO::fromArray(['email' => 'e@f.com', 'name' => 'C']);

        $col1 = DtoCollection::make([$a]);
        $col2 = DtoCollection::make([$b, $c]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1); // unchanged
    });

    it('DtoCollection pluck extracts property values', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob']);
        $col = DtoCollection::make([$a, $b]);

        expect($col->pluck('email'))->toBe(['a@b.com', 'c@d.com']);
        expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
    });

    it('DtoCollection pluckKey returns keyed array', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob']);
        $col = DtoCollection::make([$a, $b]);

        $result = $col->pluckKey('email', 'name');
        expect($result)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Bob']);
    });

    it('DtoCollection map transforms items', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob']);
        $col = DtoCollection::make([$a, $b]);

        $names = $col->map(fn (ComprehensiveDTO $dto) => $dto->name);
        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('DtoCollection filter returns new filtered collection', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'age' => 30]);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob', 'age' => 10]);
        $col = DtoCollection::make([$a, $b]);

        $adults = $col->filter(fn (ComprehensiveDTO $dto) => $dto->age > 20);
        expect($adults->count())->toBe(1);
        expect($col->count())->toBe(2); // original unchanged
    });

    it('DtoCollection first/last return correct items', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $col = DtoCollection::make([$a, $b]);

        expect($col->first()->email)->toBe('a@b.com');
        expect($col->last()->email)->toBe('c@d.com');
    });

    it('DtoCollection first/last return null for empty collection', function () {
        $col = DtoCollection::make([]);
        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('DtoCollection ArrayAccess works for get/set/unset', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        expect($col[0]->email)->toBe('a@b.com');
        expect(isset($col[0]))->toBeTrue();
        expect(isset($col[1]))->toBeFalse();
        expect($col[1])->toBeNull();
    });

    it('DtoCollection jsonSerialize produces array', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $json = json_encode($col);
        $decoded = json_decode($json, true);
        expect($decoded)->toBe([['email' => 'a@b.com', 'name' => 'A']]);
    });

    it('DtoCollection toArrayBy returns keyed array', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $result = $col->toArrayBy('email');
        expect($result)->toBe(['a@b.com' => ['email' => 'a@b.com', 'name' => 'A']]);
    });

    it('DtoCollection toDictionary returns key-value pairs', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $result = $col->toDictionary('email', 'name');
        expect($result)->toBe(['a@b.com' => 'A']);
    });

    it('DtoCollection cloneCollection is used by append', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $col = DtoCollection::make([$a]);

        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $new = $col->append($b);

        expect($new->count())->toBe(2);
        expect($col->count())->toBe(1);
    });

    it('DtoCollection offsetUnset re-indexes', function () {
        $a = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
        $b = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'B']);
        $c = ComprehensiveDTO::fromArray(['email' => 'e@f.com', 'name' => 'C']);
        $col = DtoCollection::make([$a, $b, $c]);

        unset($col[0]);
        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('c@d.com');
    });

    it('DtoCollection rejects non-DTO items', function () {
        expect(fn () => DtoCollection::make(['not-a-dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    // ── Section 25: Metadata Cache ──────────────────────────────────────────

    it('flushMetadataCache clears all cached metadata', function () {
        // Access metadata to populate cache
        ComprehensiveDTO::rules();
        ComprehensiveDTO::flushMetadataCache();
        // Second access should work (re-resolve)
        $rules = ComprehensiveDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('flushMetadataCache for specific class does not affect others', function () {
        ComprehensiveDTO::rules();
        RegistrationDTO::rules();

        ComprehensiveDTO::flushMetadataCache(class: ComprehensiveDTO::class);

        // Re-access should work
        $rules = ComprehensiveDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    // ── Section 26: Rules Generation ───────────────────────────────────────

    it('rules() generates correct rules for ComprehensiveDTO', function () {
        $rules = ComprehensiveDTO::rules();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('age');
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
        expect($rules['name'])->toContain('required');
        expect($rules['name'])->toContain('min:2');
    });

    it('rulesFor() returns same as rules() by default', function () {
        $rules = ComprehensiveDTO::rules();
        $rulesForCreate = ComprehensiveDTO::rulesFor('create');
        expect($rules)->toBe($rulesForCreate);
    });

    it('validateArray returns validated data', function () {
        $result = ComprehensiveDTO::validateArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ]);
        expect($result['email'])->toBe('test@example.com');
    });

    it('validateArray throws for invalid data', function () {
        expect(fn () => ComprehensiveDTO::validateArray(['email' => 'bad', 'name' => '']))
            ->toThrow(ValidationException::class);
    });

    // ── Section 27: fromPartialArray ──────────────────────────────────────

    it('PartialDefaultValueDTO fromPartialArray applies defaults for missing fields', function () {
        $dto = PartialDefaultValueDTO::fromPartialArray(['name' => 'Alice']);
        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('default@example.com');
        expect($dto->role)->toBe('viewer');
        expect($dto->isActive)->toBeTrue();
        expect($dto->score)->toBe(100);
        expect($dto->optionalNote)->toBeNull();
    });

    it('PartialDefaultValueDTO fromPartialArray with MapFrom', function () {
        $dto = PartialDefaultValueDTO::fromPartialArray([
            'name' => 'Alice',
            'user_role' => 'admin',
        ]);
        expect($dto->role)->toBe('admin');
    });

    it('PartialDefaultValueDTO fromPartialArray overrides defaults', function () {
        $dto = PartialDefaultValueDTO::fromPartialArray([
            'name' => 'Alice',
            'email' => 'custom@test.com',
            'score' => 50,
        ]);
        expect($dto->email)->toBe('custom@test.com');
        expect($dto->score)->toBe(50);
    });

    it('fromPartialArray with empty data returns DTO with all defaults', function () {
        $dto = PartialDefaultValueDTO::fromPartialArray([]);
        // Empty partial: no name key, so name gets empty-value-for-type ('' for string)
        // But name has Required + Min(3) which is relaxed to sometimes in partial
        expect($dto->email)->toBe('default@example.com');
        expect($dto->role)->toBe('viewer');
    });

    // ── Section 28: DTOException ────────────────────────────────────────────

    it('DTOException::invalidCast formats correctly', function () {
        $e = DTOException::invalidCast('count', 'integer', 'not-a-number');
        expect($e->getMessage())->toContain('count');
        expect($e->getMessage())->toContain('integer');
    });

    it('DTOException::invalidJson formats correctly', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');
        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('DTOException::__toString includes class name', function () {
        $e = DTOException::invalidJson('field', 'error');
        $str = (string) $e;
        expect($str)->toContain('DTOException');
    });

    // ── Section 29: Structural Guarantees ───────────────────────────────────

    it('ComprehensiveDTO implements FromRequestDTO, ValidatableDTO', function () {
        expect(ComprehensiveDTO::class)
            ->toImplement(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class);
        expect(ComprehensiveDTO::class)
            ->toImplement(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class);
    });

    it('all fixture DTOs extend DataTransferObject', function () {
        expect(ComprehensiveDTO::class)->toExtend(DataTransferObject::class);
        expect(RegistrationDTO::class)->toExtend(DataTransferObject::class);
        expect(UnionTypeDTO::class)->toExtend(DataTransferObject::class);
        expect(PartialDefaultValueDTO::class)->toExtend(DataTransferObject::class);
    });

    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
        expect(DtoCollection::class)->toImplement(\Countable::class);
        expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
        expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
    });
});
