<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Tests for contract interface compliance — ensures all DTO contracts
 * are properly implemented and have correct type signatures.
 */
final class DtoContractInterfaceComplianceTest extends TestCase
{
    // ---------------------------------------------------------------
    // FromRequestDTO compliance
    // ---------------------------------------------------------------

    public function test_dto_implements_from_request_dto(): void
    {
        $this->assertInstanceOf(FromRequestDTO::class, new class extends DataTransferObject {
            public function __construct() {}
        });
    }

    public function test_create_user_dto_implements_from_request_dto(): void
    {
        $this->assertContains(
            FromRequestDTO::class,
            class_implements(CreateUserDTO::class) ?: []
        );
    }

    public function test_from_request_dto_method_has_static_return_type(): void
    {
        $ref = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        $returnType = $ref->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('static', $returnType->getName());
    }

    public function test_from_request_dto_method_accepts_request_and_bool(): void
    {
        $ref = new ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);

        // First param: Request
        $this->assertSame('request', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('request', $type->getName());

        // Second param: bool with default
        $this->assertSame('validate', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
    }

    // ---------------------------------------------------------------
    // ValidatableDTO compliance
    // ---------------------------------------------------------------

    public function test_dto_implements_validatable_dto(): void
    {
        $this->assertInstanceOf(ValidatableDTO::class, new class extends DataTransferObject {
            public function __construct() {}
        });
    }

    public function test_create_user_dto_implements_validatable_dto(): void
    {
        $this->assertContains(
            ValidatableDTO::class,
            class_implements(CreateUserDTO::class) ?: []
        );
    }

    public function test_rules_method_returns_array(): void
    {
        $rules = CreateUserDTO::rules();

        $this->assertIsArray($rules);
    }

    public function test_rules_for_returns_array(): void
    {
        $rules = CreateUserDTO::rulesFor('create');

        $this->assertIsArray($rules);
        $this->assertSame(CreateUserDTO::rules(), $rules);
    }

    public function test_rules_for_update_matches_create_by_default(): void
    {
        $createRules = CreateUserDTO::rulesFor('create');
        $updateRules = CreateUserDTO::rulesFor('update');

        $this->assertSame($createRules, $updateRules);
    }

    public function test_rules_method_return_type_is_strict(): void
    {
        $ref = new ReflectionMethod(ValidatableDTO::class, 'rules');
        $returnType = $ref->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function test_rules_for_method_return_type_is_strict(): void
    {
        $ref = new ReflectionMethod(ValidatableDTO::class, 'rulesFor');
        $returnType = $ref->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    // ---------------------------------------------------------------
    // ValidationAttribute contract
    // ---------------------------------------------------------------

    public function test_validation_attribute_implements_rule_key(): void
    {
        $ref = new ReflectionMethod(ValidationAttribute::class, 'ruleKey');
        $returnType = $ref->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function test_all_validation_attributes_implement_contract(): void
    {
        $attributeClasses = [
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\Between::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            \ZeroBoiler\DTO\Attributes\Date::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Collection::class,
            \ZeroBoiler\DTO\Attributes\NestedArray::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
        ];

        foreach ($attributeClasses as $class) {
            $this->assertTrue(
                in_array(ValidationAttribute::class, class_implements($class) ?: [], true),
                "{$class} must implement ValidationAttribute"
            );
        }
    }

    public function test_all_validation_attributes_return_string_rule_key(): void
    {
        $attributes = [
            new \ZeroBoiler\DTO\Attributes\Required,
            new \ZeroBoiler\DTO\Attributes\Email,
            new \ZeroBoiler\DTO\Attributes\Url,
            new \ZeroBoiler\DTO\Attributes\Max(100),
            new \ZeroBoiler\DTO\Attributes\Min(1),
            new \ZeroBoiler\DTO\Attributes\Pattern('/test/'),
            new \ZeroBoiler\DTO\Attributes\Between(1, 100),
            new \ZeroBoiler\DTO\Attributes\Integer,
            new \ZeroBoiler\DTO\Attributes\Numeric,
            new \ZeroBoiler\DTO\Attributes\Boolean,
            new \ZeroBoiler\DTO\Attributes\Uuid,
            new \ZeroBoiler\DTO\Attributes\Date,
            new \ZeroBoiler\DTO\Attributes\Enum(\SomeTestEnum::class),
            new \ZeroBoiler\DTO\Attributes\Confirmed,
            new \ZeroBoiler\DTO\Attributes\Different('other'),
            new \ZeroBoiler\DTO\Attributes\Same('other'),
            new \ZeroBoiler\DTO\Attributes\ArrayRule,
            new \ZeroBoiler\DTO\Attributes\Prohibited,
            new \ZeroBoiler\DTO\Attributes\Present,
            new \ZeroBoiler\DTO\Attributes\Declined,
            new \ZeroBoiler\DTO\Attributes\Accepted,
            new \ZeroBoiler\DTO\Attributes\StartsWith('prefix'),
            new \ZeroBoiler\DTO\Attributes\EndsWith('suffix'),
            new \ZeroBoiler\DTO\Attributes\Nullable,
            new \ZeroBoiler\DTO\Attributes\Sometimes,
            new \ZeroBoiler\DTO\Attributes\Distinct,
            new \ZeroBoiler\DTO\Attributes\Size(10),
            new \ZeroBoiler\DTO\Attributes\Json,
            new \ZeroBoiler\DTO\Attributes\In(['a', 'b']),
            new \ZeroBoiler\DTO\Attributes\Collection(CreateUserDTO::class),
            new \ZeroBoiler\DTO\Attributes\NestedArray(CreateUserDTO::class),
        ];

        foreach ($attributes as $attr) {
            $this->assertIsString(
                $attr->ruleKey(),
                get_class($attr).'::ruleKey() must return a string'
            );
            $this->assertNotEmpty(
                $attr->ruleKey(),
                get_class($attr).'::ruleKey() must not be empty'
            );
        }
    }

    // ---------------------------------------------------------------
    // DataTransferObject implements all interfaces
    // ---------------------------------------------------------------

    public function test_data_transfer_object_is_abstract(): void
    {
        $ref = new ReflectionClass(DataTransferObject::class);

        $this->assertTrue($ref->isAbstract());
    }

    public function test_data_transfer_object_implements_arrayable(): void
    {
        $this->assertContains(
            \Illuminate\Contracts\Support\Arrayable::class,
            class_implements(DataTransferObject::class) ?: []
        );
    }

    public function test_data_transfer_object_implements_json_serializable(): void
    {
        $this->assertContains(
            \JsonSerializable::class,
            class_implements(DataTransferObject::class) ?: []
        );
    }
}

// Minimal backed enum for testing Enum attribute
enum SomeTestEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
