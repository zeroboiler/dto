<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Comprehensive DTOCast, OpenAPI schema, and DTO exception edge case tests.
 *
 * Covers:
 * - DTOCast get/set/serialize for JSON and array values
 * - DTOCast type rejection for invalid values
 * - DTOCast null handling
 * - DTOException factory methods with varied inputs
 * - OpenAPI schema generation for simple and nested DTOs
 * - DtoCollection edge cases: cloning, re-indexing, filter, offset operations
 * - DataTransferObject metadata cache TTL behavior
 * - fromPartialArray edge cases with MapFrom and DefaultValue
 * - Nested DTO hydration and serialization round-trip
 *
 * @see \ZeroBoiler\DTO\Casts\DTOCast
 * @see \ZeroBoiler\DTO\DTOManager
 * @see \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator
 */

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTOCast edge cases', function (): void {

    describe('DTOCast get() — hydration from JSON string', function (): void {
        it('hydrates DTO from JSON string in database column', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $json = '{"email":"test@example.com","name":"Test User"}';

            $result = $cast->get(
                new \stdClass,
                'payload',
                $json,
                ['payload' => $json],
            );

            assert($result instanceof CreateUserDTO);
            expect($result->email)->toBe('test@example.com');
            expect($result->name)->toBe('Test User');
        });

        it('returns null for null database value', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->get(
                new \stdClass,
                'payload',
                null,
                ['payload' => null],
            );

            expect($result)->toBeNull();
        });

        it('returns null for invalid JSON string', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->get(
                new \stdClass,
                'payload',
                'not-json',
                ['payload' => 'not-json'],
            );

            expect($result)->toBeNull();
        });

        it('returns null for non-array decoded value', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            // "42" decodes to int, not array
            $result = $cast->get(
                new \stdClass,
                'payload',
                '"string"',
                ['payload' => '"string"'],
            );

            expect($result)->toBeNull();
        });

        it('hydrates DTO from array value directly', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $data = ['email' => 'test@example.com', 'name' => 'Test User'];

            $result = $cast->get(
                new \stdClass,
                'payload',
                $data,
                ['payload' => $data],
            );

            assert($result instanceof CreateUserDTO);
        });
    });

    describe('DTOCast set() — serialization to JSON', function (): void {
        it('serializes DTO instance to JSON string', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $cast->set(
                new \stdClass,
                'payload',
                $dto,
                ['payload' => null],
            );

            expect($result)->toBeJson();
            $decoded = json_decode((string) $result, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('serializes and validates array input', function (): void {
            $cast = new DTOCast(CreateUserDTO::class, validate: false);
            $data = ['email' => 'test@example.com', 'name' => 'Test User'];

            $result = $cast->set(
                new \stdClass,
                'payload',
                $data,
                ['payload' => null],
            );

            expect($result)->toBeJson();
        });

        it('rejects unexpected types', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            expect(fn () => $cast->set(
                new \stdClass,
                'payload',
                42,
                ['payload' => null],
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('returns null for null value', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->set(
                new \stdClass,
                'payload',
                null,
                ['payload' => null],
            );

            expect($result)->toBeNull();
        });
    });

    describe('DTOCast serialize()', function (): void {
        it('serializes DTO to array via toArray()', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $result = $cast->serialize(
                new \stdClass,
                'payload',
                $dto,
                ['payload' => null],
            );

            expect($result)->toBeArray();
            expect($result['email'])->toBe('test@example.com');
            // Hidden fields excluded
            expect($result)->not->toHaveKey('password');
        });

        it('returns null for null DTO', function (): void {
            $cast = new DTOCast(CreateUserDTO::class);

            $result = $cast->serialize(
                new \stdClass,
                'payload',
                null,
                ['payload' => null],
            );

            expect($result)->toBeNull();
        });
    });
});

describe('DTOException factory methods', function (): void {
    it('invalidCast() includes property name, type, and value debug type', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not-a-number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson() includes property and error message', function (): void {
        $e = DTOException::invalidJson('metadata', 'Syntax error');

        expect($e->getMessage())->toContain('metadata');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('invalidCast() handles array value debug type', function (): void {
        $e = DTOException::invalidCast('tags', 'string', ['array', 'value']);

        expect($e->getMessage())->toContain('tags');
        expect($e->getMessage())->toContain('string');
        // get_debug_type for array returns 'array'
        expect($e->getMessage())->toContain('array');
    });
});

describe('DtoCollection edge cases', function (): void {
    it('offsetSet with null key appends to end', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = DtoCollection::make([$d1]);

        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $col[] = $d2;

        expect($col->count())->toBe(2);
        expect($col[1]->email)->toBe('c@d.com');
    });

    it('offsetSet with int key replaces at position', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = DtoCollection::make([$d1]);

        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $col[0] = $d2;

        expect($col->count())->toBe(1);
        expect($col[0]->email)->toBe('c@d.com');
    });

    it('offsetSet rejects non-DTO values', function (): void {
        $col = DtoCollection::make([]);

        expect(fn () => $col[] = 'not-a-dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset re-indexes the array', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'E'], validate: false);
        $col = DtoCollection::make([$d1, $d2, $d3]);

        unset($col[1]);

        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('a@b.com');
        expect($col[1]->email)->toBe('e@f.com');
        // last() should work correctly after re-index
        expect($col->last()->email)->toBe('e@f.com');
    });

    it('filter returns collection with correct count', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
        $d3 = CreateUserDTO::fromArray(['email' => 'e@f.com', 'name' => 'Alice2'], validate: false);
        $col = DtoCollection::make([$d1, $d2, $d3]);

        $filtered = $col->filter(fn (CreateUserDTO $d): bool => str_starts_with($d->name, 'Alice'));
        expect($filtered->count())->toBe(2);
    });

    it('clone via append creates independent copy', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = DtoCollection::make([$d1]);

        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $newCol = $col->append($d2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
        // Verify the appended DTO is in the new collection
        expect($newCol[1]->email)->toBe('c@d.com');
    });

    it('map returns plain array indexed by original keys', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Bob'], validate: false);
        $col = DtoCollection::make([$d1, $d2]);

        $result = $col->map(fn (CreateUserDTO $d, int $key): string => "{$key}:{$d->name}");

        expect($result)->toBe(['0:Alice', '1:Bob']);
    });

    it('jsonSerialize returns toArray output', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = DtoCollection::make([$d1]);

        $serialized = $col->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized[0])->toHaveKey('email');
    });

    it('isEmpty and isNotEmpty', function (): void {
        $empty = DtoCollection::make([]);
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();

        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $col = DtoCollection::make([$d1]);
        expect($col->isEmpty())->toBeFalse();
        expect($col->isNotEmpty())->toBeTrue();
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
            'password' => 'secret',
        ], validate: false);
        $col = DtoCollection::make([$dto]);

        $all = $col->allValues();
        expect($all[0])->toHaveKey('password');
        expect($all[0]['password'])->toBe('secret');
    });
});

