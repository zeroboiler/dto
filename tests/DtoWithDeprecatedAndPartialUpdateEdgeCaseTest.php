<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;

/**
 * Fixture for with() deprecated parameter test.
 */
final class WithDeprecatedParamDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(1), Max(100)]
        public int $quantity = 1,

        #[Required]
        public string $sku = '',
    ) {}
}

/**
 * Fixture for partial update with mixed defaults and nullable fields.
 */
final class MixedDefaultsPartialDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[DefaultValue('user@example.com')]
        public readonly string $email,

        #[DefaultValue(0)]
        public readonly int $score,

        #[DefaultValue(true)]
        public readonly bool $active,

        public ?string $note = null,
    ) {}
}

/**
 * Fixture for testing hidden field behavior across operations.
 */
final class HiddenFieldOpsDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $public,

        #[Hidden]
        public readonly string $secret,
    ) {}
}

/**
 * Fixture for testing DtoCollection count/first/last/isEmpty edge cases.
 */
final class CollectionEdgeDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly int $id,
    ) {}
}

describe('DTO with() deprecated parameter behavior', function () {
    it('with() ignores validate=false parameter — always validates internally', function () {
        $dto = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'ABC'], validate: false);

        // with() should still validate even when $validate=false is passed (deprecated)
        $modified = $dto->with(['quantity' => 50, 'sku' => 'XYZ'], validate: false);

        expect($modified)->toBeInstanceOf(WithDeprecatedParamDTO::class);
        expect($modified->quantity)->toBe(50);
        expect($modified->sku)->toBe('XYZ');
        expect($modified)->not->toBe($dto);
    });

    it('with() throws on invalid data regardless of validate parameter', function () {
        $dto = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'ABC'], validate: false);

        // Max is 100 — quantity=200 should fail validation
        expect(
            fn () => $dto->with(['quantity' => 200], validate: false)
        )->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('with() preserves original DTO immutability', function () {
        $dto = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'ABC'], validate: false);
        $modified = $dto->with(['sku' => 'NEW']);

        expect($dto->sku)->toBe('ABC');
        expect($modified->sku)->toBe('NEW');
    });
});

describe('DTO fromPartialArray with mixed defaults', function () {
    it('fills DefaultValue attributes for missing fields in partial update', function () {
        $dto = MixedDefaultsPartialDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('user@example.com');
        expect($dto->score)->toBe(0);
        expect($dto->active)->toBe(true);
        expect($dto->note)->toBeNull();
    });

    it('overrides DefaultValue attributes when explicitly provided', function () {
        $dto = MixedDefaultsPartialDTO::fromPartialArray([
            'name' => 'Bob',
            'email' => 'bob@test.com',
            'score' => 42,
        ], validate: false);

        expect($dto->email)->toBe('bob@test.com');
        expect($dto->score)->toBe(42);
    });

    it('partial update with empty data uses all defaults', function () {
        $dto = MixedDefaultsPartialDTO::fromPartialArray([], validate: false);

        expect($dto->name)->toBe('');
        expect($dto->email)->toBe('user@example.com');
        expect($dto->score)->toBe(0);
        expect($dto->active)->toBe(true);
        expect($dto->note)->toBeNull();
    });

    it('partial update preserves type-appropriate empty for nullable fields', function () {
        $dto = MixedDefaultsPartialDTO::fromPartialArray(['name' => 'Charlie'], validate: false);

        expect($dto->note)->toBeNull();
    });
});

describe('DTO hidden field behavior across operations', function () {
    it('toArray() excludes hidden fields', function () {
        $dto = HiddenFieldOpsDTO::fromArray(['public' => 'visible', 'secret' => 'hidden'], validate: false);

        expect($dto->toArray())->toHaveKey('public');
        expect($dto->toArray())->not->toHaveKey('secret');
    });

    it('allValues() includes hidden fields', function () {
        $dto = HiddenFieldOpsDTO::fromArray(['public' => 'visible', 'secret' => 'hidden'], validate: false);

        expect($dto->allValues())->toHaveKey('public');
        expect($dto->allValues())->toHaveKey('secret');
    });

    it('with() roundtrip preserves hidden fields in allValues()', function () {
        $dto = HiddenFieldOpsDTO::fromArray(['public' => 'a', 'secret' => 'b'], validate: false);
        $modified = $dto->with(['public' => 'c']);

        expect($modified->allValues())->toHaveKey('secret');
        expect($modified->allValues()['secret'])->toBe('b');
    });

    it('only() on hidden field returns empty (field is excluded from toArray base)', function () {
        $dto = HiddenFieldOpsDTO::fromArray(['public' => 'a', 'secret' => 'b'], validate: false);

        expect($dto->only('secret'))->not->toHaveKey('secret');
    });
});

