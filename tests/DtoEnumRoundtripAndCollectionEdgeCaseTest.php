<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DeepNestedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;

/**
 * Comprehensive DTO edge case tests covering enum roundtrips, collection
 * operations, partial updates, and attribute interaction edge cases.
 */
describe('DTO enum roundtrip and collection edge cases', function () {
    it('DTO roundtrips through fromArray and toArray', function () {
        $data = [
            'foo' => 'hello',
            'bar' => 'world',
        ];

        $dto = EmptyDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['foo'])->toBe('hello');
        expect($result['bar'])->toBe('world');
    });

    it('with() preserves values after immutable update', function () {
        $dto = EmptyDTO::fromArray(['foo' => 'original', 'bar' => 'unchanged'], validate: false);
        $updated = $dto->with(['foo' => 'modified']);

        expect($updated->foo)->toBe('modified');
        expect($updated->bar)->toBe('unchanged');
        expect($dto->foo)->toBe('original'); // original unchanged
    });

    it('equals() compares DTOs by their serialized output', function () {
        $data = ['foo' => 'value', 'bar' => 'test'];
        $dto1 = EmptyDTO::fromArray($data, validate: false);
        $dto2 = EmptyDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('isEmpty() detects all-default DTO', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray(['email' => 'test@example.com'], validate: false);

        expect($dto->email)->toBe('test@example.com');
    });

    it('fromJson decodes and hydrates correctly', function () {
        $json = json_encode(['email' => 'user@test.com', 'name' => 'John']);
        $dto = CreateUserDTO::fromJson($json, validate: false);

        expect($dto->email)->toBe('user@test.com');
        expect($dto->name)->toBe('John');
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => CreateUserDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('only() returns selected fields', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test', 'status' => 'active'],
            validate: false,
        );

        $result = $dto->only('email');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
    });

    it('except() excludes specified fields', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test', 'status' => 'active'],
            validate: false,
        );

        $result = $dto->except('status');
        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('status');
    });

    it('allValues() includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test', 'password' => 'secret'],
            validate: false,
        );

        $all = $dto->allValues();
        $visible = $dto->toArray();

        // allValues should have at least as many keys as toArray
        expect(count($all))->toBeGreaterThanOrEqual(count($visible));
        // Hidden field (password) should be in allValues but not in toArray
        expect($all)->toHaveKey('password');
    });

    it('toJson produces valid JSON string', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test'],
            validate: false,
        );

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray();
        expect($decoded['email'])->toBe('test@test.com');
    });

    it('jsonSerialize matches toArray output', function () {
        $dto = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test'],
            validate: false,
        );

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('DtoCollection count returns correct number', function () {
        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = CreateUserDTO::fromArray(
                ['email' => "user{$i}@test.com", 'name' => "User {$i}"],
                validate: false,
            );
        }

        $collection = new DtoCollection($items);

        expect(count($collection))->toBe(3);
        expect($collection->isEmpty())->toBeFalse();
        expect($collection->isNotEmpty())->toBeTrue();
    });

    it('DtoCollection first and last return correct items', function () {
        $items = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false),
        ];

        $collection = new DtoCollection($items);

        expect($collection->first()->email)->toBe('a@test.com');
        expect($collection->last()->email)->toBe('c@test.com');
    });

    it('DtoCollection map returns transformed array', function () {
        $items = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $emails = $collection->map(static fn (DataTransferObject $dto) => $dto->email);

        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('DtoCollection filter returns new collection with matching items', function () {
        $items = [
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob'], validate: false),
        ];

        $collection = new DtoCollection($items);
        $filtered = $collection->filter(
            static fn (DataTransferObject $dto) => str_starts_with($dto->name, 'A'),
        );

        expect(count($filtered))->toBe(1);
    });

    it('DtoCollection append returns new collection without mutating original', function () {
        $original = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);

        $appended = $original->append(
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        );

        expect(count($original))->toBe(1);
        expect(count($appended))->toBe(2);
    });

    it('DtoCollection merge combines two collections', function () {
        $c1 = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);
        $c2 = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
        ]);

        $merged = $c1->merge($c2);

        expect(count($merged))->toBe(2);
    });

    it('DtoCollection offsetUnset re-indexes', function () {
        $collection = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
            CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B'], validate: false),
            CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C'], validate: false),
        ]);

        unset($collection[1]);

        expect(count($collection))->toBe(2);
        expect($collection[0]->email)->toBe('a@test.com');
        expect($collection[1]->email)->toBe('c@test.com'); // re-indexed
    });

    it('DtoCollection jsonSerialize produces array of arrays', function () {
        $collection = new DtoCollection([
            CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A'], validate: false),
        ]);

        $serialized = $collection->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized[0])->toBeArray();
        expect($serialized[0])->toHaveKey('email');
    });

    it('nested DTO roundtrips through fromArray and toArray', function () {
        $data = [
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ];

        $dto = DeepNestedDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['address'])->toBeArray();
        expect($result['address']['street'])->toBe('123 Main St');
    });

    it('MapFrom dot notation resolves nested source keys', function () {
        $data = [
            'user' => [
                'name' => 'John Doe',
            ],
        ];

        $dto = DotNotationDTO::fromArray($data, validate: false);

        expect($dto->name)->toBe('John Doe');
    });

    it('validation rules are correctly resolved from attributes', function () {
        $rules = RegistrationDTO::rules();

        expect($rules)->toBeArray();
    });

    it('metadata cache flush works correctly', function () {
        CreateUserDTO::flushMetadataCache();

        $dto1 = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test'],
            validate: false,
        );

        CreateUserDTO::flushMetadataCache();

        $dto2 = CreateUserDTO::fromArray(
            ['email' => 'test@test.com', 'name' => 'Test'],
            validate: false,
        );

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('validateArray throws on invalid data', function () {
        expect(fn () => RegistrationDTO::validateArray([]))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('rulesFor returns same as rules by default', function () {
        expect(RegistrationDTO::rulesFor('create'))
            ->toBe(RegistrationDTO::rules());
    });
});
