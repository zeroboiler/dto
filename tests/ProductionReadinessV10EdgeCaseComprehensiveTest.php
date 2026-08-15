<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EdgeCaseDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AllScalarTypesDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

/**
 * Production readiness V10 — comprehensive structural and edge-case audit.
 *
 * Validates:
 *
 * 1. All 41 validation attributes implement ValidationAttribute and have ruleKey()
 * 2. All 4 metadata attributes (Hidden, MapFrom, Cast, DefaultValue) exist and are well-formed
 * 3. DataTransferObject abstract class has complete public API
 * 4. DtoCollection implements correct interfaces (ArrayAccess, Countable, IteratorAggregate, JsonSerializable)
 * 5. DTOException named constructors produce correct messages
 * 6. Action-scoped rulesFor() delegation
 * 7. NoConstructorDTO handles empty metadata gracefully
 * 8. AllDefaultsDTO isEmpty() / isNotEmpty() behavior
 * 9. EmptyDTO null property handling
 * 10. NullableRoundtripDTO preserves null through roundtrip
 * 11. MapFrom attribute on various fixtures
 * 12. Hidden attribute exclusion in toArray vs allValues
 * 13. All fixture DTOs are parseable (no syntax errors)
 * 14. All fixture DTOs extend DataTransferObject
 * 15. Rules deduplication: no duplicate rules in resolved metadata
 * 16. fromPartialArray with empty array uses defaults
 * 17. fromArray with empty array on all-defaults DTO
 * 18. Structural: every fixture has declare(strict_types=1)
 * 19. Structural: every fixture has license header
 * 20. Structural: every fixture uses readonly promoted properties
 * 21. Structural: DTOManager is final readonly
 * 22. Structural: Facade resolves correct accessor
 */
#[CoversNothing]
final class ProductionReadinessV10EdgeCaseComprehensiveTest extends TestCase
{
    /** @var non-empty-string */
    private const SRC_DIR = __DIR__.'/../src';
    /** @var non-empty-string */
    private const FIXTURES_DIR = __DIR__.'/Fixtures';

    // -----------------------------------------------------------------------
    // 1. All 41 validation attributes implement ValidationAttribute
    // -----------------------------------------------------------------------

    public function test_all_validation_attributes_implement_contract(): void
    {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Uuid::class, Pattern::class, In::class,
            Integer::class, Numeric::class, Boolean::class,
            Date::class, ArrayRule::class, Json::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
            Confirmed::class, Same::class, Different::class, Between::class,
            Prohibited::class, Present::class, Declined::class, Accepted::class,
            Nullable::class, Sometimes::class, Distinct::class,
            Size::class, StartsWith::class, EndsWith::class,
            RequiredIf::class, RequiredUnless::class,
            RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class,
        ];

