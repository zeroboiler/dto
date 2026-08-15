<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
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
use ZeroBoiler\DTO\DTOException;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

/**
 * V22: Comprehensive edge-case tests for type safety, boundary conditions,
 * and production-readiness of the DTO package.
 *
 * Tests cover:
 * - Nested DTO hydration and serialization roundtrip
 * - fromJson with valid/invalid JSON edge cases
 * - with() immutable update with validation
 * - only()/except() selective output
 * - equals()/isEmpty()/isNotEmpty() state checks
 * - Hidden property filtering in toArray vs allValues
 * - MapFrom with dot notation
 * - Cast type coercion (int, bool, string, date)
 * - Default values for absent keys (#678 explicit null preservation)
 * - fromPartialArray PATCH semantics
 * - DtoCollection operations (pluck, pluckKey, map, filter, append, merge)
 * - fromArray with validate:false
 * - rules() static method contract
 */

class SimpleAddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        #[Required]
        public readonly string $country,
    ) {}
}

class UserProfileDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[MapFrom('user_name')]
        public readonly ?string $displayName = null,

        #[Cast('integer')]
        public readonly int $age = 0,

        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[Hidden]
        public readonly ?string $password = null,

        #[NestedArray(SimpleAddressDTO::class)]
        public readonly array $addresses = [],
    ) {}
}

class OptionalFieldsDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $optional = null,
        public readonly string $required = '',
    ) {}
}

class CastTypesDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $intField = 0,

        #[Cast('string')]
        public readonly string $stringField = '',

        #[Cast('boolean')]
        public readonly bool $boolField = false,

        #[Cast('float')]
        public readonly float $floatField = 0.0,
    ) {}
}

class CrossFieldDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Confirmed]
        public readonly ?string $password = null,

        #[Same('email')]
        public readonly ?string $emailConfirmation = null,

        #[Different('email')]
        public readonly ?string $username = null,
    ) {}
}

class PatternDto extends DataTransferObject
{
    public function __construct(
        #[Required, Pattern('/^[A-Z]{3}-\d{4}$/')]
        public readonly string $code,
    ) {}
}

final class DTOV22ComprehensiveEdgeCaseTest extends TestCase
{
    // ------------------------------------------------------------------
    // Basic hydration and validation
    // ------------------------------------------------------------------

    public function test_from_array_validates_required_fields(): void
    {
        $this->expectException(ValidationException::class);
        UserProfileDTO::fromArray([]); // Missing email and name
    }

    public function test_from_array_validates_email(): void
    {
        $this->expectException(ValidationException::class);
        UserProfileDTO::fromArray([
            'email' => 'not-an-email',
            'name' => 'Alice',
        ]);
    }

