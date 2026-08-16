<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Json as JsonAttr;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Date as DateAttr;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

afterEach(function () {
    DataTransferObject::flushMetadataCache();
});

// ── DTOManager full delegation tests ───────────────────────────

describe('V39 DTOManager validate() delegation', function () {
    it('validates data via DTO class', function () {
        $manager = new DTOManager;

        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ]);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
    });
});

describe('V39 DTOManager make() delegation', function () {
    it('creates DTO from array', function () {
        $manager = new DTOManager;
        $dto = $manager->make(MinimalDTO::class, ['name' => 'Alice', 'value' => 'v1']);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Alice');
    });
});

describe('V39 DTOManager makeFromJson() delegation', function () {
    it('creates DTO from JSON string', function () {
        $manager = new DTOManager;
        $dto = $manager->makeFromJson(MinimalDTO::class, '{"name":"Alice","value":"v1"}');

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Alice');
    });

    it('throws DTOException for invalid JSON', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(MinimalDTO::class, 'not-json'))
            ->toThrow(DTOException::class);
    });
});

describe('V39 DTOManager fromJson() delegation', function () {
    it('creates DTO from JSON string', function () {
        $manager = new DTOManager;
        $dto = $manager->fromJson(MinimalDTO::class, '{"name":"Bob","value":"v2"}');

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Bob');
    });
});

describe('V39 DTOManager rules() delegation', function () {
    it('returns rules from DTO class', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toHaveKey('email');
    });
});

describe('V39 DTOManager rulesFor() delegation', function () {
    it('returns action-scoped rules', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

        expect($rules)->toBe($manager->rules(CreateUserDTO::class));
    });
});

describe('V39 DTOManager schema() delegation', function () {
    it('generates OpenAPI schema for simple DTO', function () {
        $manager = new DTOManager;
        $schema = $manager->schema(MinimalDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
    });
});

describe('V39 DTOManager fromPartialArray() delegation', function () {
    it('creates DTO from partial data', function () {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(MinimalDTO::class, ['name' => 'Alice']);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Alice');
    });
});

// ── DTOCast serialization edge cases ───────────────────────────

describe('V39 DTOCast get() edge cases', function () {
    it('returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->get($model, 'payload', null, []);

        expect($result)->toBeNull();
    });

    it('returns DTO from JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $json = json_encode(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x']);
        $result = $cast->get($model, 'payload', $json, []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
        expect($result->email)->toBe('a@b.com');
    });

    it('returns DTO from array', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->get($model, 'payload', [
            'email' => 'a@b.com', 'name' => 'Test', 'password' => 'x',
        ], []);

        expect($result)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('returns null for invalid JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->get($model, 'payload', 'not-json', []);

        expect($result)->toBeNull();
    });

    it('returns null for non-array non-string value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->get($model, 'payload', 42, []);

        expect($result)->toBeNull();
    });
});

describe('V39 DTOCast set() edge cases', function () {
    it('returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->set($model, 'payload', null, []);

        expect($result)->toBeNull();
    });

    it('serializes DTO instance to JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Test', 'password' => 'x',
        ], validate: false);
        $result = $cast->set($model, 'payload', $dto, []);

        expect($result)->toBeJson();
        $decoded = json_decode($result, true);
        expect($decoded['email'])->toBe('a@b.com');
    });

    it('hydrates and serializes raw array', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = (object) [];
        $result = $cast->set($model, 'payload', [
            'email' => 'a@b.com', 'name' => 'Test', 'password' => 'x',
        ], []);

        expect($result)->toBeJson();
    });

    it('throws InvalidArgumentException for unexpected type', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];

        expect(fn () => $cast->set($model, 'payload', 42, []))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('V39 DTOCast serialize() edge cases', function () {
    it('serializes DTO to array', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Test', 'password' => 'x',
        ], validate: false);
        $result = $cast->serialize($model, 'payload', $dto, []);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('email');
        // Hidden property excluded
        expect($result)->not->toHaveKey('password');
    });

    it('returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = (object) [];
        $result = $cast->serialize($model, 'payload', null, []);

        expect($result)->toBeNull();
    });
});

// ── Metadata cache TTL behavior ────────────────────────────────

