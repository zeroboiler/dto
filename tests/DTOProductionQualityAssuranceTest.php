<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ─── DTO Fixtures ───────────────────────────────────────────────

class QaUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[MapFrom('display_name')]
        public readonly ?string $displayName = null,

        #[Cast('integer')]
        public readonly int $age = 0,

        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[Hidden]
        public readonly ?string $password = null,

        #[Nullable]
        public readonly ?string $bio = null,
    ) {}
}

class QaProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title,

        #[Cast('integer')]
        public readonly int $price = 0,

        #[StartsWith(['USD', 'EUR', 'GBP'])]
        public readonly string $currency = 'USD',

        #[Required]
        public readonly bool $isActive,

        #[DefaultValue([])]
        public readonly array $tags = [],
    ) {}
}

class QaNestedDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        #[DefaultValue('')]
        public readonly string $zip = '',
    ) {}
}

class QaOrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $customerEmail,

        #[Required]
        public readonly QaNestedDTO $address,

        #[DefaultValue([])]
        public readonly array $items = [],

        #[DefaultValue(0)]
        #[Cast('integer')]
        public readonly int $totalItems = 0,
    ) {}
}

// ─── Tests ──────────────────────────────────────────────────────

describe('DTO — Production Quality Assurance', function () {

    // ── DTOException::__toString ─────────────────────────────────

    describe('DTOException __toString()', function () {
        it('formats invalidCast as string', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
            $str = (string) $e;

            expect($str)->toBeString()
                ->toContain('DTOException')
                ->toContain('age')
                ->toContain('integer')
                ->toContain('not_a_number');
        });

        it('formats invalidJson as string', function () {
            $e = DTOException::invalidJson('payload', 'Syntax error');
            $str = (string) $e;

            expect($str)->toBeString()
                ->toContain('DTOException')
                ->toContain('payload')
                ->toContain('Syntax error');
        });
    });

    // ── fromArray with MapFrom ──────────────────────────────────

    describe('MapFrom dot notation and key aliasing', function () {
        it('maps aliased keys correctly', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'display_name' => 'Alice Display',
            ], validate: false);

            expect($dto->displayName)->toBe('Alice Display');
        });

        it('falls back to property name when map_from key is absent', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->displayName)->toBeNull();
        });

        it('respects explicit null over default for mapped keys', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'display_name' => null,
            ], validate: false);

            expect($dto->displayName)->toBeNull();
        });
    });

    // ── Cast types ────────────────────────────────────────────

    describe('Type casting during hydration', function () {
        it('casts string to integer', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => '25',
            ], validate: false);

            expect($dto->age)->toBe(25);
            expect($dto->age)->toBeInt();
        });

        it('defaults age to 0 when absent', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->age)->toBe(0);
        });

        it('casts numeric string to int for product price', function () {
            $dto = QaProductDTO::fromArray([
                'title' => 'Widget',
                'price' => '199',
                'isActive' => true,
            ], validate: false);

            expect($dto->price)->toBe(199);
            expect($dto->price)->toBeInt();
        });
    });

    // ── DefaultValue attribute ─────────────────────────────────

    describe('DefaultValue attribute behavior', function () {
        it('applies default when key is absent', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto->status)->toBe('active');
        });

        it('uses provided value over default when key is present', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'status' => 'inactive',
            ], validate: false);

            expect($dto->status)->toBe('inactive');
        });

        it('respects empty string over default', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'bio' => '',
            ], validate: false);

            expect($dto->bio)->toBe('');
        });
    });

    // ── Hidden attribute ──────────────────────────────────────

    describe('Hidden attribute', function () {
        it('excludes hidden fields from toArray()', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('password');
        });

        it('includes hidden fields in allValues()', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });
    });

    // ── Serialization ─────────────────────────────────────────

    describe('Serialization roundtrip', function () {
        it('toJson produces valid JSON', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();

            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
            expect($decoded['name'])->toBe('Alice');
            expect($decoded['age'])->toBe(25);
        });

        it('jsonSerialize excludes hidden fields', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            $serialized = $dto->jsonSerialize();
            expect($serialized)->not->toHaveKey('password');
        });
    });

    // ── equals() and state checks ────────────────────────────

    describe('Equality and state checks', function () {
        it('equals() returns true for same data', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals() returns false for different data', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Alice',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });

        it('isEmpty() detects all-default DTO', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 0,
                'status' => 'active',
            ], validate: false);

            // age=0 is considered non-empty (valid meaningful value)
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ── with() immutable update ───────────────────────────────

    describe('Immutable update via with()', function () {
        it('creates new instance with overrides', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice'); // original unchanged
        });
    });

    // ── only() and except() ───────────────────────────────────

    describe('Selective output', function () {
        it('only() returns specified fields', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $result = $dto->only('email', 'name');
            expect($result)->toHaveCount(2);
            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('age');
        });

        it('except() excludes specified fields', function () {
            $dto = QaUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'age' => 25,
            ], validate: false);

            $result = $dto->except('age');
            expect($result)->not->toHaveKey('age');
            expect($result)->toHaveKey('email');
        });
    });

    // ── fromPartialArray (PATCH semantics) ───────────────────

    describe('Partial update (PATCH semantics)', function () {
        it('hydrates only provided fields with defaults for rest', function () {
            $dto = QaUserDTO::fromPartialArray([
                'name' => 'Updated',
            ], validate: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active');
        });

        it('applies cast types in partial updates', function () {
            $dto = QaUserDTO::fromPartialArray([
                'age' => '30',
            ], validate: false);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });
    });

    // ── rules() and rulesFor() ─────────────────────────────────

    describe('Validation rules', function () {
        it('rules() returns expected structure', function () {
            $rules = QaUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = QaUserDTO::rules();
            $rulesForCreate = QaUserDTO::rulesFor('create');

            expect($rulesForCreate)->toBe($rules);
        });
    });

    // ── DtoCollection ─────────────────────────────────────────

    describe('DtoCollection behavior', function () {
        it('creates collection from DTO instances', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);

            expect($col->count())->toBe(2);
            expect($col->first())->toBe($dto1);
            expect($col->last())->toBe($dto2);
        });

        it('rejects non-DTO instances in constructor', function () {
            expect(fn () => new DtoCollection(['not a DTO']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('pluck() extracts field values', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@b.com', 'c@d.com']);
        });

        it('pluckKey() skips items with null key values', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'displayName' => null,
            ], validate: false);

            $col = DtoCollection::make([$dto1]);

            // displayName is null, so pluckKey should skip it
            $result = $col->pluckKey('displayName');
            expect($result)->toBe([]);
        });

        it('pluckKey() works with non-null keys', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'displayName' => 'AliceD',
            ], validate: false);

            $col = DtoCollection::make([$dto1]);

            $result = $col->pluckKey('displayName', 'name');
            expect($result)->toBe(['AliceD' => 'Alice']);
        });

        it('append() returns new collection', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col1 = DtoCollection::make([$dto1]);
            $col2 = $col1->append($dto2);

            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(2);
        });

        it('merge() combines two collections', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col1 = DtoCollection::make([$dto1]);
            $col2 = DtoCollection::make([$dto2]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
        });

        it('filter() returns new collection with matching items', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = DtoCollection::make([$dto1, $dto2]);
            $filtered = $col->filter(fn ($dto) => str_starts_with($dto->email, 'a'));

            expect($filtered->count())->toBe(1);
        });

        it('map() transforms items', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = DtoCollection::make([$dto1]);
            $names = $col->map(fn ($dto) => $dto->name);

            expect($names)->toBe(['Alice']);
        });

        it('jsonSerialize() returns array of arrays', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);

            $col = DtoCollection::make([$dto1]);
            $json = json_encode($col);

            expect($json)->toBeJson();
        });

        it('offsetUnset re-indexes collection', function () {
            $dto1 = QaUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = QaUserDTO::fromArray([
                'email' => 'c@d.com',
                'name' => 'Charlie',
            ], validate: false);
            $dto3 = QaUserDTO::fromArray([
                'email' => 'e@f.com',
                'name' => 'Eve',
            ], validate: false);

            $col = DtoCollection::make([$dto1, $dto2, $dto3]);
            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col[0]->name)->toBe('Charlie');
            expect($col[1]->name)->toBe('Eve');
        });
    });

    // ── Boolean casting edge cases ────────────────────────────

    describe('Boolean casting edge cases', function () {
        it('casts string "true" to true', function () {
            $dto = QaProductDTO::fromArray([
                'title' => 'Widget',
                'isActive' => 'true',
            ], validate: false);

            expect($dto->isActive)->toBeTrue();
        });

        it('casts string "false" to false', function () {
            $dto = QaProductDTO::fromArray([
                'title' => 'Widget',
                'isActive' => 'false',
            ], validate: false);

            expect($dto->isActive)->toBeFalse();
        });

        it('casts integer 0 to false', function () {
            $dto = QaProductDTO::fromArray([
                'title' => 'Widget',
                'isActive' => 0,
            ], validate: false);

            expect($dto->isActive)->toBeFalse();
        });

        it('casts integer 1 to true', function () {
            $dto = QaProductDTO::fromArray([
                'title' => 'Widget',
                'isActive' => 1,
            ], validate: false);

            expect($dto->isActive)->toBeTrue();
        });
    });

    // ── Nested DTO hydration ─────────────────────────────────

    describe('Nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = QaOrderDTO::fromArray([
                'customerEmail' => 'test@example.com',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'zip' => '62701',
                ],
                'totalItems' => '5',
            ], validate: false);

            expect($dto->address)->toBeInstanceOf(QaNestedDTO::class);
            expect($dto->address->city)->toBe('Springfield');
            expect($dto->totalItems)->toBe(5);
        });

        it('serializes nested DTO recursively', function () {
            $dto = QaOrderDTO::fromArray([
                'customerEmail' => 'test@example.com',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['address'])->toBeArray();
            expect($arr['address']['city'])->toBe('Springfield');
        });
    });

    // ── Cache flush ──────────────────────────────────────────

    describe('Metadata cache management', function () {
        it('flushMetadataCache clears all cached metadata', function () {
            // Populate cache
            QaUserDTO::rules();

            // Flush
            DataTransferObject::flushMetadataCache();

            // Should still work after flush
            $rules = QaUserDTO::rules();
            expect($rules)->toBeArray();
        });

        it('flushMetadataCache for specific class only', function () {
            QaUserDTO::rules();
            QaProductDTO::rules();

            DataTransferObject::flushMetadataCache(QaUserDTO::class);

            // Product DTO rules should still be cached
            $rules = QaProductDTO::rules();
            expect($rules)->toBeArray();
        });
    });
});
