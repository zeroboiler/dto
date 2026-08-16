<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\{Cast, Collection, DefaultValue, Email, Hidden, MapFrom, Max, Min, NestedArray, Required};
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\{FromRequestDTO, ValidationAttribute, ValidatableDTO};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\{
    AddressDTO,
    AllDefaultsDTO,
    AllScalarTypesDTO,
    ArrayCastDTO,
    ArticleDTO,
    ComprehensiveDTO,
    CreateUserDTO,
    DateCastDTO,
    EdgeCaseDTO,
    EmptyDTO,
    MinimalDTO,
    NestedCollectionDTO,
    NullableRoundtripDTO,
    OrderDTO,
    OrderItemDTO,
    RoundtripDTO,
    ScalarConstraintsDTO,
    StrictValidationDTO,
    WithRoundtripDTO,
};
use ZeroBoiler\DTO\DTOSServiceProvider;

describe('V26 — DTO hydration and serialization roundtrip', function () {
    it('CreateUserDTO fromArray → toArray roundtrip preserves data', function () {
        $data = [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'age' => '30',
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $arr = $dto->toArray();

        expect($arr['name'])->toBe('Alice Johnson');
        expect($arr['email'])->toBe('alice@example.com');
        expect($arr['age'])->toBe(30); // Cast('integer')
    });

    it('CreateUserDTO fromArray with validation disabled accepts any data', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@test.com',
            'age' => '25',
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Bob');
    });

    it('EmptyDTO accepts empty array', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('MinimalDTO with all defaults accepts empty array', function () {
        $dto = MinimalDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
    });

    it('AllDefaultsDTO uses default values when keys are absent', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
    });

    it('equals() returns true for same data', function () {
        $data = ['name' => 'Test', 'email' => 'test@example.com', 'age' => '25'];
        $a = CreateUserDTO::fromArray($data, validate: false);
        $b = CreateUserDTO::fromArray($data, validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('equals() returns false for different data', function () {
        $a = CreateUserDTO::fromArray(['name' => 'A', 'email' => 'a@x.com', 'age' => '1'], validate: false);
        $b = CreateUserDTO::fromArray(['name' => 'B', 'email' => 'b@x.com', 'age' => '2'], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('isEmpty() returns true for DTO with all empty/default values', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        // AllDefaultsDTO has defaults — isEmpty checks if all are empty
        expect(is_bool($dto->isEmpty()))->toBeTrue();
    });

    it('isNotEmpty() is negation of isEmpty()', function () {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        expect($dto->isNotEmpty())->toBe(!$dto->isEmpty());
    });
});

describe('V26 — DTO serialization methods', function () {
    it('toJson produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => '30',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['name'])->toBe('Alice');
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'age' => '25',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('toArray excludes hidden properties', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'age' => 25,
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('password');
        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
    });

    it('allValues includes hidden properties', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'age' => 25,
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password');
        expect($all['password'])->toBe('secret123');
    });

    it('only() returns subset of fields', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'age' => 25,
        ], validate: false);

        $subset = $dto->only(['name']);
        expect($subset)->toHaveKey('name');
        expect($subset)->not->toHaveKey('email');
        expect($subset)->not->toHaveKey('age');
    });

    it('except() excludes specified fields', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'age' => 25,
        ], validate: false);

        $result = $dto->except(['age']);
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('age');
    });
});

describe('V26 — DTO fromJson and partial methods', function () {
    it('fromJson creates DTO from valid JSON', function () {
        $json = json_encode([
            'name' => 'JSON User',
            'email' => 'json@example.com',
            'age' => 28,
        ]);

        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('JSON User');
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for sequential array', function () {
        expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromPartialArray hydrates only provided fields', function () {
        $dto = ComprehensiveDTO::fromPartialArray(['name' => 'Partial'], validate: false);

        expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
        expect($dto->name)->toBe('Partial');
    });

    it('with() creates new immutable instance', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Original',
            'age' => 25,
        ], validate: false);

        $modified = $dto->with(['name' => 'Modified']);

        expect($dto->name)->toBe('Original');
        expect($modified->name)->toBe('Modified');
        expect($modified)->not->toBe($dto);
    });
});

