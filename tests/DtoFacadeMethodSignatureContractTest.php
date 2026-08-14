<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facade as DTOFacade;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArrayCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO;

/**
 * Contract compliance test — verifies DTO facade and DTOManager method signatures
 * match the base class methods they delegate to, ensuring PHPStan Level 9 type safety.
 */
describe('DTO Facade and Manager — Method Signature Contract', function () {

    describe('DTOManager delegates correctly', function () {
        it('make() returns same result as fromArray()', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Test User', 'password' => 'secret123'];
            $expected = CreateUserDTO::fromArray($data);
            $actual = app(DTOManager::class)->make(CreateUserDTO::class, $data);

            expect($actual->toArray())->toBe($expected->toArray());
        });

        it('validate() returns same result as validateArray()', function () {
            $data = ['email' => 'test@example.com', 'name' => 'Test User', 'password' => 'secret123'];
            $expected = CreateUserDTO::validateArray($data);
            $actual = app(DTOManager::class)->validate(CreateUserDTO::class, $data);

            expect($actual)->toBe($expected);
        });

        it('rules() returns same result as static rules()', function () {
            $expected = CreateUserDTO::rules();
            $actual = app(DTOManager::class)->rules(CreateUserDTO::class);

            expect($actual)->toBe($expected);
        });

        it('rulesFor() returns same result as static rulesFor()', function () {
            $expected = CreateUserDTO::rulesFor('create');
            $actual = app(DTOManager::class)->rulesFor(CreateUserDTO::class, 'create');

            expect($actual)->toBe($expected);
        });
    });

    describe('DTOManager — partial update delegation', function () {
        it('fromPartialArray() delegates correctly', function () {
            $dto = app(DTOManager::class)->fromPartialArray(CreateUserDTO::class, ['name' => 'Updated']);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->name)->toBe('Updated');
            expect($dto->email)->toBeEmpty();
        });
    });

    describe('DTOManager — JSON delegation', function () {
        it('fromJson() delegates correctly', function () {
            $json = '{"email":"test@example.com","name":"Test User","password":"secret123"}';
            $dto = app(DTOManager::class)->fromJson(CreateUserDTO::class, $json);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
        });

        it('fromJson() throws DTOException for invalid JSON', function () {
            expect(fn () => app(DTOManager::class)->fromJson(CreateUserDTO::class, 'not-json'))
                ->throws(DTOException::class);
        });

        it('fromJson() throws DTOException for sequential array', function () {
            expect(fn () => app(DTOManager::class)->fromJson(CreateUserDTO::class, '[1,2,3]'))
                ->throws(DTOException::class);
        });
    });

    describe('DtoCollection — type safety', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection([new \stdClass()]))
                ->throws(\InvalidArgumentException::class);
        });

        it('pluck returns correct types', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A', 'password' => 'x'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'B', 'password' => 'y'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('pluckKey returns associative array', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'x'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob', 'password' => 'y'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $map = $col->pluckKey('email', 'name');

            expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Bob']);
        });

        it('toDictionary returns correct key-value pairs', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'x'], validate: false);

            $col = new DtoCollection([$dto1]);
            $dict = $col->toDictionary('email', 'name');

            expect($dict)->toBe(['a@b.com' => 'Alice']);
        });

        it('toArrayBy returns correct result', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => 'x'], validate: false);

            $col = new DtoCollection([$dto1]);
            $keyed = $col->toArrayBy('email');

            expect($keyed)->toHaveKey('a@b.com');
            expect($keyed['a@b.com'])->toHaveKey('email');
            expect($keyed['a@b.com'])->toHaveKey('name');
        });

        it('append returns new immutable collection', function () {
            $dto1 = MinimalDTO::fromArray([]);
            $dto2 = MinimalDTO::fromArray([]);
            $col = new DtoCollection([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge combines two collections', function () {
            $col1 = new DtoCollection([MinimalDTO::fromArray([])]);
            $col2 = new DtoCollection([MinimalDTO::fromArray([]), MinimalDTO::fromArray([])]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(3);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(2);
        });

        it('offsetUnset re-indexes correctly', function () {
            $col = new DtoCollection([
                MinimalDTO::fromArray([]),
                MinimalDTO::fromArray([]),
                MinimalDTO::fromArray([]),
            ]);

            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col->first())->not->toBeNull();
            expect($col->last())->not->toBeNull();
        });
    });

    describe('DTOCast — type safety contract', function () {
        it('get() returns null for null value', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();
            $result = $cast->get($model, 'data', null, []);

            expect($result)->toBeNull();
        });

        it('get() returns DTO from JSON string', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();
            $json = json_encode(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x']);
            $result = $cast->get($model, 'data', $json, []);

            expect($result)->toBeInstanceOf(CreateUserDTO::class);
            expect($result->email)->toBe('a@b.com');
        });

        it('set() returns JSON string for DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);
            $result = $cast->set($model, 'data', $dto, []);

            $decoded = json_decode($result, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('a@b.com');
        });

        it('set() throws for unexpected type', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();

            expect(fn () => $cast->set($model, 'data', 42, []))
                ->throws(\InvalidArgumentException::class);
        });

        it('serialize() returns array for DTO instance', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);
            $result = $cast->serialize($model, 'data', $dto, []);

            expect($result)->toBeArray();
            expect($result['email'])->toBe('a@b.com');
        });

        it('serialize() returns null for null', function () {
            $cast = new DTOCast(CreateUserDTO::class);
            $model = new \stdClass();
            $result = $cast->serialize($model, 'data', null, []);

            expect($result)->toBeNull();
        });
    });

    describe('DataTransferObject — immutable state contract', function () {
        it('with() creates new instance without modifying original', function () {
            $original = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Original', 'password' => 'x'], validate: false);
            $updated = $original->with(['name' => 'Updated']);

            expect($original->name)->toBe('Original');
            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('a@b.com');
        });

        it('equals() compares toArray output', function () {
            $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);
            $dto2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);
            $dto3 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Other', 'password' => 'y'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
            expect($dto1->equals($dto3))->toBeFalse();
        });

        it('isEmpty() and isNotEmpty() work correctly', function () {
            $empty = CreateUserDTO::fromArray(['email' => '', 'name' => '', 'password' => ''], validate: false);
            $nonEmpty = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'x'], validate: false);

            expect($empty->isEmpty())->toBeTrue();
            expect($empty->isNotEmpty())->toBeFalse();
            expect($nonEmpty->isEmpty())->toBeFalse();
            expect($nonEmpty->isNotEmpty())->toBeTrue();
        });

        it('only() returns specified fields', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'secret'], validate: false);
            $result = $dto->only('email', 'name');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('password');
        });

        it('except() excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Test', 'password' => 'secret'], validate: false);
            $result = $dto->except('password');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('password');
        });
    });
});
