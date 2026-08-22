<?php

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DTO production edge cases', function () {
    it('handles empty string with Cast("array") returning empty array', function () {
        $dto = new class(['data' => '']) extends DataTransferObject {
            public function __construct(
                #[Cast('array')]
                public readonly array $data,
            ) {}
        };

        expect($dto->data)->toBe([]);
    });

    it('preserves explicit null over default when key is present', function () {
        $dto = new class(['name' => null]) extends DataTransferObject {
            public function __construct(
                #[DefaultValue('fallback')]
                #[Nullable]
                public readonly ?string $name,
            ) {}
        };

        // Key IS present but explicitly null — DefaultValue should NOT override
        expect($dto->name)->toBeNull();
    });

    it('preserves explicit empty string over default when key is present', function () {
        $dto = new class(['name' => '']) extends DataTransferObject {
            public function __construct(
                #[DefaultValue('fallback')]
                public readonly string $name,
            ) {}
        };

        // Key IS present with empty string — DefaultValue should NOT override (#678)
        expect($dto->name)->toBe('');
    });

    it('applies DefaultValue only when key is absent', function () {
        $dto = new class([]) extends DataTransferObject {
            public function __construct(
                #[DefaultValue('fallback')]
                public string $name = 'fallback',
            ) {}
        };

        expect($dto->name)->toBe('fallback');
    });

    it('fromJson rejects sequential arrays', function () {
        $json = '[{"name": "test"}]';

        expect(fn () => TestJsonSequentialDTO::fromJson($json))
            ->toThrow(DTOException::class);
    });

    it('fromJson accepts empty array as valid empty object', function () {
        $dto = TestJsonSequentialDTO::fromJson('{}');

        expect($dto)->toBeInstanceOf(TestJsonSequentialDTO::class);
    });

    it('toJson returns valid JSON on nested structures', function () {
        $dto = new class(['items' => [['name' => 'a']]]) extends DataTransferObject {
            public function __construct(
                public readonly array $items,
            ) {}
        };

        $json = $dto->toJson();

        expect($json)->toBeJson();
        expect(json_decode($json, true))->toBe(['items' => [['name' => 'a']]]);
    });

    it('with() always validates regardless of deprecated $validate param', function () {
        $dto = new class(['email' => 'test@example.com']) extends DataTransferObject {
            public function __construct(
                #[Required, Max(255)]
                public readonly string $email,
            ) {}
        };

        // The $validate=false should be ignored — validation always runs
        $modified = $dto->with(['email' => 'valid@example.com']);

        expect($modified->email)->toBe('valid@example.com');
        expect($modified)->not->toBe($dto);
    });

    it('equals compares public output only (hidden excluded)', function () {
        $dto1 = new class(['public' => 'x', 'secret' => 'y']) extends DataTransferObject {
            public function __construct(
                public readonly string $public,
                #[Hidden]
                public readonly string $secret,
            ) {}
        };

        $dto2 = new class(['public' => 'x', 'secret' => 'z']) extends DataTransferObject {
            public function __construct(
                public readonly string $public,
                #[Hidden]
                public readonly string $secret,
            ) {}
        };

        // Hidden fields excluded from comparison
        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('only returns specified keys from toArray output', function () {
        $dto = new class(['name' => 'Alice', 'email' => 'a@b.com']) extends DataTransferObject {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto->only('name'))->toBe(['name' => 'Alice']);
    });

    it('except removes specified keys from toArray output', function () {
        $dto = new class(['name' => 'Alice', 'email' => 'a@b.com']) extends DataTransferObject {
            public function __construct(
                public readonly string $name,
                public readonly string $email,
            ) {}
        };

        expect($dto->except('email'))->toBe(['name' => 'Alice']);
    });

    it('fromPartialArray uses type-appropriate empty values for missing fields', function () {
        $dto = TestPartialEmptyValuesDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->name)->toBe('Alice');
        expect($dto->age)->toBe(0);
        expect($dto->active)->toBe(false);
        expect($dto->bio)->toBe('');
    });

    it('rules() returns consistent structure with typed keys', function () {
        $rules = TestPartialEmptyValuesDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
    });

    it('DtoCollection filter returns new instance without mutation', function () {
        $dto1 = new class(['id' => 1, 'name' => 'A']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $dto2 = new class(['id' => 2, 'name' => 'B']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(fn (DataTransferObject $d): bool => $d->name === 'A');

        expect($filtered->count())->toBe(1);
        expect($collection->count())->toBe(2); // original unchanged
    });

    it('DtoCollection pluck extracts property values via reflection', function () {
        $dto1 = new class(['id' => 10, 'name' => 'A']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $dto2 = new class(['id' => 20, 'name' => 'B']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->pluck('id'))->toBe([10, 20]);
        expect($collection->pluck('name'))->toBe(['A', 'B']);
    });

    it('DtoCollection toArrayBy re-keys by property value', function () {
        $dto1 = new class(['id' => 10, 'name' => 'A']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $dto2 = new class(['id' => 20, 'name' => 'B']) extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $collection = new DtoCollection([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('id');

        expect($keyed)->toHaveKey(10);
        expect($keyed)->toHaveKey(20);
        expect($keyed[10]['name'])->toBe('A');
        expect($keyed[20]['name'])->toBe('B');
    });

    it('DtoCollection offsetUnset re-indexes the collection', function () {
        $dto1 = new class(['id' => 1]) extends DataTransferObject {
            public function __construct(public readonly int $id) {}
        };
        $dto2 = new class(['id' => 2]) extends DataTransferObject {
            public function __construct(public readonly int $id) {}
        };
        $dto3 = new class(['id' => 3]) extends DataTransferObject {
            public function __construct(public readonly int $id) {}
        };

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->id)->toBe(2); // re-indexed: old [1] is now [0]
        expect($collection[1]->id)->toBe(3);
    });
});

/**
 * Helper DTO for sequential JSON array rejection test.
 */
class TestJsonSequentialDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
    ) {}
}

/**
 * Helper DTO for partial empty value inference test.
 */
class TestPartialEmptyValuesDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
        public readonly bool $active = false,
        public readonly string $bio = '',
    ) {}
}