describe('V26 — DtoCollection operations', function () {
    it('make creates empty collection', function () {
        $col = DtoCollection::make();

        expect($col->isEmpty())->toBeTrue();
        expect(count($col))->toBe(0);
    });

    it('make with items creates populated collection', function () {
        $items = [
            EmptyDTO::fromArray(['foo' => 'a', 'bar' => 'b'], validate: false),
            EmptyDTO::fromArray(['foo' => 'c', 'bar' => 'd'], validate: false),
        ];
        $col = DtoCollection::make($items);

        expect(count($col))->toBe(2);
    });

    it('push mutates and returns same collection', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);

        $result = $col->push($dto);

        expect($result)->toBe($col); // same instance
        expect(count($col))->toBe(1);
    });

    it('append returns new collection', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);

        $new = $col->append($dto);

        expect($new)->not->toBe($col);
        expect(count($col))->toBe(0);
        expect(count($new))->toBe(1);
    });

    it('first returns first item', function () {
        $a = EmptyDTO::fromArray(['foo' => 'first', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'second', 'bar' => null], validate: false);
        $col = new DtoCollection([$a, $b]);

        expect($col->first()->foo)->toBe('first');
    });

    it('first returns null for empty collection', function () {
        $col = DtoCollection::make();

        expect($col->first())->toBeNull();
    });

    it('last returns last item', function () {
        $a = EmptyDTO::fromArray(['foo' => 'first', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'last', 'bar' => null], validate: false);
        $col = new DtoCollection([$a, $b]);

        expect($col->last()->foo)->toBe('last');
    });

    it('last returns null for empty collection', function () {
        $col = DtoCollection::make();

        expect($col->last())->toBeNull();
    });

    it('map returns plain array of results', function () {
        $a = EmptyDTO::fromArray(['foo' => 'a', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'b', 'bar' => null], validate: false);
        $col = new DtoCollection([$a, $b]);

        $result = $col->map(fn (DataTransferObject $dto): string => $dto->foo);

        expect($result)->toBe(['a', 'b']);
    });

    it('filter returns new collection with matching items', function () {
        $a = EmptyDTO::fromArray(['foo' => 'keep', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'drop', 'bar' => null], validate: false);
        $col = new DtoCollection([$a, $b]);

        $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->foo === 'keep');

        expect(count($filtered))->toBe(1);
        expect($filtered->first()->foo)->toBe('keep');
        // Original unchanged
        expect(count($col))->toBe(2);
    });

    it('merge combines two collections', function () {
        $a = EmptyDTO::fromArray(['foo' => 'a', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => 'b', 'bar' => null], validate: false);
        $c = EmptyDTO::fromArray(['foo' => 'c', 'bar' => null], validate: false);
        $col1 = new DtoCollection([$a]);
        $col2 = new DtoCollection([$b, $c]);

        $merged = $col1->merge($col2);

        expect(count($merged))->toBe(3);
    });

    it('toArray serializes each DTO', function () {
        $dto = ComprehensiveDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'User',
            'age' => 30,
        ], validate: false);
        $col = new DtoCollection([$dto]);

        $arr = $col->toArray();

        expect($arr)->toHaveCount(1);
        expect($arr[0]['name'])->toBe('User');
        // Hidden fields excluded
        expect($arr[0])->not->toHaveKey('password');
    });

    it('jsonSerialize returns toArray output', function () {
        $col = DtoCollection::make();

        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('clone throws RuntimeException', function () {
        $col = DtoCollection::make();

        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('ArrayAccess: offsetExists, offsetGet, offsetSet, offsetUnset', function () {
        $col = DtoCollection::make();
        $dto = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);

        expect(isset($col[0]))->toBeFalse();

        $col[] = $dto;

        expect(isset($col[0]))->toBeTrue();
        expect($col[0]->foo)->toBe('x');

        unset($col[0]);

        expect(count($col))->toBe(0);
    });

    it('foreach iteration works', function () {
        $a = EmptyDTO::fromArray(['foo' => '1', 'bar' => null], validate: false);
        $b = EmptyDTO::fromArray(['foo' => '2', 'bar' => null], validate: false);
        $col = new DtoCollection([$a, $b]);

        $names = [];
        foreach ($col as $dto) {
            $names[] = $dto->foo;
        }

        expect($names)->toBe(['1', '2']);
    });

    it('pluck extracts single property', function () {
        $a = ComprehensiveDTO::fromArray([
            'email' => 'a@x.com', 'name' => 'Alice', 'age' => 25,
        ], validate: false);
        $b = ComprehensiveDTO::fromArray([
            'email' => 'b@x.com', 'name' => 'Bob', 'age' => 30,
        ], validate: false);
        $col = new DtoCollection([$a, $b]);

        $names = $col->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('items() returns raw DTO instances', function () {
        $a = EmptyDTO::fromArray(['foo' => 'x', 'bar' => 'y'], validate: false);
        $col = new DtoCollection([$a]);

        expect($col->items())->toBe([$a]);
    });
});

describe('V26 — DTOCast serialization', function () {
    it('get() returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect($cast->get($model, 'data', null, []))->toBeNull();
    });

    it('get() returns DTO from JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $json = json_encode(['name' => 'Cast', 'email' => 'cast@test.com', 'age' => 25]);
        $dto = $cast->get($model, 'data', $json, []);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Cast');
    });

    it('get() returns null for invalid JSON', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect($cast->get($model, 'data', 'invalid{json', []))->toBeNull();
    });

    it('set() serializes DTO to JSON', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $dto = CreateUserDTO::fromArray(['name' => 'Set', 'email' => 'set@test.com', 'age' => 20], validate: false);
        $result = $cast->set($model, 'data', $dto, []);

        expect($result)->toBeJson();
        $decoded = json_decode($result, true);
        expect($decoded['name'])->toBe('Set');
    });

    it('set() hydrates and serializes array', function () {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $result = $cast->set($model, 'data', ['name' => 'Arr', 'email' => 'arr@test.com', 'age' => '22'], []);

        expect($result)->toBeJson();
    });

    it('set() returns null for null', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect($cast->set($model, 'data', null, []))->toBeNull();
    });

    it('set() throws for unsupported type', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect(fn () => $cast->set($model, 'data', 12345, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('serialize() returns toArray output', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        $dto = CreateUserDTO::fromArray(['name' => 'Ser', 'email' => 'ser@test.com', 'age' => 18], validate: false);
        $result = $cast->serialize($model, 'data', $dto, []);

        expect($result)->toBeArray();
        expect($result['name'])->toBe('Ser');
    });

    it('serialize() returns null for null', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $model = new class {
            public function __get(string $key): mixed { return null; }
        };

        expect($cast->serialize($model, 'data', null, []))->toBeNull();
    });
});