describe('V39 metadata cache TTL invalidation', function () {
    it('setMetadataCacheTtl affects cache behavior', function () {
        // Resolve first to populate cache
        MinimalDTO::rules();
        $cached = true;

        // Flush and set short TTL
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);

        // Resolve again — with TTL=0, cache is always stale
        $rules = MinimalDTO::rules();

        expect($rules)->toBeArray();
    });

    it('flushMetadataCache clears specific class', function () {
        MinimalDTO::rules();
        CreateUserDTO::rules();

        // Flush only one class
        DataTransferObject::flushMetadataCache(MinimalDTO::class);

        // Re-resolving the flushed class should work
        $rules = MinimalDTO::rules();
        expect($rules)->toBeArray();
    });

    it('flushMetadataCache with null clears all', function () {
        MinimalDTO::rules();
        CreateUserDTO::rules();

        DataTransferObject::flushMetadataCache(null);

        // Both should re-resolve fine
        expect(MinimalDTO::rules())->toBeArray();
        expect(CreateUserDTO::rules())->toBeArray();
    });
});

// ── NoConstructorDTO edge case ────────────────────────────────

describe('V39 NoConstructorDTO handling', function () {
    it('resolves to empty metadata for DTO without constructor', function () {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
    });

    it('rules() returns empty for DTO without constructor', function () {
        $rules = NoConstructorDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toBeEmpty();
    });
});

// ── AllDefaultsDTO edge case ───────────────────────────────────

describe('V39 AllDefaultsDTO handling', function () {
    it('creates DTO from empty array using all defaults', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    });

    it('overrides individual defaults', function () {
        $dto = AllDefaultsDTO::fromArray(['name' => 'Custom'], validate: false);

        expect($dto->name)->toBe('Custom');
    });
});

// ── DtoCollection boundary tests ───────────────────────────────

describe('V39 DtoCollection make() factory', function () {
    it('creates empty collection', function () {
        $col = DtoCollection::make();

        expect($col->count())->toBe(0);
        expect($col->isEmpty())->toBeTrue();
    });

    it('creates collection with items', function () {
        $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'v'], validate: false);
        $col = DtoCollection::make([$dto]);

        expect($col->count())->toBe(1);
    });
});

describe('V39 DtoCollection first() and last() edge cases', function () {
    it('first() returns null for empty collection', function () {
        $col = new DtoCollection;

        expect($col->first())->toBeNull();
    });

    it('last() returns null for empty collection', function () {
        $col = new DtoCollection;

        expect($col->last())->toBeNull();
    });

    it('first() and last() return same item for single-item collection', function () {
        $dto = MinimalDTO::fromArray(['name' => 'A', 'value' => 'v'], validate: false);
        $col = new DtoCollection([$dto]);

        expect($col->first())->toBe($dto);
        expect($col->last())->toBe($dto);
    });

    it('first() returns first item, last() returns last item', function () {
        $a = MinimalDTO::fromArray(['name' => 'A', 'value' => 'v1'], validate: false);
        $b = MinimalDTO::fromArray(['name' => 'B', 'value' => 'v2'], validate: false);
        $c = MinimalDTO::fromArray(['name' => 'C', 'value' => 'v3'], validate: false);
        $col = new DtoCollection([$a, $b, $c]);

        expect($col->first()->name)->toBe('A');
        expect($col->last()->name)->toBe('C');
    });
});

describe('V39 DtoCollection allValues() includes hidden', function () {
    it('serializes all including hidden properties', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);
        $col = new DtoCollection([$dto]);

        $all = $col->allValues();

        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });
});

// ── Contract compliance tests ───────────────────────────────────

describe('V39 DTO implements Arrayable', function () {
    it('toArray() returns array', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->toArray())->toBeArray();
    });
});

describe('V39 DTO implements JsonSerializable', function () {
    it('jsonSerialize() returns array', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        expect($dto->jsonSerialize())->toBeArray();
    });

    it('json_encode works on DTO', function () {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);

        $json = json_encode($dto);
        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['name'])->toBe('Alice');
    });
});

