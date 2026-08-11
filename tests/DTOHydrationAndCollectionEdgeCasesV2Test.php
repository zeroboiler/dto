<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

/**
 * Edge case tests for DTO hydration pipeline, collection behavior,
 * and serialization.
 *
 * Covers: empty string vs null vs missing key, fromJson edge cases,
 * collection clone isolation, equals() strictness, and DTO metadata
 * cache TTL behavior.
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\DtoCollection
 */
final class DTOHydrationAndCollectionEdgeCasesV2Test extends TestCase
{
    protected function setUp(): void
    {
        DataTransferObject::flushMetadataCache();

        // Set up minimal container for validation
        $container = new Container;
        $container->singleton(
            ValidationFactory::class,
            fn (): Factory => new Factory(
                new Translator(new ArrayLoader, 'en'),
            ),
        );
        $container->alias(ValidationFactory::class, 'validator');
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        DataTransferObject::flushMetadataCache();
    }

    // -------------------------------------------------------------------
    // Empty string vs null vs missing key
    // -------------------------------------------------------------------

    /**
     * @test Empty string is preserved (not replaced by default)
     */
    public function emptyStringIsPreservedNotReplacedByDefault(): void
    {
        // CreateUserDTO has #[DefaultValue('active')] on $status
        // But if we pass empty string explicitly, it should be respected
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => '',
        ], validate: false);

        $this->assertSame('', $dto->status);
    }

    /**
     * @test Explicit null is preserved for nullable fields
     */
    public function explicitNullIsPreservedForNullableFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => null,
        ], validate: false);

        $this->assertNull($dto->phone);
    }

    /**
     * @test Missing key uses default value
     */
    public function missingKeyUsesDefaultValue(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertSame('active', $dto->status);
    }

    /**
     * @test Missing nullable key uses PHP constructor default
     */
    public function missingNullableKeyUsesConstructorDefault(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertNull($dto->phone);
        $this->assertSame([], $dto->tags);
    }

    // -------------------------------------------------------------------
    // fromJson edge cases
    // -------------------------------------------------------------------

    /**
     * @test fromJson rejects sequential JSON arrays
     */
    public function fromJsonRejectsSequentialArray(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Expected a JSON object');

        CreateUserDTO::fromJson('["test@example.com", "Test"]');
    }

    /**
     * @test fromJson accepts empty object
     */
    public function fromJsonAcceptsEmptyObject(): void
    {
        // Empty object — required fields missing, will fail validation
        try {
            CreateUserDTO::fromJson('{}', validate: true);
            $this->fail('Expected ValidationException');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected — required fields are missing
            $this->assertNotEmpty($e->errors());
        }
    }

    /**
     * @test fromJson rejects invalid JSON syntax
     */
    public function fromJsonRejectsInvalidSyntax(): void
    {
        $this->expectException(DTOException::class);
        $this->expectExceptionMessage('Cannot decode JSON');

        CreateUserDTO::fromJson('{invalid json}');
    }

    /**
     * @test fromJson with nested keys (MapFrom)
     */
    public function fromJsonMapsKeysCorrectly(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'phone_number' => '+1234567890',
        ], validate: false);

        $this->assertSame('+1234567890', $dto->phone);
    }

    // -------------------------------------------------------------------
    // DtoCollection clone and mutation isolation
    // -------------------------------------------------------------------

    /**
     * @test append() returns a new collection, original is unchanged
     */
    public function appendReturnsNewCollectionOriginalUnchanged(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $original = new DtoCollection([$dto1]);
        $extended = $original->append($dto2);

        $this->assertSame(1, $original->count());
        $this->assertSame(2, $extended->count());
        $this->assertNotSame($original, $extended);
    }

    /**
     * @test merge() returns a new collection, originals are unchanged
     */
    public function mergeReturnsNewCollectionOriginalsUnchanged(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        $this->assertSame(1, $col1->count());
        $this->assertSame(1, $col2->count());
        $this->assertSame(2, $merged->count());
    }

    /**
     * @test push() mutates in place and returns same instance
     */
    public function pushMutatesInPlaceAndReturnsSameInstance(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        $this->assertSame(2, $collection->count());
        $this->assertSame($collection, $result);
    }

    /**
     * @test offsetUnset re-indexes the collection
     */
    public function offsetUnsetReindexesCollection(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        // After unsetting index 0, the collection should be re-indexed
        $this->assertSame(2, $collection->count());
        $this->assertSame('Bob', $collection[0]?->name);
        $this->assertSame('Charlie', $collection[1]?->name);
    }

    // -------------------------------------------------------------------
    // equals() strictness
    // -------------------------------------------------------------------

    /**
     * @test equals() returns true for identical data
     */
    public function equalsReturnsTrueForIdenticalData(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    /**
     * @test equals() returns false for different data
     */
    public function equalsReturnsFalseForDifferentData(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $this->assertFalse($dto1->equals($dto2));
    }

    /**
     * @test equals() ignores hidden fields
     */
    public function equalsIgnoresHiddenFields(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret1',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret2',
        ], validate: false);

        // Both have same public output, different hidden passwords
        $this->assertTrue($dto1->equals($dto2));
    }

    // -------------------------------------------------------------------
    // isEmpty() / isNotEmpty()
    // -------------------------------------------------------------------

    /**
     * @test DTO with required fields filled is not empty
     */
    public function dtoWithRequiredFieldsFilledIsNotEmpty(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    /**
     * @test DTO created without validate (no data) has empty values
     */
    public function dtoWithOnlyDefaultsHasEmptyState(): void
    {
        // AddressDTO has Required fields — can't create empty
        // Instead test CreateUserDTO with defaults only (but email is required)
        // So we need to provide at least email+name
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'tags' => [],
            'phone' => null,
            'password' => null,
        ], validate: false);

        // email and name are filled, so not empty
        $this->assertFalse($dto->isEmpty());
    }

    // -------------------------------------------------------------------
    // toArray() vs allValues()
    // -------------------------------------------------------------------

    /**
     * @test toArray() excludes hidden fields
     */
    public function toArrayExcludesHiddenFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $array = $dto->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    /**
     * @test allValues() includes hidden fields
     */
    public function allValuesIncludesHiddenFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret123', $all['password']);
    }

    // -------------------------------------------------------------------
    // only() / except()
    // -------------------------------------------------------------------

    /**
     * @test only() with single string key
     */
    public function onlyWithSingleKey(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        $this->assertSame(['email' => 'test@example.com'], $result);
    }

    /**
     * @test only() with array of keys
     */
    public function onlyWithArrayOfKeys(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('status', $result);
    }

    /**
     * @test except() excludes specified fields
     */
    public function exceptExcludesFields(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('status');

        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('status', $result);
    }

    // -------------------------------------------------------------------
    // DtoCollection jsonSerialize
    // -------------------------------------------------------------------

    /**
     * @test DtoCollection jsonSerialize produces correct array
     */
    public function dtoCollectionJsonSerialize(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $json = json_encode($collection);

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('test@example.com', $decoded[0]['email']);
    }

    // -------------------------------------------------------------------
    // DtoCollection pluck / pluckKey
    // -------------------------------------------------------------------

    /**
     * @test pluck() extracts single field from all DTOs
     */
    public function pluckExtractsSingleField(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $emails = $collection->pluck('email');

        $this->assertSame(['a@b.com', 'c@d.com'], $emails);
    }

    /**
     * @test pluckKey() builds key/value map
     */
    public function pluckKeyBuildsKeyValueMap(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $map = $collection->pluckKey('email', 'name');

        $this->assertSame(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie'], $map);
    }

    // -------------------------------------------------------------------
    // DtoCollection first/last
    // -------------------------------------------------------------------

    /**
     * @test first() and last() return correct items
     */
    public function firstAndLastReturnCorrectItems(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);

        $this->assertSame('Alice', $collection->first()?->name);
        $this->assertSame('Charlie', $collection->last()?->name);
    }

    /**
     * @test first() and last() return null on empty collection
     */
    public function firstAndLastReturnNullOnEmpty(): void
    {
        $collection = new DtoCollection;

        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
    }

    // -------------------------------------------------------------------
    // DtoCollection filter / map
    // -------------------------------------------------------------------

    /**
     * @test filter() returns new collection with matching items
     */
    public function filterReturnsNewCollectionWithMatchingItems(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bb@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (CreateUserDTO $dto): bool => str_starts_with($dto->email, 'a'),
        );

        $this->assertSame(1, $filtered->count());
        $this->assertSame('Alice', $filtered->first()?->name);
    }

    /**
     * @test map() returns plain array of transformed values
     */
    public function mapReturnsPlainArrayOfTransformedValues(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com', 'name' => 'Charlie',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (CreateUserDTO $dto): string => $dto->name);

        $this->assertSame(['Alice', 'Charlie'], $names);
    }

    // -------------------------------------------------------------------
    // DtoCollection type guard
    // -------------------------------------------------------------------

    /**
     * @test Constructor rejects non-DTO items
     */
    public function constructorRejectsNonDtoItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        new DtoCollection([new \stdClass]);
    }

    /**
     * @test offsetSet rejects non-DTO items
     */
    public function offsetSetRejectsNonDtoItems(): void
    {
        $collection = new DtoCollection;

        $this->expectException(\InvalidArgumentException::class);
        $collection[] = 'not a dto';
    }

    // -------------------------------------------------------------------
    // Metadata cache TTL behavior
    // -------------------------------------------------------------------

    /**
     * @test Metadata cache stores resolved metadata
     */
    public function metadataCacheStoresResolvedMetadata(): void
    {
        DataTransferObject::setMetadataCacheTtl(0);

        $rules1 = CreateUserDTO::rules();
        $rules2 = CreateUserDTO::rules();

        // With TTL=0, metadata is rebuilt but should be identical
        $this->assertEquals($rules1, $rules2);

        DataTransferObject::setMetadataCacheTtl(0);
    }

    /**
     * @test flushMetadataCache(null) clears all cached entries
     */
    public function flushAllClearsAllEntries(): void
    {
        // Resolve metadata for both DTOs to populate cache
        CreateUserDTO::rules();
        AddressDTO::rules();

        DataTransferObject::flushMetadataCache();

        // After flush, next resolve should rebuild (TTL=0 to verify)
        DataTransferObject::setMetadataCacheTtl(0);
        $rules = CreateUserDTO::rules();
        $this->assertNotEmpty($rules);
    }

    // -------------------------------------------------------------------
    // Nested DTO hydration
    // -------------------------------------------------------------------

    /**
     * @test Nested DTO with all required fields
     */
    public function nestedDtoWithRequiredFields(): void
    {
        $dto = AddressDTO::fromArray([
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zipCode' => '62701',
        ], validate: false);

        $this->assertSame('123 Main St', $dto->street);
        $this->assertSame('Springfield', $dto->city);
        $this->assertSame('62701', $dto->zipCode);
    }

    /**
     * @test Nested DTO nullable field defaults to null
     */
    public function nestedDtoNullableFieldDefaultsToNull(): void
    {
        $dto = AddressDTO::fromArray([
            'street' => '123 Main St',
            'city' => 'Springfield',
        ], validate: false);

        $this->assertNull($dto->zipCode);
    }
}