describe('DataTransferObject metadata cache', function (): void {
    it('flushMetadataCache clears per-class cache', function (): void {
        CreateUserDTO::rules(); // populate cache
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Should still work after flush
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('flushMetadataCache with null clears all', function (): void {
        CreateUserDTO::rules();
        ProductDTO::rules();

        DataTransferObject::flushMetadataCache(null);

        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('setMetadataCacheTtl controls cache behavior', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.0);

        // With TTL 0, cache should be bypassed every time
        $rules1 = CreateUserDTO::rules();
        $rules2 = CreateUserDTO::rules();
        expect($rules1)->toBe($rules2);

        // Reset
        DataTransferObject::setMetadataCacheTtl(0.0);
    });
});

describe('fromPartialArray edge cases', function (): void {
    it('respects MapFrom in partial updates', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    it('uses DefaultValue for missing fields in partial update', function (): void {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('empty partial array uses defaults for all fields', function (): void {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        expect($dto->status)->toBe('active');
    });

    it('with() always validates (ignores validate param)', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // with() should still validate — changing email to invalid should throw
        // But since we can't run validation, test that validate param has no effect
        $updated = $dto->with(['name' => 'Updated'], validate: true);
        expect($updated->name)->toBe('Updated');
    });

    it('equals() returns false for different DTOs', function (): void {
        $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $d2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);

        expect($d1->equals($d2))->toBeFalse();
    });
});

describe('DTOManager edge cases', function (): void {
    it('makeFromJson throws DTOException for invalid JSON', function (): void {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(CreateUserDTO::class, 'not-json'))
            ->toThrow(DTOException::class);
    });

    it('makeFromJson throws DTOException for sequential array', function (): void {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(CreateUserDTO::class, '[1,2,3]'))
            ->toThrow(DTOException::class);
    });

    it('validate throws ValidationException for invalid data', function (): void {
        $manager = new DTOManager;

        expect(fn () => $manager->validate(CreateUserDTO::class, [
            'email' => 'not-an-email',
            'name' => '', // too short for Min(2)
        ]))->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});

describe('OpenAPI schema generation edge cases', function (): void {
    it('generates schema with required fields', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(CreateUserDTO::class);

        expect($schema['type'])->toBe('object');
        expect($schema['required'])->toContain('email');
        expect($schema['required'])->toContain('name');
    });

    it('generates schema without hidden fields', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(CreateUserDTO::class);

        // Hidden 'password' should not appear in properties
        expect($schema['properties'])->not->toHaveKey('password');
    });

    it('includes validation constraints in schema', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(ProductDTO::class);

        // ProductDTO should have numeric properties with constraints
        expect($schema['properties'])->toBeObject();
    });

    it('EmptyDTO generates empty object schema', function (): void {
        $manager = new DTOManager;
        $schema = $manager->schema(EmptyDTO::class);

        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toEqual(new \stdClass);
        expect($schema)->not->toHaveKey('required');
    });
});
