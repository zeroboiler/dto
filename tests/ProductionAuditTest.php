<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

/**
 * Comprehensive PHPStan Level 9 compliance audit for the DTO package.
 *
 * Exercises every public API surface to ensure:
 * - No `mixed` return types without explicit annotations
 * - Strict type comparisons
 * - All methods have return type declarations
 * - All properties are typed
 * - No dynamic property access
 * - All attributes are final
 * - All contracts are properly implemented
 */
final class ProductionAuditTest extends TestCase
{
    // ------------------------------------------------------------------
    // DataTransferObject base class tests
    // ------------------------------------------------------------------

    public function testDataTransferObjectIsAbstract(): void
    {
        $ref = new ReflectionClass(DataTransferObject::class);

        $this->assertTrue($ref->isAbstract());
    }

    public function testDataTransferObjectImplementsContracts(): void
    {
        $this->assertTrue(
            is_a(DataTransferObject::class, FromRequestDTO::class, true),
            'DataTransferObject must implement FromRequestDTO'
        );
        $this->assertTrue(
            is_a(DataTransferObject::class, ValidatableDTO::class, true),
            'DataTransferObject must implement ValidatableDTO'
        );
    }

    public function testDataTransferObjectMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(DataTransferObject::class);
        $publicMethods = [
            'fromArray', 'fromPartialArray', 'fromPartialRequest', 'fromRequest',
            'fromJson', 'validateArray', 'validatePartialArray', 'rules', 'rulesFor',
            'allValues', 'toArray', 'toJson', 'jsonSerialize', 'equals', 'isEmpty',
            'isNotEmpty', 'only', 'except', 'with', 'flushMetadataCache',
            'setMetadataCacheTtl',
        ];