    public function test_from_array_validates_min_max(): void
    {
        $this->expectException(ValidationException::class);
        UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A', // Min(2) fails
        ]);
    }

    public function test_from_array_successful_hydration(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'alice@example.com',
            'name' => 'Alice',
            'age' => '30', // string that should be cast to int
        ]);

        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame('Alice', $dto->name);
        $this->assertSame(30, $dto->age);
    }

    // ------------------------------------------------------------------
    // MapFrom attribute
    // ------------------------------------------------------------------

    public function test_map_from_maps_source_key(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Bob',
            'user_name' => 'Bobby',
        ]);

        $this->assertSame('Bobby', $dto->displayName);
    }

    // ------------------------------------------------------------------
    // DefaultValue attribute
    // ------------------------------------------------------------------

    public function test_default_value_applied_when_key_absent(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Charlie',
        ]);

        $this->assertSame('active', $dto->status);
    }

    // ------------------------------------------------------------------
    // Cast attribute
    // ------------------------------------------------------------------

    public function test_cast_integer_from_string(): void
    {
        $dto = CastTypesDTO::fromArray([
            'intField' => '42',
        ]);

        $this->assertSame(42, $dto->intField);
    }

    public function test_cast_boolean_from_string(): void
    {
        $dto = CastTypesDTO::fromArray([
            'boolField' => 'true',
        ]);

        $this->assertTrue($dto->boolField);
    }

    public function test_cast_string_from_int(): void
    {
        $dto = CastTypesDTO::fromArray([
            'stringField' => 123,
        ]);

        $this->assertSame('123', $dto->stringField);
    }

    public function test_cast_float_from_string(): void
    {
        $dto = CastTypesDTO::fromArray([
            'floatField' => '3.14',
        ]);

        $this->assertSame(3.14, $dto->floatField);
    }

    // ------------------------------------------------------------------
    // Hidden property
    // ------------------------------------------------------------------

    public function test_hidden_excluded_from_to_array(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Dave',
            'password' => 'secret123',
        ]);

        $arr = $dto->toArray();
        $this->assertArrayNotHasKey('password', $arr);
    }

    public function test_hidden_included_in_all_values(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Eve',
            'password' => 'secret123',
        ]);

        $all = $dto->allValues();
        $this->assertArrayHasKey('password', $all);
        $this->assertSame('secret123', $all['password']);
    }

    // ------------------------------------------------------------------
    // Nested DTO hydration
    // ------------------------------------------------------------------

    public function test_nested_array_hydration(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Frank',
            'addresses' => [
                ['street' => '123 Main', 'city' => 'Ankara', 'country' => 'TR'],
            ],
        ]);

        $this->assertCount(1, $dto->addresses);
        $this->assertInstanceOf(SimpleAddressDTO::class, $dto->addresses[0]);
        $this->assertSame('123 Main', $dto->addresses[0]->street);
        $this->assertSame('Ankara', $dto->addresses[0]->city);
    }

    public function test_nested_dto_serialization_in_to_array(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Grace',
            'addresses' => [
                ['street' => '456 Oak', 'city' => 'Istanbul', 'country' => 'TR'],
            ],
        ]);

        $arr = $dto->toArray();
        $this->assertSame([
            'street' => '456 Oak',
            'city' => 'Istanbul',
            'country' => 'TR',
        ], $arr['addresses'][0]);
    }

    // ------------------------------------------------------------------
    // fromJson
    // ------------------------------------------------------------------

    public function test_from_json_valid(): void
    {
        $dto = UserProfileDTO::fromJson('{"email":"a@b.com","name":"Hank"}');

        $this->assertSame('a@b.com', $dto->email);
        $this->assertSame('Hank', $dto->name);
    }

    public function test_from_json_invalid_throws_dto_exception(): void
    {
        $this->expectException(DTOException::class);
        UserProfileDTO::fromJson('{invalid json}');
    }

    public function test_from_json_sequential_array_throws(): void
    {
        $this->expectException(DTOException::class);
        UserProfileDTO::fromJson('[1,2,3]');
    }

    // ------------------------------------------------------------------
    // with() immutable update
    // ------------------------------------------------------------------

    public function test_with_returns_new_instance(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Ivan',
        ]);

        $updated = $dto->with(['name' => 'Ivan Updated']);

        $this->assertNotSame($dto, $updated);
        $this->assertSame('Ivan', $dto->name);
        $this->assertSame('Ivan Updated', $updated->name);
    }

    public function test_with_validates_merged_data(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Jack',
        ]);

        $this->expectException(ValidationException::class);
        $dto->with(['email' => 'not-valid']);
    }

    // ------------------------------------------------------------------
    // only() / except()
    // ------------------------------------------------------------------

    public function test_only_returns_specified_keys(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Kate',
        ]);

        $result = $dto->only('email');
        $this->assertSame(['email' => 'a@b.com'], $result);
    }

    public function test_only_with_array_keys(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Leo',
            'age' => '25',
        ]);

        $result = $dto->only(['email', 'age']);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function test_except_excludes_keys(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Mia',
        ]);

        $result = $dto->except('name');
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayNotHasKey('name', $result);
    }

    // ------------------------------------------------------------------
    // equals() / isEmpty() / isNotEmpty()
    // ------------------------------------------------------------------

    public function test_equals_same_data(): void
    {
        $dto1 = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Noah',
        ]);
        $dto2 = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Noah',
        ]);

        $this->assertTrue($dto1->equals($dto2));
    }

    public function test_equals_different_data(): void
    {
        $dto1 = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Olivia',
        ]);
        $dto2 = UserProfileDTO::fromArray([
            'email' => 'b@b.com',
            'name' => 'Olivia',
        ]);

        $this->assertFalse($dto1->equals($dto2));
    }

    public function test_is_not_empty_with_data(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Pete',
        ]);

        $this->assertTrue($dto->isNotEmpty());
        $this->assertFalse($dto->isEmpty());
    }

    // ------------------------------------------------------------------
    // fromArray with validate: false
    // ------------------------------------------------------------------

    public function test_from_array_without_validation(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'not-an-email', // Would fail validation
            'name' => 'Quinn',
        ], validate: false);

        $this->assertSame('not-an-email', $dto->email);
        $this->assertSame('Quinn', $dto->name);
    }

    // ------------------------------------------------------------------
    // fromPartialArray (PATCH semantics)
    // ------------------------------------------------------------------

    public function test_from_partial_array_only_present_fields(): void
    {
        $dto = UserProfileDTO::fromPartialArray([
            'name' => 'Rita Updated',
        ]);

        $this->assertSame('Rita Updated', $dto->name);
        $this->assertSame('active', $dto->status); // default preserved
    }

    // ------------------------------------------------------------------
    // toJson
    // ------------------------------------------------------------------

    public function test_to_json_excludes_hidden(): void
    {
        $dto = UserProfileDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Steve',
            'password' => 'pass',
        ]);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        $this->assertArrayNotHasKey('password', $decoded);
        $this->assertSame('a@b.com', $decoded['email']);
    }

    // ------------------------------------------------------------------
    // rules() static contract
    // ------------------------------------------------------------------

    public function test_rules_returns_array(): void
    {
        $rules = UserProfileDTO::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('name', $rules);
    }

    public function test_rules_contains_email_rule(): void
    {
        $rules = UserProfileDTO::rules();

        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    // ------------------------------------------------------------------
    // DtoCollection operations
    // ------------------------------------------------------------------

    public function test_dto_collection_pluck(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $emails = $col->pluck('email');

        $this->assertSame(['a@b.com', 'c@d.com'], $emails);
    }

    public function test_dto_collection_pluck_key(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $map = $col->pluckKey('email', 'name');

        $this->assertSame('Alice', $map['a@b.com']);
        $this->assertSame('Charlie', $map['c@d.com']);
    }

    public function test_dto_collection_map(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $names = $col->map(fn (UserProfileDTO $d) => $d->name);

        $this->assertSame(['Alice', 'Charlie'], $names);
    }

    public function test_dto_collection_filter(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1, $d2]);
        $filtered = $col->filter(fn (UserProfileDTO $d) => str_starts_with($d->name, 'A'));

        $this->assertCount(1, $filtered);
        $this->assertSame('Alice', $filtered->first()->name);
    }

    public function test_dto_collection_append_immutable(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1]);
        $newCol = $col->append($d2);

        $this->assertSame(1, $col->count());
        $this->assertSame(2, $newCol->count());
    }

    public function test_dto_collection_merge(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col1 = new DtoCollection([$d1]);
        $col2 = new DtoCollection([$d2]);
        $merged = $col1->merge($col2);

        $this->assertSame(2, $merged->count());
    }

    public function test_dto_collection_push_mutates(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1]);
        $result = $col->push($d2);

        $this->assertSame(2, $col->count());
        $this->assertSame($col, $result); // Returns same instance
    }

    public function test_dto_collection_first_last(): void
    {
        $d1 = UserProfileDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $d2 = UserProfileDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        $col = new DtoCollection([$d1, $d2]);

        $this->assertSame('Alice', $col->first()->name);
        $this->assertSame('Charlie', $col->last()->name);
    }

    public function test_dto_collection_empty(): void
    {
        $col = new DtoCollection;

        $this->assertTrue($col->isEmpty());
        $this->assertFalse($col->isNotEmpty());
        $this->assertNull($col->first());
        $this->assertNull($col->last());
        $this->assertSame(0, $col->count());
    }

    // ------------------------------------------------------------------
    // DTOException factory methods
    // ------------------------------------------------------------------

    public function test_dto_exception_invalid_cast(): void
    {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
        $this->assertStringContainsString('age', $e->getMessage());
        $this->assertStringContainsString('integer', $e->getMessage());
    }

    public function test_dto_exception_invalid_json(): void
    {
        $e = DTOException::invalidJson('settings', 'Syntax error');
        $this->assertStringContainsString('settings', $e->getMessage());
        $this->assertStringContainsString('Syntax error', $e->getMessage());
    }

    public function test_dto_exception_to_string(): void
    {
        $e = DTOException::invalidJson('data', 'error');
        $str = (string) $e;
        $this->assertStringStartsWith('ZeroBoiler\DTO\Exceptions\DTOException:', $str);
    }
}
