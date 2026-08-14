<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

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
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Comprehensive attribute contract completeness test.
 *
 * Verifies every attribute class in the DTO package is:
 * 1. `final` (prevents uncontrolled inheritance)
 * 2. Has `declare(strict_types=1)` (strict typing)
 * 3. ValidationAttribute implementations return non-empty string from `ruleKey()`
 * 4. Metadata-only attributes do NOT implement ValidationAttribute
 * 5. All constructor parameters are `public readonly` (promoted)
 * 6. All attributes target `Attribute::TARGET_PROPERTY` (or TARGET_PARAMETER for DefaultValue)
 */
final class AttributeContractCompletenessTest extends TestCase
{
    /**
     * @return array<string, array{class: class-string, isValidation: bool, expectedRuleKey: string|null}>
     */
    public static function attributeProvider(): array
    {
        return [
            // Validation attributes (implement ValidationAttribute)
            'Required' => ['class' => Required::class, 'isValidation' => true, 'expectedRuleKey' => 'required'],
            'Email' => ['class' => Email::class, 'isValidation' => true, 'expectedRuleKey' => 'email'],
            'Max' => ['class' => Max::class, 'isValidation' => true, 'expectedRuleKey' => 'max'],
            'Min' => ['class' => Min::class, 'isValidation' => true, 'expectedRuleKey' => 'min'],
            'Url' => ['class' => Url::class, 'isValidation' => true, 'expectedRuleKey' => 'url'],
            'Uuid' => ['class' => Uuid::class, 'isValidation' => true, 'expectedRuleKey' => 'uuid'],
            'Pattern' => ['class' => Pattern::class, 'isValidation' => true, 'expectedRuleKey' => 'regex'],
            'In' => ['class' => In::class, 'isValidation' => true, 'expectedRuleKey' => 'in'],
            'Integer' => ['class' => Integer::class, 'isValidation' => true, 'expectedRuleKey' => 'integer'],
            'Numeric' => ['class' => Numeric::class, 'isValidation' => true, 'expectedRuleKey' => 'numeric'],
            'Boolean' => ['class' => Boolean::class, 'isValidation' => true, 'expectedRuleKey' => 'boolean'],
            'Date' => ['class' => Date::class, 'isValidation' => true, 'expectedRuleKey' => 'date'],
            'Json' => ['class' => Json::class, 'isValidation' => true, 'expectedRuleKey' => 'json'],
            'ArrayRule' => ['class' => ArrayRule::class, 'isValidation' => true, 'expectedRuleKey' => 'array'],
            'Enum' => ['class' => Enum::class, 'isValidation' => true, 'expectedRuleKey' => 'enum'],
            'NestedArray' => ['class' => NestedArray::class, 'isValidation' => true, 'expectedRuleKey' => 'array'],
            'Collection' => ['class' => Collection::class, 'isValidation' => true, 'expectedRuleKey' => 'array'],
            'Confirmed' => ['class' => Confirmed::class, 'isValidation' => true, 'expectedRuleKey' => 'confirmed'],
            'Same' => ['class' => Same::class, 'isValidation' => true, 'expectedRuleKey' => 'same'],
            'Different' => ['class' => Different::class, 'isValidation' => true, 'expectedRuleKey' => 'different'],
            'Distinct' => ['class' => Distinct::class, 'isValidation' => true, 'expectedRuleKey' => 'distinct'],
            'Prohibited' => ['class' => Prohibited::class, 'isValidation' => true, 'expectedRuleKey' => 'prohibited'],
            'Accepted' => ['class' => Accepted::class, 'isValidation' => true, 'expectedRuleKey' => 'accepted'],
            'Declined' => ['class' => Declined::class, 'isValidation' => true, 'expectedRuleKey' => 'declined'],
            'Present' => ['class' => Present::class, 'isValidation' => true, 'expectedRuleKey' => 'present'],
            'Nullable' => ['class' => Nullable::class, 'isValidation' => true, 'expectedRuleKey' => 'nullable'],
            'Sometimes' => ['class' => Sometimes::class, 'isValidation' => true, 'expectedRuleKey' => 'sometimes'],
            'Size' => ['class' => Size::class, 'isValidation' => true, 'expectedRuleKey' => 'size'],
            'StartsWith' => ['class' => StartsWith::class, 'isValidation' => true, 'expectedRuleKey' => 'starts_with'],
            'EndsWith' => ['class' => EndsWith::class, 'isValidation' => true, 'expectedRuleKey' => 'ends_with'],
            'Between' => ['class' => Between::class, 'isValidation' => true, 'expectedRuleKey' => 'between'],
            'RequiredIf' => ['class' => RequiredIf::class, 'isValidation' => true, 'expectedRuleKey' => 'required_if'],
            'RequiredUnless' => ['class' => RequiredUnless::class, 'isValidation' => true, 'expectedRuleKey' => 'required_unless'],
            'RequiredWith' => ['class' => RequiredWith::class, 'isValidation' => true, 'expectedRuleKey' => 'required_with'],
            'RequiredWithAll' => ['class' => RequiredWithAll::class, 'isValidation' => true, 'expectedRuleKey' => 'required_with_all'],
            'RequiredWithout' => ['class' => RequiredWithout::class, 'isValidation' => true, 'expectedRuleKey' => 'required_without'],
            'RequiredWithoutAll' => ['class' => RequiredWithoutAll::class, 'isValidation' => true, 'expectedRuleKey' => 'required_without_all'],

            // Metadata-only attributes (do NOT implement ValidationAttribute)
            'Hidden' => ['class' => Hidden::class, 'isValidation' => false, 'expectedRuleKey' => null],
            'MapFrom' => ['class' => MapFrom::class, 'isValidation' => false, 'expectedRuleKey' => null],
            'Cast' => ['class' => Cast::class, 'isValidation' => false, 'expectedRuleKey' => null],
            'DefaultValue' => ['class' => DefaultValue::class, 'isValidation' => false, 'expectedRuleKey' => null],
        ];
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_is_final_class(string $class): void
    {
        $ref = new ReflectionClass($class);

        $this->assertTrue(
            $ref->isFinal(),
            "{$class} must be final to prevent uncontrolled inheritance"
        );
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_has_strict_types(string $class): void
    {
        $filename = (new ReflectionClass($class))->getFileName();
        $contents = is_string($filename) ? file_get_contents($filename) : '';

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(
            'declare(strict_types=1)',
            $contents,
            "{$class} must declare strict_types=1"
        );
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_has_correct_validation_interface(string $class, bool $isValidation): void
    {
        $implements = array_keys((new ReflectionClass($class))->getInterfaces());

        if ($isValidation) {
            $this->assertContains(
                ValidationAttribute::class,
                $implements,
                "{$class} should implement ValidationAttribute"
            );
        } else {
            $this->assertNotContains(
                ValidationAttribute::class,
                $implements,
                "{$class} should NOT implement ValidationAttribute (metadata-only)"
            );
        }
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_has_correct_rule_key(string $class, bool $isValidation, ?string $expectedRuleKey): void
    {
        if (! $isValidation || $expectedRuleKey === null) {
            $this->assertTrue(true); // Skip metadata-only attributes

            return;
        }

        $instance = new ($class)();
        $this->assertInstanceOf(ValidationAttribute::class, $instance);
        $this->assertSame($expectedRuleKey, $instance->ruleKey());
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_has_attribute_target_property(string $class): void
    {
        $ref = new ReflectionClass($class);
        $attrs = $ref->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attrs, "{$class} must have #[Attribute] declaration");

        $attribute = $attrs[0]->newInstance();
        $flags = $attribute->getFlags();

        // All DTO attributes target TARGET_PROPERTY
        // DefaultValue also targets TARGET_PARAMETER
        $this->assertNotEmpty($flags & \Attribute::TARGET_PROPERTY);
    }

    /**
     * @test
     * @dataProvider attributeProvider
     */
    public function it_has_readonly_promoted_properties(string $class): void
    {
        $ref = new ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            // Hidden has no constructor
            $this->assertTrue(true);

            return;
        }

        foreach ($constructor->getParameters() as $param) {
            if (! $ref->hasProperty($param->getName())) {
                continue;
            }

            $prop = $ref->getProperty($param->getName());
            $this->assertTrue(
                $prop->isReadOnly(),
                "{$class}::\${$param->getName()} must be readonly"
            );
            $this->assertTrue(
                $prop->isPublic(),
                "{$class}::\${$param->getName()} must be public (promoted)"
            );
        }
    }

    /**
     * Verify all validation attributes have a nullable `message` property for custom error messages.
     *
     * @test
     */
    public function all_validation_attributes_have_message_property(): void
    {
        $validationClasses = [
            Required::class, Email::class, Max::class, Min::class, Url::class, Uuid::class,
            Pattern::class, Integer::class, Numeric::class, Boolean::class, Date::class,
            Json::class, Enum::class, NestedArray::class, Collection::class,
            Confirmed::class, Same::class, Different::class, Distinct::class,
            Prohibited::class, Accepted::class, Declined::class, Present::class,
            Nullable::class, Sometimes::class, Size::class, StartsWith::class,
            EndsWith::class, Between::class, RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class, RequiredWithout::class,
            RequiredWithoutAll::class,
        ];

        foreach ($validationClasses as $class) {
            $ref = new ReflectionClass($class);
            $props = $ref->getProperties();

            $messageProp = null;
            foreach ($props as $prop) {
                if ($prop->getName() === 'message') {
                    $messageProp = $prop;
                    break;
                }
            }

            $this->assertNotNull(
                $messageProp,
                "{$class} must have a \$message property for custom validation messages"
            );

            // Verify the type allows null (nullable ?string $message = null)
            $type = $messageProp->getType();
            $this->assertNotNull($type, "{$class}::\$message must have a declared type");
            $this->assertTrue(
                $type->allowsNull(),
                "{$class}::\$message must be nullable (?string)"
            );
        }
    }

    /**
     * Verify metadata-only attributes do NOT have a `message` property.
     *
     * @test
     */
    public function metadata_attributes_have_no_message_property(): void
    {
        $metadataClasses = [Hidden::class, MapFrom::class, Cast::class, DefaultValue::class];

        foreach ($metadataClasses as $class) {
            $ref = new ReflectionClass($class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                $this->assertNotSame(
                    'message',
                    $prop->getName(),
                    "{$class} should NOT have a \$message property (metadata-only attribute)"
                );
            }
        }
    }

    /**
     * Verify the attribute count matches expectations.
     *
     * @test
     */
    public function attribute_count_matches_documentation(): void
    {
        // 37 validation + 4 metadata = 41 total
        $allProviderKeys = array_keys(self::attributeProvider());
        $this->assertCount(41, $allProviderKeys, 'Attribute provider should list all 41 attributes');

        $validationCount = 0;
        $metadataCount = 0;
        foreach (self::attributeProvider() as $data) {
            if ($data['isValidation']) {
                $validationCount++;
            } else {
                $metadataCount++;
            }
        }

        $this->assertSame(37, $validationCount, 'Should have 37 validation attributes');
        $this->assertSame(4, $metadataCount, 'Should have 4 metadata attributes');
    }
}