describe('V39 DTO implements FromRequestDTO', function () {
    it('fromRequest() is callable as static method', function () {
        $request = new \Illuminate\Http\Request([
            'name' => 'Alice',
            'value' => 'v1',
        ]);

        $dto = MinimalDTO::fromRequest($request, validate: false);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Alice');
    });
});

describe('V39 DTO implements ValidatableDTO', function () {
    it('rules() returns array', function () {
        expect(CreateUserDTO::rules())->toBeArray();
    });

    it('rulesFor() returns array', function () {
        expect(CreateUserDTO::rulesFor('create'))->toBeArray();
    });
});

// ── fromJson edge cases ────────────────────────────────────────

describe('V39 fromJson() edge cases', function () {
    it('accepts empty JSON object', function () {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('rejects empty string', function () {
        expect(fn () => MinimalDTO::fromJson('', validate: false))
            ->toThrow(DTOException::class);
    });

    it('rejects JSON null', function () {
        expect(fn () => MinimalDTO::fromJson('null', validate: false))
            ->toThrow(DTOException::class);
    });

    it('rejects JSON number', function () {
        expect(fn () => MinimalDTO::fromJson('42', validate: false))
            ->toThrow(DTOException::class);
    });
});

// ── with() immutability guarantee ──────────────────────────────

describe('V39 with() immutability', function () {
    it('original DTO is unchanged after with()', function () {
        $original = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $modified = $original->with(['name' => 'Bob']);

        expect($original->name)->toBe('Alice');
        expect($original->value)->toBe('v1');
        expect($modified->name)->toBe('Bob');
        expect($modified->value)->toBe('v1');
    });

    it('with() preserves all unmodified fields', function () {
        $original = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'v1'], validate: false);
        $modified = $original->with(['value' => 'v2']);

        expect($modified->name)->toBe('Alice');
        expect($modified->value)->toBe('v2');
    });
});

// ── DotNotationDTO MapFrom support ─────────────────────────────

describe('V39 MapFrom dot notation support', function () {
    it('maps dot-notation keys to properties', function () {
        $dto = DotNotationDTO::fromArray([
            'user' => [
                'email' => 'a@b.com',
            ],
            'user.name' => 'Alice',
        ], validate: false);

        expect($dto)->toBeInstanceOf(DotNotationDTO::class);
    });
});

// ── DTOException edge cases ────────────────────────────────────

describe('V39 DTOException contract', function () {
    it('invalidCast() produces descriptive message', function () {
        $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect($ex->getMessage())->toContain('age');
        expect($ex->getMessage())->toContain('integer');
        expect($ex->__toString())->toContain('DTOException');
    });

    it('invalidJson() produces descriptive message', function () {
        $ex = DTOException::invalidJson('payload', 'Syntax error');

        expect($ex->getMessage())->toContain('payload');
        expect($ex->getMessage())->toContain('Syntax error');
    });
});

// ── Fixture DTOs for this test file ────────────────────────────

final class V39SimpleDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $name = '',
    ) {}
}

final class V39BoolCastDTO extends DataTransferObject
{
    public function __construct(
        #[Boolean]
        public readonly bool $active = false,
    ) {}
}

final class V39IntCastDTO extends DataTransferObject
{
    public function __construct(
        #[Integer, Min(0)]
        public readonly int $count = 0,
    ) {}
}

final class V39ArrayCastDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule]
        public readonly array $tags = [],
    ) {}
}

final class V39NullableDTO extends DataTransferObject
{
    public function __construct(
        #[Nullable]
        public readonly ?string $optional = null,
    ) {}
}

final class V39SometimesDTO extends DataTransferObject
{
    public function __construct(
        #[Sometimes, Max(255)]
        public readonly string $nickname = '',
    ) {}
}

final class V39ProhibitedDTO extends DataTransferObject
{
    public function __construct(
        #[Prohibited]
        public readonly ?string $blocked = null,
    ) {}
}

final class V39PresentDTO extends DataTransferObject
{
    public function __construct(
        #[Present]
        public readonly ?string $field = null,
    ) {}
}

final class V39ConfirmedDTO extends DataTransferObject
{
    public function __construct(
        #[Confirmed]
        public readonly string $password = '',
    ) {}
}

