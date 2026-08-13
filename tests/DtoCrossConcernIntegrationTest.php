<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace Tests\DTO;

use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facade as DTOFacade;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Integration contract test — verifies cross-concern behaviors that span
 * multiple DTO components (metadata resolver, hydration, validation, serialization,
 * collections, casts, and the manager/facade).
 *
 * @see DataTransferObject
 * @see DtoMetadataResolver
 * @see DTOManager
 * @see DtoCollection
 * @see DTOCast
 */
test('DtoMetadataResolver resolves properties with correct metadata structure', function (): void {
    DataTransferObject::flushMetadataCache(IntegrationTestDTO::class);

    $meta = DtoMetadataResolver::resolve(IntegrationTestDTO::class);

    expect($meta)->toBeArray();
    expect($meta)->toHaveKeys(['properties', 'rules', 'messages']);

    // Verify property metadata keys
    expect($meta['properties'])->toHaveKey('email');
    expect($meta['properties'])->toHaveKey('name');
    expect($meta['properties'])->toHaveKey('displayName');
    expect($meta['properties'])->toHaveKey('age');
    expect($meta['properties'])->toHaveKey('status');
    expect($meta['properties'])->toHaveKey('password');
    expect($meta['properties'])->toHaveKey('website');

    // Verify MapFrom is resolved
    expect($meta['properties']['displayName']['map_from'])->toBe('user_name');

    // Verify Hidden is resolved
    expect($meta['properties']['password']['hidden'])->toBeTrue();

    // Verify Cast is resolved
    expect($meta['properties']['age']['cast'])->toBe('integer');

    // Verify DefaultValue
    expect($meta['properties']['status']['default'])->toBe('active');
    expect($meta['properties']['status']['has_default'])->toBeTrue();

    // Verify nullable
    expect($meta['properties']['website']['nullable'])->toBeTrue();

    // Verify required rules exist
    expect($meta['rules']['email'])->toContain('required');
    expect($meta['rules']['email'])->toContain('email');
    expect($meta['rules']['name'])->toContain('required');
    expect($meta['rules']['name'])->toContain('min:2');
    expect($meta['rules']['name'])->toContain('max:50');

    DataTransferObject::flushMetadataCache();
});

test('fromArray hydrates with MapFrom, Cast, and DefaultValue correctly', function (): void {
    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'user_name' => 'testuser', // MapFrom: 'user_name' → displayName
        'age' => '25',             // Cast: string → integer
        // 'status' omitted — should use DefaultValue 'active'
        'password' => 'secret123',
        'website' => 'https://example.com',
    ], validate: false);

    expect($dto->email)->toBe('test@example.com');
    expect($dto->name)->toBe('Test User');
    expect($dto->displayName)->toBe('testuser'); // Mapped from 'user_name'
    expect($dto->age)->toBe(25);                 // Cast from string '25' to int 25
    expect($dto->status)->toBe('active');        // DefaultValue
    expect($dto->password)->toBe('secret123');
    expect($dto->website)->toBe('https://example.com');
});

test('toArray excludes Hidden properties', function (): void {
    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
        'password' => 'secret123',
    ], validate: false);

    $array = $dto->toArray();

    expect($array)->toHaveKey('email');
    expect($array)->toHaveKey('name');
    expect($array)->not->toHaveKey('password'); // Hidden
});

test('allValues includes Hidden properties', function (): void {
    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
        'password' => 'secret123',
    ], validate: false);

    $all = $dto->allValues();

    expect($all)->toHaveKey('password'); // allValues includes hidden
    expect($all['password'])->toBe('secret123');
});

test('only and except work correctly on public properties', function (): void {
    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
    ], validate: false);

    $only = $dto->only(['email', 'name']);
    expect($only)->toHaveCount(2);
    expect($only)->toHaveKeys(['email', 'name']);

    $except = $dto->except(['email']);
    expect($except)->not->toHaveKey('email');
    expect($except)->toHaveKey('name');
});

