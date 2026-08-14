<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

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
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;

/**
 * Edge-case DTO fixture: Tests MapFrom with dot-notation nested keys.
 */
final class DotNotationMapFromDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user.profile.name')]
        public readonly ?string $name = null,

        #[MapFrom('user.profile.email')]
        public readonly ?string $email = null,

        #[MapFrom('settings.preferences.theme')]
        #[DefaultValue('light')]
        public readonly string $theme = 'light',
    ) {}
}

/**
 * Edge-case DTO fixture: Tests boolean casting edge cases.
 */
final class BoolCastEdgeCaseDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('boolean')]
        public readonly bool $active = false,

        #[Cast('boolean')]
        public readonly bool $deleted = false,

        #[Nullable]
        #[Cast('boolean')]
        public readonly ?bool $flag = null,
    ) {}
}

beforeEach(function () {
    DataTransferObject::flushMetadataCache();
});

afterEach(function () {
    DataTransferObject::flushMetadataCache();
});

describe('Dot-notation MapFrom', function () {
    it('hydrates from nested array using dot-notation keys', function () {
        $dto = DotNotationMapFromDTO::fromArray([
            'user' => [
                'profile' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ],
            'settings' => [
                'preferences' => [
                    'theme' => 'dark',
                ],
            ],
        ], validate: false);

        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('alice@example.com');
        expect($dto->theme)->toBe('dark');
    });

    it('applies default when dot-notation key is absent', function () {
        $dto = DotNotationMapFromDTO::fromArray([], validate: false);

        expect($dto->name)->toBeNull();
        expect($dto->email)->toBeNull();
        expect($dto->theme)->toBe('light'); // default
    });

    it('serializes dot-mapped properties correctly', function () {
        $dto = DotNotationMapFromDTO::fromArray([
            'user' => [
                'profile' => [
                    'name' => 'Bob',
                    'email' => 'bob@test.com',
                ],
            ],
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->toHaveKey('name');
        expect($array['name'])->toBe('Bob');
        expect($array['email'])->toBe('bob@test.com');
    });
});

describe('Boolean casting edge cases', function () {
    it('casts string "true" to true', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'active' => 'true',
        ], validate: false);

        expect($dto->active)->toBeTrue();
    });

    it('casts string "false" to false', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'active' => 'false',
        ], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('casts string "1" to true', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'active' => '1',
        ], validate: false);

        expect($dto->active)->toBeTrue();
    });

    it('casts string "0" to false', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'active' => '0',
        ], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('casts empty string to false', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'active' => '',
        ], validate: false);

        expect($dto->active)->toBeFalse();
    });

    it('casts integer 0 to false', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'deleted' => 0,
        ], validate: false);

        expect($dto->deleted)->toBeFalse();
    });

    it('casts integer 1 to true', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'deleted' => 1,
        ], validate: false);

        expect($dto->deleted)->toBeTrue();
    });

    it('preserves null for nullable boolean property', function () {
        $dto = BoolCastEdgeCaseDTO::fromArray([
            'flag' => null,
        ], validate: false);

        expect($dto->flag)->toBeNull();
    });
});

describe('fromPartialArray edge cases', function () {
    it('returns DTO with all defaults when given empty array', function () {
        $dto = AllDefaultsDTO::fromPartialArray([], validate: false);

        expect($dto->name)->toBe('default-name');
        expect($dto->count)->toBe(0);
        expect($dto->active)->toBeFalse();
        expect($dto->items)->toBe([]);
    });

    it('overwrites defaults with provided partial data', function () {
        $dto = AllDefaultsDTO::fromPartialArray([
            'name' => 'custom-name',
        ], validate: false);

        expect($dto->name)->toBe('custom-name');
        expect($dto->count)->toBe(0); // default preserved
    });

    it('handles MapFrom in partial updates', function () {
        $dto = AllScalarTypesDTO::fromPartialArray([
            'source_field' => 'mapped-value',
        ], validate: false);

        expect($dto->mapped)->toBe('mapped-value');
    });

    it('returns DTO with only provided fields changed', function () {
        $original = AllDefaultsDTO::fromArray([
            'name' => 'original',
            'count' => 5,
        ], validate: false);

        $partial = AllDefaultsDTO::fromPartialArray([
            'name' => 'updated',
        ], validate: false);

        expect($partial->name)->toBe('updated');
        expect($partial->count)->toBe(0); // default, not from original
    });
});

describe('DtoCollection push chain and immutability', function () {
    it('push returns self for method chaining', function () {
        $collection = DtoCollection::make();

        $result = $collection->push(
            AllDefaultsDTO::fromArray(['name' => 'a'], validate: false),
        );

        expect($result)->toBe($collection); // same instance
        expect($collection->count())->toBe(1);
    });

    it('append returns a new collection without mutating original', function () {
        $original = DtoCollection::make([
            AllDefaultsDTO::fromArray(['name' => 'a'], validate: false),
        ]);

        $appended = $original->append(
            AllDefaultsDTO::fromArray(['name' => 'b'], validate: false),
        );

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('filter returns a new collection without mutating original', function () {
        $dto1 = AllDefaultsDTO::fromArray(['name' => 'keep'], validate: false);
        $dto2 = AllDefaultsDTO::fromArray(['name' => 'remove'], validate: false);

        $original = DtoCollection::make([$dto1, $dto2]);

        $filtered = $original->filter(
            fn (DataTransferObject $dto) => $dto->name === 'keep',
        );

        expect($original->count())->toBe(2);
        expect($filtered->count())->toBe(1);
    });

    it('merge returns a new collection with combined items', function () {
        $col1 = DtoCollection::make([
            AllDefaultsDTO::fromArray(['name' => 'a'], validate: false),
        ]);
        $col2 = DtoCollection::make([
            AllDefaultsDTO::fromArray(['name' => 'b'], validate: false),
        ]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
    });
});