final class V39DistinctDTO extends DataTransferObject
{
    public function __construct(
        #[Distinct]
        public readonly array $items = [],
    ) {}
}

final class V39JsonAttrDTO extends DataTransferObject
{
    public function __construct(
        #[JsonAttr]
        public readonly string $meta = '',
    ) {}
}

final class V39UuidDTO extends DataTransferObject
{
    public function __construct(
        #[Uuid]
        public readonly string $id = '',
    ) {}
}

final class V39UrlDTO extends DataTransferObject
{
    public function __construct(
        #[Url]
        public readonly string $website = '',
    ) {}
}

final class V39PatternDTO extends DataTransferObject
{
    public function __construct(
        #[Pattern('/^[a-z]+$/')]
        public readonly string $slug = '',
    ) {}
}

final class V39StartsWithDTO extends DataTransferObject
{
    public function __construct(
        #[StartsWith(['admin', 'user_'])]
        public readonly string $role = '',
    ) {}
}

final class V39EndsWithDTO extends DataTransferObject
{
    public function __construct(
        #[EndsWith(['@example.com'])]
        public readonly string $email = '',
    ) {}
}

final class V39BetweenDTO extends DataTransferObject
{
    public function __construct(
        #[Between(1, 100)]
        public readonly int $age = 0,
    ) {}
}

final class V39InDTO extends DataTransferObject
{
    public function __construct(
        #[In(['active', 'inactive'])]
        public readonly string $status = 'active',
    ) {}
}

final class V39SizeDTO extends DataTransferObject
{
    public function __construct(
        #[Size(10)]
        public readonly int $exact = 0,
    ) {}
}

final class V39AcceptedDTO extends DataTransferObject
{
    public function __construct(
        #[Accepted]
        public readonly bool $terms = false,
    ) {}
}

final class V39DeclinedDTO extends DataTransferObject
{
    public function __construct(
        #[Declined]
        public readonly bool $reject = false,
    ) {}
}

final class V39NumericDTO extends DataTransferObject
{
    public function __construct(
        #[Numeric]
        public readonly int|float $amount = 0,
    ) {}
}

final class V39SameDTO extends DataTransferObject
{
    public function __construct(
        #[Same('password')]
        public readonly string $password_confirmation = '',
    ) {}
}

final class V39DifferentDTO extends DataTransferObject
{
    public function __construct(
        #[Different('old_password')]
        public readonly string $new_password = '',
    ) {}
}

final class V39RequiredWithDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWith('email')]
        public readonly ?string $email_name = null,
        #[Max(255)]
        public readonly ?string $email = null,
    ) {}
}

final class V39RequiredWithAllDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithAll('a', 'b')]
        public readonly ?string $c = null,
        public readonly ?string $a = null,
        public readonly ?string $b = null,
    ) {}
}

final class V39RequiredWithoutDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithout('email')]
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
    ) {}
}

final class V39RequiredWithoutAllDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWithoutAll('a', 'b')]
        public readonly ?string $c = null,
        public readonly ?string $a = null,
        public readonly ?string $b = null,
    ) {}
}

final class V39RequiredIfDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredIf('type', 'business')]
        public readonly ?string $company = null,
        public readonly string $type = 'personal',
    ) {}
}

final class V39RequiredUnlessDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredUnless('type', 'personal')]
        public readonly ?string $company = null,
        public readonly string $type = 'personal',
    ) {}
}

final class V39DateCastDTO extends DataTransferObject
{
    public function __construct(
        #[DateAttr]
        public readonly ?\Illuminate\Support\Carbon $created_at = null,
    ) {}
}

final class V39DefaultValueDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('pending')]
        public readonly string $status = 'active',
    ) {}
}

final class V39CastIntDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $age = 0,
    ) {}
}

final class V39CastStringDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('string')]
        public readonly string $name = '',
    ) {}
}

final class V39ArrayMinMaxDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule(min: 1, max: 5)]
        public readonly array $tags = [],
    ) {}
}

final class V39ArrayMinOnlyDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule(min: 2)]
        public readonly array $items = [],
    ) {}
}

final class V39ArrayMaxOnlyDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule(max: 10)]
        public readonly array $list = [],
    ) {}
}