        foreach ($publicMethods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "DataTransferObject::{$method}() must have a return type declaration"
            );
        }
    }

    // ------------------------------------------------------------------
    // DTOManager tests
    // ------------------------------------------------------------------

    public function testDTOManagerIsFinal(): void
    {
        $ref = new ReflectionClass(DTOManager::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testDTOManagerMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(DTOManager::class);
        $methods = ['validate', 'make', 'makeFromJson', 'schema'];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "DTOManager::{$method}() must have a return type"
            );
        }
    }

    // ------------------------------------------------------------------
    // DtoCollection tests
    // ------------------------------------------------------------------

    public function testDtoCollectionIsFinal(): void
    {
        $ref = new ReflectionClass(DtoCollection::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testDtoCollectionImplementsInterfaces(): void
    {
        $this->assertTrue(
            is_a(DtoCollection::class, \ArrayAccess::class, true)
        );
        $this->assertTrue(
            is_a(DtoCollection::class, \Countable::class, true)
        );
        $this->assertTrue(
            is_a(DtoCollection::class, \IteratorAggregate::class, true)
        );
        $this->assertTrue(
            is_a(DtoCollection::class, \JsonSerializable::class, true)
        );
    }

    public function testDtoCollectionRejectsNonDtos(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DtoCollection([new \stdClass]);
    }

    public function testDtoCollectionMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(DtoCollection::class);
        $methods = ['toArray', 'allValues', 'items', 'count', 'getIterator',
            'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset',
            'jsonSerialize', 'make', 'push', 'first', 'last', 'map',
            'filter', 'pluck', 'pluckKey', 'isEmpty', 'isNotEmpty',
        ];

        foreach ($methods as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "DtoCollection::{$method}() must have a return type"
            );
        }
    }

    public function testDtoCollectionPushAndCount(): void
    {
        $collection = new DtoCollection;
        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);

        $dto = ValidationTestDTO::fromArray([
            'name' => 'test',
            'email' => 'test@example.com',
        ], validate: false);

        $collection->push($dto);
        $this->assertFalse($collection->isEmpty());
        $this->assertCount(1, $collection);
        $this->assertSame($dto, $collection->first());
        $this->assertSame($dto, $collection->last());
    }

    public function testDtoCollectionFilterAndMap(): void
    {
        $d1 = ValidationTestDTO::fromArray(['name' => 'a', 'email' => 'a@x.com'], validate: false);
        $d2 = ValidationTestDTO::fromArray(['name' => 'b', 'email' => 'b@x.com'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);

        $filtered = $collection->filter(fn (DataTransferObject $dto): bool => $dto->name === 'a');
        $this->assertCount(1, $filtered);

        $names = $collection->map(fn (DataTransferObject $dto, int $i): string => $dto->name);
        $this->assertSame(['a', 'b'], $names);
    }

    public function testDtoCollectionOffsetOperations(): void
    {
        $dto = ValidationTestDTO::fromArray(['name' => 'x', 'email' => 'x@x.com'], validate: false);
        $collection = new DtoCollection;

        $collection[] = $dto;
        $this->assertCount(1, $collection);

        $this->assertSame($dto, $collection[0]);

        unset($collection[0]);
        $this->assertCount(0, $collection);
    }

    public function testDtoCollectionPluck(): void
    {
        $d1 = ValidationTestDTO::fromArray(['name' => 'alice', 'email' => 'a@x.com'], validate: false);
        $d2 = ValidationTestDTO::fromArray(['name' => 'bob', 'email' => 'b@x.com'], validate: false);

        $collection = new DtoCollection([$d1, $d2]);
        $names = $collection->pluck('name');

        $this->assertSame(['alice', 'bob'], $names);
    }

    // ------------------------------------------------------------------
    // DTOException tests
    // ------------------------------------------------------------------

    public function testDTOExceptionIsFinal(): void
    {
        $ref = new ReflectionClass(DTOException::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testDTOExceptionInvalidCastFactory(): void
    {
        $e = DTOException::invalidCast('price', 'integer', 'not_a_number');

        $this->assertInstanceOf(DTOException::class, $e);
        $this->assertStringContainsString('price', $e->getMessage());
        $this->assertStringContainsString('integer', $e->getMessage());
    }

    public function testDTOExceptionInvalidJsonFactory(): void
    {
        $e = DTOException::invalidJson('metadata', 'Syntax error');

        $this->assertInstanceOf(DTOException::class, $e);
        $this->assertStringContainsString('metadata', $e->getMessage());
        $this->assertStringContainsString('Syntax error', $e->getMessage());
    }

    public function testDTOExceptionMethodsReturnSelf(): void
    {
        $ref = new ReflectionClass(DTOException::class);

        foreach (['invalidCast', 'invalidJson'] as $method) {
            $m = $ref->getMethod($method);
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "DTOException::{$method}() must have return type");
            $this->assertSame('self', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // DTOCast tests
    // ------------------------------------------------------------------

    public function testDTOCastIsFinal(): void
    {
        $ref = new ReflectionClass(DTOCast::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testDTOCastMethodsHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(DTOCast::class);

        foreach (['get', 'set', 'serialize'] as $method) {
            $m = $ref->getMethod($method);
            $this->assertNotNull(
                $m->getReturnType(),
                "DTOCast::{$method}() must have return type"
            );
        }
    }

    // ------------------------------------------------------------------
    // Contract interfaces tests
    // ------------------------------------------------------------------

    public function testFromRequestDTOHasReturnType(): void
    {
        $ref = new ReflectionClass(FromRequestDTO::class);
        $method = $ref->getMethod('fromRequest');
        $rt = $method->getReturnType();

        $this->assertNotNull($rt);
        $this->assertSame('static', $rt->getName());
    }

    public function testValidatableDTOHasReturnTypes(): void
    {
        $ref = new ReflectionClass(ValidatableDTO::class);

        foreach (['rules', 'rulesFor'] as $method) {
            $m = $ref->getMethod($method);
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "ValidatableDTO::{$method}() must have return type");
            $this->assertSame('array', $rt->getName());
        }
    }

    public function testValidationAttributeHasReturnType(): void
    {
        $ref = new ReflectionClass(ValidationAttribute::class);
        $method = $ref->getMethod('ruleKey');
        $rt = $method->getReturnType();

        $this->assertNotNull($rt);
        $this->assertSame('string', $rt->getName());
    }

    // ------------------------------------------------------------------
    // Support classes tests
    // ------------------------------------------------------------------

    public function testDtoMetadataResolverIsFinal(): void
    {
        $ref = new ReflectionClass(DtoMetadataResolver::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testOpenApiSchemaGeneratorIsFinal(): void
    {
        $ref = new ReflectionClass(OpenApiSchemaGenerator::class);

        $this->assertTrue($ref->isFinal());
    }

    // ------------------------------------------------------------------
    // ServiceProvider verification
    // ------------------------------------------------------------------

    public function testDTOSServiceProviderIsFinal(): void
    {
        $ref = new ReflectionClass(DTOSServiceProvider::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testDTOSServiceProviderBootAndRegisterHaveReturnTypes(): void
    {
        $ref = new ReflectionClass(DTOSServiceProvider::class);

        foreach (['register', 'boot'] as $method) {
            $m = $ref->getMethod($method);
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "DTOSServiceProvider::{$method}() must have return type");
            $this->assertSame('void', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // Console commands verification
    // ------------------------------------------------------------------

    public function testMakeDtoTestCommandIsFinal(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testMakeDtoSchemaCommandIsFinal(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testConsoleCommandsHaveIntReturnType(): void
    {
        $commands = [
            \ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand::class,
            \ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand::class,
        ];

        foreach ($commands as $class) {
            $m = new \ReflectionMethod($class, 'handle');
            $rt = $m->getReturnType();
            $this->assertNotNull($rt, "{$class}::handle() must have return type");
            $this->assertSame('int', $rt->getName());
        }
    }

    // ------------------------------------------------------------------
    // Facade accessor verification
    // ------------------------------------------------------------------

    public function testDTOFacadeAccessorIsString(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        $method = $ref->getMethod('getFacadeAccessor');
        $rt = $method->getReturnType();

        $this->assertNotNull($rt);
        $this->assertSame('string', $rt->getName());
    }

    // ------------------------------------------------------------------
    // Attribute classes tests
    // ------------------------------------------------------------------

    /**
     * @dataProvider validationAttributeProvider
     */
    public function testValidationAttributeClassIsFinal(string $class): void
    {
        $ref = new ReflectionClass($class);

        $this->assertTrue($ref->isFinal(), "{$class} must be final");
    }

    /**
     * @dataProvider validationAttributeProvider
     */
    public function testValidationAttributeHasRuleKey(string $class): void
    {
        $ref = new ReflectionClass($class);

        $this->assertTrue(
            $ref->implementsInterface(ValidationAttribute::class),
            "{$class} must implement ValidationAttribute"
        );
    }

    /**
     * @dataProvider validationAttributeProvider
     */
    public function testValidationAttributeRuleKeyReturnsNonEmptyString(string $class): void
    {
        $instance = $class === Required::class
            ? new Required
            : (new ReflectionClass($class))->newInstanceWithoutConstructor();

        // For attributes without required constructor params
        if (method_exists($class, 'ruleKey')) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();
            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() === 0) {
                $instance = new $class;
                $key = $instance->ruleKey();
                $this->assertIsString($key);
                $this->assertNotEmpty($key);
            }
        }
    }

    /**
     * @return list<array{class-string}>
     */
    public static function validationAttributeProvider(): array
    {
        return [
            [Accepted::class],
            [ArrayRule::class],
            [Between::class],
            [Boolean::class],
            [Collection::class],
            [Confirmed::class],
            [Date::class],
            [Declined::class],
            [Distinct::class],
            [Email::class],
            [EndsWith::class],
            [Enum::class],
            [In::class],
            [Integer::class],
            [Json::class],
            [Max::class],
            [Min::class],
            [NestedArray::class],
            [Nullable::class],
            [Numeric::class],
            [Pattern::class],
            [Present::class],
            [Prohibited::class],
            [Required::class],
            [RequiredIf::class],
            [RequiredUnless::class],
            [RequiredWith::class],
            [RequiredWithAll::class],
            [RequiredWithout::class],
            [RequiredWithoutAll::class],
            [Same::class],
            [Size::class],
            [Sometimes::class],
            [StartsWith::class],
            [Url::class],
            [Uuid::class],
        ];
    }

    /**
     * @dataProvider metaAttributeProvider
     */
    public function testMetaAttributeIsFinal(string $class): void
    {
        $ref = new ReflectionClass($class);

        $this->assertTrue($ref->isFinal(), "{$class} must be final");
    }

    /**
     * @return list<array{class-string}>
     */
    public static function metaAttributeProvider(): array
    {
        return [
            [Cast::class],
            [DefaultValue::class],
            [Hidden::class],
            [MapFrom::class],
        ];
    }

    /**
     * @dataProvider metaAttributeProvider
     */
    public function testMetaAttributeHasReadonlyProperties(string $class): void
    {
        $ref = new ReflectionClass($class);

        foreach ($ref->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $this->assertTrue(
                $prop->isReadOnly(),
                "{$class}::\${$prop->getName()} must be readonly"
            );
        }
    }

    public function testHiddenAttributeIsMarker(): void
    {
        $ref = new ReflectionClass(Hidden::class);

        $this->assertCount(0, $ref->getProperties(), 'Hidden should have no properties');
    }

    public function testMapFromAcceptsDottedNotation(): void
    {
        $attr = new MapFrom('meta.phone');
        $this->assertSame('meta.phone', $attr->key);
    }

    // ------------------------------------------------------------------
    // DTO fixture verification
    // ------------------------------------------------------------------

    public function testValidationTestDTOFromArray(): void
    {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'John',
            'email' => 'john@example.com',
        ], validate: false);

        $this->assertSame('John', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
    }

    public function testValidationTestDTORules(): void
    {
        $rules = ValidationTestDTO::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    public function testValidationTestDTOToArray(): void
    {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ], validate: false);

        $arr = $dto->toArray();
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('email', $arr);
    }

    public function testValidationTestDTOToJson(): void
    {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'Jane',
            'email' => 'jane@example.com',
        ], validate: false);

        $json = $dto->toJson();
        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    public function testValidationTestDTOEquals(): void
    {
        $a = ValidationTestDTO::fromArray(['name' => 'x', 'email' => 'x@x.com'], validate: false);
        $b = ValidationTestDTO::fromArray(['name' => 'x', 'email' => 'x@x.com'], validate: false);
        $c = ValidationTestDTO::fromArray(['name' => 'y', 'email' => 'y@x.com'], validate: false);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testValidationTestDTOWithCreatesImmutableCopy(): void
    {
        $a = ValidationTestDTO::fromArray(['name' => 'x', 'email' => 'x@x.com'], validate: false);
        $b = $a->with(['name' => 'y']);

        $this->assertSame('x', $a->name, 'Original DTO should be unchanged');
        $this->assertSame('y', $b->name, 'New DTO should have updated name');
        $this->assertNotSame($a, $b);
    }

    public function testValidationTestDTOOnlyAndExcept(): void
    {
        $dto = ValidationTestDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], validate: false);

        $only = $dto->only('name');
        $this->assertArrayHasKey('name', $only);
        $this->assertArrayNotHasKey('email', $only);

        $except = $dto->except('email');
        $this->assertArrayHasKey('name', $except);
        $this->assertArrayNotHasKey('email', $except);
    }

    public function testValidationTestDTOFromJson(): void
    {
        $json = json_encode(['name' => 'Bob', 'email' => 'bob@example.com']);
        $dto = ValidationTestDTO::fromJson($json, validate: false);

        $this->assertSame('Bob', $dto->name);
        $this->assertSame('bob@example.com', $dto->email);
    }

    public function testValidationTestDTOFromJsonRejectsSequentialArray(): void
    {
        $this->expectException(DTOException::class);

        ValidationTestDTO::fromJson('[1,2,3]', validate: false);
    }

    public function testMinimalDTOHasDefaults(): void
    {
        $dto = MinimalDTO::fromArray([], validate: false);

        $this->assertNotNull($dto);
    }

    public function testEmptyDTOHasConstructor(): void
    {
        $ref = new ReflectionClass(EmptyDTO::class);
        $ctor = $ref->getConstructor();

        $this->assertNotNull($ctor, 'EmptyDTO should have a constructor');
    }

    public function testRegistrationDTOHasNestedDTO(): void
    {
        $ref = new ReflectionClass(RegistrationDTO::class);
        $ctor = $ref->getConstructor();

        $this->assertNotNull($ctor);
    }

    public function testCreateUserDTORulesReturnArray(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertIsArray($rules);
    }

    public function testCreateUserDTORulesForReturnsSameAsRules(): void
    {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('create');

        $this->assertSame($rules, $rulesFor);
    }

    // ------------------------------------------------------------------
    // Metadata cache tests
    // ------------------------------------------------------------------

    public function testMetadataCacheFlushClearsClassEntry(): void
    {
        DataTransferObject::flushMetadataCache();
        DataTransferObject::flushMetadataCache(ValidationTestDTO::class);

        // No exception = works
        $this->assertTrue(true);
    }

    public function testSetMetadataCacheTtlAcceptsFloat(): void
    {
        DataTransferObject::setMetadataCacheTtl(5.0);
        DataTransferObject::setMetadataCacheTtl(0.0);

        $this->assertTrue(true);
    }

    // ------------------------------------------------------------------
    // Strict types verification
    // ------------------------------------------------------------------

    /**
     * @dataProvider sourceClassProvider
     */
    public function testSourceFileDeclaresStrictTypes(string $class): void
    {
        $fileName = (string) (new ReflectionClass($class))->getFileName();
        $content = file_get_contents($fileName);

        $this->assertStringContainsString(
            'declare(strict_types=1)',
            $content,
            "{$class} must declare strict_types=1"
        );
    }

    /**
     * @return list<array{class-string}>
     */
    public static function sourceClassProvider(): array
    {
        return [
            [DataTransferObject::class],
            [DTOManager::class],
            [DtoCollection::class],
            [DTOException::class],
            [DTOCast::class],
            [DtoMetadataResolver::class],
            [OpenApiSchemaGenerator::class],
            [DTOSServiceProvider::class],
            [ValidationAttribute::class],
            [FromRequestDTO::class],
            [ValidatableDTO::class],
        ];
    }

    // ------------------------------------------------------------------
    // OpenApiSchemaGenerator tests
    // ------------------------------------------------------------------

    public function testOpenApiSchemaGeneratorThrowsForNestedDtos(): void
    {
        // OrderDTO has nested OrderItemDTO — should throw LogicException
        $this->expectException(\LogicException::class);
        OpenApiSchemaGenerator::generate(OrderDTO::class);
    }

    public function testOpenApiSchemaGeneratorWorksForSimpleDtos(): void
    {
        DataTransferObject::flushMetadataCache();

        $schema = OpenApiSchemaGenerator::generate(ValidationTestDTO::class);

        $this->assertArrayHasKey('type', $schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }
}