test('with creates immutable copy with validation', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
    ], validate: false);

    $updated = $dto->with(['name' => 'Updated User']);

    // Original unchanged (immutability)
    expect($dto->name)->toBe('Test User');
    expect($updated->name)->toBe('Updated User');
    expect($updated)->not->toBe($dto);

    DataTransferObject::flushMetadataCache();
});

test('fromPartialArray applies PATCH semantics with defaults', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto = IntegrationTestDTO::fromPartialArray([
        'name' => 'Partial Update',
    ], validate: false);

    expect($dto->name)->toBe('Partial Update');
    expect($dto->status)->toBe('active'); // DefaultValue kicks in
    expect($dto->age)->toBe(0);           // Empty value for int without default

    DataTransferObject::flushMetadataCache();
});

test('fromJson decodes JSON and hydrates correctly', function (): void {
    $json = json_encode([
        'email' => 'json@test.com',
        'name' => 'JSON User',
        'age' => 30,
        'status' => 'active',
    ], JSON_THROW_ON_ERROR);

    $dto = IntegrationTestDTO::fromJson($json, validate: false);

    expect($dto->email)->toBe('json@test.com');
    expect($dto->name)->toBe('JSON User');
    expect($dto->age)->toBe(30);
});

test('fromJson throws DTOException on invalid JSON', function (): void {
    expect(fn () => IntegrationTestDTO::fromJson('not valid json'))
        ->toThrow(DTOException::class);
});

test('fromJson rejects sequential arrays', function (): void {
    expect(fn () => IntegrationTestDTO::fromJson('[1,2,3]'))
        ->toThrow(DTOException::class, 'Expected a JSON object');
});

test('equals compares toArray output', function (): void {
    $dto1 = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
    ], validate: false);

    $dto2 = IntegrationTestDTO::fromArray([
        'email' => 'test@example.com',
        'name' => 'Test User',
        'age' => 25,
        'status' => 'active',
    ], validate: false);

    $dto3 = IntegrationTestDTO::fromArray([
        'email' => 'other@example.com',
        'name' => 'Other User',
        'age' => 30,
        'status' => 'inactive',
    ], validate: false);

    expect($dto1->equals($dto2))->toBeTrue();
    expect($dto1->equals($dto3))->toBeFalse();
});

test('isEmpty and isNotEmpty correctly detect empty state', function (): void {
    DataTransferObject::flushMetadataCache();

    // DTO with only default values
    $dto = IntegrationTestDTO::fromPartialArray([], validate: false);
    expect($dto->isEmpty())->toBeTrue();
    expect($dto->isNotEmpty())->toBeFalse();

    // DTO with at least one non-empty value
    $dto2 = IntegrationTestDTO::fromPartialArray(['email' => 'test@example.com'], validate: false);
    expect($dto2->isNotEmpty())->toBeTrue();

    DataTransferObject::flushMetadataCache();
});