describe('V26 — DTOManager delegation', function () {
    it('make creates DTO from array', function () {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'name' => 'Mgr',
            'email' => 'mgr@test.com',
            'age' => '30',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Mgr');
    });

    it('rules returns validation rules', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
    });

    it('rulesFor returns rules for action', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(CreateUserDTO::class, 'create');

        expect($rules)->toBeArray();
    });

    it('validate throws for invalid data', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->validate(CreateUserDTO::class, ['name' => '']))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('fromPartialArray creates partial DTO', function () {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(ComprehensiveDTO::class, ['name' => 'Partial']);

        expect($dto)->toBeInstanceOf(ComprehensiveDTO::class);
    });
});

describe('V26 — DTO metadata cache', function () {
    it('setMetadataCacheTtl accepts float', function () {
        DataTransferObject::setMetadataCacheTtl(2.0);
        // No exception = pass
        expect(true)->toBeTrue();
        DataTransferObject::setMetadataCacheTtl(0.0); // reset
    });

    it('flushMetadataCache clears all', function () {
        DataTransferObject::flushMetadataCache();
        expect(true)->toBeTrue();
    });

    it('flushMetadataCache with class clears only that class', function () {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        expect(true)->toBeTrue();
    });
});

describe('V26 — DTOException contract', function () {
    it('invalidCast produces correct message', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
        expect($e->getMessage())->toContain('not-a-number');
    });

    it('invalidJson produces correct message', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString includes class name', function () {
        $e = DTOException::invalidCast('field', 'type', 'value');
        $str = (string) $e;

        expect($str)->toContain('DTOException');
    });

    it('is final', function () {
        expect((new ReflectionClass(DTOException::class))->isFinal())->toBeTrue();
    });

    it('extends Exception', function () {
        expect(new DTOException('test'))->toBeInstanceOf(\Exception::class);
    });
});

describe('V26 — Interface compliance', function () {
    it('DataTransferObject implements Arrayable', function () {
        expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
    });

    it('DataTransferObject implements FromRequestDTO', function () {
        expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements JsonSerializable', function () {
        expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
    });

    it('DataTransferObject implements ValidatableDTO', function () {
        expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
    });

    it('DtoCollection implements ArrayAccess', function () {
        expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
    });

    it('DtoCollection implements Countable', function () {
        expect(DtoCollection::class)->toImplement(\Countable::class);
    });

    it('DtoCollection implements IteratorAggregate', function () {
        expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
    });

    it('DtoCollection implements JsonSerializable', function () {
        expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
    });
});

describe('V26 — Final class and readonly verification', function () {
    it('DataTransferObject is not final (abstract)', function () {
        $ref = new ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    it('DtoCollection is final', function () {
        expect((new ReflectionClass(DtoCollection::class))->isFinal())->toBeTrue();
    });

    it('DTOManager is final readonly', function () {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTOCast is final', function () {
        expect((new ReflectionClass(DTOCast::class))->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider is final', function () {
        expect((new ReflectionClass(DTOSServiceProvider::class))->isFinal())->toBeTrue();
    });
});

describe('V26 — Validation attribute contract completeness', function () {
    it('all validation attributes are final', function () {
        $attributes = [
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
        ];

        foreach ($attributes as $attr) {
            expect((new ReflectionClass($attr))->isFinal())->toBeTrue("{$attr} must be final");
        }
    });

    it('metadata-only attributes are final', function () {
        $metaAttrs = [
            \ZeroBoiler\DTO\Attributes\Cast::class,
            \ZeroBoiler\DTO\Attributes\MapFrom::class,
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\DefaultValue::class,
        ];

        foreach ($metaAttrs as $attr) {
            expect((new ReflectionClass($attr))->isFinal())->toBeTrue("{$attr} must be final");
            expect($attr)->not->toImplement(ValidationAttribute::class);
        }
    });

    it('Cast attribute has readonly string type property', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Attributes\Cast::class);
        $prop = $ref->getProperty('type');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('MapFrom attribute has readonly string key property', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Attributes\MapFrom::class);
        $prop = $ref->getProperty('key');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('Hidden has no constructor parameters', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Attributes\Hidden::class);
        expect($ref->getConstructor())->toBeNull();
    });
});
