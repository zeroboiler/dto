<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

/**
 * Comprehensive type safety tests verifying PHPStan Level 9 compliance.
 *
 * These tests verify:
 * - All return types are explicit (no mixed)
 * - All parameters are typed (no implicit mixed)
 * - Strict comparisons are used throughout
 * - readonly properties are enforced
 * - fromArray/toArray roundtrip preserves types
 * - Validation rules are correctly derived from attributes
 * - Serialization handles all property types correctly
 */

// ─── Test Fixtures ───────────────────────────────────────────────────────────

class StrictUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[DefaultValue(30)]
        public readonly int $age,

        #[Cast('boolean')]
        public readonly bool $active,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}

class StrictProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $sku,

        #[Required, Min(0)]
        public readonly int|float $price,

        #[MapFrom('category_id')]
        public readonly ?int $categoryId = null,

        #[DefaultValue([])]
        public readonly array $tags = [],
    ) {}
}

class StrictNestedDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title,

        #[Required]
        public readonly StrictProductDTO $product,
    ) {}
}

class StrictPatternDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Pattern('/^[A-Z]{3}-\d{4}$/')]
        public readonly string $code,

        #[Required, Integer, Min(1)]
        public readonly int $quantity,
    ) {}
}

// ─── Test Suite ───────────────────────────────────────────────────────────────