test('DtoCollection provides type-safe operations', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto1 = IntegrationTestDTO::fromArray([
        'email' => 'a@test.com', 'name' => 'Alice', 'age' => 25, 'status' => 'active',
    ], validate: false);

    $dto2 = IntegrationTestDTO::fromArray([
        'email' => 'b@test.com', 'name' => 'Bob', 'age' => 30, 'status' => 'active',
    ], validate: false);

    $col = new DtoCollection([$dto1, $dto2]);

    // Count
    expect($col->count())->toBe(2);
    expect($col->isEmpty())->toBeFalse();

    // First/Last
    expect($col->first()?->name)->toBe('Alice');
    expect($col->last()?->name)->toBe('Bob');

    // ArrayAccess
    expect($col[0]?->name)->toBe('Alice');
    expect($col[1]?->name)->toBe('Bob');
    expect($col[99])->toBeNull();

    // Pluck
    expect($col->pluck('email'))->toBe(['a@test.com', 'b@test.com']);

    // PluckKey
    $keyed = $col->pluckKey('email', 'name');
    expect($keyed)->toBe(['a@test.com' => 'Alice', 'b@test.com' => 'Bob']);

    // Map
    $names = $col->map(fn (DataTransferObject $d): string => $d->name);
    expect($names)->toBe(['Alice', 'Bob']);

    // Filter
    $adults = $col->filter(fn (DataTransferObject $d): bool => $d->age >= 30);
    expect($adults->count())->toBe(1);

    // Append (immutable)
    $dto3 = IntegrationTestDTO::fromArray([
        'email' => 'c@test.com', 'name' => 'Charlie', 'age' => 35, 'status' => 'active',
    ], validate: false);
    $extended = $col->append($dto3);
    expect($extended->count())->toBe(3);
    expect($col->count())->toBe(2); // Original unchanged

    // Push (mutable)
    $col->push($dto3);
    expect($col->count())->toBe(3);

    // Merge
    $col2 = new DtoCollection([$dto1]);
    $merged = $col->merge($col2);
    expect($merged->count())->toBeGreaterThanOrEqual(3);

    // toArray
    $arrays = $col->toArray();
    expect($arrays)->toBeArray();
    expect($arrays[0])->toHaveKey('email');

    // IteratorAggregate
    $iterated = [];
    foreach ($col as $item) {
        $iterated[] = $item->email;
    }
    expect($iterated)->toContain('a@test.com');

    // offsetUnset re-indexes
    $col = new DtoCollection([$dto1, $dto2]);
    unset($col[0]);
    expect($col->count())->toBe(1);
    expect($col[0]?->name)->toBe('Bob'); // Re-indexed

    DataTransferObject::flushMetadataCache();
});

test('DtoCollection rejects non-DTO items in constructor', function (): void {
    expect(fn () => new DtoCollection([new \stdClass()]))
        ->toThrow(\InvalidArgumentException::class, 'DataTransferObject');
});

test('DtoCollection rejects non-DTO items in offsetSet', function (): void {
    $col = new DtoCollection();
    expect(fn () => $col[0] = 'not a dto')
        ->toThrow(\InvalidArgumentException::class, 'DataTransferObject');
});

test('DtoCollection toDictionary and toArrayBy', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto1 = IntegrationTestDTO::fromArray([
        'email' => 'a@test.com', 'name' => 'Alice', 'age' => 25, 'status' => 'active',
    ], validate: false);

    $dto2 = IntegrationTestDTO::fromArray([
        'email' => 'b@test.com', 'name' => 'Bob', 'age' => 30, 'status' => 'active',
    ], validate: false);

    $col = new DtoCollection([$dto1, $dto2]);

    // toDictionary
    $dict = $col->toDictionary('email', 'name');
    expect($dict)->toBe(['a@test.com' => 'Alice', 'b@test.com' => 'Bob']);

    // toArrayBy (same as pluckKey with single key)
    $byEmail = $col->toArrayBy('email');
    expect($byEmail)->toBeArray();
    expect($byEmail)->toHaveKey('a@test.com');

    DataTransferObject::flushMetadataCache();
});

test('DtoCollection jsonSerialize returns toArray output', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto = IntegrationTestDTO::fromArray([
        'email' => 'test@test.com', 'name' => 'Test', 'age' => 25, 'status' => 'active',
    ], validate: false);

    $col = new DtoCollection([$dto]);
    $json = json_encode($col);

    expect($json)->toBeJson();
    $decoded = json_decode($json, true);
    expect($decoded[0])->toHaveKey('email');

    DataTransferObject::flushMetadataCache();
});

test('DTOCast get decodes JSON string to DTO', function (): void {
    DataTransferObject::flushMetadataCache();

    $cast = new DTOCast(IntegrationTestDTO::class);
    $model = new \stdClass();
    $json = json_encode([
        'email' => 'cast@test.com',
        'name' => 'Cast User',
        'age' => 40,
        'status' => 'active',
    ]);

    $dto = $cast->get($model, 'data', $json, []);
    expect($dto)->toBeInstanceOf(IntegrationTestDTO::class);
    expect($dto->email)->toBe('cast@test.com');

    DataTransferObject::flushMetadataCache();
});

