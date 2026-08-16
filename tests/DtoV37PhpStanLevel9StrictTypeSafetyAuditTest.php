<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Accepted, ArrayRule, Between, Boolean, Cast, Collection, Confirmed, Date, Declined, DefaultValue, Different, Distinct, Email, EndsWith, Enum, Hidden, In, Integer, Json, MapFrom, Max, Min, NestedArray, Nullable, Numeric, Pattern, Present, Prohibited, Required, RequiredIf, RequiredUnless, RequiredWith, RequiredWithAll, RequiredWithout, RequiredWithoutAll, Same, Size, Sometimes, StartsWith, Url, Uuid};
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\{
    ActionScopedDTO,
    AddressDTO,
    AllDefaultsDTO,
    AllScalarTypesDTO,
    ArrayCastDTO,
    ArticleDTO,
    ComprehensiveDTO,
    ComprehensiveValidationDTO,
    ConstraintCompositeDTO,
    CreateUserDTO,
    DateCastDTO,
    DateTimeCastDTO,
    DeepNestedDTO,
    DotNotationDTO,
    EdgeCaseDTO,
    EmptyDTO,
    InteractionEdgeCaseDTO,
    ItemDTO,
    MinimalDTO,
    MixedAttributesDTO,
    MixedCollectionDTO,
    MultiConstraintDTO,
    NestedCollectionDTO,
    NestedWithHiddenDTO,
    NoConstructorDTO,
    NullableRoundtripDTO,
    OpenApiValidationDTO,
    OrderDTO,
    OrderItemDTO,
    PartialDefaultValueDTO,
    ProductDTO,
    RegistrationDTO,
    RoundtripDTO,
    ScalarConstraintsDTO,
    StrictValidationDTO,
    TaskListDTO,
    UnionTypeDTO,
    ValidationAttributeContractDTO,
    ValidationTestDTO,
    VoUserDTO,
    WithRoundtripDTO,
};

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

