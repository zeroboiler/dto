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
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Enums\Tests\Fixtures\UserStatus;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

// ── Inline test DTOs for V15 coverage ──────────────────────────────────

class V15MapFromAndDefaultDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('source_email'), Required, Email]
        public readonly string $email,

        #[DefaultValue('active'), MapFrom('user_status')]
        public readonly string $status = 'active',

        #[DefaultValue(0), MapFrom('user_age')]
        public readonly int $age = 0,
    ) {}
}

class V15HiddenAndNullableDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Hidden]
        public readonly ?string $secret = null,

        #[Nullable]
        public readonly ?string $middleName = null,
    ) {}
}

class V15BoolCastDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('boolean')]
        public readonly bool $active = false,

        #[Cast('integer')]
        public readonly int $score = 0,

        #[Cast('float')]
        public readonly float $rate = 0.0,
    ) {}
}

class V15WithRulesForDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,

        #[Nullable]
        public readonly ?string $name = null,
    ) {}

    public static function rulesFor(string $action): array
    {
        if ($action === 'update') {
            // On update, email is not required but password min is lower
            return [
                'email' => ['sometimes', 'email', 'max:255'],
                'password' => ['nullable', 'min:1'],
                'name' => ['nullable', 'string', 'max:255'],
            ];
        }

        return parent::rulesFor($action);
    }
}