        foreach ($validationAttributes as $attrClass) {
            $this->assertTrue(
                is_a($attrClass, ValidationAttribute::class, true),
                "{$attrClass} must implement ValidationAttribute interface."
            );

            $ref = new \ReflectionClass($attrClass);
            $this->assertTrue(
                $ref->hasMethod('ruleKey'),
                "{$attrClass} must have a ruleKey() method."
            );

            $method = $ref->getMethod('ruleKey');
            $this->assertSame(
                'string',
                $method->getReturnType()?->getName(),
                "{$attrClass}::ruleKey() must return string."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 2. Metadata attributes exist and are well-formed
    // -----------------------------------------------------------------------

    public function test_metadata_attributes_exist(): void
    {
        $this->assertTrue(class_exists(Hidden::class));
        $this->assertTrue(class_exists(MapFrom::class));
        $this->assertTrue(class_exists(Cast::class));
        $this->assertTrue(class_exists(DefaultValue::class));
        $this->assertTrue(class_exists(NestedArray::class));
        $this->assertTrue(class_exists(Collection::class));
    }

    public function test_hidden_attribute_is_attribute(): void
    {
        $ref = new \ReflectionClass(Hidden::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        $this->assertNotEmpty($attrs, 'Hidden must be an Attribute.');
    }

    public function test_map_from_has_readonly_key(): void
    {
        $ref = new \ReflectionClass(MapFrom::class);
        $prop = $ref->getProperty('key');
        $this->assertTrue($prop->isReadOnly(), 'MapFrom::$key must be readonly.');
        $this->assertTrue($prop->isPublic(), 'MapFrom::$key must be public.');
    }

    // -----------------------------------------------------------------------
    // 3. DataTransferObject has complete public API
    // -----------------------------------------------------------------------

    public function test_dto_has_complete_public_api(): void
    {
        $ref = new \ReflectionClass(DataTransferObject::class);
        $requiredMethods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromJson',
            'toArray', 'toJson', 'jsonSerialize', 'allValues',
            'only', 'except', 'with', 'equals', 'isEmpty', 'isNotEmpty',
            'rules', 'rulesFor', 'validateArray', 'validatePartialArray',
            'fromPartialRequest', 'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "DataTransferObject is missing method: {$method}()"
            );
        }
    }

    public function test_dto_is_abstract(): void
    {
        $ref = new \ReflectionClass(DataTransferObject::class);
        $this->assertTrue($ref->isAbstract(), 'DataTransferObject must be abstract.');
    }

    public function test_dto_implements_arrayable(): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Support\Arrayable::class, MinimalDTO::class);
    }

    // -----------------------------------------------------------------------
    // 4. DtoCollection interfaces
    // -----------------------------------------------------------------------

    public function test_dto_collection_implements_interfaces(): void
    {
        $ref = new \ReflectionClass(DtoCollection::class);

        $this->assertTrue($ref->implementsInterface(\ArrayAccess::class));
        $this->assertTrue($ref->implementsInterface(\Countable::class));
        $this->assertTrue($ref->implementsInterface(\IteratorAggregate::class));
        $this->assertTrue($ref->implementsInterface(\JsonSerializable::class));
    }

    public function test_dto_collection_is_final(): void
    {
        $ref = new \ReflectionClass(DtoCollection::class);
        $this->assertTrue($ref->isFinal(), 'DtoCollection must be final.');
    }

    public function test_dto_collection_has_clone_guard(): void
    {
        $ref = new \ReflectionClass(DtoCollection::class);
        $method = $ref->getMethod('__clone');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('never', $returnType->getName());
    }

    // -----------------------------------------------------------------------
    // 5. DTOException named constructors
    // -----------------------------------------------------------------------

    public function test_dto_exception_invalid_cast_message(): void
    {
        $exception = DTOException::invalidCast('status', 'integer', 'abc');
        $this->assertStringContainsString('status', $exception->getMessage());
        $this->assertStringContainsString('integer', $exception->getMessage());
    }

    public function test_dto_exception_invalid_json_message(): void
    {
        $exception = DTOException::invalidJson('payload', 'Syntax error');
        $this->assertStringContainsString('payload', $exception->getMessage());
        $this->assertStringContainsString('Syntax error', $exception->getMessage());
    }

    public function test_dto_exception_to_string(): void
    {
        $exception = DTOException::invalidJson('field', 'error');
        $string = (string) $exception;
        $this->assertStringContainsString(DTOException::class, $string);
    }

    // -----------------------------------------------------------------------
    // 6. Action-scoped rulesFor()
    // -----------------------------------------------------------------------

    public function test_action_scoped_rules_for_create(): void
    {
        $rules = ActionScopedDTO::rulesFor('create');
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    public function test_action_scoped_rules_for_update(): void
    {
        $rules = ActionScopedDTO::rulesFor('update');
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        // update rules should use 'sometimes' instead of 'required'
        $this->assertContains('sometimes', $rules['email']);
    }

    public function test_action_scoped_rules_for_unknown_returns_base(): void
    {
        $rules = ActionScopedDTO::rulesFor('unknown_action');
        $this->assertSame(ActionScopedDTO::rules(), $rules);
    }

    // -----------------------------------------------------------------------
    // 7. NoConstructorDTO empty metadata
    // -----------------------------------------------------------------------

    public function test_no_constructor_dto_empty_metadata(): void
    {
        $rules = NoConstructorDTO::rules();
        $this->assertEmpty($rules);
    }

    public function test_no_constructor_dto_from_array_empty(): void
    {
        $dto = NoConstructorDTO::fromArray([], validate: false);
        $this->assertInstanceOf(NoConstructorDTO::class, $dto);
        $this->assertEmpty($dto->toArray());
    }

    public function test_no_constructor_dto_is_empty(): void
    {
        $dto = NoConstructorDTO::fromArray([], validate: false);
        $this->assertTrue($dto->isEmpty());
        $this->assertFalse($dto->isNotEmpty());
    }

    // -----------------------------------------------------------------------
    // 8. AllDefaultsDTO isEmpty/isNotEmpty behavior
    // -----------------------------------------------------------------------

    public function test_all_defaults_dto_is_empty_with_defaults(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        // All properties have default values; isEmpty checks if meaningful data present
        // count=0 is NOT empty (it's a valid value)
        // name='default-name' is NOT empty
        // active=false IS empty
        // items=[] IS empty
        // token is hidden
        $this->assertFalse($dto->isEmpty()); // name is non-empty string
    }

    public function test_all_defaults_dto_all_values_includes_hidden(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        $all = $dto->allValues();
        $this->assertArrayHasKey('token', $all);
        $this->assertSame('hidden-secret', $all['token']);
    }

    public function test_all_defaults_dto_to_array_excludes_hidden(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);
        $visible = $dto->toArray();
        $this->assertArrayNotHasKey('token', $visible);
    }

    // -----------------------------------------------------------------------
    // 9. EmptyDTO null property handling
    // -----------------------------------------------------------------------

    public function test_empty_dto_with_nulls(): void
    {
        $dto = EmptyDTO::fromArray(['foo' => null, 'bar' => null], validate: false);
        $this->assertNull($dto->foo);
        $this->assertNull($dto->bar);
        $this->assertTrue($dto->isEmpty());
    }

    // -----------------------------------------------------------------------
    // 10. NullableRoundtripDTO preserves null
    // -----------------------------------------------------------------------

    public function test_nullable_roundtrip_preserves_null(): void
    {
        $dto = NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
            'nickname' => null,
            'email' => null,
        ], validate: false);

        $arr = $dto->toArray();
        $this->assertNull($arr['nickname']);
        $this->assertNull($arr['email']);
    }

    public function test_nullable_roundtrip_with_returns_new_instance(): void
    {
        $dto1 = NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
            'nickname' => 'Ali',
        ], validate: false);

        $dto2 = $dto1->with(['nickname' => 'Ally']);

        $this->assertNotSame($dto1, $dto2);
        $this->assertSame('Ali', $dto1->nickname);
        $this->assertSame('Ally', $dto2->nickname);
    }

    // -----------------------------------------------------------------------
    // 11. MapFrom attribute
    // -----------------------------------------------------------------------

    public function test_map_from_resolves_correctly(): void
    {
        $rules = CreateUserDTO::rules();
        // MapFrom only changes source key; rules should still use property name
        $this->assertArrayHasKey('phone', $rules);
    }

    public function test_dot_notation_map_from(): void
    {
        // EdgeCaseDTO has #[MapFrom('meta.avatar')]
        $rules = EdgeCaseDTO::rules();
        $this->assertArrayHasKey('avatar', $rules);
    }

    // -----------------------------------------------------------------------
    // 12. Hidden attribute exclusion
    // -----------------------------------------------------------------------

    public function test_hidden_excluded_from_to_array(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $arr = $dto->toArray();
        $this->assertArrayNotHasKey('password', $arr);
    }

    public function test_hidden_included_in_all_values(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'password' => 'secret',
        ], validate: false);

        $all = $dto->allValues();
        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret', $all['password']);
    }

    // -----------------------------------------------------------------------
    // 13-14. All fixture DTOs parseable and extend DataTransferObject
    // -----------------------------------------------------------------------

    public function test_all_fixture_dtos_are_parseable(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');

        foreach ($fixtures as $file) {
            $contents = file_get_contents($file);
            $tokens = token_get_all($contents);
            $errors = [];

            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_ERROR) {
                    $errors[] = $token[1];
                }
            }

            $this->assertEmpty(
                $errors,
                "Fixture file {$file} has parse error(s): ".implode(', ', $errors)
            );
        }
    }

    public function test_all_fixture_dtos_extend_base(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');

        foreach ($fixtures as $file) {
            $contents = file_get_contents($file);
            // Should extend DataTransferObject
            $this->assertStringContainsString(
                'extends DataTransferObject',
                $contents,
                basename($file).' must extend DataTransferObject.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 15. Rules deduplication
    // -----------------------------------------------------------------------

    public function test_rules_no_duplicate_string_rules(): void
    {
        $fixtures = [
            CreateUserDTO::class,
            EdgeCaseDTO::class,
            RegistrationDTO::class,
            ComprehensiveDTO::class,
            AllScalarTypesDTO::class,
            ValidationTestDTO::class,
            StrictValidationDTO::class,
        ];

        foreach ($fixtures as $dtoClass) {
            $rules = $dtoClass::rules();

            foreach ($rules as $field => $fieldRules) {
                $stringRules = array_filter(
                    $fieldRules,
                    static fn (mixed $r): bool => is_string($r)
                );

                $unique = array_unique($stringRules);
                $this->assertSameSize(
                    $stringRules,
                    $unique,
                    "Duplicate string rules found in {$dtoClass}::\${$field}: "
                    .implode(', ', $stringRules)
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // 16-17. Partial array handling
    // -----------------------------------------------------------------------

    public function test_from_partial_array_empty_uses_defaults(): void
    {
        $dto = AllDefaultsDTO::fromPartialArray([], validate: false);

        $this->assertSame('default-name', $dto->name);
        $this->assertSame(0, $dto->count);
        $this->assertFalse($dto->active);
        $this->assertSame([], $dto->items);
    }

    public function test_from_array_empty_on_all_defaults(): void
    {
        $dto = AllDefaultsDTO::fromArray([], validate: false);

        $this->assertSame('default-name', $dto->name);
        $this->assertSame(0, $dto->count);
    }

    public function test_from_partial_array_merges_provided(): void
    {
        $dto = AllDefaultsDTO::fromPartialArray([
            'name' => 'custom-name',
        ], validate: false);

        $this->assertSame('custom-name', $dto->name);
        $this->assertSame(0, $dto->count); // default
    }

    // -----------------------------------------------------------------------
    // 18. strict_types in all fixtures
    // -----------------------------------------------------------------------

    public function test_all_fixture_files_declare_strict_types(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');

        foreach ($fixtures as $file) {
            $contents = file_get_contents($file);
            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                basename($file).' is missing declare(strict_types=1).'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 19. License header in all fixtures
    // -----------------------------------------------------------------------

    public function test_all_fixture_files_have_license_header(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');

        foreach ($fixtures as $file) {
            $contents = file_get_contents($file);
            $this->assertStringContainsString(
                'This file is part of ZeroBoiler, licensed under the proprietary license.',
                $contents,
                basename($file).' is missing license header.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 20. Readonly promoted properties in fixtures
    // -----------------------------------------------------------------------

    public function test_fixture_dtos_use_readonly_properties(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');

        foreach ($fixtures as $file) {
            $contents = file_get_contents($file);
            // Skip NoConstructorDTO which has no properties
            if (str_contains($contents, 'Intentionally no constructor')) {
                continue;
            }

            // Should have at least one public readonly property
            $hasReadonly = preg_match('/public\s+readonly\s+/', $contents);
            $this->assertTrue(
                (bool) $hasReadonly,
                basename($file).' should use public readonly promoted properties.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 21. DTOManager is final readonly
    // -----------------------------------------------------------------------

    public function test_dto_manager_is_final_readonly(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        $this->assertTrue($ref->isFinal());
        $this->assertTrue($ref->isReadOnly());
    }

    // -----------------------------------------------------------------------
    // 22. Facade resolves correct accessor
    // -----------------------------------------------------------------------

    public function test_dto_facade_resolves_correct_accessor(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        $method = $ref->getMethod('getFacadeAccessor');
        $this->assertTrue($method->isStatic());

        // Use reflection to invoke the method
        $result = $method->invoke(null);
        $this->assertSame('zeroboiler.dto', $result);
    }

    // -----------------------------------------------------------------------
    // 23. equals() method
    // -----------------------------------------------------------------------

    public function test_equals_same_data(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => 'b'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'a', 'value' => 'b'], validate: false);
        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_different_data(): void
    {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => 'b'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'x', 'value' => 'y'], validate: false);
        $this->assertFalse($dto1->equals($dto2));
    }

    // -----------------------------------------------------------------------
    // 24. only() / except()
    // -----------------------------------------------------------------------

    public function test_only_single_key(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');
        $this->assertSame(['email' => 'a@b.com'], $result);
    }

    public function test_only_multiple_keys(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only(['email', 'name']);
        $this->assertSame(
            ['email' => 'a@b.com', 'name' => 'Test'],
            $result
        );
    }

    public function test_except_single_key(): void
    {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email');
        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
    }

    // -----------------------------------------------------------------------
    // 25. JSON serialization
    // -----------------------------------------------------------------------

    public function test_to_json_produces_valid_json(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '123'], validate: false);
        $json = $dto->toJson();
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('test', $decoded['name']);
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '123'], validate: false);
        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    // -----------------------------------------------------------------------
    // 26. ValidationAttribute ruleKey consistency
    // -----------------------------------------------------------------------

    public function test_validation_attribute_rule_keys_are_valid_laravel_rules(): void
    {
        $attributes = [
            Required::class => 'required',
            Email::class => 'email',
            Max::class => 'max',
            Min::class => 'min',
            Url::class => 'url',
            Uuid::class => 'uuid',
            Integer::class => 'integer',
            Numeric::class => 'numeric',
            Boolean::class => 'boolean',
            Confirmed::class => 'confirmed',
            Prohibited::class => 'prohibited',
            Present::class => 'present',
            Declined::class => 'declined',
            Accepted::class => 'accepted',
            Nullable::class => 'nullable',
            Sometimes::class => 'sometimes',
            Distinct::class => 'distinct',
            Json::class => 'json',
        ];

        foreach ($attributes as $attrClass => $expectedKey) {
            $instance = new (\ReflectionClass($attrClass))();
            $this->assertSame(
                $expectedKey,
                $instance->ruleKey(),
                "{$attrClass}::ruleKey() should return '{$expectedKey}'."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 27. Contracts are complete
    // -----------------------------------------------------------------------

    public function test_validatable_dto_contract(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class);
        $this->assertTrue($ref->isInterface());
        $this->assertTrue($ref->hasMethod('rules'));
        $this->assertTrue($ref->hasMethod('rulesFor'));
    }

    public function test_from_request_dto_contract(): void
    {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class);
        $this->assertTrue($ref->isInterface());
        $this->assertTrue($ref->hasMethod('fromRequest'));
    }

    // -----------------------------------------------------------------------
    // 28. DtoCollection make/first/last/isEmpty/count
    // -----------------------------------------------------------------------

    public function test_dto_collection_make_empty(): void
    {
        $col = DtoCollection::make([]);
        $this->assertCount(0, $col);
        $this->assertTrue($col->isEmpty());
        $this->assertNull($col->first());
        $this->assertNull($col->last());
    }

    // -----------------------------------------------------------------------
    // 29. Fixture count matches expectation
    // -----------------------------------------------------------------------

    public function test_fixture_count(): void
    {
        $fixtures = glob(self::FIXTURES_DIR.'/*.php');
        $this->assertCount(
            41,
            $fixtures,
            'Expected 41 fixture DTOs, found '.count($fixtures).'.'
        );
    }

    // -----------------------------------------------------------------------
    // 30. Source file count
    // -----------------------------------------------------------------------

    public function test_source_file_count(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::SRC_DIR, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        $this->assertCount(
            55,
            $files,
            'Expected 55 source files in src/, found '.count($files).'.'
        );
    }
}