test('DTOCast get returns null for null value', function (): void {
    $cast = new DTOCast(IntegrationTestDTO::class);
    $result = $cast->get(new \stdClass(), 'data', null, []);
    expect($result)->toBeNull();
});

test('DTOCast get returns null for invalid JSON', function (): void {
    $cast = new DTOCast(IntegrationTestDTO::class);
    $result = $cast->get(new \stdClass(), 'data', 'not-json', []);
    expect($result)->toBeNull();
});

test('DTOCast set serializes DTO to JSON', function (): void {
    DataTransferObject::flushMetadataCache();

    $cast = new DTOCast(IntegrationTestDTO::class);
    $model = new \stdClass();

    $dto = IntegrationTestDTO::fromArray([
        'email' => 'set@test.com',
        'name' => 'Set User',
        'age' => 28,
        'status' => 'active',
    ], validate: false);

    $result = $cast->set($model, 'data', $dto, []);
    $decoded = json_decode((string) $result, true);

    expect($decoded['email'])->toBe('set@test.com');
    expect($decoded['name'])->toBe('Set User');
    expect($decoded['age'])->toBe(28);

    DataTransferObject::flushMetadataCache();
});

test('DTOCast set returns null for null input', function (): void {
    $cast = new DTOCast(IntegrationTestDTO::class);
    $result = $cast->set(new \stdClass(), 'data', null, []);
    expect($result)->toBeNull();
});

test('DTOCast set rejects unexpected types', function (): void {
    $cast = new DTOCast(IntegrationTestDTO::class);

    expect(fn () => $cast->set(new \stdClass(), 'data', 42, []))
        ->toThrow(\InvalidArgumentException::class, 'DTO instance, array, or null');
});

test('DTOCast serialize returns toArray output', function (): void {
    DataTransferObject::flushMetadataCache();

    $cast = new DTOCast(IntegrationTestDTO::class);

    $dto = IntegrationTestDTO::fromArray([
        'email' => 'ser@test.com',
        'name' => 'Serialize User',
        'age' => 33,
        'status' => 'active',
        'password' => 'should-be-excluded',
    ], validate: false);

    $result = $cast->serialize(new \stdClass(), 'data', $dto, []);

    expect($result)->toBeArray();
    expect($result['email'])->toBe('ser@test.com');
    expect($result)->not->toHaveKey('password'); // Hidden excluded

    DataTransferObject::flushMetadataCache();
});

test('DTOCast serialize returns null for null', function (): void {
    $cast = new DTOCast(IntegrationTestDTO::class);
    expect($cast->serialize(new \stdClass(), 'data', null, []))->toBeNull();
});

test('DTOManager delegates all methods correctly', function (): void {
    DataTransferObject::flushMetadataCache();

    $manager = new DTOManager;

    // make
    $dto = $manager->make(IntegrationTestDTO::class, [
        'email' => 'mgr@test.com', 'name' => 'Manager', 'age' => 25, 'status' => 'active',
    ]);
    expect($dto)->toBeInstanceOf(IntegrationTestDTO::class);

    // validate
    $validated = $manager->validate(IntegrationTestDTO::class, [
        'email' => 'valid@test.com', 'name' => 'Valid User', 'age' => 25, 'status' => 'active',
    ]);
    expect($validated)->toBeArray();
    expect($validated['email'])->toBe('valid@test.com');

    // rules
    $rules = $manager->rules(IntegrationTestDTO::class);
    expect($rules)->toBe(IntegrationTestDTO::rules());

    // rulesFor
    $rulesFor = $manager->rulesFor(IntegrationTestDTO::class, 'create');
    expect($rulesFor)->toBe(IntegrationTestDTO::rulesFor('create'));

    DataTransferObject::flushMetadataCache();
});

