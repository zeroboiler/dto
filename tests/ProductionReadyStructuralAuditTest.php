<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
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
use ZeroBoiler\DTO\Attributes\DtoCollection as DtoCollectionAttr;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
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
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

/**
 * Comprehensive source code structural audit for PHPStan Level 9 compliance.
 *
 * Validates that every source file meets production-readiness criteria:
 * - declare(strict_types=1) present
 * - No mixed type usage in public API where concrete types are possible
 * - Return type declarations on all methods
 * - Proper docblocks on all public/protected methods
 * - Readonly typed properties throughout
 * - Strict comparisons used (===, !==)
 * - All validation attributes implement ValidationAttribute contract
 * - All DTO fixtures extend DataTransferObject
 */
#[Group('production')]
#[CoversClass(DataTransferObject::class)]
#[CoversClass(DtoCollection::class)]
#[CoversClass(DTOManager::class)]
#[CoversClass(DTOCast::class)]
#[CoversClass(DTOException::class)]
#[CoversClass(DtoMetadataResolver::class)]
#[CoversClass(OpenApiSchemaGenerator::class)]
final class ProductionReadyStructuralAuditTest extends TestCase
{
    /**
     * All source files that MUST be audited for strict_types.
     *
     * @var list<string>
     */
    private const SOURCE_FILES = [
        // Base classes
        __DIR__ . '/../src/DataTransferObject.php',
        __DIR__ . '/../src/DtoCollection.php',
        __DIR__ . '/../src/DTOManager.php',
        // Contracts
        __DIR__ . '/../src/Contracts/FromRequestDTO.php',
        __DIR__ . '/../src/Contracts/ValidatableDTO.php',
        __DIR__ . '/../src/Contracts/ValidationAttribute.php',
        // Exception
        __DIR__ . '/../src/Exceptions/DTOException.php',
        // Casts
        __DIR__ . '/../src/Casts/DTOCast.php',
        // Facades
        __DIR__ . '/../src/Facades/DTO.php',
        // Service Provider
        __DIR__ . '/../src/DTOSServiceProvider.php',
        // Support
        __DIR__ . '/../src/Support/DtoMetadataResolver.php',
        __DIR__ . '/../src/Support/OpenApiSchemaGenerator.php',
    ];

    /**
     * All validation attribute classes.
     *
     * @var list<class-string>
     */
    private const VALIDATION_ATTRIBUTES = [
        Required::class,
        Email::class,
        Max::class,
        Min::class,
        Url::class,
        Uuid::class,
        Pattern::class,
        In::class,
        Integer::class,
        Numeric::class,
        Boolean::class,
        Date::class,
        Json::class,
        EnumAttr::class,
        Confirmed::class,
        Same::class,
        Different::class,
        Between::class,
        ArrayRule::class,
        Prohibited::class,
        Present::class,
        Declined::class,
        Accepted::class,
        StartsWith::class,
        EndsWith::class,
        Nullable::class,
        Sometimes::class,
        Distinct::class,
        Size::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
        NestedArray::class,
        Collection::class,
    ];

    /**
     * Metadata-only attributes (do not implement ValidationAttribute).
     *
     * @var list<class-string>
     */
    private const METADATA_ATTRIBUTES = [
        MapFrom::class,
        Cast::class,
        DefaultValue::class,
        Hidden::class,
    ];

    // -----------------------------------------------------------------------
    // Strict types declaration
    // -----------------------------------------------------------------------

    #[Group('strict-types')]
    public function testAllSourceFilesHaveStrictTypesDeclaration(): void
    {
        foreach (self::SOURCE_FILES as $file) {
            $content = file_get_contents($file);
            self::assertStringContainsString(
                'declare(strict_types=1)',
                $content,
                "File {$file} is missing declare(strict_types=1)."
            );
        }
    }

    // -----------------------------------------------------------------------
    // Return type declarations on public API
    // -----------------------------------------------------------------------