describe('V37 PHPStan Level 9 Strict Type Safety Audit', function () {
    describe('Return type strictness — no mixed leaks in public API', function () {
        it('toArray() returns array<string, mixed> with correct keys', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKeys(['email', 'name', 'password']);
            expect($arr['email'])->toBe('a@b.com');
            expect($arr['name'])->toBe('Alice');
        });

        it('allValues() includes hidden fields', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);
            $all = $dto->allValues();
            expect($all)->toBeArray();
            expect($all)->toHaveKey('name');
        });

        it('toJson() returns JSON string', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $json = $dto->toJson();
            expect($json)->toBeString();
            expect(json_validate($json))->toBeTrue();
        });

        it('jsonSerialize() returns array — JsonSerializable contract', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $serialized = $dto->jsonSerialize();
            expect($serialized)->toBeArray();
            expect($serialized)->toEqual($dto->toArray());
        });

        it('rules() returns array<string, array<int, mixed>>', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            foreach ($rules as $field => $fieldRules) {
                expect($field)->toBeString();
                expect($fieldRules)->toBeArray();
                foreach ($fieldRules as $rule) {
                    expect($rule)->toBeString()->or()->toBeObject();
                }
            }
        });

        it('rulesFor() returns same shape as rules()', function () {
            $rules = CreateUserDTO::rulesFor('create');
            expect($rules)->toBeArray();
            expect($rules)->toEqual(CreateUserDTO::rules());
        });

        it('only() returns array with only specified keys', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $only = $dto->only('email');
            expect($only)->toBeArray();
            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
        });

        it('except() returns array without specified keys', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $except = $dto->except('password');
            expect($except)->toBeArray();
            expect($except)->toHaveKey('email');
            expect($except)->toHaveKey('name');
            expect($except)->not->toHaveKey('password');
        });

        it('equals() returns strict boolean', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob', 'password' => 'pass456'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
            expect($dto1->equals($dto3))->toBeFalse();
        });

        it('isEmpty() returns strict boolean', function () {
            expect(EmptyDTO::fromArray([])->isEmpty())->toBeTrue();
            expect(CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false)->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() is exact negation of isEmpty()', function () {
            $empty = EmptyDTO::fromArray([]);
            expect($empty->isNotEmpty())->toBe(! $empty->isEmpty());

            $full = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            expect($full->isNotEmpty())->toBe(! $full->isEmpty());
        });
    });

    describe('Hydration type safety', function () {
        it('fromArray() returns static — correct concrete type', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto)->toBeInstanceOf(DataTransferObject::class);
        });

        it('fromJson() returns static — correct concrete type', function () {
            $json = '{"email":"a@b.com","name":"Alice","password":"secret123"}';
            $dto = CreateUserDTO::fromJson($json, validate: false);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('fromJson() rejects non-object JSON arrays', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromJson() rejects invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not json', validate: false))
                ->toThrow(DTOException::class);
        });

        it('fromPartialArray() returns same type as fromArray()', function () {
            $partial = CreateUserDTO::fromPartialArray(['name' => 'Bob'], validate: false);
            expect($partial)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('with() returns new instance — immutability', function () {
            $original = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $updated = $original->with(['name' => 'Bob']);

            expect($updated)->not->toBe($original);
            expect($original->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
        });
    });

    describe('DtoCollection type safety', function () {
        it('make() returns DtoCollection instance', function () {
            $col = DtoCollection::make([]);
            expect($col)->toBeInstanceOf(DtoCollection::class);
        });

        it('constructor rejects non-DTO items', function () {
            expect(fn () => new DtoCollection([new \stdClass]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('toArray() returns array of arrays', function () {
            $dtoArray = [
                MinimalDTO::fromArray(['name' => 'A'], validate: false),
                MinimalDTO::fromArray(['name' => 'B'], validate: false),
            ];
            $col = new DtoCollection($dtoArray);
            $arr = $col->toArray();
            expect($arr)->toBeArray();
            expect(count($arr))->toBe(2);
            expect($arr[0])->toBeArray();
            expect($arr[0])->toHaveKey('name');
        });

        it('count() returns int', function () {
            $col = new DtoCollection([MinimalDTO::fromArray(['name' => 'A'], validate: false)]);
            expect($col->count())->toBeInt();
            expect($col->count())->toBe(1);
            expect(count($col))->toBe(1);
        });

        it('isEmpty() and isNotEmpty() return strict bool', function () {
            $empty = new DtoCollection([]);
            $full = new DtoCollection([MinimalDTO::fromArray(['name' => 'A'], validate: false)]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($full->isEmpty())->toBeFalse();
            expect($full->isNotEmpty())->toBeTrue();
        });

        it('push() mutates and returns self', function () {
            $col = new DtoCollection([]);
            $dto = MinimalDTO::fromArray(['name' => 'A'], validate: false);
            $result = $col->push($dto);

            expect($result)->toBe($col);
            expect($col->count())->toBe(1);
        });

        it('append() returns new collection — immutability', function () {
            $col = new DtoCollection([]);
            $dto = MinimalDTO::fromArray(['name' => 'A'], validate: false);
            $newCol = $col->append($dto);

            expect($newCol)->not->toBe($col);
            expect($col->count())->toBe(0);
            expect($newCol->count())->toBe(1);
        });

        it('filter() returns new collection — immutability', function () {
            $items = [
                MinimalDTO::fromArray(['name' => 'A'], validate: false),
                MinimalDTO::fromArray(['name' => 'B'], validate: false),
            ];
            $col = new DtoCollection($items);
            $filtered = $col->filter(fn (DataTransferObject $dto) => $dto->name === 'A');

            expect($filtered)->not->toBe($col);
            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2);
        });

        it('first() and last() return DTO or null', function () {
            $col = new DtoCollection([]);
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();

            $dto = MinimalDTO::fromArray(['name' => 'X'], validate: false);
            $col = new DtoCollection([$dto]);
            expect($col->first())->toBeInstanceOf(DataTransferObject::class);
            expect($col->last())->toBeInstanceOf(DataTransferObject::class);
        });

        it('map() returns plain array — not DtoCollection', function () {
            $col = new DtoCollection([MinimalDTO::fromArray(['name' => 'A'], validate: false)]);
            $result = $col->map(fn (DataTransferObject $dto) => $dto->name);
            expect($result)->toBeArray();
            expect($result)->not->toBeInstanceOf(DtoCollection::class);
            expect($result[0])->toBe('A');
        });

        it('pluck() returns array of property values', function () {
            $col = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'A'], validate: false),
                MinimalDTO::fromArray(['name' => 'B'], validate: false),
            ]);
            $names = $col->pluck('name');
            expect($names)->toEqual(['A', 'B']);
        });

        it('pluckKey() returns associative array', function () {
            $col = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'A'], validate: false),
                MinimalDTO::fromArray(['name' => 'B'], validate: false),
            ]);
            $keyed = $col->pluckKey('name');
            expect($keyed)->toBeArray();
            expect($keyed)->toHaveKey('A');
        });

        it('merge() returns new collection combining both', function () {
            $col1 = new DtoCollection([MinimalDTO::fromArray(['name' => 'A'], validate: false)]);
            $col2 = new DtoCollection([MinimalDTO::fromArray(['name' => 'B'], validate: false)]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('clone prevention throws RuntimeException', function () {
            $col = new DtoCollection([]);
            expect(fn () => clone $col)->toThrow(\RuntimeException::class);
        });

        it('jsonSerialize() returns array', function () {
            $col = new DtoCollection([MinimalDTO::fromArray(['name' => 'A'], validate: false)]);
            $result = $col->jsonSerialize();
            expect($result)->toBeArray();
            expect($result)->toEqual($col->toArray());
        });

        it('ArrayAccess: offsetExists returns bool, offsetGet returns mixed', function () {
            $dto = MinimalDTO::fromArray(['name' => 'A'], validate: false);
            $col = new DtoCollection([$dto]);

            expect(isset($col[0]))->toBeTrue();
            expect(isset($col[1]))->toBeFalse();
            expect($col[0])->toBeInstanceOf(DataTransferObject::class);
            expect($col[99])->toBeNull();
        });

        it('offsetUnset re-indexes the collection', function () {
            $col = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'A'], validate: false),
                MinimalDTO::fromArray(['name' => 'B'], validate: false),
            ]);
            unset($col[0]);
            expect($col->count())->toBe(1);
            expect($col[0]->name)->toBe('B');
        });
    });

    describe('DTOManager delegation type safety', function () {
        it('make() returns DataTransferObject', function () {
            $manager = new DTOManager;
            $dto = $manager->make(CreateUserDTO::class, ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123']);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('validate() returns array', function () {
            $manager = new DTOManager;
            $result = $manager->validate(CreateUserDTO::class, ['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123']);
            expect($result)->toBeArray();
            expect($result)->toHaveKey('email');
        });

        it('rules() returns array<string, array<int, mixed>>', function () {
            $manager = new DTOManager;
            $rules = $manager->rules(CreateUserDTO::class);
            expect($rules)->toBeArray();
            expect($rules)->toEqual(CreateUserDTO::rules());
        });

        it('rulesFor() returns array', function () {
            $manager = new DTOManager;
            $rules = $manager->rulesFor(CreateUserDTO::class, 'create');
            expect($rules)->toBeArray();
        });

        it('schema() returns array', function () {
            $manager = new DTOManager;
            $schema = $manager->schema(MinimalDTO::class);
            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
        });

        it('fromPartialArray() returns DataTransferObject', function () {
            $manager = new DTOManager;
            $dto = $manager->fromPartialArray(CreateUserDTO::class, ['name' => 'Bob']);
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('fromJson() returns DataTransferObject', function () {
            $manager = new DTOManager;
            $dto = $manager->fromJson(CreateUserDTO::class, '{"email":"a@b.com","name":"Alice","password":"secret123"}');
            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('makeFromJson() is alias for fromJson()', function () {
            $manager = new DTOManager;
            $json = '{"email":"a@b.com","name":"Alice","password":"secret123"}';
            $a = $manager->fromJson(CreateUserDTO::class, $json);
            $b = $manager->makeFromJson(CreateUserDTO::class, $json);
            expect($a->toArray())->toEqual($b->toArray());
        });
    });

    describe('DTOCast type strictness', function () {
        it('get() returns DTO or null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $result = $cast->get(new \stdClass, 'data', '{"email":"a@b.com","name":"Alice","password":"secret123"}', []);
            expect($result)->toBeInstanceOf(CreateUserDTO::class);

            $nullResult = $cast->get(new \stdClass, 'data', null, []);
            expect($nullResult)->toBeNull();
        });

        it('set() returns string or null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $result = $cast->set(new \stdClass, 'data', $dto, []);
            expect($result)->toBeString();
            expect(json_validate($result))->toBeTrue();

            $nullResult = $cast->set(new \stdClass, 'data', null, []);
            expect($nullResult)->toBeNull();
        });

        it('set() rejects non-DTO non-array values', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            expect(fn () => $cast->set(new \stdClass, 'data', 'invalid', []))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize() returns array or null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            $result = $cast->serialize(new \stdClass, 'data', $dto, []);
            expect($result)->toBeArray();

            $nullResult = $cast->serialize(new \stdClass, 'data', null, []);
            expect($nullResult)->toBeNull();
        });
    });

    describe('DTOException named constructors', function () {
        it('invalidCast() creates exception with correct message', function () {
            $ex = DTOException::invalidCast('name', 'integer', 'abc');
            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('name');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidJson() creates exception with correct message', function () {
            $ex = DTOException::invalidJson('data', 'syntax error');
            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('data');
            expect($ex->getMessage())->toContain('syntax error');
        });

        it('__toString() returns class name + message', function () {
            $ex = DTOException::invalidCast('x', 'int', 'y');
            $str = (string) $ex;
            expect($str)->toContain('DTOException');
            expect($str)->toContain('Cannot cast');
        });
    });

    describe('Hidden attribute behavior', function () {
        it('toArray() excludes hidden properties', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);
            $arr = $dto->toArray();
            expect($arr)->toHaveKey('name');
        });

        it('allValues() includes all properties regardless of hidden', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);
            expect($dto->allValues())->toEqual($dto->toArray());
        });

        it('__debugInfo() returns same as toArray()', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'secret123'], validate: false);
            expect($dto->__debugInfo())->toEqual($dto->toArray());
        });
    });

    describe('MapFrom and Cast attribute behavior', function () {
        it('MapFrom maps source key to property', function () {
            $dto = DotNotationDTO::fromArray(['user' => ['name' => 'Alice']], validate: false);
            expect($dto)->toBeInstanceOf(DotNotationDTO::class);
        });

        it('Cast attribute transforms values', function () {
            $dto = AllScalarTypesDTO::fromArray([
                'strField' => 'hello',
                'intField' => '42',
                'floatField' => '3.14',
                'boolField' => '1',
                'arrayField' => '["a","b"]',
            ], validate: false);
            expect($dto)->toBeInstanceOf(AllScalarTypesDTO::class);
        });
    });

    describe('Validation attribute contract compliance', function () {
        it('all validation attributes implement ValidationAttribute interface', function () {
            $attributes = [
                Required::class,
                Email::class,
                Max::class,
                Min::class,
                Url::class,
                Uuid::class,
                Integer::class,
                Numeric::class,
                Boolean::class,
                Pattern::class,
                In::class,
                Between::class,
                Date::class,
                Enum::class,
                Confirmed::class,
                Same::class,
                Different::class,
                Prohibited::class,
                Accepted::class,
                Declined::class,
                Present::class,
                Nullable::class,
                Sometimes::class,
                Distinct::class,
                Size::class,
                Json::class,
                StartsWith::class,
                EndsWith::class,
                ArrayRule::class,
                NestedArray::class,
                Collection::class,
                RequiredIf::class,
                RequiredUnless::class,
                RequiredWith::class,
                RequiredWithAll::class,
                RequiredWithout::class,
                RequiredWithoutAll::class,
            ];

            foreach ($attributes as $attrClass) {
                $implements = class_implements($attrClass) ?: [];
                expect($implements)->toContain(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
            }
        });

        it('each validation attribute has a ruleKey() method returning non-empty string', function () {
            $attrs = [
                new Required,
                new Email,
                new Max(100),
                new Min(1),
                new Url,
                new Uuid,
                new Integer,
                new Numeric,
                new Boolean,
                new Pattern('/test/'),
                new In(['a', 'b']),
                new Between(1, 10),
                new Date,
                new Enum(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class),
                new Confirmed,
                new Same('other'),
                new Different('other'),
                new Prohibited,
                new Accepted,
                new Declined,
                new Present,
                new Nullable,
                new Sometimes,
                new Distinct,
                new Size(10),
                new Json,
                new StartsWith('pre'),
                new EndsWith('suf'),
                new ArrayRule,
                new NestedArray(MinimalDTO::class),
                new Collection(MinimalDTO::class),
                new RequiredIf('field', 'value'),
                new RequiredUnless('field', 'value'),
                new RequiredWith('field'),
                new RequiredWithAll(['a', 'b']),
                new RequiredWithout('field'),
                new RequiredWithoutAll(['a', 'b']),
            ];

            foreach ($attrs as $attr) {
                $key = $attr->ruleKey();
                expect($key)->toBeString();
                expect(strlen($key))->toBeGreaterThan(0);
            }
        });
    });

    describe('Empty and edge-case DTOs', function () {
        it('NoConstructorDTO handles gracefully', function () {
            expect(NoConstructorDTO::rules())->toEqual([]);
            expect(NoConstructorDTO::rulesFor('create'))->toEqual([]);
        });

        it('AllDefaultsDTO hydrates with all defaults', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('EmptyDTO handles empty data', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto)->toBeInstanceOf(EmptyDTO::class);
            expect($dto->isEmpty())->toBeTrue();
        });
    });

    describe('Cross-package type consistency', function () {
        it('fromArray() and toArray() roundtrip preserves types', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $restored = CreateUserDTO::fromArray($original->toArray(), validate: false);
            expect($restored->toArray())->toEqual($original->toArray());
        });

        it('fromJson() and toJson() roundtrip preserves data', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $json = $original->toJson();
            $restored = CreateUserDTO::fromJson($json, validate: false);
            expect($restored->toArray())->toEqual($original->toArray());
        });

        it('with() preserves non-overridden fields', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $updated = $original->with(['name' => 'Bob']);
            expect($updated->email)->toBe('a@b.com');
            expect($updated->name)->toBe('Bob');
            expect($updated->password)->toBe('secret123');
        });
    });
});