describe('V15 — DTOCast Edge Cases, OpenApi, Manager, Immutability, and Type System Contract', function () {
    // ── Section 1: DTOCast Serialization ────────────────────────────────────

    it('DTOCast get() returns null for null value', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->get(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('DTOCast get() returns null for invalid JSON string', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->get(new \stdClass, 'data', 'not-valid-json{{', []);

        expect($result)->toBeNull();
    });

    it('DTOCast get() returns null for non-array non-string value', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->get(new \stdClass, 'data', 42, []);

        expect($result)->toBeNull();
    });

    it('DTOCast set() returns null for null value', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->set(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('DTOCast set() rejects unexpected type with InvalidArgumentException', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);

        expect(fn () => $cast->set(new \stdClass, 'data', 42, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DTOCast set() hydrates array through DTO with validation', function () {
        $cast = new DTOCast(ComprehensiveDTO::class, validate: true);
        $result = $cast->set(new \stdClass, 'data', [
            'email' => 'a@b.com',
            'name' => 'Test',
        ], []);

        expect($result)->toBeString();
        $decoded = json_decode($result, true);
        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('a@b.com');
        expect($decoded['name'])->toBe('Test');
        expect($decoded)->not->toHaveKey('password'); // hidden
    });

    it('DTOCast serialize returns null for null value', function () {
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->serialize(new \stdClass, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('DTOCast serialize returns toArray for DTO instance', function () {
        $dto = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $cast = new DTOCast(ComprehensiveDTO::class);
        $result = $cast->serialize(new \stdClass, 'data', $dto, []);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('a@b.com');
        expect($result)->not->toHaveKey('password');
    });

    // ── Section 2: DTOManager full API contract ────────────────────────────

    it('DTOManager make creates DTO from valid data', function () {
        $manager = new DTOManager;
        $dto = $manager->make(ComprehensiveDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Test User');
    });

    it('DTOManager validate returns validated data', function () {
        $manager = new DTOManager;
        $result = $manager->validate(ComprehensiveDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('test@example.com');
    });

    it('DTOManager validate throws on invalid data', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->validate(ComprehensiveDTO::class, ['email' => 'not-an-email', 'name' => '']))
            ->toThrow(ValidationException::class);
    });

    it('DTOManager rules returns rule array', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(ComprehensiveDTO::class);

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('DTOManager rulesFor delegates to DTO method', function () {
        $manager = new DTOManager;

        $createRules = $manager->rulesFor(V15WithRulesForDTO::class, 'create');
        $updateRules = $manager->rulesFor(V15WithRulesForDTO::class, 'update');

        expect($createRules)->toBeArray();
        expect($updateRules)->toBeArray();
        // update should have 'sometimes' instead of 'required' for email
        expect($updateRules['email'])->toContain('sometimes');
    });

    it('DTOManager fromPartialArray creates partial DTO', function () {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(ComprehensiveDTO::class, ['name' => 'Updated']);

        expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
        expect($dto->name)->toBe('Updated');
    });

    it('DTOManager schema generates OpenAPI schema', function () {
        $manager = new DTOManager;
        $schema = $manager->schema(ComprehensiveDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema)->toHaveKey('properties');
        expect($schema['type'])->toBe('object');
    });

    // ── Section 3: MapFrom + DefaultValue combined ──────────────────────────

    it('fromArray resolves MapFrom key and applies DefaultValue for missing', function () {
        $dto = V15MapFromAndDefaultDTO::fromArray(['source_email' => 'a@b.com'], validate: false);

        expect($dto->email)->toBe('a@b.com');
        expect($dto->status)->toBe('active'); // DefaultValue
        expect($dto->age)->toBe(0);           // DefaultValue
    });

    it('fromArray uses MapFrom key over property name', function () {
        $dto = V15MapFromAndDefaultDTO::fromArray([
            'source_email' => 'a@b.com',
            'user_status' => 'inactive',
            'user_age' => 25,
        ], validate: false);

        expect($dto->email)->toBe('a@b.com');
        expect($dto->status)->toBe('inactive');
        expect($dto->age)->toBe(25);
    });

    it('fromPartialArray preserves existing values and applies defaults for missing', function () {
        $dto = V15MapFromAndDefaultDTO::fromPartialArray([
            'source_email' => 'new@b.com',
            'user_age' => 30,
        ], validatePresent: false);

        expect($dto->email)->toBe('new@b.com');
        expect($dto->age)->toBe(30);
        expect($dto->status)->toBe('active'); // default, not in partial data
    });

    // ── Section 4: Hidden + Nullable DTO ───────────────────────────────────

    it('toArray excludes hidden fields', function () {
        $dto = V15HiddenAndNullableDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'password123',
            'middleName' => 'Marie',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('secret');
        expect($arr)->toHaveKey('middleName');
    });

    it('allValues includes hidden fields', function () {
        $dto = V15HiddenAndNullableDTO::fromArray([
            'name' => 'Alice',
            'secret' => 'password123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('password123');
    });

    it('only() returns only specified keys', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 30,
        ], validate: false);

        $only = $dto->only('email');
        expect($only)->toHaveCount(1);
        expect($only['email'])->toBe('a@b.com');
    });

    it('except() excludes specified keys', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'age' => 30,
        ], validate: false);

        $except = $dto->except('email');
        expect($except)->not->toHaveKey('email');
        expect($except['name'])->toBe('Alice');
    });

    // ── Section 5: Type casting DTO ─────────────────────────────────────────

    it('boolean cast converts "true" string to true', function () {
        $dto = V15BoolCastDTO::fromArray(['active' => 'true'], validate: false);
        expect($dto->active)->toBeTrue();
    });

    it('boolean cast converts "false" string to false', function () {
        $dto = V15BoolCastDTO::fromArray(['active' => 'false'], validate: false);
        expect($dto->active)->toBeFalse();
    });

    it('boolean cast converts "1" to true and "0" to false', function () {
        $dto1 = V15BoolCastDTO::fromArray(['active' => '1'], validate: false);
        expect($dto1->active)->toBeTrue();

        $dto2 = V15BoolCastDTO::fromArray(['active' => '0'], validate: false);
        expect($dto2->active)->toBeFalse();
    });

    it('integer cast converts string to int', function () {
        $dto = V15BoolCastDTO::fromArray(['score' => '42'], validate: false);
        expect($dto->score)->toBe(42);
    });

    it('float cast converts string to float', function () {
        $dto = V15BoolCastDTO::fromArray(['rate' => '3.14'], validate: false);
        expect($dto->rate)->toBe(3.14);
    });

    it('cast defaults to 0/false/0.0 for non-numeric strings', function () {
        $dto = V15BoolCastDTO::fromArray([
            'score' => 'not-a-number',
            'rate' => 'not-a-float',
        ], validate: false);

        expect($dto->score)->toBe(0);
        expect($dto->rate)->toBe(0.0);
    });

    // ── Section 6: DtoCollection immutability contracts ─────────────────────

    it('DtoCollection append returns new instance without mutating original', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended)->not->toBe($original);
    });

    it('DtoCollection merge returns new combined collection', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
    });

    it('DtoCollection filter returns new filtered collection', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $dto3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $col->filter(fn (DataTransferObject $dto) => $dto->foo !== 'b');

        expect($filtered->count())->toBe(2);
        expect($col->count())->toBe(3); // original unchanged
    });

    it('DtoCollection push mutates in-place and returns self', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

        $col = new DtoCollection([$dto1]);
        $result = $col->push($dto2);

        expect($col->count())->toBe(2); // mutated in-place
        expect($result)->toBe($col);    // returns same instance
    });

    it('DtoCollection clone throws RuntimeException', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $col = new DtoCollection([$dto]);

        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('DtoCollection offsetUnset re-indexes', function () {
        $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
        $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
        $dto3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $col->offsetUnset(0);

        expect($col->count())->toBe(2);
        // After re-index, index 0 should exist
        expect($col->offsetExists(0))->toBeTrue();
        expect($col->offsetExists(1))->toBeTrue();
    });

    // ── Section 7: OpenApiSchemaGenerator ──────────────────────────────────

    it('OpenApiSchemaGenerator produces valid schema for EmptyDTO', function () {
        $schema = OpenApiSchemaGenerator::generate(EmptyDTO::class);

        expect($schema)->toBeArray();
        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toBeArray();
        expect($schema['properties'])->toHaveKey('foo');
        expect($schema['properties'])->toHaveKey('bar');
    });

    it('OpenApiSchemaGenerator marks required fields correctly', function () {
        $schema = OpenApiSchemaGenerator::generate(ComprehensiveDTO::class);

        expect($schema['required'])->toBeArray();
        expect($schema['required'])->toContain('email');
        expect($schema['required'])->toContain('name');
    });

    it('OpenApiSchemaGenerator infers string format from Email attribute', function () {
        $schema = OpenApiSchemaGenerator::generate(ComprehensiveDTO::class);

        expect($schema['properties']['email'])->toBeArray();
        expect($schema['properties']['email']['type'])->toBe('string');
        expect($schema['properties']['email']['format'])->toBe('email');
    });

    // ── Section 8: DTOException named constructors ──────────────────────────

    it('DTOException::invalidCast includes property and type in message', function () {
        $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');
        $msg = $exception->getMessage();

        expect($msg)->toContain('age');
        expect($msg)->toContain('integer');
    });

    it('DTOException::invalidJson includes property and error in message', function () {
        $exception = DTOException::invalidJson('payload', 'Syntax error');
        $msg = $exception->getMessage();

        expect($msg)->toContain('payload');
        expect($msg)->toContain('Syntax error');
    });

    it('DTOException __toString includes class name', function () {
        $exception = DTOException::invalidCast('x', 'y', 'z');
        $str = (string) $exception;

        expect($str)->toBe(DTOException::class.': '.$exception->getMessage());
    });

    // ── Section 9: equals and isEmpty semantics ──────────────────────────────

    it('equals returns true for same data, false for different', function () {
        $dto1 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto2 = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test'], validate: false);
        $dto3 = ComprehensiveDTO::fromArray(['email' => 'c@d.com', 'name' => 'Other'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty returns true for all-default EmptyDTO', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false when any property has a value', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('isEmpty treats 0 and 0.0 as non-empty for non-nullable numeric', function () {
        $dto = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'age' => 0], validate: false);

        // age=0 is non-nullable int, so DTO is NOT empty (age has meaningful value)
        expect($dto->isEmpty())->toBeFalse();
    });

    // ── Section 10: fromJson edge cases ─────────────────────────────────────

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => ComprehensiveDTO::fromJson('["a","b"]', validate: false))
            ->toThrow(DTOException::class, 'Expected a JSON object');
    });

    it('fromJson accepts empty object', function () {
        $dto = EmptyDTO::fromJson('{}', validate: false);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('fromJson decodes valid JSON object correctly', function () {
        $dto = ComprehensiveDTO::fromJson(
            json_encode(['email' => 'a@b.com', 'name' => 'Test']),
            validate: false
        );

        expect($dto->email)->toBe('a@b.com');
        expect($dto->name)->toBe('Test');
    });

    // ── Section 11: with() immutability and validation ──────────────────────

    it('with() returns new instance without modifying original', function () {
        $dto = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $updated = $dto->with(['name' => 'Bob']);

        expect($dto->name)->toBe('Alice');
        expect($updated->name)->toBe('Bob');
        expect($updated->email)->toBe('a@b.com');
    });

    it('with() always validates regardless of $validate param', function () {
        $dto = ComprehensiveDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);

        // Passing invalid data should still throw
        expect(fn () => $dto->with(['email' => 'invalid-email'], validate: true))
            ->toThrow(ValidationException::class);

        // Even with validate=false, with() always validates
        expect(fn () => $dto->with(['email' => 'invalid-email'], validate: false))
            ->toThrow(ValidationException::class);
    });

    // ── Section 12: Interface compliance ───────────────────────────────────

    it('DataTransferObject implements FromRequestDTO', function () {
        expect(ComprehensiveDTO::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function () {
        expect(ComprehensiveDTO::class)->toImplement(ValidatableDTO::class);
    });

    it('ValidationAttribute implementations have ruleKey() method', function () {
        $attributes = [
            new Required, new Email, new Max(10), new Min(1), new Url,
            new Pattern('/test/'), new Integer, new Numeric, new Boolean,
            new Uuid, new Date, new In(['a']), new Accepted, new Declined,
            new Prohibited, new Present, new Sometimes, new Nullable,
            new Hidden, new StartsWith('x'), new EndsWith('y'), new Size(5),
            new Json, new Between(1, 10), new Confirmed, new Same('field'),
            new Different('field'), new Distinct, new ArrayRule,
            new RequiredIf('field', 'val'), new RequiredUnless('field', 'val'),
            new RequiredWith('field'), new RequiredWithAll(['a', 'b']),
            new RequiredWithout('field'), new RequiredWithoutAll(['a', 'b']),
        ];

        foreach ($attributes as $attr) {
            expect($attr)->toBeInstanceOf(ValidationAttribute::class);
            expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });
});