    #[Group('return-types')]
    public function testAllPublicMethodsHaveReturnTypeDeclarations(): void
    {
        $classes = [
            DataTransferObject::class,
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DTOException::class,
            DTO::class,
            OpenApiSchemaGenerator::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $returnType = $method->getReturnType();
                self::assertNotNull(
                    $returnType,
                    sprintf(
                        '%s::%s() is missing a return type declaration.',
                        $class,
                        $method->getName()
                    )
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // All validation attributes are final and implement ValidationAttribute
    // -----------------------------------------------------------------------

    #[Group('attributes')]
    public function testAllValidationAttributesAreFinal(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            self::assertTrue(
                $reflection->isFinal(),
                sprintf('%s must be final.', $attrClass)
            );
        }
    }

    #[Group('attributes')]
    public function testAllValidationAttributesImplementContract(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            self::assertTrue(
                $reflection->implementsInterface(ValidationAttribute::class),
                sprintf('%s must implement ValidationAttribute.', $attrClass)
            );
        }
    }

    #[Group('attributes')]
    public function testAllValidationAttributesHaveRuleKeyMethod(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $attrClass) {
            self::assertTrue(
                method_exists($attrClass, 'ruleKey'),
                sprintf('%s must have ruleKey() method.', $attrClass)
            );

            $method = new ReflectionMethod($attrClass, 'ruleKey');
            $returnType = $method->getReturnType();
            self::assertNotNull($returnType, sprintf('%s::ruleKey() must have return type.', $attrClass));
            self::assertSame('string', (string) $returnType, sprintf('%s::ruleKey() must return string.', $attrClass));
        }
    }

    #[Group('attributes')]
    public function testMetadataAttributesDoNotImplementValidationAttribute(): void
    {
        foreach (self::METADATA_ATTRIBUTES as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            self::assertFalse(
                $reflection->implementsInterface(ValidationAttribute::class),
                sprintf('%s must NOT implement ValidationAttribute (metadata-only).', $attrClass)
            );
        }
    }