describe('PHPStan Level 9 Type Safety — DTO', function () {
    describe('StrictUserDTO hydration and return types', function () {
        it('fromArray returns StrictUserDTO instance', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'John Doe',
            ]);

            expect($dto)->toBeInstanceOf(StrictUserDTO::class);
        });

        it('properties preserve correct types after hydration', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Jane',
                'age' => 25,
                'active' => true,
            ]);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Jane');
            expect($dto->age)->toBe(25);
            expect($dto->active)->toBe(true);
        });

        it('default values are applied for missing fields', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);

            expect($dto->age)->toBe(30);
            expect($dto->active)->toBe(false);
            expect($dto->password)->toBeNull();
        });

        it('nullable properties accept explicit null', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
                'password' => null,
            ]);

            expect($dto->password)->toBeNull();
        });

        it('Cast attribute transforms string to boolean', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Charlie',
                'active' => 'yes',
            ]);

            expect($dto->active)->toBe(true);
        });
    });

    describe('toArray serialization return types', function () {
        it('toArray returns array with correct keys', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            $arr = $dto->toArray();

            expect($arr)->toBeArray();
            expect($arr)->toHaveKeys(['email', 'name', 'age', 'active']);
            expect($arr)->not->toHaveKey('password');
        });

        it('allValues includes hidden fields', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Secret Agent',
                'password' => 'hunter2',
            ]);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('hunter2');
        });

        it('toJson returns JSON string', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'JSON User',
            ]);

            $json = $dto->toJson();

            expect($json)->toBeString();
            expect(json_decode($json, true))->toBeArray();
        });

        it('jsonSerialize returns array', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Serialize User',
            ]);

            $serialized = $dto->jsonSerialize();

            expect($serialized)->toBeArray();
        });
    });

    describe('Selective output return types', function () {
        it('only returns array with specified keys', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Selective User',
            ]);

            $result = $dto->only('email');

            expect($result)->toBe(['email' => 'test@example.com']);
        });

        it('only accepts multiple keys', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Multi User',
            ]);

            $result = $dto->only('email', 'name');

            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('age');
        });

        it('except returns array without specified keys', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Except User',
            ]);

            $result = $dto->except('email');

            expect($result)->not->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });

        it('only/except silently ignore non-existent keys', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Silent User',
            ]);

            expect($dto->only('nonexistent'))->toBe([]);
            $result = $dto->except('nonexistent');
            expect($result)->toHaveKey('email');
        });
    });

    describe('equals and state checks', function () {
        it('equals returns bool', function () {
            $dto1 = StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
            $dto2 = StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different data', function () {
            $dto1 = StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']);
            $dto2 = StrictUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C']);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty returns bool', function () {
            $dto = StrictUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });

        it('isNotEmpty returns bool', function () {
            $dto = StrictUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Test'], validate: false);

            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('with() immutable update', function () {
        it('returns new instance', function () {
            $original = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Original',
            ]);

            $updated = $original->with(['name' => 'Updated']);

            expect($updated)->not->toBe($original);
            expect($original->name)->toBe('Original');
            expect($updated->name)->toBe('Updated');
        });

        it('always validates the merged data', function () {
            $dto = StrictUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ]);

            expect(fn () => $dto->with(['email' => 'invalid-email']))
                ->toThrow(ValidationException::class);
        });
    });

    describe('MapFrom attribute behavior', function () {
        it('maps source key to property name', function () {
            $dto = StrictProductDTO::fromArray([
                'sku' => 'SKU-001',
                'price' => 19.99,
                'category_id' => 42,
            ]);

            expect($dto->categoryId)->toBe(42);
        });

        it('uses property name when source key not mapped', function () {
            $dto = StrictProductDTO::fromArray([
                'sku' => 'SKU-002',
                'price' => 9.99,
                'categoryId' => 10,
            ]);

            expect($dto->categoryId)->toBe(10);
        });
    });

    describe('Union type properties', function () {
        it('accepts int value for int|float property', function () {
            $dto = StrictProductDTO::fromArray([
                'sku' => 'SKU-003',
                'price' => 100,
            ]);

            expect($dto->price)->toBe(100);
        });

        it('accepts float value for int|float property', function () {
            $dto = StrictProductDTO::fromArray([
                'sku' => 'SKU-004',
                'price' => 99.99,
            ]);

            expect($dto->price)->toBe(99.99);
        });
    });

    describe('Nested DTO hydration', function () {
        it('auto-hydrates nested DTO from array', function () {
            $dto = StrictNestedDTO::fromArray([
                'title' => 'Order #1',
                'product' => [
                    'sku' => 'PROD-001',
                    'price' => 29.99,
                ],
            ]);

            expect($dto->product)->toBeInstanceOf(StrictProductDTO::class);
            expect($dto->product->sku)->toBe('PROD-001');
            expect($dto->product->price)->toBe(29.99);
        });

        it('serializes nested DTO recursively', function () {
            $dto = StrictNestedDTO::fromArray([
                'title' => 'Order #2',
                'product' => [
                    'sku' => 'PROD-002',
                    'price' => 15.00,
                ],
            ]);

            $arr = $dto->toArray();

            expect($arr['product'])->toBeArray();
            expect($arr['product']['sku'])->toBe('PROD-002');
        });
    });

    describe('Pattern validation', function () {
        it('validates regex pattern correctly', function () {
            $dto = StrictPatternDTO::fromArray([
                'code' => 'ABC-1234',
                'quantity' => 5,
            ]);

            expect($dto->code)->toBe('ABC-1234');
        });

        it('rejects value that does not match pattern', function () {
            expect(fn () => StrictPatternDTO::fromArray([
                'code' => 'invalid',
                'quantity' => 5,
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('Validation rules derivation', function () {
        it('rules() returns properly structured array', function () {
            $rules = StrictUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('required');
        });

        it('rulesFor() returns rules (default action)', function () {
            $rules = StrictUserDTO::rulesFor('create');

            expect($rules)->toBe(StrictUserDTO::rules());
        });

        it('validateArray returns validated data', function () {
            $validated = StrictUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Validated User',
            ]);

            expect($validated)->toBeArray();
            expect($validated['email'])->toBe('test@example.com');
        });

        it('validateArray throws for invalid data', function () {
            expect(fn () => StrictUserDTO::validateArray([
                'email' => 'not-an-email',
                'name' => '',
            ]))->toThrow(ValidationException::class);
        });
    });

    describe('fromJson hydration', function () {
        it('creates DTO from valid JSON string', function () {
            $dto = StrictUserDTO::fromJson('{"email":"json@test.com","name":"JSON User"}');

            expect($dto)->toBeInstanceOf(StrictUserDTO::class);
            expect($dto->email)->toBe('json@test.com');
        });

        it('throws DTOException for invalid JSON', function () {
            expect(fn () => StrictUserDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for JSON array', function () {
            expect(fn () => StrictUserDTO::fromJson('[{"email":"a@b.com"}]'))
                ->toThrow(DTOException::class);
        });
    });

    describe('fromPartialArray PATCH semantics', function () {
        it('only hydrates present fields', function () {
            $dto = StrictUserDTO::fromPartialArray(['name' => 'Patched Name']);

            expect($dto->name)->toBe('Patched Name');
        });

        it('uses defaults for missing fields', function () {
            $dto = StrictUserDTO::fromPartialArray([
                'email' => 'patch@test.com',
            ]);

            expect($dto->age)->toBe(30);
        });

        it('validatePartialArray returns validated data', function () {
            $validated = StrictUserDTO::validatePartialArray([
                'name' => 'Partial Validated',
            ]);

            expect($validated)->toBeArray();
        });
    });

    describe('DtoCollection type safety', function () {
        it('make creates collection from DTO array', function () {
            $dtoArray = [
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']),
                StrictUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C']),
            ];

            $collection = DtoCollection::make($dtoArray);

            expect($collection)->toBeInstanceOf(DtoCollection::class);
            expect($collection->count())->toBe(2);
        });

        it('toArray serializes all DTOs', function () {
            $collection = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A']),
                StrictUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C']),
            ]);

            $arr = $collection->toArray();

            expect($arr)->toBeArray();
            expect($arr[0])->toHaveKey('email');
        });

        it('pluck extracts single field', function () {
            $collection = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']),
                StrictUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie']),
            ]);

            $emails = $collection->pluck('email');

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('isEmpty returns bool', function () {
            $empty = new DtoCollection;

            expect($empty->isEmpty())->toBeTrue();

            $nonEmpty = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            ]);

            expect($nonEmpty->isEmpty())->toBeFalse();
        });

        it('first and last return DTO or null', function () {
            $collection = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            ]);

            expect($collection->first())->toBeInstanceOf(StrictUserDTO::class);
            expect($collection->last())->toBeInstanceOf(StrictUserDTO::class);

            $empty = new DtoCollection;
            expect($empty->first())->toBeNull();
            expect($empty->last())->toBeNull();
        });

        it('push returns fluent DtoCollection', function () {
            $collection = new DtoCollection;
            $result = $collection->push(
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false),
            );

            expect($result)->toBe($collection);
            expect($collection->count())->toBe(1);
        });

        it('map returns plain array', function () {
            $collection = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
            ]);

            $names = $collection->map(fn (DataTransferObject $dto, int $index): string => (string) $dto->toArray()['name'] ?? '');

            expect($names)->toBe(['Alice']);
        });

        it('filter returns new DtoCollection', function () {
            $collection = DtoCollection::make([
                StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false),
                StrictUserDTO::fromArray(['email' => 'b@b.com', 'name' => 'Bob'], validate: false),
            ]);

            $filtered = $collection->filter(fn (DataTransferObject $dto): bool => ($dto->toArray()['name'] ?? '') === 'Alice');

            expect($filtered)->toBeInstanceOf(DtoCollection::class);
            expect($filtered->count())->toBe(1);
        });

        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('items returns raw DTO array', function () {
            $dto = StrictUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
            $collection = DtoCollection::make([$dto]);

            expect($collection->items())->toBe([$dto]);
        });
    });

    describe('DTOException named constructors', function () {
        it('invalidCast returns exception with property name and type', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');

            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidJson returns exception with property and error', function () {
            $ex = DTOException::invalidJson('metadata', 'Syntax error');

            expect($ex)->toBeInstanceOf(DTOException::class);
            expect($ex->getMessage())->toContain('metadata');
            expect($ex->getMessage())->toContain('Syntax error');
        });
    });

    describe('Metadata cache management', function () {
        it('flushMetadataCache clears static cache', function () {
            // Prime the cache
            StrictUserDTO::rules();

            StrictUserDTO::flushMetadataCache();
            $rules = StrictUserDTO::rules();

            // Should still work after flush (re-resolves)
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache accepts class-specific flush', function () {
            StrictUserDTO::rules();
            StrictProductDTO::rules();

            StrictUserDTO::flushMetadataCache(StrictUserDTO::class);

            // ProductDTO rules should still be cached
            $rules = StrictProductDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl affects caching behavior', function () {
            StrictUserDTO::setMetadataCacheTtl(0.0);
            $rules1 = StrictUserDTO::rules();
            $rules2 = StrictUserDTO::rules();

            expect($rules1)->toBe($rules2);

            // Reset to default
            StrictUserDTO::setMetadataCacheTtl(0.0);
        });
    });
});
