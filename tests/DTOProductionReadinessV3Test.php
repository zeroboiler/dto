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
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

/**
 * Production readiness edge case tests for ZeroBoiler DTO.
 *
 * Covers: with() always validates, rulesFor() action scoping, DTOCollection
 * static factory, metadata cache class-specific flush, DTOCast serialization
 * edge cases, DTOException factory methods, facade accessor, fromArray
 * with validate:false, toJson error handling, and DtoCollection iteration.
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\Casts\DTOCast
 */
final class DTOProductionReadinessV3Test extends TestCase
{
    protected function setUp(): void
    {
        DataTransferObject::flushMetadataCache();

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
    // with() always validates
    // -------------------------------------------------------------------

    /**
     * @test with() creates new instance with overrides
     */
    public function withCreatesNewInstanceWithOverrides(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $updated = $dto->with(['name' => 'Updated']);

        $this->assertNotSame($dto, $updated);
        $this->assertSame('test@example.com', $updated->email);
        $this->assertSame('Updated', $updated->name);
        // Original unchanged
        $this->assertSame('Test', $dto->name);
    }

    /**
     * @test with() ignores deprecated validate parameter
     */
    public function withIgnoresDeprecatedValidateParameter(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        // Even with validate=false, validation always runs
        $updated = $dto->with(['name' => 'Updated'], validate: false);

        $this->assertSame('Updated', $updated->name);
    }

    /**
     * @test with() preserves hidden fields in merge
     */
    public function withPreservesHiddenFieldsInMerge(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $updated = $dto->with(['status' => 'inactive']);

        $this->assertSame('secret', $updated->password);
    }

    // -------------------------------------------------------------------
    // rulesFor() action scoping
    // -------------------------------------------------------------------

    /**
     * @test rulesFor() returns base rules for unknown action
     */
    public function rulesForReturnsBaseRulesForUnknownAction(): void
    {
        $baseRules = ActionScopedDTO::rules();
        $unknownRules = ActionScopedDTO::rulesFor('unknown_action');

        $this->assertSame($baseRules, $unknownRules);
    }

    /**
     * @test rulesFor('create') returns same as rules()
     */
    public function rulesForCreateReturnsSameAsRules(): void
    {
        $baseRules = ActionScopedDTO::rules();
        $createRules = ActionScopedDTO::rulesFor('create');

        $this->assertSame($baseRules, $createRules);
    }

    /**
     * @test rulesFor('update') returns relaxed rules
     */
    public function rulesForUpdateReturnsRelaxedRules(): void
    {
        $updateRules = ActionScopedDTO::rulesFor('update');

        $this->assertArrayHasKey('email', $updateRules);
        $this->assertContains('sometimes', $updateRules['email']);
    }

    // -------------------------------------------------------------------
    // DtoCollection static factory
    // -------------------------------------------------------------------

    /**
     * @test DtoCollection::make() creates from array
     */
    public function dtoCollectionMakeCreatesFromItems(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $collection = DtoCollection::make([$dto]);

        $this->assertSame(1, $collection->count());
        $this->assertSame('test@example.com', $collection->first()?->email);
    }

    /**
     * @test DtoCollection::make() with empty array
     */
    public function dtoCollectionMakeWithEmptyArray(): void
    {
        $collection = DtoCollection::make([]);

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
    }

    // -------------------------------------------------------------------
    // Metadata cache class-specific flush
    // -------------------------------------------------------------------

    /**
     * @test flushMetadataCache(null) clears all
     */
    public function flushAllClearsAllCachedEntries(): void
    {
        CreateUserDTO::rules();
        AddressDTO::rules();

        DataTransferObject::flushMetadataCache();

        // After flush, resolve again with TTL=0 to verify rebuild
        DataTransferObject::setMetadataCacheTtl(0);
        $rules = CreateUserDTO::rules();
        $this->assertNotEmpty($rules);
    }

    /**
     * @test flushMetadataCache(class) clears only that class
     */
    public function flushClassClearsOnlySpecificClass(): void
    {
        DataTransferObject::setMetadataCacheTtl(0);
        CreateUserDTO::rules();
        AddressDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // AddressDTO rules should still be cached
        DataTransferObject::setMetadataCacheTtl(0);
        $addressRules = AddressDTO::rules();
        $this->assertNotEmpty($addressRules);
    }

    // -------------------------------------------------------------------
    // DTOCast edge cases
    // -------------------------------------------------------------------

    /**
     * @test DTOCast get() returns null for null value
     */
    public function dtoCastGetReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            new \stdClass,
            'data',
            null,
            ['data' => null],
        );

