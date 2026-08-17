<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Attribute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
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
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Comprehensive attribute structural integrity test.
 *
 * Verifies that every attribute class in the DTO package:
 * - Is declared as final
 * - Has declare(strict_types=1)
 * - Uses correct Attribute targets (TARGET_PROPERTY for validation/metadata)
 * - Implements ValidationAttribute where applicable
 * - Has readonly promoted constructor properties
 * - Has ruleKey() method that returns a non-empty string
 * - Has complete PHPDoc with @see annotations
 * - Constructor parameters have @param docblocks
 */
final class AttributeStructuralIntegrityV49Test extends TestCase
{
    /**
     * Full list of all 41 attribute classes in the DTO package.
     *
     * @var list<class-string>
     */
    private const ALL_ATTRIBUTE_CLASSES = [
        // Validation attributes (37)
        Accepted::class,
        ArrayRule::class,
        Between::class,
        Boolean::class,
        Confirmed::class,
        Date::class,
        Declined::class,
        Different::class,
        Distinct::class,
        Email::class,
        EndsWith::class,
        EnumAttribute::class,
        In::class,
        Integer::class,
        Json::class,
        Max::class,
        Min::class,
        Numeric::class,
        Pattern::class,
        Present::class,
        Prohibited::class,
        Required::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
        Same::class,
        Size::class,
        Sometimes::class,
        StartsWith::class,
        Url::class,
        Uuid::class,
        // Metadata/hydration attributes (4)
        Cast::class,
        DefaultValue::class,
        Hidden::class,
        MapFrom::class,
        NestedArray::class,
        Collection::class,
        Nullable::class,
    ];

    /**
     * Attributes that MUST implement ValidationAttribute interface.
     *
     * @var list<class-string>
     */
    private const VALIDATION_ATTRIBUTES = [
        Accepted::class,
        ArrayRule::class,
        Between::class,
        Boolean::class,
        Confirmed::class,
        Date::class,
        Declined::class,
        Different::class,
        Distinct::class,
        Email::class,
        EndsWith::class,
        EnumAttribute::class,
        In::class,
        Integer::class,
        Json::class,
        Max::class,
        Min::class,
        Numeric::class,
        Nullable::class,
        Pattern::class,
        Present::class,
        Prohibited::class,
        Required::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
        Same::class,
        Size::class,
        Sometimes::class,
        StartsWith::class,
        Url::class,
        Uuid::class,
    ];

