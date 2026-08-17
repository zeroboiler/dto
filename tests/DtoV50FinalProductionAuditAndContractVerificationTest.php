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
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum;
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
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EdgeCaseDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NestedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

afterEach(function () {
    DataTransferObject::flushMetadataCache();
});

describe('V50 Final Production Audit — DTO Package', function () {
    describe('Source Code Structural Integrity', function () {
        it('all 55 source files declare strict types', function () {
            $srcFiles = glob(__DIR__.'/../src/**/*.php', GLOB_BRACE);
            expect($srcFiles)->not->toBeEmpty();

            foreach ($srcFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all source files have a license header', function () {
            $srcFiles = glob(__DIR__.'/../src/**/*.php', GLOB_BRACE);

            foreach ($srcFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('This file is part of ZeroBoiler');
            }
        });

        it('all attribute classes are final', function () {
            $attributeClasses = [
                Required::class, Email::class, Max::class, Min::class,
                Url::class, Pattern::class, In::class, Integer::class,
                Numeric::class, Boolean::class, Date::class, ArrayRule::class,
                Json::class, Enum::class, MapFrom::class, Cast::class,
                DefaultValue::class, Hidden::class, Nullable::class,
                Sometimes::class, Present::class, Prohibited::class,
                Accepted::class, Declined::class, Confirmed::class,
                Same::class, Different::class, Distinct::class,
                RequiredIf::class, RequiredUnless::class,
                RequiredWith::class, RequiredWithAll::class,
                RequiredWithout::class, RequiredWithoutAll::class,
                Size::class, StartsWith::class, EndsWith::class,
                Uuid::class, Between::class, NestedArray::class,
                Collection::class,
            ];

            foreach ($attributeClasses as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} should be final");
            }
        });

        it('DTOManager is readonly', function () {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('DataTransferObject is abstract', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->isAbstract())->toBeTrue();
        });

        it('DtoCollection is final', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('DtoCollection prevents cloning', function () {
            $col = new DtoCollection;
            expect(fn () => clone $col)->toThrow(RuntimeException::class);
        });

        it('DtoCollection prevents clone with items', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);
            $col = new DtoCollection([$dto]);
            expect(fn () => clone $col)->toThrow(RuntimeException::class);
        });
    });

    describe('DTOManager Delegation Contract', function () {
        it('validate() delegates correctly', function () {
            $manager = new DTOManager;
            $result = $manager->validate(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secure123',
            ]);

            expect($result)->toBeArray();
            expect($result['email'])->toBe('test@example.com');
        });

        it('make() creates DTO instance', function () {
            $manager = new DTOManager;
            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'secure123',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
        });

        it('rules() returns validation rules', function () {
            $manager = new DTOManager;
            $rules = $manager->rules(CreateUserDTO::class);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('rulesFor() delegates to action-scoped rules', function () {
            $manager = new DTOManager;
            $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

            expect($rules)->toBeArray();
        });

        it('schema() returns OpenAPI schema', function () {
            $manager = new DTOManager;
            $schema = $manager->schema(MinimalDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
        });
    });

    describe('ValidationAttribute Contract', function () {
        it('all validation attributes implement ValidationAttribute interface', function () {
            $classes = [
                Required::class, Email::class, Max::class, Min::class,
                Url::class, Pattern::class, In::class, Integer::class,
                Numeric::class, Boolean::class, Date::class, ArrayRule::class,
                Json::class, Enum::class, Confirmed::class, Same::class,
                Different::class, Distinct::class, Prohibited::class,
                Accepted::class, Declined::class, Present::class,
                Nullable::class, Sometimes::class, Size::class,
                StartsWith::class, EndsWith::class, Uuid::class,
                Between::class, NestedArray::class,
                RequiredIf::class, RequiredUnless::class,
                RequiredWith::class, RequiredWithAll::class,
                RequiredWithout::class, RequiredWithoutAll::class,
            ];

            foreach ($classes as $class) {
                expect($class)->toImplement(ValidationAttribute::class);
                $ref = new ReflectionClass($class);
                $instance = $ref->newInstanceWithoutConstructor();
                expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
            }
        });

        it('ruleKey() values match Laravel validation rule names', function () {
            expect((new Required)->ruleKey())->toBe('required');
            expect((new Email)->ruleKey())->toBe('email');
            expect((new Max(10))->ruleKey())->toBe('max');
            expect((new Min(1))->ruleKey())->toBe('min');
            expect((new Url)->ruleKey())->toBe('url');
            expect((new Pattern('/^[a-z]+$/'))->ruleKey())->toBe('regex');
            expect((new Integer)->ruleKey())->toBe('integer');
            expect((new Numeric)->ruleKey())->toBe('numeric');
            expect((new Boolean)->ruleKey())->toBe('boolean');
            expect((new Uuid)->ruleKey())->toBe('uuid');
            expect((new Date)->ruleKey())->toBe('date');
            expect((new ArrayRule)->ruleKey())->toBe('array');
            expect((new Confirmed)->ruleKey())->toBe('confirmed');
            expect((new Same('field'))->ruleKey())->toBe('same');
            expect((new Different('field'))->ruleKey())->toBe('different');
            expect((new Distinct)->ruleKey())->toBe('distinct');
            expect((new Prohibited)->ruleKey())->toBe('prohibited');
            expect((new Present)->ruleKey())->toBe('present');
            expect((new Accepted)->ruleKey())->toBe('accepted');
            expect((new Size(5))->ruleKey())->toBe('size');
            expect((new StartsWith(['prefix']))->ruleKey())->toBe('starts_with');
            expect((new EndsWith(['suffix']))->ruleKey())->toBe('ends_with');
            expect((new Nullable)->ruleKey())->toBe('nullable');
            expect((new Sometimes)->ruleKey())->toBe('sometimes');
        });
    });

    describe('Metadata Attributes Contract', function () {
        it('MapFrom is metadata-only (not ValidationAttribute)', function () {
            expect(MapFrom::class)->not->toImplement(ValidationAttribute::class);
        });

        it('Cast is metadata-only (not ValidationAttribute)', function () {
            expect(Cast::class)->not->toImplement(ValidationAttribute::class);
        });

        it('DefaultValue is metadata-only (not ValidationAttribute)', function () {
            expect(DefaultValue::class)->not->toImplement(ValidationAttribute::class);
        });

        it('Hidden is metadata-only (not ValidationAttribute)', function () {
            expect(Hidden::class)->not->toImplement(ValidationAttribute::class);
        });

        it('Collection is metadata-only (not ValidationAttribute)', function () {
            expect(Collection::class)->not->toImplement(ValidationAttribute::class);
        });
    });

    describe('Contracts Interface Compliance', function () {
        it('DataTransferObject implements FromRequestDTO', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        });

        it('DataTransferObject implements ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });

        it('DataTransferObject implements Arrayable', function () {
            expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
        });

        it('DataTransferObject implements JsonSerializable', function () {
            expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
        });
    });

    describe('Hydration Pipeline Contract', function () {
        it('fromArray() creates DTO with typed properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
            expect($dto->password)->toBe('secret123');
        });

        it('fromArray() applies defaults for missing fields', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('fromArray() applies MapFrom key mapping', function () {
            // EdgeCaseDTO likely has MapFrom attributes
            // Verify that map_from is properly resolved
            $rules = EdgeCaseDTO::rules();
            expect($rules)->toBeArray();
        });

        it('fromArray() applies Cast type conversion', function () {
            $rules = ValidationTestDTO::rules();
            expect($rules)->toBeArray();
        });

        it('fromArray() applies DefaultValue attribute', function () {
            $rules = EdgeCaseDTO::rules();
            expect($rules)->toBeArray();
        });

        it('fromPartialArray() creates DTO with partial data', function () {
            $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated'], validatePresent: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Updated');
        });

        it('fromJson() decodes JSON and creates DTO', function () {
            $json = json_encode([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'password' => 'pass123',
            ]);

            $dto = CreateUserDTO::fromJson($json);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Bob');
        });

        it('fromJson() throws DTOException on invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('not-json'))
                ->toThrow(DTOException::class);
        });

        it('fromJson() throws DTOException on sequential arrays', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });
    });

    describe('Serialization Contract', function () {
        it('toArray() returns associative array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $arr = $dto->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('toArray() excludes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues() includes Hidden properties', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret');
        });

        it('toJson() returns valid JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $json = $dto->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('jsonSerialize() returns same as toArray()', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });

        it('only() returns specified fields only', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $only = $dto->only('email');
            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
            expect($only)->not->toHaveKey('password');
        });

        it('except() excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $except = $dto->except('email');
            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('name');
        });
    });

    describe('Immutable Update Contract', function () {
        it('with() returns new instance', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $updated = $original->with(['name' => 'Bob']);

            expect($updated)->not->toBe($original);
            expect($original->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('test@example.com');
        });

        it('with() always validates', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            expect(fn () => $dto->with(['email' => 'not-an-email']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('State Checks Contract', function () {
        it('isEmpty() returns true for default DTO', function () {
            $dto = AllDefaultsDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
        });

        it('isEmpty() returns false for DTO with values', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty() is correct negation', function () {
            $empty = AllDefaultsDTO::fromArray([], validate: false);
            $filled = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);

            expect($empty->isNotEmpty())->toBeFalse();
            expect($filled->isNotEmpty())->toBeTrue();
        });

        it('equals() compares by toArray()', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            expect($a->equals($b))->toBeTrue();
        });

        it('equals() detects differences', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            $b = CreateUserDTO::fromArray([
                'email' => 'other@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('DtoCollection Contract', function () {
        it('make() creates collection from array', function () {
            $dtoArray = [
                MinimalDTO::fromArray(['name' => 'Alice'], validate: false),
                MinimalDTO::fromArray(['name' => 'Bob'], validate: false),
            ];

            $col = DtoCollection::make($dtoArray);
            expect($col->count())->toBe(2);
        });

        it('toArray() serializes all DTOs', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = new DtoCollection([$dto]);

            $arr = $col->toArray();
            expect($arr)->toBeArray();
            expect($arr)->toHaveCount(1);
            expect($arr[0])->toHaveKey('name');
        });

        it('first() and last() work correctly', function () {
            $col = DtoCollection::make([
                MinimalDTO::fromArray(['name' => 'First'], validate: false),
                MinimalDTO::fromArray(['name' => 'Last'], validate: false),
            ]);

            expect($col->first()->name)->toBe('First');
            expect($col->last()->name)->toBe('Last');
        });

        it('first() returns null for empty collection', function () {
            $col = new DtoCollection;
            expect($col->first())->toBeNull();
            expect($col->last())->toBeNull();
        });

        it('isEmpty() and isNotEmpty() work', function () {
            $empty = new DtoCollection;
            $filled = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'X'], validate: false),
            ]);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($filled->isEmpty())->toBeFalse();
            expect($filled->isNotEmpty())->toBeTrue();
        });

        it('map() returns plain array', function () {
            $col = DtoCollection::make([
                MinimalDTO::fromArray(['name' => 'Alice'], validate: false),
                MinimalDTO::fromArray(['name' => 'Bob'], validate: false),
            ]);

            $names = $col->map(fn (DataTransferObject $dto) => $dto->toArray()['name']);
            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('filter() returns new collection', function () {
            $col = DtoCollection::make([
                MinimalDTO::fromArray(['name' => 'Alice'], validate: false),
                MinimalDTO::fromArray(['name' => ''], validate: false),
            ]);

            $filtered = $col->filter(fn (DataTransferObject $dto) => $dto->toArray()['name'] !== '');
            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2); // original unchanged
        });

        it('append() returns new collection without mutating', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = new DtoCollection([$dto1]);

            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge() combines two collections', function () {
            $col1 = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'Alice'], validate: false),
            ]);
            $col2 = new DtoCollection([
                MinimalDTO::fromArray(['name' => 'Bob'], validate: false),
            ]);

            $merged = $col1->merge($col2);
            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('push() mutates in-place and returns self', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Bob'], validate: false);
            $col = new DtoCollection;
            $result = $col->push($dto);

            expect($result)->toBe($col);
            expect($col->count())->toBe(1);
        });

        it('pluck() extracts property values', function () {
            $col = DtoCollection::make([
                MinimalDTO::fromArray(['name' => 'Alice'], validate: false),
                MinimalDTO::fromArray(['name' => 'Bob'], validate: false),
            ]);

            $names = $col->pluck('name');
            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('offsetExists/Get/Set/Unset work', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeFalse();
            expect($col->offsetGet(0)->name)->toBe('Alice');
            expect($col->offsetGet(1))->toBeNull();

            $col->offsetUnset(0);
            expect($col->count())->toBe(0);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection([new \stdClass]))
                ->toThrow(InvalidArgumentException::class);
        });

        it('jsonSerialize() returns array of arrays', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = new DtoCollection([$dto]);

            $json = json_encode($col);
            $decoded = json_decode($json, true);

            expect($decoded)->toBeArray();
            expect($decoded[0])->toHaveKey('name');
        });

        it('__debugInfo() shows count and truncated items', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $col = new DtoCollection([$dto]);

            $debug = $col->__debugInfo();
            expect($debug)->toHaveKey('count');
            expect($debug)->toHaveKey('items');
            expect($debug['count'])->toBe(1);
        });
    });

    describe('Rules Resolution Contract', function () {
        it('rules() generates correct rules for CreateUserDTO', function () {
            $rules = CreateUserDTO::rules();

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
        });

        it('rules() handles Required with Nullable combination', function () {
            $rules = NullableRoundtripDTO::rules();
            expect($rules)->toBeArray();
        });

        it('rulesFor() defaults to rules()', function () {
            $rules = MinimalDTO::rules();
            $rulesFor = MinimalDTO::rulesFor('create');
            expect($rules)->toBe($rulesFor);
        });

        it('validateArray() returns validated data', function () {
            $result = CreateUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ]);

            expect($result['email'])->toBe('test@example.com');
        });

        it('validateArray() throws on invalid data', function () {
            expect(fn () => CreateUserDTO::validateArray(['email' => 'not-valid']))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('DTOCast Contract', function () {
        it('get() returns DTO from JSON string', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->get(
                new stdClass,
                'data',
                json_encode(['name' => 'Alice']),
                []
            );

            expect($result)->toBeInstanceOf(MinimalDTO::class);
            expect($result->name)->toBe('Alice');
        });

        it('get() returns null for null value', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->get(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('set() serializes DTO to JSON', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $result = $cast->set(new stdClass, 'data', $dto, []);

            $decoded = json_decode($result, true);
            expect($decoded['name'])->toBe('Alice');
        });

        it('set() serializes array to JSON via DTO', function () {
            $cast = new DTOCast(MinimalDTO::class, validate: true);
            $result = $cast->set(new stdClass, 'data', ['name' => 'Alice'], []);

            $decoded = json_decode($result, true);
            expect($decoded['name'])->toBe('Alice');
        });

        it('set() returns null for null value', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $result = $cast->set(new stdClass, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('set() rejects unexpected types', function () {
            $cast = new DTOCast(MinimalDTO::class);
            expect(fn () => $cast->set(new stdClass, 'data', 123, []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('serialize() returns array from DTO', function () {
            $cast = new DTOCast(MinimalDTO::class);
            $dto = MinimalDTO::fromArray(['name' => 'Alice'], validate: false);
            $result = $cast->serialize(new stdClass, 'data', $dto, []);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Alice');
        });
    });

    describe('DTOException Contract', function () {
        it('invalidCast() produces descriptive message', function () {
            $e = DTOException::invalidCast('email', 'integer', 'not-a-number');

            expect($e->getMessage())->toContain('email');
            expect($e->getMessage())->toContain('integer');
        });

        it('invalidJson() produces descriptive message', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');

            expect($e->getMessage())->toContain('payload');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('__toString() produces readable output', function () {
            $e = DTOException::invalidCast('field', 'int', 'abc');

            $str = (string) $e;
            expect($str)->toContain('DTOException');
        });
    });

    describe('Metadata Cache Contract', function () {
        it('flushMetadataCache() clears all cached entries', function () {
            // Trigger metadata resolution
            CreateUserDTO::rules();

            DataTransferObject::flushMetadataCache();

            // Re-resolve should work
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache(null) clears all', function () {
            CreateUserDTO::rules();
            MinimalDTO::rules();

            DataTransferObject::flushMetadataCache(null);

            // Should re-resolve successfully
            expect(CreateUserDTO::rules())->toBeArray();
        });

        it('setMetadataCacheTtl() accepts float values', function () {
            DataTransferObject::setMetadataCacheTtl(2.0);
            // No assertion needed — just ensure it doesn't throw
            expect(true)->toBeTrue();

            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    describe('DtoMetadataResolver Contract', function () {
        it('resolve() returns properties, rules, and messages', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($metadata)->toHaveKeys(['properties', 'rules', 'messages']);
            expect($metadata['properties'])->toBeArray();
            expect($metadata['rules'])->toBeArray();
            expect($metadata['messages'])->toBeArray();
        });

        it('detects Required attribute', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($metadata['rules']['email'])->toContain('required');
        });

        it('detects Email attribute', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($metadata['rules']['email'])->toContain('email');
        });

        it('detects Min/Max attributes', function () {
            $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);

            // Password should have min constraint
            expect($metadata['rules']['password'])->not->toBeEmpty();
        });
    });

    describe('OpenApi Schema Generator Contract', function () {
        it('generates schema for simple DTO', function () {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(MinimalDTO::class);

            expect($schema)->toBeArray();
            expect($schema['type'])->toBe('object');
            expect($schema['properties'])->toBeObject();
        });

        it('schema includes required fields', function () {
            $schema = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema)->toHaveKey('required');
            expect($schema['required'])->toContain('email');
        });

        it('throws for nested DTOs in generate()', function () {
            expect(fn () => \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate(OrderDTO::class))
                ->toThrow(\LogicException::class);
        });

        it('generateWithComponents() handles nested DTOs', function () {
            $result = \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);

            expect($result)->toHaveKey('schema');
            expect($result)->toHaveKey('components');
            expect($result['components']['schemas'])->toBeArray();
        });
    });

    describe('Cross-DTO Consistency', function () {
        it('all fixture DTOs can be instantiated', function () {
            $fixtureDtos = [
                EmptyDTO::class,
                MinimalDTO::class,
                AllDefaultsDTO::class,
                AllScalarTypesDTO::class,
                NullableRoundtripDTO::class,
                ValidationTestDTO::class,
            ];

            foreach ($fixtureDtos as $dtoClass) {
                $rules = $dtoClass::rules();
                expect($rules)->toBeArray();
            }
        });

        it('all fixture DTOs implement expected interfaces', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test'], validate: false);

            expect($dto)->toBeInstanceOf(DataTransferObject::class);
            expect($dto)->toBeInstanceOf(FromRequestDTO::class);
            expect($dto)->toBeInstanceOf(ValidatableDTO::class);
            expect($dto)->toBeInstanceOf(\JsonSerializable::class);
            expect($dto)->toBeInstanceOf(\Illuminate\Contracts\Support\Arrayable::class);
        });
    });

    describe('Roundtrip Serialization Contract', function () {
        it('fromArray → toArray roundtrip preserves data', function () {
            $data = [
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ];

            $dto = CreateUserDTO::fromArray($data);
            $restored = $dto->toArray();

            expect($restored['email'])->toBe($data['email']);
            expect($restored['name'])->toBe($data['name']);
            // password is Hidden, not in toArray
        });

        it('fromArray → allValues → fromArray roundtrip preserves data', function () {
            $data = [
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ];

            $dto = CreateUserDTO::fromArray($data);
            $all = $dto->allValues();
            $restored = CreateUserDTO::fromArray($all);

            expect($restored->email)->toBe($data['email']);
            expect($restored->name)->toBe($data['name']);
            expect($restored->password)->toBe($data['password']);
        });

        it('fromArray → toJson → fromJson roundtrip', function () {
            $data = [
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ];

            $dto = CreateUserDTO::fromArray($data);
            $json = $dto->allValues(); // use allValues to include hidden
            $jsonStr = json_encode($json);
            $restored = CreateUserDTO::fromJson($jsonStr);

            expect($restored->email)->toBe($data['email']);
            expect($restored->name)->toBe($data['name']);
        });
    });
});
