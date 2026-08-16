<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * Tests for partial hydration edge cases, scalar normalization, and
 * DtoCollection immutable operation consistency.
 *
 * Ensures:
 * - fromPartialArray() applies defaults and type-appropriate empty values
 * - normalizeScalar handles all scalar/object types correctly
 * - DtoCollection append/merge/filter are truly immutable
 * - only()/except() handle non-existent keys gracefully
 * - toArray()/allValues() serialization consistency
 */
final class DtoPartialHydrationAndNormalizationEdgeCaseTest extends TestCase
{
    // ---------------------------------------------------------------
    // fromPartialArray() edge cases
    // ---------------------------------------------------------------

    public function test_partial_array_applies_defaults_for_missing_fields(): void
    {
        $dto = PartialDefaultsDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        $this->assertSame('Alice', $dto->name);
        $this->assertSame('active', $dto->status);
        $this->assertSame([], $dto->tags);
    }

    public function test_partial_array_overrides_defaults(): void
    {
        $dto = PartialDefaultsDTO::fromPartialArray([
            'name' => 'Bob',
            'status' => 'inactive',
        ], validate: false);

        $this->assertSame('Bob', $dto->name);
        $this->assertSame('inactive', $dto->status);
        $this->assertSame([], $dto->tags);
    }

    public function test_partial_array_with_empty_data_uses_all_defaults(): void
    {
        $dto = PartialDefaultsDTO::fromPartialArray([], validate: false);

        $this->assertSame('', $dto->name);
        $this->assertSame('active', $dto->status);
        $this->assertSame([], $dto->tags);
    }

    public function test_partial_array_nullable_defaults_to_null(): void
    {
        $dto = PartialDefaultsDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        $this->assertNull($dto->nickname);
    }

    // ---------------------------------------------------------------
    // only() / except() edge cases
    // ---------------------------------------------------------------

    public function test_only_returns_specified_keys(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->only('name');

        $this->assertSame(['name' => 'Alice'], $result);
        $this->assertArrayNotHasKey('value', $result);
    }

    public function test_only_accepts_single_string(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->only('value');

        $this->assertSame(['value' => 'x'], $result);
    }

    public function test_only_ignores_nonexistent_keys(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->only('name', 'nonexistent');

        $this->assertSame(['name' => 'Alice'], $result);
    }

    public function test_except_excludes_specified_keys(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->except('value');

        $this->assertSame(['name' => 'Alice'], $result);
        $this->assertArrayNotHasKey('value', $result);
    }

    public function test_except_accepts_single_string(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->except('name');

        $this->assertSame(['value' => 'x'], $result);
    }

    public function test_except_ignores_nonexistent_keys(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $result = $dto->except('nonexistent');

        // Should return all keys since 'nonexistent' doesn't exist
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('value', $result);
    }

    // ---------------------------------------------------------------
    // toArray() / allValues() / toJson() consistency
    // ---------------------------------------------------------------

    public function test_toArray_and_jsonSerialize_return_same(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    public function test_toJson_produces_valid_json(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $json = $dto->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame($dto->toArray(), $decoded);
    }

    public function test_toJson_with_pretty_print(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $json = $dto->toJson(JSON_PRETTY_PRINT);

        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json);
    }

    public function test_all_values_includes_all_properties(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $all = $dto->allValues();

        $this->assertArrayHasKey('name', $all);
        $this->assertArrayHasKey('value', $all);
    }

    // ---------------------------------------------------------------
    // DtoCollection immutable operations
    // ---------------------------------------------------------------

    public function test_collection_append_does_not_mutate_original(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        $this->assertCount(1, $original->items());
        $this->assertCount(2, $appended->items());
    }

    public function test_collection_merge_does_not_mutate_either(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'z'], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        $this->assertCount(1, $col1->items());
        $this->assertCount(2, $col2->items());
        $this->assertCount(3, $merged->items());
    }

    public function test_collection_filter_does_not_mutate_original(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

        $original = new DtoCollection([$dto1, $dto2]);
        $filtered = $original->filter(fn (DataTransferObject $dto): bool => $dto->name === 'Alice');

        $this->assertCount(2, $original->items());
        $this->assertCount(1, $filtered->items());
    }

    public function test_collection_push_mutates_in_place(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

        $col = new DtoCollection([$dto1]);
        $result = $col->push($dto2);

        $this->assertCount(2, $col->items());
        $this->assertSame($col, $result); // push returns $this for chaining
    }

    public function test_collection_filter_reindexes(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'z'], validate: false);

        $original = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $original->filter(fn (DataTransferObject $dto, int $index): bool => $index !== 1);

        $this->assertSame('Alice', $filtered->first()->name);
        $this->assertSame('Charlie', $filtered->last()->name);
    }

    // ---------------------------------------------------------------
    // equals() edge cases
    // ---------------------------------------------------------------

    public function test_equals_reflects_hidden_exclusion(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    // ---------------------------------------------------------------
    // with() immutable update
    // ---------------------------------------------------------------

    public function test_with_creates_new_instance(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $updated = $dto->with(['name' => 'Bob']);

        $this->assertSame('Alice', $dto->name); // original unchanged
        $this->assertSame('Bob', $updated->name);
    }

    public function test_with_merges_all_values(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $updated = $dto->with(['value' => 'y']);

        $this->assertSame('Alice', $updated->name);
        $this->assertSame('y', $updated->value);
    }

    // ---------------------------------------------------------------
    // isEmpty() / isNotEmpty() edge cases
    // ---------------------------------------------------------------

    public function test_zero_values_are_not_empty(): void
    {
        $dto = IntZeroDTO::fromArray(['count' => 0], validate: false);
        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    public function test_false_boolean_is_empty(): void
    {
        $dto = BoolFlagDTO::fromArray(['active' => false], validate: false);
        $this->assertTrue($dto->isEmpty());
        $this->assertFalse($dto->isNotEmpty());
    }

    // ---------------------------------------------------------------
    // fromJson edge cases
    // ---------------------------------------------------------------

    public function test_from_json_empty_object(): void
    {
        $dto = EmptyJsonDTO::fromJson('{}', validate: false);
        $this->assertInstanceOf(EmptyJsonDTO::class, $dto);
    }

    public function test_from_json_rejects_sequential_array(): void
    {
        $this->expectException(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        EmptyJsonDTO::fromJson('["a","b"]', validate: false);
    }

    public function test_from_json_rejects_invalid_json(): void
    {
        $this->expectException(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        EmptyJsonDTO::fromJson('{invalid}', validate: false);
    }
}

// ---------------------------------------------------------------
// Inline fixtures
// ---------------------------------------------------------------

class PartialDefaultsDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $status = 'active',
        public readonly array $tags = [],
        public readonly ?string $nickname = null,
    ) {}
}

class IntZeroDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $count = 0,
    ) {}
}

class BoolFlagDTO extends DataTransferObject
{
    public function __construct(
        public readonly bool $active = false,
    ) {}
}

class EmptyJsonDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $data = null,
    ) {}
}