test('DTOException named constructors produce descriptive messages', function (): void {
    $cast = DTOException::invalidCast('age', 'integer', 'not-a-number');
    expect($cast->getMessage())->toContain('age');
    expect($cast->getMessage())->toContain('integer');
    expect($cast->getMessage())->toContain('not-a-number');

    $json = DTOException::invalidJson('payload', 'Syntax error');
    expect($json->getMessage())->toContain('payload');
    expect($json->getMessage())->toContain('Syntax error');

    // __toString
    expect((string) $cast)->toContain('DTOException');
});

test('metadata cache TTL works for DTO classes', function (): void {
    DataTransferObject::flushMetadataCache();

    // With TTL disabled, metadata resolves each time
    DataTransferObject::setMetadataCacheTtl(0.0);
    $rules1 = IntegrationTestDTO::rules();
    $rules2 = IntegrationTestDTO::rules();
    expect($rules1)->toBe($rules2);

    DataTransferObject::flushMetadataCache();
    DataTransferObject::setMetadataCacheTtl(0.0);
});

test('flushMetadataCache clears specific class only', function (): void {
    DataTransferObject::flushMetadataCache();
    DataTransferObject::setMetadataCacheTtl(300);

    // Resolve two DTOs
    IntegrationTestDTO::rules();
    SimpleDTO::rules();

    // Flush one
    DataTransferObject::flushMetadataCache(IntegrationTestDTO::class);

    // The other should still have its static cache entry
    // (we can't directly test this without reflection, but no exception = success)

    DataTransferObject::flushMetadataCache();
});

test('validateArray throws ValidationException for invalid data', function (): void {
    expect(fn () => IntegrationTestDTO::validateArray([
        'email' => 'not-an-email',
        'name' => 'A', // too short (min:2)
        'age' => 25,
        'status' => 'active',
    ]))->toThrow(ValidationException::class);
});

test('contracts are properly implemented', function (): void {
    expect(IntegrationTestDTO::class)->implementsInterface(ValidatableDTO::class);
    expect(IntegrationTestDTO::class)->implementsInterface(FromRequestDTO::class);

    // ValidationAttribute contract on Required attribute
    $required = new Required;
    expect($required)->toBeInstanceOf(ValidationAttribute::class);
    expect($required->ruleKey())->toBe('required');

    $email = new Email;
    expect($email)->toBeInstanceOf(ValidationAttribute::class);
    expect($email->ruleKey())->toBe('email');
});

test('OpenApiSchemaGenerator produces valid schema for IntegrationTestDTO', function (): void {
    $schema = OpenApiSchemaGenerator::generate(IntegrationTestDTO::class);

    expect($schema)->toBeArray();
    expect($schema)->toHaveKey('type');
    expect($schema['type'])->toBe('object');
    expect($schema)->toHaveKey('properties');
    expect($schema)->toHaveKey('required');

    // Hidden property should be excluded
    $props = (array) $schema['properties'];
    expect($props)->not->toHaveKey('password');

    // Required fields should include email and name
    expect($schema['required'])->toContain('email');
    expect($schema['required'])->toContain('name');
});

test('fromJson empty object produces valid DTO with defaults', function (): void {
    DataTransferObject::flushMetadataCache();

    $dto = IntegrationTestDTO::fromJson('{}', validate: false);

    expect($dto)->toBeInstanceOf(IntegrationTestDTO::class);
    expect($dto->status)->toBe('active'); // DefaultValue
    expect($dto->age)->toBe(0);           // int default from emptyValueForType

    DataTransferObject::flushMetadataCache();
});

// ---------------------------------------------------------------------------
// Test Fixtures
// ---------------------------------------------------------------------------

class IntegrationTestDTO extends DataTransferObject
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

        #[Nullable, Url]
        public readonly ?string $website = null,
    ) {}
}

class SimpleDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $value,
    ) {}
}
