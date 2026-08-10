<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DateCastDTO;
use ZeroBoiler\DTO\Tests\Fixtures\DotNotationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MixedCollectionDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

/**
 * Comprehensive production readiness audit for the DTO package.
 *
 * Tests strict type safety, hydration/serialization cycles,
 * validation rule resolution, metadata caching, and PHPStan level 9 patterns.
 */
final class DTOProductionReadinessTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Hydration & Serialization Roundtrip
    // -----------------------------------------------------------------------

    public function test_from_array_to_array_roundtrip_preserves_data(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'age' => '30',
            'active' => '1',
            'score' => 95.5,
            'tags' => '["php","laravel"]',
            'source_bio' => 'A developer',
            'secret' => 'should-be-hidden',
            'role' => 'admin',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        $this->assertSame('Jane Doe', $result['name']);
        $this->assertSame(30, $result['age']);
        $this->assertTrue($result['active']);
        $this->assertSame(95.5, $result['score']);
        $this->assertSame(['php', 'laravel'], $result['tags']);
        $this->assertSame('A developer', $result['bio']);
        $this->assertSame('admin', $result['role']);
    }

    public function test_hidden_property_excluded_from_to_array(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'secret' => 'hidden-value',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        $this->assertArrayNotHasKey('secret', $result);
    }

    public function test_hidden_property_included_in_all_values(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'secret' => 'hidden-value',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->allValues();

        $this->assertArrayHasKey('secret', $result);
        $this->assertSame('hidden-value', $result['secret']);
    }

    public function test_map_from_resolves_different_source_key(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'source_bio' => 'Biography text',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        $this->assertSame('Biography text', $result['bio']);
    }

    // -----------------------------------------------------------------------
    // DefaultValue Attribute
    // -----------------------------------------------------------------------

    public function test_default_value_applied_when_key_absent(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);

        $this->assertSame(0.0, $dto->score);
        $this->assertSame([], $dto->tags);
        $this->assertSame('user', $dto->role);
    }

    public function test_explicit_null_not_overridden_by_default(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'bio' => null,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);

        $this->assertNull($dto->bio);
    }

    // -----------------------------------------------------------------------
    // Cast Attribute
    // -----------------------------------------------------------------------

    public function test_cast_integer_converts_string_to_int(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '42',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);

        $this->assertSame(42, $dto->age);
        $this->assertIsInt($dto->age);
    }

    public function test_cast_array_decodes_json_string(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'tags' => '{"key":"value"}',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);

        $this->assertSame(['key' => 'value'], $dto->tags);
    }

    // -----------------------------------------------------------------------
    // JSON Serialization
    // -----------------------------------------------------------------------

    public function test_to_json_returns_valid_json(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $json = $dto->toJson();

        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Test', $decoded['name']);
    }

    public function test_json_serialize_returns_array(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->jsonSerialize();

        $this->assertIsArray($result);
        $this->assertSame('Test', $result['name']);
    }

    // -----------------------------------------------------------------------
    // fromJson Hydration
    // -----------------------------------------------------------------------

    public function test_from_json_creates_dto(): void
    {
        $json = json_encode([
            'name' => 'From JSON',
            'age' => '30',
            'active' => true,
        ], JSON_THROW_ON_ERROR);

        $dto = RoundtripDTO::fromJson($json, validate: false);

        $this->assertSame('From JSON', $dto->name);
        $this->assertSame(30, $dto->age);
    }

    public function test_from_json_rejects_sequential_array(): void
    {
        $this->expectException(DTOException::class);

        RoundtripDTO::fromJson('[1,2,3]', validate: false);
    }

    // -----------------------------------------------------------------------
    // Immutable with()
    // -----------------------------------------------------------------------

    public function test_with_creates_new_instance(): void
    {
        $data = [
            'name' => 'Original',
            'age' => '25',
            'active' => true,
        ];

        $dto1 = RoundtripDTO::fromArray($data, validate: false);
        $dto2 = $dto1->with(['name' => 'Modified']);

        $this->assertNotSame($dto1, $dto2);
        $this->assertSame('Original', $dto1->name);
        $this->assertSame('Modified', $dto2->name);
    }

    public function test_with_preserves_hidden_exclusion(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
            'secret' => 'hidden',
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $modified = $dto->with(['name' => 'New Name']);

        $result = $modified->toArray();
        $this->assertArrayNotHasKey('secret', $result);
    }

    // -----------------------------------------------------------------------
    // equals() and isEmpty()
    // -----------------------------------------------------------------------

    public function test_equals_same_data(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto1 = RoundtripDTO::fromArray($data, validate: false);
        $dto2 = RoundtripDTO::fromArray($data, validate: false);

        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_different_data(): void
    {
        $dto1 = RoundtripDTO::fromArray(['name' => 'A', 'age' => '25', 'active' => true], validate: false);
        $dto2 = RoundtripDTO::fromArray(['name' => 'B', 'age' => '25', 'active' => true], validate: false);

        $this->assertFalse($dto1->equals($dto2));
    }

    public function test_is_not_empty_with_data(): void
    {
        $dto = RoundtripDTO::fromArray(['name' => 'Test', 'age' => '25', 'active' => true], validate: false);

        $this->assertFalse($dto->isEmpty());
        $this->assertTrue($dto->isNotEmpty());
    }

    // -----------------------------------------------------------------------
    // only() and except()
    // -----------------------------------------------------------------------

    public function test_only_returns_specified_fields(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->only('name');

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('age', $result);
        $this->assertArrayNotHasKey('active', $result);
    }

    public function test_only_accepts_array_of_keys(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->only(['name', 'active']);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('active', $result);
        $this->assertArrayNotHasKey('age', $result);
    }

    public function test_except_excludes_specified_fields(): void
    {
        $data = [
            'name' => 'Test',
            'age' => '25',
            'active' => true,
        ];

        $dto = RoundtripDTO::fromArray($data, validate: false);
        $result = $dto->except('age');

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('age', $result);
    }

    // -----------------------------------------------------------------------
    // Validation Rules Resolution
    // -----------------------------------------------------------------------

    public function test_rules_returns_array(): void
    {
        $rules = RoundtripDTO::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
    }

    public function test_rules_for_returns_same_as_rules_by_default(): void
    {
        $this->assertSame(RoundtripDTO::rules(), RoundtripDTO::rulesFor('create'));
        $this->assertSame(RoundtripDTO::rules(), RoundtripDTO::rulesFor('update'));
    }

    public function test_rules_include_cast_inference(): void
    {
        $rules = RoundtripDTO::rules();

        // 'age' has Cast('integer') — should infer 'integer' rule
        $this->assertContains('integer', $rules['age']);
    }

    // -----------------------------------------------------------------------
    // fromPartialArray (PATCH semantics)
    // -----------------------------------------------------------------------

    public function test_from_partial_array_hydrates_provided_fields(): void
    {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Partial'], validate: false);

        $this->assertSame('Partial', $dto->name);
    }

    public function test_from_partial_array_applies_defaults_for_missing_fields(): void
    {
        $dto = RoundtripDTO::fromPartialArray(['name' => 'Partial'], validate: false);

        $this->assertSame(0.0, $dto->score);
        $this->assertSame([], $dto->tags);
        $this->assertSame('user', $dto->role);
    }

    // -----------------------------------------------------------------------
    // Dot Notation MapFrom
    // -----------------------------------------------------------------------

    public function test_dot_notation_map_from(): void
    {
        $data = [
            'user.profile.firstName' => 'John',
            'user.profile.lastName' => 'Doe',
        ];

        $dto = DotNotationDTO::fromArray($data, validate: false);

        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
    }

    // -----------------------------------------------------------------------
    // Empty / No-Constructor DTO
    // -----------------------------------------------------------------------

    public function test_no_constructor_dto_from_array(): void
    {
        $dto = NoConstructorDTO::fromArray([], validate: false);

        $this->assertInstanceOf(NoConstructorDTO::class, $dto);
        $this->assertSame([], $dto->toArray());
    }

    public function test_empty_dto_from_array(): void
    {
        $dto = EmptyDTO::fromArray([], validate: false);

        $this->assertInstanceOf(EmptyDTO::class, $dto);
        $this->assertSame([], $dto->toArray());
    }

    // -----------------------------------------------------------------------
    // DtoCollection
    // -----------------------------------------------------------------------

    public function test_dto_collection_basic_operations(): void
    {
        $item1 = new AddressDTO(street: 'Main St', city: 'NYC');
        $item2 = new AddressDTO(street: 'Broadway', city: 'NYC');

        $collection = new DtoCollection([$item1, $item2]);

        $this->assertCount(2, $collection);
        $this->assertSame($item1, $collection->first());
        $this->assertSame($item2, $collection->last());
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    public function test_dto_collection_empty(): void
    {
        $collection = new DtoCollection();

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
    }

    public function test_dto_collection_to_array(): void
    {
        $item = new AddressDTO(street: 'Main St', city: 'NYC');
        $collection = new DtoCollection([$item]);

        $result = $collection->toArray();

        $this->assertCount(1, $result);
        $this->assertSame('Main St', $result[0]['street']);
    }

    public function test_dto_collection_push_and_map(): void
    {
        $collection = new DtoCollection();
        $item = new AddressDTO(street: 'Main St', city: 'NYC');

        $collection->push($item);

        $cities = $collection->map(fn (AddressDTO $dto, int $i): string => $dto->city);

        $this->assertSame(['NYC'], $cities);
    }

    public function test_dto_collection_filter(): void
    {
        $item1 = new AddressDTO(street: 'Main St', city: 'NYC');
        $item2 = new AddressDTO(street: 'Broadway', city: 'LA');

        $collection = new DtoCollection([$item1, $item2]);
        $filtered = $collection->filter(fn (AddressDTO $dto): bool => $dto->city === 'NYC');

        $this->assertCount(1, $filtered);
    }

    public function test_dto_collection_merge(): void
    {
        $item1 = new AddressDTO(street: 'Main St', city: 'NYC');
        $item2 = new AddressDTO(street: 'Broadway', city: 'LA');

        $col1 = new DtoCollection([$item1]);
        $col2 = new DtoCollection([$item2]);
        $merged = $col1->merge($col2);

        $this->assertCount(2, $merged);
    }

    public function test_dto_collection_rejects_non_dto(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DtoCollection([new \stdClass()]);
    }

    public function test_dto_collection_pluck(): void
    {
        $item1 = new AddressDTO(street: 'Main St', city: 'NYC');
        $item2 = new AddressDTO(street: 'Broadway', city: 'LA');

        $collection = new DtoCollection([$item1, $item2]);
        $cities = $collection->pluck('city');

        $this->assertSame(['NYC', 'LA'], $cities);
    }

    public function test_dto_collection_json_serialize(): void
    {
        $item = new AddressDTO(street: 'Main St', city: 'NYC');
        $collection = new DtoCollection([$item]);

        $result = $collection->jsonSerialize();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    // -----------------------------------------------------------------------
    // DTOCast (Eloquent Cast)
    // -----------------------------------------------------------------------

    public function test_dto_cast_get_from_json_string(): void
    {
        $cast = new DTOCast(RoundtripDTO::class);
        $json = json_encode(['name' => 'Cast', 'age' => '25', 'active' => true]);

        $result = $cast->get(new \stdClass(), 'data', $json, []);

        $this->assertInstanceOf(RoundtripDTO::class, $result);
        $this->assertSame('Cast', $result->name);
    }

    public function test_dto_cast_get_returns_null_for_null(): void
    {
        $cast = new DTOCast(RoundtripDTO::class);

        $this->assertNull($cast->get(new \stdClass(), 'data', null, []));
    }

    public function test_dto_cast_set_from_dto(): void
    {
        $cast = new DTOCast(RoundtripDTO::class, validate: false);
        $dto = RoundtripDTO::fromArray(['name' => 'Set', 'age' => '25', 'active' => true], validate: false);

        $result = $cast->set(new \stdClass(), 'data', $dto, []);

        $decoded = json_decode($result, true);
        $this->assertSame('Set', $decoded['name']);
    }

    public function test_dto_cast_set_returns_null_for_null(): void
    {
        $cast = new DTOCast(RoundtripDTO::class);

        $this->assertNull($cast->set(new \stdClass(), 'data', null, []));
    }

    public function test_dto_cast_rejects_invalid_type(): void
    {
        $cast = new DTOCast(RoundtripDTO::class);

        $this->expectException(\InvalidArgumentException::class);
        $cast->set(new \stdClass(), 'data', 42, []);
    }

    public function test_dto_cast_serialize(): void
    {
        $cast = new DTOCast(RoundtripDTO::class);
        $dto = RoundtripDTO::fromArray(['name' => 'Ser', 'age' => '25', 'active' => true], validate: false);

        $result = $cast->serialize(new \stdClass(), 'data', $dto, []);

        $this->assertIsArray($result);
        $this->assertSame('Ser', $result['name']);
    }

    // -----------------------------------------------------------------------
    // DTOException
    // -----------------------------------------------------------------------

    public function test_dto_exception_invalid_cast(): void
    {
        $exception = DTOException::invalidCast('age', 'integer', 'string');

        $this->assertStringContainsString('age', $exception->getMessage());
        $this->assertStringContainsString('integer', $exception->getMessage());
    }

    public function test_dto_exception_invalid_json(): void
    {
        $exception = DTOException::invalidJson('tags', 'Syntax error');

        $this->assertStringContainsString('tags', $exception->getMessage());
        $this->assertStringContainsString('Syntax error', $exception->getMessage());
    }

    // -----------------------------------------------------------------------
    // Metadata Resolver
    // -----------------------------------------------------------------------

    public function test_metadata_resolver_returns_properties_rules_messages(): void
    {
        $metadata = DtoMetadataResolver::resolve(RoundtripDTO::class);

        $this->assertArrayHasKey('properties', $metadata);
        $this->assertArrayHasKey('rules', $metadata);
        $this->assertArrayHasKey('messages', $metadata);
        $this->assertNotEmpty($metadata['properties']);
        $this->assertNotEmpty($metadata['rules']);
    }

    public function test_metadata_resolver_empty_for_no_constructor(): void
    {
        $metadata = DtoMetadataResolver::resolve(NoConstructorDTO::class);

        $this->assertSame([], $metadata['properties']);
        $this->assertSame([], $metadata['rules']);
        $this->assertSame([], $metadata['messages']);
    }

    public function test_metadata_resolver_detects_map_from(): void
    {
        $metadata = DtoMetadataResolver::resolve(RoundtripDTO::class);

        $this->assertSame('source_bio', $metadata['properties']['bio']['map_from']);
    }

    public function test_metadata_resolver_detects_hidden(): void
    {
        $metadata = DtoMetadataResolver::resolve(RoundtripDTO::class);

        $this->assertTrue($metadata['properties']['secret']['hidden']);
    }

    public function test_metadata_resolver_detects_cast(): void
    {
        $metadata = DtoMetadataResolver::resolve(RoundtripDTO::class);

        $this->assertSame('integer', $metadata['properties']['age']['cast']);
    }

    // -----------------------------------------------------------------------
    // Cache Management
    // -----------------------------------------------------------------------

    public function test_flush_metadata_cache_clears_all(): void
    {
        DataTransferObject::flushMetadataCache();

        $dto = RoundtripDTO::fromArray(['name' => 'Cache Test', 'age' => '25', 'active' => true], validate: false);
        $this->assertSame('Cache Test', $dto->name);

        // Flush and recreate — should work fine
        DataTransferObject::flushMetadataCache();
        $dto2 = RoundtripDTO::fromArray(['name' => 'After Flush', 'age' => '30', 'active' => false], validate: false);
        $this->assertSame('After Flush', $dto2->name);
    }

    public function test_flush_metadata_cache_for_specific_class(): void
    {
        DataTransferObject::flushMetadataCache(RoundtripDTO::class);

        $dto = RoundtripDTO::fromArray(['name' => 'Specific Flush', 'age' => '25', 'active' => true], validate: false);
        $this->assertSame('Specific Flush', $dto->name);
    }

    // -----------------------------------------------------------------------
    // Strict Type Safety (PHPStan Level 9 patterns)
    // -----------------------------------------------------------------------

    public function test_dto_manager_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    public function test_dto_collection_is_final(): void
    {
        $ref = new \ReflectionClass(DtoCollection::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_dto_exception_is_final(): void
    {
        $ref = new \ReflectionClass(DTOException::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_dto_metadata_resolver_is_final(): void
    {
        $ref = new \ReflectionClass(DtoMetadataResolver::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_base_dto_is_abstract(): void
    {
        $ref = new \ReflectionClass(DataTransferObject::class);
        $this->assertTrue($ref->isAbstract());
    }

    public function test_validation_test_dto_has_required_validation(): void
    {
        $rules = ValidationTestDTO::rules();
        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    // -----------------------------------------------------------------------
    // Attribute Classes Type Safety
    // -----------------------------------------------------------------------

    public function test_all_validation_attributes_have_rule_key(): void
    {
        $attributeClasses = [
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
            \ZeroBoiler\DTO\Attributes\Collection::class,
            \ZeroBoiler\DTO\Attributes\NestedArray::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
        ];

        foreach ($attributeClasses as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            $this->assertTrue(
                $ref->implementsInterface(ValidationAttribute::class),
                "{$attrClass} must implement ValidationAttribute"
            );

            // Verify ruleKey() method exists and returns string
            $instance = $ref->newInstanceWithoutConstructor();
            $this->assertIsString(
                $instance->ruleKey(),
                "{$attrClass}::ruleKey() must return string"
            );
            $this->assertNotEmpty(
                $instance->ruleKey(),
                "{$attrClass}::ruleKey() must not return empty string"
            );
        }
    }

    public function test_all_validation_attribute_properties_are_readonly(): void
    {
        $attributeClasses = [
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\MapFrom::class,
            \ZeroBoiler\DTO\Attributes\Cast::class,
            \ZeroBoiler\DTO\Attributes\DefaultValue::class,
        ];

        foreach ($attributeClasses as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            foreach ($ref->getProperties() as $prop) {
                $this->assertTrue(
                    $prop->isReadOnly(),
                    "Property {$prop->getName()} in {$attrClass} must be readonly"
                );
            }
        }
    }
}