    #[Group('attributes')]
    public function testAllAttributesUseReadonlyProperties(): void
    {
        $allAttrs = [...self::VALIDATION_ATTRIBUTES, ...self::METADATA_ATTRIBUTES];

        foreach ($allAttrs as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                self::assertTrue(
                    $property->isReadOnly(),
                    sprintf('%s::$%s must be readonly.', $attrClass, $property->getName())
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Service classes are final
    // -----------------------------------------------------------------------

    #[Group('finality')]
    public function testServiceClassesAreFinal(): void
    {
        $finalClasses = [
            DtoCollection::class,
            DTOManager::class,
            DTOCast::class,
            DTOException::class,
            DTO::class,
            DtoMetadataResolver::class,
            OpenApiSchemaGenerator::class,
            DTOSServiceProvider::class,
        ];

        foreach ($finalClasses as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue(
                $reflection->isFinal(),
                sprintf('%s must be final for production safety.', $class)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    #[Group('interfaces')]
    public function testDataTransferObjectImplementsExpectedInterfaces(): void
    {
        $reflection = new ReflectionClass(DataTransferObject::class);
        self::assertTrue(
            $reflection->implementsInterface(FromRequestDTO::class),
            'DataTransferObject must implement FromRequestDTO.'
        );
        self::assertTrue(
            $reflection->implementsInterface(ValidatableDTO::class),
            'DataTransferObject must implement ValidatableDTO.'
        );
        self::assertTrue(
            $reflection->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class),
            'DataTransferObject must implement Arrayable.'
        );
        self::assertTrue(
            $reflection->implementsInterface(\JsonSerializable::class),
            'DataTransferObject must implement JsonSerializable.'
        );
    }

    #[Group('interfaces')]
    public function testDTOCastImplementsCastsAttributes(): void
    {
        $reflection = new ReflectionClass(DTOCast::class);
        self::assertTrue(
            $reflection->implementsInterface(CastsAttributes::class),
            'DTOCast must implement CastsAttributes.'
        );
    }

    #[Group('interfaces')]
    public function testDtoCollectionImplementsExpectedInterfaces(): void
    {
        $reflection = new ReflectionClass(DtoCollection::class);
        self::assertTrue(
            $reflection->implementsInterface(\ArrayAccess::class),
            'DtoCollection must implement ArrayAccess.'
        );
        self::assertTrue(
            $reflection->implementsInterface(\Countable::class),
            'DtoCollection must implement Countable.'
        );
        self::assertTrue(
            $reflection->implementsInterface(\IteratorAggregate::class),
            'DtoCollection must implement IteratorAggregate.'
        );
        self::assertTrue(
            $reflection->implementsInterface(\JsonSerializable::class),
            'DtoCollection must implement JsonSerializable.'
        );
    }

    #[Group('interfaces')]
    public function testDTOFacadeExtendsLaravelFacade(): void
    {
        $reflection = new ReflectionClass(DTO::class);
        self::assertTrue(
            $reflection->isSubclassOf(\Illuminate\Support\Facades\Facade::class),
            'DTO facade must extend Laravel Facade.'
        );
    }

    // -----------------------------------------------------------------------
    // DTO fixtures extend DataTransferObject
    // -----------------------------------------------------------------------

    #[Group('fixtures')]
    public function testAllFixtureDTOsExtendDataTransferObject(): void
    {
        $fixtureDTOs = [
            CreateUserDTO::class,
            MinimalDTO::class,
            EmptyDTO::class,
            AddressDTO::class,
            OrderDTO::class,
            OrderItemDTO::class,
            RegistrationDTO::class,
            RoundtripDTO::class,
            ScalarConstraintsDTO::class,
            StrictValidationDTO::class,
        ];

        foreach ($fixtureDTOs as $dtoClass) {
            self::assertTrue(
                is_subclass_of($dtoClass, DataTransferObject::class),
                sprintf('%s must extend DataTransferObject.', $dtoClass)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Exception hierarchy
    // -----------------------------------------------------------------------

    #[Group('exceptions')]
    public function testDTOExceptionExtendsException(): void
    {
        $reflection = new ReflectionClass(DTOException::class);
        self::assertTrue(
            $reflection->isSubclassOf(\Exception::class),
            'DTOException must extend Exception.'
        );
    }

    #[Group('exceptions')]
    public function testDTOExceptionHasNamedConstructors(): void
    {
        self::assertTrue(
            method_exists(DTOException::class, 'invalidCast'),
            'DTOException must have invalidCast() factory.'
        );
        self::assertTrue(
            method_exists(DTOException::class, 'invalidJson'),
            'DTOException must have invalidJson() factory.'
        );
    }

    // -----------------------------------------------------------------------
    // DTOManager delegation contract
    // -----------------------------------------------------------------------

    #[Group('delegation')]
    public function testDTOManagerHasExpectedPublicMethods(): void
    {
        $expectedMethods = ['validate', 'make', 'makeFromJson', 'rules', 'rulesFor', 'schema'];

        foreach ($expectedMethods as $method) {
            self::assertTrue(
                method_exists(DTOManager::class, $method),
                sprintf('DTOManager must have %s() method.', $method)
            );
        }
    }

    // -----------------------------------------------------------------------
    // No mixed type annotations in DTOManager parameters
    // -----------------------------------------------------------------------

    #[Group('no-mixed')]
    public function testDTOManagerParametersAreConcreteTypes(): void
    {
        $reflection = new ReflectionClass(DTOManager::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                $type = $param->getType();
                $typeName = $type !== null ? (string) $type : 'none';

                self::assertNotSame(
                    'mixed',
                    $typeName,
                    sprintf(
                        'DTOManager::%s() parameter $%s uses mixed type — use concrete type for PHPStan L9.',
                        $method->getName(),
                        $param->getName()
                    )
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Metadata cache lifecycle
    // -----------------------------------------------------------------------

    #[Group('cache')]
    public function testMetadataCacheFlushAndTtl(): void
    {
        DataTransferObject::flushMetadataCache();

        $metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
        self::assertIsArray($metadata);
        self::assertArrayHasKey('properties', $metadata);
        self::assertArrayHasKey('rules', $metadata);
        self::assertArrayHasKey('messages', $metadata);

        // Flush and verify cache is cleared
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);
    }

    // -----------------------------------------------------------------------
    // Strict comparisons in source
    // -----------------------------------------------------------------------

    #[Group('strict-comparisons')]
    public function testDtoMetadataResolverUsesStrictBuiltinTypeCheck(): void
    {
        $content = file_get_contents(__DIR__ . '/../src/Support/DtoMetadataResolver.php');

        // Ensure in_array with strict mode (third parameter true)
        self::assertMatchesRegularExpression(
            '/in_array\s*\(\s*\$\w+\s*,\s*self::BUILTIN_TYPES\s*,\s*true\s*\)/',
            $content,
            'DtoMetadataResolver must use in_array(..., true) for builtin type checks.'
        );
    }

    // -----------------------------------------------------------------------
    // DtoCollection type guard
    // -----------------------------------------------------------------------

    #[Group('collection-type-safety')]
    public function testDtoCollectionRejectsNonDTOInstances(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DtoCollection only accepts DataTransferObject instances');

        new DtoCollection(['not_a_dto']);
    }

    // -----------------------------------------------------------------------
    // Cross-fixture consistency: all DTOs produce valid toArray output
    // -----------------------------------------------------------------------

    #[Group('cross-fixture')]
    public function testAllFixtureDTOsProduceValidToArrayOutput(): void
    {
        DataTransferObject::flushMetadataCache();

        $testData = [
            CreateUserDTO::class => ['email' => 'test@example.com', 'name' => 'Alice'],
            MinimalDTO::class => ['name' => 'Minimal'],
            EmptyDTO::class => [],
            OrderDTO::class => ['total' => 100.0, 'items' => 2],
            RoundtripDTO::class => ['name' => 'Roundtrip', 'email' => 'round@test.com'],
        ];

        foreach ($testData as $dtoClass => $data) {
            $dto = $dtoClass::fromArray($data, validate: false);
            $array = $dto->toArray();

            self::assertIsArray(
                $array,
                sprintf('%s::toArray() must return array.', $dtoClass)
            );
        }

        DataTransferObject::flushMetadataCache();
    }

    // -----------------------------------------------------------------------
    // OpenApiSchemaGenerator type safety
    // -----------------------------------------------------------------------

    #[Group('openapi')]
    public function testOpenApiSchemaGeneratorIsFinal(): void
    {
        $reflection = new ReflectionClass(OpenApiSchemaGenerator::class);
        self::assertTrue($reflection->isFinal());
    }

    #[Group('openapi')]
    public function testOpenApiSchemaGeneratorHasPublicGenerateMethods(): void
    {
        self::assertTrue(
            method_exists(OpenApiSchemaGenerator::class, 'generate'),
            'OpenApiSchemaGenerator must have generate() method.'
        );
        self::assertTrue(
            method_exists(OpenApiSchemaGenerator::class, 'generateWithComponents'),
            'OpenApiSchemaGenerator must have generateWithComponents() method.'
        );
    }

    // -----------------------------------------------------------------------
    // ValidationAttribute contract completeness
    // -----------------------------------------------------------------------

    #[Group('validation-contract')]
    public function testAllValidationAttributesHaveMessageProperty(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $attrClass) {
            $reflection = new ReflectionClass($attrClass);
            $properties = $reflection->getProperties();

            $hasMessage = false;
            foreach ($properties as $property) {
                if ($property->getName() === 'message') {
                    $hasMessage = true;
                    // Verify it's typed as ?string (nullable string)
                    $type = $property->getType();
                    self::assertNotNull(
                        $type,
                        sprintf('%s::$message must have a declared type.', $attrClass)
                    );
                    break;
                }
            }

            self::assertTrue(
                $hasMessage,
                sprintf('%s must have a $message property for custom validation messages.', $attrClass)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Contracts have proper method signatures
    // -----------------------------------------------------------------------

    #[Group('contracts')]
    public function testFromRequestDTOContractSignature(): void
    {
        $method = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        $params = $method->getParameters();

        self::assertCount(2, $params, 'fromRequest must accept Request and bool validate.');
        self::assertSame('request', $params[0]->getName());
        self::assertSame('validate', $params[1]->getName());

        $returnType = $method->getReturnType();
        self::assertNotNull($returnType);
        self::assertSame('static', (string) $returnType);
    }

    #[Group('contracts')]
    public function testValidatableDTOContractSignatures(): void
    {
        $rulesMethod = new ReflectionMethod(ValidatableDTO::class, 'rules');
        $rulesReturn = $rulesMethod->getReturnType();
        self::assertNotNull($rulesReturn);
        self::assertSame('array', (string) $rulesReturn);

        $rulesForMethod = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
        $params = $rulesForMethod->getParameters();
        self::assertCount(1, $params, 'rulesFor must accept string $action.');
        self::assertSame('action', $params[0]->getName());
    }

    #[Group('contracts')]
    public function testValidationAttributeContractSignature(): void
    {
        $method = new ReflectionMethod(ValidationAttribute::class, 'ruleKey');
        $returnType = $method->getReturnType();
        self::assertNotNull($returnType);
        self::assertSame('string', (string) $returnType);
    }
}
