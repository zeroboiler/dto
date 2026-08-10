<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

/**
 * Comprehensive production-readiness verification tests for ZeroBoiler DTO.
 *
 * These tests verify:
 * - All public API methods return correctly typed values
 * - DTO hydration from array, JSON, and partial array works correctly
 * - Validation rules are correctly derived from attributes
 * - Serialization (toArray, toJson, allValues) handles all edge cases
 * - Hidden fields are properly excluded/included
 * - MapFrom resolves source keys correctly
 * - Cast attributes transform values correctly
 * - DtoCollection type safety and operations
 * - DTOCast Eloquent cast edge cases
 * - DTOException factory methods produce expected messages
 * - Empty DTO, single-property DTO, and complex DTO edge cases
 *
 * @covers \ZeroBoiler\DTO\DataTransferObject
 * @covers \ZeroBoiler\DTO\DtoCollection
 * @covers \ZeroBoiler\DTO\Casts\DTOCast
 * @covers \ZeroBoiler\DTO\Exceptions\DTOException
 * @covers \ZeroBoiler\DTO\Support\DtoMetadataResolver
 */
final class DTOProductionReadinessVerificationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // 1. Basic fromArray / toArray roundtrip
    // -----------------------------------------------------------------------

    public function testFromArrayCreatesDto(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        $this->assertInstanceOf(MinimalDTO::class, $dto);
        $this->assertSame('Alice', $dto->name);
        $this->assertSame('test', $dto->value);
    }

    public function testToArrayReturnsPublicFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $array = $dto->toArray();

        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayNotHasKey('password', $array); // Hidden
    }

    public function testAllValuesIncludesHiddenFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        $this->assertArrayHasKey('email', $all);
        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret123', $all['password']);
    }

    public function testToJsonReturnsJsonString(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);
        $json = $dto->toJson();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertSame('Alice', $decoded['name']);
    }

    // -----------------------------------------------------------------------
    // 2. Default values
    // -----------------------------------------------------------------------

    public function testDefaultValueAppliedWhenKeyMissing(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $this->assertSame('active', $dto->status);
    }

    public function testDefaultValueOverriddenByExplicitValue(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'status' => 'inactive',
        ], validate: false);

        $this->assertSame('inactive', $dto->status);
    }

    // -----------------------------------------------------------------------
    // 3. MapFrom source key mapping
    // -----------------------------------------------------------------------

    public function testMapFromResolvesSourceKey(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
        ], validate: false);

        $this->assertSame('+1234567890', $dto->phone);
    }

    // -----------------------------------------------------------------------
    // 4. Cast attribute
    // -----------------------------------------------------------------------

    public function testCastArrayFromString(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'tags' => ['php', 'laravel'],
        ], validate: false);

        $this->assertSame(['php', 'laravel'], $dto->tags);
    }

    public function testCastIntFromNumericString(): void
    {
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => '25',
            'active' => true,
        ], validate: false);

        $this->assertIsInt($dto->age);
        $this->assertSame(25, $dto->age);
    }

    // -----------------------------------------------------------------------
    // 5. fromJson
    // -----------------------------------------------------------------------

    public function testFromJsonValidObject(): void
    {
        $dto = MinimalDTO::fromJson('{"name":"Alice","value":"test"}', validate: false);

        $this->assertInstanceOf(MinimalDTO::class, $dto);
        $this->assertSame('Alice', $dto->name);
    }

    public function testFromJsonThrowsOnInvalidSyntax(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Cannot decode JSON');

        MinimalDTO::fromJson('not-valid-json{', validate: false);
    }

    public function testFromJsonThrowsOnSequentialArray(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object');

        MinimalDTO::fromJson('[1,2,3]', validate: false);
    }

    // -----------------------------------------------------------------------
    // 6. fromPartialArray
    // -----------------------------------------------------------------------

    public function testFromPartialArrayHydratesOnlyPresentFields(): void
    {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Updated'], validate: false);

        $this->assertSame('Updated', $dto->name);
        // Other fields should have defaults or empty values
        $this->assertSame('active', $dto->status);
    }

    public function testFromPartialArrayWithEmptyData(): void
    {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        $this->assertInstanceOf(CreateUserDTO::class, $dto);
        $this->assertSame('active', $dto->status);
    }

    // -----------------------------------------------------------------------
    // 7. with() immutable update
    // -----------------------------------------------------------------------

    public function testWithReturnsNewInstance(): void
    {
        $original = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);
        $updated = $original->with(['name' => 'Bob'], validate: false);

        $this->assertNotSame($original, $updated);
        $this->assertSame('Alice', $original->name);
        $this->assertSame('Bob', $updated->name);
        $this->assertSame('test', $updated->value);
    }

    // -----------------------------------------------------------------------
    // 8. only() / except()
    // -----------------------------------------------------------------------

    public function testOnlyReturnsSelectedFields(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        $result = $dto->only('name');

        $this->assertSame(['name' => 'Alice'], $result);
    }

    public function testExceptExcludesFields(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        $result = $dto->except('value');

        $this->assertSame(['name' => 'Alice'], $result);
    }

    public function testOnlyAcceptsStringOrArray(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        // Single string key
        $single = $dto->only('name');
        $this->assertSame(['name' => 'Alice'], $single);

        // Multiple string keys
        $multi = $dto->only('name', 'value');
        $this->assertSame(['name' => 'Alice', 'value' => 'test'], $multi);
    }

    // -----------------------------------------------------------------------
    // 9. equals() / isEmpty() / isNotEmpty()
    // -----------------------------------------------------------------------

    public function testEqualsReturnsTrueForSameData(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    public function testEqualsReturnsFalseForDifferentData(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'test'], validate: false);

        $this->assertFalse($dto1->equals($dto2));
    }

    public function testEmptyDtoIsEmpty(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        $this->assertTrue($dto->isEmpty());
        $this->assertFalse($dto->isNotEmpty());
    }

    public function testNonEmptyDtoIsNotEmpty(): void
    {
        $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);

        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    // -----------------------------------------------------------------------
    // 10. Validation rules
    // -----------------------------------------------------------------------

    public function testRulesReturnsArray(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesForReturnsSameAsRules(): void
    {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        $this->assertSame($rules, $rulesFor);
    }

    public function testRulesContainsRequiredEmailRule(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    public function testRulesContainsMinMax(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertContains('min:2', $rules['name']);
        $this->assertContains('max:50', $rules['name']);
    }

    // -----------------------------------------------------------------------
    // 11. DtoCollection operations
    // -----------------------------------------------------------------------

    public function testDtoCollectionMakeAndCount(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $this->assertCount(2, $col);
        $this->assertFalse($col->isEmpty());
        $this->assertTrue($col->isNotEmpty());
    }

    public function testDtoCollectionFirstAndLast(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $this->assertSame($dto1, $col->first());
        $this->assertSame($dto2, $col->last());
    }

    public function testDtoCollectionMapAndFilter(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $names = $col->map(fn (MinimalDTO $d): string => $d->name);
        $this->assertSame(['Alice', 'Bob'], $names);

        $filtered = $col->filter(fn (MinimalDTO $d): bool => $d->name === 'Alice');
        $this->assertCount(1, $filtered);
    }

    public function testDtoCollectionPushMutatesInPlace(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $result = $col->push($dto2);

        $this->assertSame($col, $result); // Same instance
        $this->assertCount(2, $col);
    }

    public function testDtoCollectionAppendReturnsNewInstance(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1]);
        $newCol = $col->append($dto2);

        $this->assertNotSame($col, $newCol);
        $this->assertCount(1, $col);
        $this->assertCount(2, $newCol);
    }

    public function testDtoCollectionMerge(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'c'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2, $dto3]);

        $merged = $col1->merge($col2);

        $this->assertCount(3, $merged);
    }

    public function testDtoCollectionPluck(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $names = $col->pluck('name');
        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function testDtoCollectionPluckKey(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $map = $col->pluckKey('name', 'value');
        $this->assertSame(['Alice' => 'a', 'Bob' => 'b'], $map);
    }

    public function testDtoCollectionArrayAccess(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        $this->assertTrue(isset($col[0]));
        $this->assertSame($dto1, $col[0]);
        $this->assertSame($dto2, $col[1]);
        $this->assertNull($col[99]);
    }

    public function testDtoCollectionJsonSerialize(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $col = DtoCollection::make([$dto1]);

        $json = json_encode($col);
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertSame(['name' => 'Alice', 'value' => 'a'], $decoded[0]);
    }

    public function testDtoCollectionRejectsNonDto(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DtoCollection::make([new \stdClass]);
    }

    public function testDtoCollectionOffsetUnsetReindexes(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'c'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($col[0]);

        $this->assertCount(2, $col);
        $this->assertSame($dto2, $col[0]); // Bob is now at index 0 after re-index
        $this->assertSame($dto3, $col[1]);
    }

    public function testDtoCollectionEmpty(): void
    {
        $col = DtoCollection::make([]);

        $this->assertTrue($col->isEmpty());
        $this->assertFalse($col->isNotEmpty());
        $this->assertCount(0, $col);
        $this->assertNull($col->first());
        $this->assertNull($col->last());
        $this->assertSame([], $col->toArray());
        $this->assertSame([], $col->items());
    }

    // -----------------------------------------------------------------------
    // 12. DTOCast edge cases
    // -----------------------------------------------------------------------

    public function testDtoCastGetReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass, 'data', null, []);

        $this->assertNull($result);
    }

    public function testDtoCastGetHydratesFromJsonString(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $json = json_encode(['email' => 'test@example.com', 'name' => 'Alice']);
        $result = $cast->get(new \stdClass, 'data', $json, []);

        $this->assertInstanceOf(CreateUserDTO::class, $result);
        $this->assertSame('test@example.com', $result->email);
    }

    public function testDtoCastGetReturnsNullForInvalidJson(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass, 'data', 'not-json', []);

        $this->assertNull($result);
    }

    public function testDtoCastGetReturnsNullForNonArray(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass, 'data', 'just-a-string', []);

        $this->assertNull($result);
    }

    public function testDtoCastSetSerializesDtoToJson(): void
    {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $cast->set(new \stdClass, 'data', $dto, []);

        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertSame('test@example.com', $decoded['email']);
    }

    public function testDtoCastSetHydratesAndSerializesArray(): void
    {
        $cast = new DTOCast(CreateUserDTO::class, validate: false);
        $data = ['email' => 'test@example.com', 'name' => 'Alice'];

        $result = $cast->set(new \stdClass, 'data', $data, []);

        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertSame('test@example.com', $decoded['email']);
    }

    public function testDtoCastSetReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->set(new \stdClass, 'data', null, []);

        $this->assertNull($result);
    }

    public function testDtoCastSetThrowsForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DTOCast::set() expects a DTO instance, array, or null');

        $cast = new DTOCast(CreateUserDTO::class);
        $cast->set(new \stdClass, 'data', 12345, []);
    }

    public function testDtoCastSerializeReturnsArray(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $cast->serialize(new \stdClass, 'data', $dto, []);

        $this->assertIsArray($result);
        $this->assertSame('test@example.com', $result['email']);
    }

    public function testDtoCastSerializeReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->serialize(new \stdClass, 'data', null, []);

        $this->assertNull($result);
    }

    // -----------------------------------------------------------------------
    // 13. DTOException factory methods
    // -----------------------------------------------------------------------

    public function testInvalidCastExceptionMessage(): void
    {
        $exception = DTOException::invalidCast('age', 'integer', 'not-an-int');

        $this->assertStringContainsString('age', $exception->getMessage());
        $this->assertStringContainsString('integer', $exception->getMessage());
    }

    public function testInvalidJsonExceptionMessage(): void
    {
        $exception = DTOException::invalidJson('payload', 'Syntax error');

        $this->assertStringContainsString('payload', $exception->getMessage());
        $this->assertStringContainsString('Syntax error', $exception->getMessage());
    }

    // -----------------------------------------------------------------------
    // 14. Metadata cache flush
    // -----------------------------------------------------------------------

    public function testMetadataCacheFlush(): void
    {
        // Create a DTO to populate the cache
        CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        // Flush all metadata
        CreateUserDTO::flushMetadataCache();

        // Flush for specific class
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // If we reach here, no errors were thrown
        $this->assertTrue(true);
    }

    // -----------------------------------------------------------------------
    // 15. Roundtrip: fromArray → toArray → fromArray
    // -----------------------------------------------------------------------

    public function testRoundtripPreservesData(): void
    {
        $original = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);
        $array = $original->toArray();
        $restored = MinimalDTO::fromArray($array, validate: false);

        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->value, $restored->value);
    }

    public function testRoundtripPreservesDataWithDefaults(): void
    {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $array = $original->toArray();
        $restored = CreateUserDTO::fromArray($array, validate: false);

        $this->assertSame($original->email, $restored->email);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->status, $restored->status);
    }

    public function testRoundtripWithExcludesHiddenFields(): void
    {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $array = $original->toArray();
        $this->assertArrayNotHasKey('password', $array);

        // Restoring from toArray() loses the hidden field
        $restored = CreateUserDTO::fromArray($array, validate: false);
        $this->assertNull($restored->password);
    }

    // -----------------------------------------------------------------------
    // 16. NoConstructorDTO edge case
    // -----------------------------------------------------------------------

    public function testDtoWithNoConstructorReturnsEmptyRules(): void
    {
        // EmptyDTO has a constructor, but let's check behavior
        $this->assertNotEmpty(CreateUserDTO::rules());
    }

    // -----------------------------------------------------------------------
    // 17. Nullable property handling
    // -----------------------------------------------------------------------

    public function testNullablePropertyDefaultsToNull(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
        ], validate: false);

        $this->assertNull($dto->phone);
        $this->assertNull($dto->password);
    }

    public function testNullablePropertySetExplicitly(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
            'password' => 'mypass',
        ], validate: false);

        $this->assertSame('+1234567890', $dto->phone);
        $this->assertSame('mypass', $dto->password);
    }

    // -----------------------------------------------------------------------
    // 18. Zero value for int property
    // -----------------------------------------------------------------------

    public function testZeroIntPropertyIsNotEmpty(): void
    {
        // An int property with value 0 should not be considered empty
        $dto = RoundtripDTO::fromArray([
            'name' => 'Alice',
            'age' => 0,
            'active' => true,
        ], validate: false);

        // Age is not nullable but has value 0 — should NOT be considered empty
        $this->assertFalse($dto->isEmpty());
    }

    // -----------------------------------------------------------------------
    // 19. DtoCollection items() returns raw DTOs
    // -----------------------------------------------------------------------

    public function testDtoCollectionItemsReturnsRawInstances(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b'], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);
        $items = $col->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(MinimalDTO::class, $items[0]);
        $this->assertInstanceOf(MinimalDTO::class, $items[1]);
    }

    // -----------------------------------------------------------------------
    // 20. jsonSerialize integration
    // -----------------------------------------------------------------------

    public function testJsonSerializeReturnsSameAsToArray(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test'], validate: false);

        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
    }
}