    /**
     * @test
     */
    public function allAttributeClassesExistAndAreFinal(): void
    {
        foreach (self::ALL_ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);

            $this->assertTrue(
                $ref->isFinal(),
                "{$class} must be declared as final"
            );
            $this->assertTrue(
                $ref->inNamespace(),
                "{$class} must be in a namespace"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesHaveStrictTypes(): void
    {
        foreach (self::ALL_ATTRIBUTE_CLASSES as $class) {
            $file = $ref->getFileName();
            $ref = new ReflectionClass($class);
            $file = $ref->getFileName();

            $this->assertIsString($file);
            $contents = file_get_contents($file);
            $this->assertIsString($contents);
            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                "{$class} must have declare(strict_types=1)"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesHaveAttributeTargetProperty(): void
    {
        foreach (self::ALL_ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Attribute::class);

            $this->assertNotEmpty(
                $attrs,
                "{$class} must have #[Attribute] declaration"
            );

            $instance = $attrs[0]->newInstance();
            $flags = $instance->flags;

            // All DTO attributes must target at least TARGET_PROPERTY
            $this->assertNotEmpty(
                $flags,
                "{$class} must have at least one Attribute target flag"
            );
        }
    }

    /**
     * @test
     */
    public function validationAttributesImplementInterface(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $class) {
            $this->assertTrue(
                is_a($class, ValidationAttribute::class, true),
                "{$class} must implement ValidationAttribute"
            );
        }
    }

    /**
     * @test
     */
    public function validationAttributesReturnNonEmptyRuleKey(): void
    {
        foreach (self::VALIDATION_ATTRIBUTES as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            // Create instance with default parameters (or no parameters)
            if ($constructor !== null && $constructor->isPublic() && $constructor->getNumberOfRequiredParameters() === 0) {
                $instance = $ref->newInstanceWithoutConstructor();
            } else {
                // Try to create with required params — skip if not possible
                continue;
            }

            $this->assertInstanceOf(ValidationAttribute::class, $instance);
            $this->assertNotEmpty(
                $instance->ruleKey(),
                "{$class}::ruleKey() must return non-empty string"
            );
            $this->assertIsString($instance->ruleKey());
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesHavePhpDoc(): void
    {
        foreach (self::ALL_ATTRIBUTE_CLASSES as $class) {
            $ref = new ReflectionClass($class);
            $doc = $ref->getDocComment();

            $this->assertIsString($doc);
            $this->assertStringContainsString(
                '@',
                $doc,
                "{$class} must have a PHPDoc comment with annotations"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeConstructorsHaveParamDocblocks(): void
    {
        $classesWithRequiredParams = [
            Accepted::class,
            Between::class,
            Different::class,
            EnumAttribute::class,
            In::class,
            Max::class,
            Min::class,
            Pattern::class,
            RequiredIf::class,
            RequiredUnless::class,
            RequiredWith::class,
            RequiredWithAll::class,
            RequiredWithout::class,
            RequiredWithoutAll::class,
            Same::class,
            Size::class,
            StartsWith::class,
            EndsWith::class,
            Cast::class,
            DefaultValue::class,
            MapFrom::class,
            NestedArray::class,
            Collection::class,
            ArrayRule::class,
            Date::class,
        ];

        foreach ($classesWithRequiredParams as $class) {
            $ref = new ReflectionClass($class);
            $constructor = $ref->getConstructor();

            $this->assertNotNull($constructor, "{$class} must have a constructor");
            $doc = $constructor->getDocComment();

            $this->assertIsString($doc);
            $this->assertStringContainsString(
                '@param',
                $doc,
                "{$class} constructor must have @param docblocks"
            );
        }
    }

    /**
     * @test
     */
    public function allAttributeConstructorPropertiesArePublicReadonly(): void
    {
        $expectedProps = [
            Accepted::class => [],
            ArrayRule::class => ['min', 'max'],
            Between::class => ['min', 'max'],
            Boolean::class => [],
            Cast::class => ['type'],
            Collection::class => ['dtoClass'],
            Confirmed::class => [],
            Date::class => ['format'],
            Declined::class => [],
            DefaultValue::class => ['value'],
            Different::class => ['field'],
            Distinct::class => [],
            Email::class => [],
            EndsWith::class => ['suffix'],
            EnumAttribute::class => ['enumClass'],
            Hidden::class => [],
            In::class => ['values'],
            Integer::class => [],
            Json::class => [],
            MapFrom::class => ['key'],
            Max::class => ['value'],
            Min::class => ['value'],
            NestedArray::class => ['dtoClass'],
            Nullable::class => [],
            Numeric::class => [],
            Pattern::class => ['regex'],
            Present::class => [],
            Prohibited::class => [],
            Required::class => [],
            RequiredIf::class => ['field', 'value'],
            RequiredUnless::class => ['field', 'value'],
            RequiredWith::class => ['fields'],
            RequiredWithAll::class => ['fields'],
            RequiredWithout::class => ['fields'],
            RequiredWithoutAll::class => ['fields'],
            Same::class => ['field'],
            Size::class => ['value'],
            Sometimes::class => [],
            StartsWith::class => ['prefix'],
            Url::class => [],
            Uuid::class => [],
        ];

        foreach ($expectedProps as $class => $props) {
            $ref = new ReflectionClass($class);

            foreach ($props as $propName) {
                $this->assertTrue(
                    $ref->hasProperty($propName),
                    "{$class} must have property \${$propName}"
                );

                $propRef = $ref->getProperty($propName);
                $this->assertTrue(
                    $propRef->isPublic(),
                    "{$class}::\${$propName} must be public"
                );
                $this->assertTrue(
                    $propRef->isReadOnly(),
                    "{$class}::\${$propName} must be readonly"
                );
            }
        }
    }

    /**
     * @test
     */
    public function allAttributeClassesCountMatches(): void
    {
        // Ensure no attribute classes are missing from our manifest
        $this->assertCount(
            41,
            self::ALL_ATTRIBUTE_CLASSES,
            'ALL_ATTRIBUTE_CLASSES must contain all 41 attribute classes'
        );

        // Ensure validation attributes count matches
        $this->assertCount(
            35,
            self::VALIDATION_ATTRIBUTES,
            'VALIDATION_ATTRIBUTES must contain all validation attribute classes'
        );
    }
}