        $this->assertNull($result);
    }

    /**
     * @test DTOCast get() returns null for invalid JSON string
     */
    public function dtoCastGetReturnsNullForInvalidJson(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            new \stdClass,
            'data',
            '{invalid json}',
            ['data' => '{invalid json}'],
        );

        $this->assertNull($result);
    }

    /**
     * @test DTOCast get() returns null for non-array data
     */
    public function dtoCastGetReturnsNullForNonArray(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            new \stdClass,
            'data',
            'just a string',
            ['data' => 'just a string'],
        );

        $this->assertNull($result);
    }

    /**
     * @test DTOCast get() hydrates from valid JSON string
     */
    public function dtoCastGetHydratesFromValidJson(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $json = json_encode(['email' => 'test@example.com', 'name' => 'Test']);
        $result = $cast->get(
            new \stdClass,
            'data',
            $json,
            ['data' => $json],
        );

        $this->assertInstanceOf(CreateUserDTO::class, $result);
        $this->assertSame('test@example.com', $result->email);
    }

    /**
     * @test DTOCast get() hydrates from array directly
     */
    public function dtoCastGetHydratesFromArray(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->get(
            new \stdClass,
            'data',
            ['email' => 'test@example.com', 'name' => 'Test'],
            ['data' => ['email' => 'test@example.com', 'name' => 'Test']],
        );

        $this->assertInstanceOf(CreateUserDTO::class, $result);
        $this->assertSame('test@example.com', $result->email);
    }

    /**
     * @test DTOCast set() returns null for null
     */
    public function dtoCastSetReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->set(
            new \stdClass,
            'data',
            null,
            [],
        );

        $this->assertNull($result);
    }

    /**
     * @test DTOCast set() rejects non-DTO non-array values
     */
    public function dtoCastSetRejectsInvalidValues(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DTOCast::set() expects a DTO instance, array, or null');

        $cast->set(
            new \stdClass,
            'data',
            'invalid',
            [],
        );
    }

    /**
     * @test DTOCast serialize returns null for null
     */
    public function dtoCastSerializeReturnsNullForNull(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $result = $cast->serialize(
            new \stdClass,
            'data',
            null,
            [],
        );

        $this->assertNull($result);
    }

    /**
     * @test DTOCast serialize returns toArray for DTO instance
     */
    public function dtoCastSerializeReturnsToArrayForDto(): void
    {
        $cast = new DTOCast(CreateUserDTO::class);

        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $result = $cast->serialize(
            new \stdClass,
            'data',
            $dto,
            [],
        );

        $this->assertIsArray($result);
        $this->assertSame('test@example.com', $result['email']);
        $this->assertArrayNotHasKey('password', $result);
    }

    // -------------------------------------------------------------------
    // DTOException factory methods
    // -------------------------------------------------------------------

    /**
     * @test DTOException::invalidCast() formats message correctly
     */
    public function dtoExceptionInvalidCastFormatsMessage(): void
    {
        $exception = DTOException::invalidCast('age', 'integer', 'not-a-number');

        $this->assertStringContainsString('age', $exception->getMessage());
        $this->assertStringContainsString('integer', $exception->getMessage());
        $this->assertStringContainsString('not-a-number', $exception->getMessage());
    }

    /**
     * @test DTOException::invalidCast() handles null value
     */
    public function dtoExceptionInvalidCastHandlesNull(): void
    {
        $exception = DTOException::invalidCast('field', 'string', null);

        $this->assertStringContainsString('null', $exception->getMessage());
    }

    /**
     * @test DTOException::invalidJson() formats message correctly
     */
    public function dtoExceptionInvalidJsonFormatsMessage(): void
    {
        $exception = DTOException::invalidJson('data', 'Syntax error');

        $this->assertStringContainsString('data', $exception->getMessage());
        $this->assertStringContainsString('Syntax error', $exception->getMessage());
    }

    // -------------------------------------------------------------------
    // Facade accessor
    // -------------------------------------------------------------------

    /**
     * @test DTO facade accessor returns correct string
     */
    public function dtoFacadeAccessorReturnsCorrectString(): void
    {
        $accessor = DTO::getFacadeAccessor();

        $this->assertSame('zeroboiler.dto', $accessor);
    }

    // -------------------------------------------------------------------
    // fromArray with validate:false
    // -------------------------------------------------------------------

    /**
     * @test fromArray with validate:false skips validation entirely
     */
    public function fromArrayWithValidateFalseSkipsValidation(): void
    {
        // Create DTO with invalid email — no exception since validate=false
        $dto = CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => 'Test',
        ], validate: false);

        $this->assertSame('not-an-email', $dto->email);
    }

    /**
     * @test fromArray with validate:true fails on invalid data
     */
    public function fromArrayWithValidateTrueFailsOnInvalidData(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        CreateUserDTO::fromArray([
            'email' => '',
            'name' => 'T',
        ], validate: true);
    }

    // -------------------------------------------------------------------
    // toJson error handling
    // -------------------------------------------------------------------

    /**
     * @test toJson returns JSON string for valid DTO
     */
    public function toJsonReturnsJsonString(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('test@example.com', $decoded['email']);
    }

    // -------------------------------------------------------------------
    // DtoCollection iteration
    // -------------------------------------------------------------------

    /**
     * @test DtoCollection is iterable via foreach
     */
    public function dtoCollectionIsIterable(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@c.com', 'name' => 'Bob',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);

        $names = [];
        foreach ($collection as $dto) {
            $names[] = $dto->name;
        }

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    /**
     * @test DtoCollection toArray serializes all items
     */
    public function dtoCollectionToArraySerializesAllItems(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $array = $collection->toArray();

        $this->assertCount(1);
        $this->assertSame('a@b.com', $array[0]['email']);
    }

    /**
     * @test DtoCollection allValues includes hidden fields
     */
    public function dtoCollectionAllValuesIncludesHidden(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $all = $collection->allValues();

        $this->assertArrayHasKey('password', $all[0]);
        $this->assertSame('secret', $all[0]['password']);
    }

    /**
     * @test DtoCollection isEmpty and isNotEmpty
     */
    public function dtoCollectionIsEmptyAndIsNotEmpty(): void
    {
        $empty = new DtoCollection;
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($empty->isNotEmpty());

        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com', 'name' => 'Alice',
        ], validate: false);
        $nonEmpty = new DtoCollection([$dto]);
        $this->assertFalse($nonEmpty->isEmpty());
        $this->assertTrue($nonEmpty->isNotEmpty());
    }

    // -------------------------------------------------------------------
    // Empty DTO
    // -------------------------------------------------------------------

    /**
     * @test EmptyDTO has no properties
     */
    public function emptyDtoHasNoProperties(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        $this->assertSame([], $dto->toArray());
        $this->assertTrue($dto->isEmpty());
    }

    /**
     * @test EmptyDTO rules returns empty array
     */
    public function emptyDtoRulesReturnsEmptyArray(): void
    {
        $this->assertSame([], EmptyDTO::rules());
    }

    // -------------------------------------------------------------------
    // validatePartialArray edge cases
    // -------------------------------------------------------------------

    /**
     * @test validatePartialArray with empty data returns data as-is
     */
    public function validatePartialArrayWithEmptyDataReturnsAsIs(): void
    {
        $result = CreateUserDTO::validatePartialArray([]);

        $this->assertSame([], $result);
    }

    /**
     * @test validatePartialArray validates only present fields
     */
    public function validatePartialArrayValidatesOnlyPresentFields(): void
    {
        // Only name is present — email is not required
        $result = CreateUserDTO::validatePartialArray([
            'name' => 'Test',
        ]);

        $this->assertArrayHasKey('name', $result);
    }
}