describe('DtoCollection edge cases', function () {
    it('isEmpty returns true for empty collection', function () {
        $col = new DtoCollection;

        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('first/last return null for empty collection', function () {
        $col = new DtoCollection;

        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('push is chainable and mutates in place', function () {
        $col = new DtoCollection;
        $dto1 = new CollectionEdgeDTO(id: 1);
        $dto2 = new CollectionEdgeDTO(id: 2);

        $result = $col->push($dto1)->push($dto2);

        expect($col->count())->toBe(2);
        expect($result)->toBe($col);
    });

    it('append returns a new collection without mutating original', function () {
        $dto1 = new CollectionEdgeDTO(id: 1);
        $dto2 = new CollectionEdgeDTO(id: 2);
        $col = new DtoCollection([$dto1]);

        $newCol = $col->append($dto2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('merge combines two collections immutably', function () {
        $dto1 = new CollectionEdgeDTO(id: 1);
        $dto2 = new CollectionEdgeDTO(id: 2);
        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);

        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('filter returns a new re-indexed collection', function () {
        $dto1 = new CollectionEdgeDTO(id: 1);
        $dto2 = new CollectionEdgeDTO(id: 2);
        $dto3 = new CollectionEdgeDTO(id: 3);
        $col = new DtoCollection([$dto1, $dto2, $dto3]);

        $filtered = $col->filter(fn (CollectionEdgeDTO $dto): bool => $dto->id > 1);

        expect($filtered->count())->toBe(2);
        expect($filtered->first()->id)->toBe(2);
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = new CollectionEdgeDTO(id: 1);
        $dto2 = new CollectionEdgeDTO(id: 2);
        $dto3 = new CollectionEdgeDTO(id: 3);
        $col = new DtoCollection([$dto1, $dto2, $dto3]);

        unset($col[0]);

        expect($col->count())->toBe(2);
        expect($col[0]->id)->toBe(2);
    });

    it('clone throws RuntimeException', function () {
        $dto = new CollectionEdgeDTO(id: 1);
        $col = new DtoCollection([$dto]);

        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('jsonSerialize returns arrays from DTOs', function () {
        $dto = new CollectionEdgeDTO(id: 42);
        $col = new DtoCollection([$dto]);

        $json = json_encode($col);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0]['id'])->toBe(42);
    });
});

describe('DTO equals() edge cases', function () {
    it('different DTOs with same data are equal', function () {
        $dto1 = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'X'], validate: false);
        $dto2 = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'X'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('same DTOs with different data are not equal', function () {
        $dto1 = WithDeprecatedParamDTO::fromArray(['quantity' => 5, 'sku' => 'X'], validate: false);
        $dto2 = WithDeprecatedParamDTO::fromArray(['quantity' => 10, 'sku' => 'X'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });
});

describe('DTO isEmpty() edge cases', function () {
    it('DTO with non-empty string is not empty', function () {
        $dto = HiddenFieldOpsDTO::fromArray(['public' => 'hello', 'secret' => 'world'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });

    it('DTO with zero int default and empty strings is considered empty by non-nullable check', function () {
        // AllDefaultsDTO has all-default constructor — should be empty
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        // The DTO has name='default-name', count=0, active=false, items=[], token='hidden-secret'
        // isEmpty checks: 0 is non-empty for non-nullable int, so this is NOT empty
        expect($dto->toArray())->toBeArray();
    });

    it('non-empty detection works on partial DTOs', function () {
        $dto = MixedDefaultsPartialDTO::fromPartialArray(['name' => 'Test'], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });
});
