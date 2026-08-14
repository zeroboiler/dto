<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Tests for fromPartialArray() empty value inference and edge cases.
 *
 * Verifies that missing fields in partial updates receive correct type-appropriate
 * empty values (0 for int, '' for string, false for bool, [] for array, null for nullable).
 * Also tests validatePartialArray() with no data and explicit null/empty value handling.
 *
 * @covers \ZeroBoiler\DTO\DataTransferObject::fromPartialArray
 * @covers \ZeroBoiler\DTO\DataTransferObject::emptyValueForType
 * @covers \ZeroBoiler\DTO\DataTransferObject::validatePartialArray
 */
final class DtoPartialArrayEmptyValueTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Empty array partial — all fields get defaults or empty values
    // -----------------------------------------------------------------------

    public function test_partial_with_empty_array_uses_defaults(): void
    {
        // AllScalarTypesDTO has defaults for every field
        $dto = AllScalarTypesDTO::fromPartialArray([], validate: false);

        self::assertInstanceOf(AllScalarTypesDTO::class, $dto);
        // Default values should be applied
        self::assertSame(1, $dto->count);
        self::assertSame('', $dto->name);
        self::assertFalse($dto->active);
        self::assertSame(0.0, $dto->score);
        self::assertNull($dto->optional);
        self::assertSame('default-tag', $dto->tag);
        self::assertSame([], $dto->items);
        self::assertSame(0, $dto->castedInt);
    }

    // -----------------------------------------------------------------------
    // Partial with only one field — others get defaults
    // -----------------------------------------------------------------------

    public function test_partial_with_single_field_fills_rest_with_defaults(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['name' => 'Alice'],
            validate: false,
        );

        self::assertSame('Alice', $dto->name);
        // Other fields retain defaults
        self::assertSame(1, $dto->count);
        self::assertFalse($dto->active);
    }

    // -----------------------------------------------------------------------
    // Nullable fields get null as empty value
    // -----------------------------------------------------------------------

    public function test_partial_nullable_field_gets_null_when_missing(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray([], validate: false);

        self::assertNull($dto->optional);
        self::assertNull($dto->mapped);
    }

    // -----------------------------------------------------------------------
    // Explicit null should be respected (not replaced with default)
    // -----------------------------------------------------------------------

    public function test_partial_explicit_null_is_respected(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['tag' => null],
            validate: false,
        );

        // tag has a default 'default-tag' but explicit null should override it
        self::assertNull($dto->tag);
    }

    // -----------------------------------------------------------------------
    // Partial with all fields provided — behaves like fromArray
    // -----------------------------------------------------------------------

    public function test_partial_with_all_fields_hydrates_correctly(): void
    {
        $data = [
            'count' => 10,
            'name' => 'Test',
            'active' => true,
            'score' => 95.5,
            'optional' => 'present',
            'tag' => 'custom',
            'mapped' => 'value',
            'secret' => 'hidden-value',
            'items' => ['x', 'y'],
            'castedInt' => '42',
        ];

        $dto = AllScalarTypesDTO::fromPartialArray($data, validate: false);

        self::assertInstanceOf(AllScalarTypesDTO::class, $dto);
        self::assertSame(10, $dto->count);
        self::assertSame('Test', $dto->name);
        self::assertTrue($dto->active);
        self::assertSame(95.5, $dto->score);
        self::assertSame('present', $dto->optional);
        self::assertSame('custom', $dto->tag);
        self::assertSame('value', $dto->mapped);
        self::assertSame(['x', 'y'], $dto->items);
        self::assertSame(42, $dto->castedInt); // Cast('integer') applied
    }

    // -----------------------------------------------------------------------
    // validatePartialArray with no data returns data as-is
    // -----------------------------------------------------------------------

    public function test_validate_partial_array_with_empty_data_returns_empty(): void
    {
        $result = AllScalarTypesDTO::validatePartialArray([]);

        self::assertSame([], $result);
    }

    // -----------------------------------------------------------------------
    // Empty string is not treated as "missing"
    // -----------------------------------------------------------------------

    public function test_partial_empty_string_is_kept_as_value(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['name' => ''],
            validate: false,
        );

        self::assertSame('', $dto->name);
    }

    // -----------------------------------------------------------------------
    // Zero values are not treated as "empty"
    // -----------------------------------------------------------------------

    public function test_partial_zero_count_is_kept_as_value(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['count' => 0],
            validate: false,
        );

        self::assertSame(0, $dto->count);
    }

    public function test_partial_zero_score_is_kept_as_value(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['score' => 0.0],
            validate: false,
        );

        self::assertSame(0.0, $dto->score);
    }

    public function test_partial_false_bool_is_kept_as_value(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['active' => false],
            validate: false,
        );

        self::assertFalse($dto->active);
    }

    // -----------------------------------------------------------------------
    // MapFrom works in partial context
    // -----------------------------------------------------------------------

    public function test_partial_map_from_maps_source_key(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['source_field' => 'mapped-value'],
            validate: false,
        );

        self::assertSame('mapped-value', $dto->mapped);
    }

    // -----------------------------------------------------------------------
    // toArray / allValues consistency after partial
    // -----------------------------------------------------------------------

    public function test_partial_dto_to_array_includes_all_fields(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray([], validate: false);

        $array = $dto->toArray();

        self::assertIsArray($array);
        self::assertArrayHasKey('count', $array);
        self::assertArrayHasKey('name', $array);
        // Hidden fields excluded from toArray
        self::assertArrayNotHasKey('secret', $array);
    }

    public function test_partial_dto_all_values_includes_hidden_fields(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['secret' => 'my-secret'],
            validate: false,
        );

        $array = $dto->allValues();

        self::assertIsArray($array);
        // allValues includes hidden
        self::assertArrayHasKey('secret', $array);
        self::assertSame('my-secret', $array['secret']);
    }

    // -----------------------------------------------------------------------
    // Cast('integer') applied in partial context
    // -----------------------------------------------------------------------

    public function test_partial_cast_integer_applied(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['castedInt' => '100'],
            validate: false,
        );

        self::assertSame(100, $dto->castedInt);
    }

    // -----------------------------------------------------------------------
    // isEqual — two partial DTOs with same defaults are equal
    // -----------------------------------------------------------------------

    public function test_two_empty_partials_are_equal(): void
    {
        $dto1 = AllScalarTypesDTO::fromPartialArray([], validate: false);
        $dto2 = AllScalarTypesDTO::fromPartialArray([], validate: false);

        self::assertTrue($dto1->equals($dto2));
    }

    public function test_partial_with_override_not_equal_to_default(): void
    {
        $dto1 = AllScalarTypesDTO::fromPartialArray([], validate: false);
        $dto2 = AllScalarTypesDTO::fromPartialArray(['name' => 'Changed'], validate: false);

        self::assertFalse($dto1->equals($dto2));
    }

    // -----------------------------------------------------------------------
    // JSON roundtrip through partial
    // -----------------------------------------------------------------------

    public function test_partial_to_json_roundtrip(): void
    {
        $dto = AllScalarTypesDTO::fromPartialArray(
            ['name' => 'Roundtrip'],
            validate: false,
        );

        $json = $dto->toJson();
        $restored = AllScalarTypesDTO::fromJson($json, validate: false);

        self::assertTrue($dto->equals($restored));
    }
}
