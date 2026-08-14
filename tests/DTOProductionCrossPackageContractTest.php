<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Production cross-package contract tests.
 *
 * Verifies that the DTO package works correctly in real-world scenarios:
 * - Full hydration pipeline (fromArray, fromRequest, fromJson, fromPartialArray)
 * - Serialization (toArray, toJson, jsonSerialize, allValues)
 * - Validation rules generation and execution
 * - Immutable updates (with)
 * - Selective output (only, except)
 * - State checks (isEmpty, isNotEmpty, equals)
 * - Hidden fields
 * - MapFrom field aliasing
 * - Cast type transformations
 * - Default values
 * - Collection helpers
 * - Exception factory methods
 * - Attribute contract compliance
 */
describe('DTO Production Cross-Package Contract', function () {
    // -----------------------------------------------------------------------
    // Test fixture DTOs
    // -----------------------------------------------------------------------

    describe('Basic DTO with validation attributes', function () {
        class TestBasicDTO extends DataTransferObject
        {
            public function __construct(
                #[Required, Email, Max(255)]
                public readonly string $email,

                #[Required, Min(2), Max(100)]
                public readonly string $name,

                #[Nullable, Max(500)]
                public readonly ?string $bio = null,
            ) {}
        }

        it('implements FromRequestDTO, ValidatableDTO, JsonSerializable', function () {
            expect(TestBasicDTO::class)->implementsInterface(FromRequestDTO::class);
            expect(TestBasicDTO::class)->implementsInterface(ValidatableDTO::class);
        });

        it('generates validation rules from attributes', function () {
            $rules = TestBasicDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules)->toHaveKey('bio');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['email'])->toContain('max:255');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:100');
            expect($rules['bio'])->toContain('nullable');
            expect($rules['bio'])->toContain('max:500');
        });

        it('creates from array without validation', function () {
            $dto = TestBasicDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto)->toBeInstanceOf(TestBasicDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->bio)->toBeNull();
        });

        it('serializes to array', function () {
            $dto = TestBasicDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'bio' => 'Hello',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->toBe([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'bio' => 'Hello',
            ]);
        });

        it('serializes to JSON', function () {
            $dto = TestBasicDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Charlie',
            ], validate: false);

            expect($dto->toJson())->toBeJson();
            $decoded = json_decode($dto->toJson(), true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('jsonSerialize returns array', function () {
            $dto = TestBasicDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'D',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('DTO with hidden fields and defaults', function () {
        class TestHiddenDTO extends DataTransferObject
        {
            public function __construct(
                #[Required, Email]
                public readonly string $email,

                #[Required]
                public readonly string $name,

                #[Hidden]
                public readonly ?string $password = null,

                #[DefaultValue('active')]
                public readonly string $status = 'active',
            ) {}
        }

        it('excludes hidden fields from toArray', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
            expect($dto->password)->toBe('secret123');
        });

        it('includes hidden fields in allValues', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret123');
        });

        it('applies default value when key absent', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('respects explicitly provided value over default', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->status)->toBe('inactive');
        });

        it('only() returns specified fields only', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toBe(['email' => 'test@example.com']);
        });

        it('except() returns all fields except specified', function () {
            $dto = TestHiddenDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('status');
        });
    });

    describe('DTO with MapFrom and Cast', function () {
        class TestMappedDTO extends DataTransferObject
        {
            public function __construct(
                #[Required, Email]
                public readonly string $email,

                #[MapFrom('user_name'), Required, Max(100)]
                public readonly string $displayName,

                #[Cast('integer')]
                public readonly int $age = 0,

                #[Cast('boolean')]
                public readonly bool $isActive = false,
            ) {}
        }

        it('maps source key via MapFrom', function () {
            $dto = TestMappedDTO::fromArray([
                'email' => 'a@b.com',
                'user_name' => 'Alice',
            ], validate: false);

            expect($dto->displayName)->toBe('Alice');
        });

        it('casts string to integer', function () {
            $dto = TestMappedDTO::fromArray([
                'email' => 'a@b.com',
                'user_name' => 'Alice',
                'age' => '25',
            ], validate: false);

            expect($dto->age)->toBe(25);
            expect($dto->age)->toBeInt();
        });

        it('casts string to boolean', function () {
            $dto = TestMappedDTO::fromArray([
                'email' => 'a@b.com',
                'user_name' => 'Alice',
                'isActive' => '1',
            ], validate: false);

            expect($dto->isActive)->toBeTrue();
        });
    });

    describe('DTO immutable update (with)', function () {
        class TestWithDTO extends DataTransferObject
        {
            public function __construct(
                public readonly string $name = '',
                public readonly string $status = 'active',
            ) {}
        }

        it('creates new instance with overrides', function () {
            $original = TestWithDTO::fromArray(['name' => 'Alice'], validate: false);
            $updated = $original->with(['status' => 'inactive']);

            expect($updated)->not->toBe($original);
            expect($updated->name)->toBe('Alice');
            expect($updated->status)->toBe('inactive');
            expect($original->status)->toBe('active');
        });
    });

    describe('DTO state checks', function () {
        class TestStateDTO extends DataTransferObject
        {
            public function __construct(
                #[Nullable]
                public readonly ?string $name = null,

                #[Nullable]
                public readonly ?string $email = null,

                #[DefaultValue('active')]
                public readonly string $status = 'active',
            ) {}
        }

        it('isEmpty returns true when all properties are empty', function () {
            $dto = TestStateDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when a property has a non-empty value', function () {
            $dto = TestStateDTO::fromArray(['name' => 'Alice'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });

        it('equals compares toArray output', function () {
            $data = ['name' => 'Bob', 'email' => 'bob@test.com'];
            $dto1 = TestStateDTO::fromArray($data, validate: false);
            $dto2 = TestStateDTO::fromArray($data, validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different values', function () {
            $dto1 = TestStateDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = TestStateDTO::fromArray(['name' => 'Bob'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('DTO fromJson', function () {
        class TestJsonDTO extends DataTransferObject
        {
            public function __construct(
                #[Required]
                public readonly string $name,
            ) {}
        }

        it('creates from valid JSON string', function () {
            $dto = TestJsonDTO::fromJson('{"name": "Alice"}', validate: false);

            expect($dto->name)->toBe('Alice');
        });

        it('throws DTOException on invalid JSON', function () {
            expect(fn () => TestJsonDTO::fromJson('not-json'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON array (sequential)', function () {
            expect(fn () => TestJsonDTO::fromJson('["name","Alice"]'))
                ->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            // name is required but we skip validation
            $dto = TestJsonDTO::fromJson('{}', validate: false);

            expect($dto)->toBeInstanceOf(TestJsonDTO::class);
        });
    });

    describe('DTO fromPartialArray (PATCH semantics)', function () {
        class TestPartialDTO extends DataTransferObject
        {
            public function __construct(
                #[Required]
                public readonly string $name,

                #[DefaultValue('active')]
                public readonly string $status = 'active',

                #[Nullable]
                public readonly ?string $email = null,
            ) {}
        }

        it('hydrates only provided fields', function () {
            $dto = TestPartialDTO::fromPartialArray(['name' => 'Updated'], validate: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active');
            expect($dto->email)->toBeNull();
        });

        it('preserves defaults for missing fields', function () {
            $dto = TestPartialDTO::fromPartialArray([], validate: false);

            expect($dto->status)->toBe('active');
        });
    });

    describe('DTOCollection', function () {
        class TestCollectionItemDTO extends DataTransferObject
        {
            public function __construct(
                public readonly string $name,
                public readonly int $score = 0,
            ) {}
        }

        it('creates from array of DTOs', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->count())->toBe(2);
            expect($collection->isEmpty())->toBeFalse();
        });

        it('serializes to array', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $collection = new DtoCollection([$dto1]);

            expect($collection->toArray())->toBe([
                ['name' => 'Alice', 'score' => 95],
            ]);
        });

        it('supports pluck', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->pluck('name'))->toBe(['Alice', 'Bob']);
        });

        it('supports map', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $collection = new DtoCollection([$dto1]);

            $result = $collection->map(fn ($dto) => $dto->name);

            expect($result)->toBe(['Alice']);
        });

        it('supports filter', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            $filtered = $collection->filter(fn ($dto) => $dto->score >= 90);

            expect($filtered->count())->toBe(1);
        });

        it('push mutates in-place and returns self', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1]);

            $result = $collection->push($dto2);

            expect($result)->toBe($collection);
            expect($collection->count())->toBe(2);
        });

        it('append returns new collection', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1]);

            $new = $collection->append($dto2);

            expect($new)->not->toBe($collection);
            expect($new->count())->toBe(2);
            expect($collection->count())->toBe(1);
        });

        it('merge combines two collections', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);

            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
        });

        it('first and last return correct items', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            expect($collection->first()->name)->toBe('Alice');
            expect($collection->last()->name)->toBe('Bob');
        });

        it('first returns null for empty collection', function () {
            $collection = new DtoCollection;

            expect($collection->first())->toBeNull();
            expect($collection->last())->toBeNull();
        });

        it('pluckKey returns keyed array', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            $result = $collection->pluckKey('name', 'score');

            expect($result)->toBe(['Alice' => 95, 'Bob' => 87]);
        });

        it('toArrayBy returns keyed array', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $collection = new DtoCollection([$dto1]);

            $result = $collection->toArrayBy('name');

            expect($result)->toHaveKey('Alice');
        });

        it('toDictionary returns key-value pairs', function () {
            $dto1 = TestCollectionItemDTO::fromArray(['name' => 'Alice', 'score' => 95], validate: false);
            $dto2 = TestCollectionItemDTO::fromArray(['name' => 'Bob', 'score' => 87], validate: false);
            $collection = new DtoCollection([$dto1, $dto2]);

            $result = $collection->toDictionary('name', 'score');

            expect($result)->toBe(['Alice' => 95, 'Bob' => 87]);
        });
    });

    describe('DTOCast serialization', function () {
        class TestCastDTO extends DataTransferObject
        {
            public function __construct(
                public readonly string $name = '',
            ) {}
        }

        it('get returns null for null database value', function () {
            $cast = new DTOCast(TestCastDTO::class);

            $result = $cast->get(new \stdClass(), 'data', null, ['data' => null]);

            expect($result)->toBeNull();
        });

        it('get hydrates from JSON string', function () {
            $cast = new DTOCast(TestCastDTO::class);

            $result = $cast->get(
                new \stdClass(),
                'data',
                '{"name":"Alice"}',
                ['data' => '{"name":"Alice"}'],
            );

            expect($result)->toBeInstanceOf(TestCastDTO::class);
            expect($result->name)->toBe('Alice');
        });

        it('set serializes DTO to JSON', function () {
            $cast = new DTOCast(TestCastDTO::class);
            $dto = TestCastDTO::fromArray(['name' => 'Bob'], validate: false);

            $result = $cast->set(new \stdClass(), 'data', $dto, ['data' => null]);

            expect($result)->toBe('{"name":"Bob"}');
        });

        it('set returns null for null value', function () {
            $cast = new DTOCast(TestCastDTO::class);

            $result = $cast->set(new \stdClass(), 'data', null, ['data' => null]);

            expect($result)->toBeNull();
        });

        it('set throws for unexpected type', function () {
            $cast = new DTOCast(TestCastDTO::class);

            expect(fn () => $cast->set(new \stdClass(), 'data', 42, ['data' => null]))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('serialize returns toArray for DTO instance', function () {
            $cast = new DTOCast(TestCastDTO::class);
            $dto = TestCastDTO::fromArray(['name' => 'Alice'], validate: false);

            $result = $cast->serialize(new \stdClass(), 'data', $dto, ['data' => null]);

            expect($result)->toBe(['name' => 'Alice']);
        });

        it('serialize returns null for null', function () {
            $cast = new DTOCast(TestCastDTO::class);

            $result = $cast->serialize(new \stdClass(), 'data', null, ['data' => null]);

            expect($result)->toBeNull();
        });
    });

    describe('DTOException factory methods', function () {
        it('invalidCast creates exception with correct message', function () {
            $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');

            expect($exception->getMessage())->toContain('age');
            expect($exception->getMessage())->toContain('integer');
            expect($exception->getMessage())->toContain('not-a-number');
        });

        it('invalidJson creates exception with correct message', function () {
            $exception = DTOException::invalidJson('data', 'Syntax error');

            expect($exception->getMessage())->toContain('data');
            expect($exception->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function () {
            $exception = DTOException::invalidCast('x', 'y', 'z');

            $str = (string) $exception;
            expect($str)->toContain(DTOException::class);
            expect($str)->toContain('x');
        });
    });

    describe('rulesFor action scoping', function () {
        class TestActionDTO extends DataTransferObject
        {
            public function __construct(
                #[Required]
                public readonly string $name,
            ) {}
        }

        it('rulesFor returns same rules by default', function () {
            expect(TestActionDTO::rulesFor('create'))
                ->toBe(TestActionDTO::rules());
            expect(TestActionDTO::rulesFor('update'))
                ->toBe(TestActionDTO::rules());
        });
    });

    describe('ValidationAttribute contract compliance', function () {
        it('all validation attributes implement ValidationAttribute interface', function () {
            $attributes = [
                Accepted::class,
                ArrayRule::class,
                Between::class,
                Boolean::class,
                Confirmed::class,
                Date::class,
                Declined::class,
                Different::class,
                Distinct::class,
                Email::class,
                EndsWith::class,
                In::class,
                Integer::class,
                Json::class,
                Max::class,
                Min::class,
                Numeric::class,
                Pattern::class,
                Present::class,
                Prohibited::class,
                Required::class,
                RequiredIf::class,
                RequiredUnless::class,
                RequiredWith::class,
                RequiredWithout::class,
                Same::class,
                Size::class,
                Sometimes::class,
                StartsWith::class,
                Uuid::class,
                Url::class,
            ];

            foreach ($attributes as $attrClass) {
                expect($attrClass)->toImplement(ValidationAttribute::class);
            }
        });

        it('each validation attribute returns non-empty ruleKey', function () {
            $attributes = [
                new Accepted,
                new ArrayRule,
                new Between(min: 1, max: 10),
                new Boolean,
                new Confirmed,
                new Date,
                new Declined,
                new Different('other'),
                new Distinct,
                new Email,
                new EndsWith('com'),
                new In(['a', 'b']),
                new Integer,
                new Json,
                new Max(100),
                new Min(1),
                new Numeric,
                new Pattern('/^[a-z]+$/'),
                new Present,
                new Prohibited,
                new Required,
                new RequiredIf('field', 'value'),
                new RequiredUnless('field', 'value'),
                new RequiredWith('field'),
                new RequiredWithout('field'),
                new Same('other'),
                new Size(10),
                new Sometimes,
                new StartsWith('pre'),
                new Uuid,
                new Url,
            ];

            foreach ($attributes as $attr) {
                expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('Nested DTO hydration', function () {
        class TestAddressDTO extends DataTransferObject
        {
            public function __construct(
                #[Required]
                public readonly string $city,

                #[Required]
                public readonly string $country,
            ) {}
        }

        class TestUserDTO extends DataTransferObject
        {
            public function __construct(
                #[Required]
                public readonly string $name,

                #[Nullable]
                public readonly ?TestAddressDTO $address = null,
            ) {}
        }

        it('hydrates nested DTO from array', function () {
            $dto = TestUserDTO::fromArray([
                'name' => 'Alice',
                'address' => [
                    'city' => 'Istanbul',
                    'country' => 'Turkey',
                ],
            ], validate: false);

            expect($dto->address)->toBeInstanceOf(TestAddressDTO::class);
            expect($dto->address->city)->toBe('Istanbul');
            expect($dto->address->country)->toBe('Turkey');
        });

        it('serializes nested DTO recursively', function () {
            $dto = TestUserDTO::fromArray([
                'name' => 'Alice',
                'address' => [
                    'city' => 'Istanbul',
                    'country' => 'Turkey',
                ],
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr['address'])->toBe([
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ]);
        });

        it('handles null nested DTO', function () {
            $dto = TestUserDTO::fromArray([
                'name' => 'Alice',
            ], validate: false);

            expect($dto->address)->toBeNull();
            $arr = $dto->toArray();
            expect($arr['address'])->toBeNull();
        });
    });

    describe('Enum attribute in DTO', function () {
        enum TestUserRole: string
        {
            case ADMIN = 'admin';
            case USER = 'user';
        }

        class TestRoleDTO extends DataTransferObject
        {
            public function __construct(
                public readonly string $name,

                #[DefaultValue('user')]
                public readonly string $role = 'user',
            ) {}
        }

        it('creates DTO with enum-like string property', function () {
            $dto = TestRoleDTO::fromArray([
                'name' => 'Alice',
                'role' => 'admin',
            ], validate: false);

            expect($dto->role)->toBe('admin');
        });
    });

    describe('Pattern attribute with regex', function () {
        class TestPatternDTO extends DataTransferObject
        {
            public function __construct(
                #[Required, Pattern('/^[A-Z]{2,3}$/')]
                public readonly string $code,
            ) {}
        }

        it('includes regex rule in rules', function () {
            $rules = TestPatternDTO::rules();

            expect($rules['code'])->toContain('regex:/^[A-Z]{2,3}$/');
        });
    });

    describe('DtoCollection immutability', function () {
        class TestImmutableDTO extends DataTransferObject
        {
            public function __construct(
                public readonly string $value = '',
            ) {}
        }

        it('prevents cloning', function () {
            $dto = TestImmutableDTO::fromArray(['value' => 'test'], validate: false);
            $collection = new DtoCollection([$dto]);

            expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
        });

        it('offsetUnset re-indexes', function () {
            $dto1 = TestImmutableDTO::fromArray(['value' => 'a'], validate: false);
            $dto2 = TestImmutableDTO::fromArray(['value' => 'b'], validate: false);
            $dto3 = TestImmutableDTO::fromArray(['value' => 'c'], validate: false);
            $collection = new DtoCollection([$dto1, $dto2, $dto3]);

            unset($collection[0]);

            expect($collection->count())->toBe(2);
            expect($collection->first()->value)->toBe('b');
        });
    });
});
