# ZeroBoiler DTO

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4)](https://php.net)
[![Laravel 13+](https://img.shields.io/badge/Laravel-13%2B-FF2D20)](https://laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-blue)](https://phpstan.org)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-yellow)]()

Zero-boilerplate type-safe DTO system for Laravel — attribute-based validation,
auto-hydration, serialization, request mapping, and OpenAPI schema generation.

## Table of Contents

- [Installation](#installation)
- [Type System](#type-system)
  - [Readonly Promoted Properties](#readonly-promoted-properties)
  - [Property Types](#property-types)
  - [Hydration Pipeline](#hydration-pipeline)
  - [Architecture](#architecture)
- [Features](#features)
- [Usage](#usage)
  - [Basic DTO](#basic-dto)
  - [Hydration](#hydration)
  - [Serialization](#serialization)
  - [Immutable Update](#immutable-update)
  - [Selective Output](#selective-output)
  - [Collection Helpers](#collection-helpers)
  - [Partial Updates (PATCH)](#partial-updates-patch)
  - [Validation Rules](#validation-rules)
  - [Nested DTOs](#nested-dtos)
  - [DTO Collections](#dto-collections)
  - [Value Object Integration](#value-object-integration)
  - [Eloquent Cast](#eloquent-cast)
  - [CLI Commands](#cli-commands)
  - [DTO Facade / Manager](#dto-facade--manager)
  - [OpenAPI Schema Generation](#openapi-schema-generation)
- [Configuration](#configuration)
- [Attributes Reference](#attributes-reference)
  - [Validation Attributes](#validation-attributes)
  - [Metadata Attributes](#metadata-attributes)
- [API Quick Reference](#api-quick-reference)
  - [DataTransferObject (abstract base)](#datatransferobject-abstract-base)
  - [DtoCollection](#dtocollection)
  - [DTOManager (via Facade)](#dtomanager-via-facade)
  - [OpenApiSchemaGenerator](#openapischemagenerator)
- [Design Principles](#design-principles)
- [Exception Hierarchy](#exception-hierarchy)
- [Testing](#testing)
- [Contributing](#contributing)

## Installation

```bash
composer require zeroboiler/dto
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+
- `zeroboiler/value-objects` (installed automatically as a dependency)

## Quick Start

Create your first DTO in under a minute:

```php
// app/DTOs/CreateUserDTO.php
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}

// Hydrate from request (auto-validates)
$dto = CreateUserDTO::fromRequest($request);

// Or from array
$dto = CreateUserDTO::fromArray(['email' => 'test@example.com', 'name' => 'Doruk']);

// Serialize (hidden fields excluded)
$dto->toArray();
// ['email' => 'test@example.com', 'name' => 'Doruk']

// Get validation rules (for Form Requests or API docs)
CreateUserDTO::rules();
// ['email' => ['required', 'email'], 'name' => ['required', 'min:2', 'max:50'], ...]

// OpenAPI schema
php artisan zeroboiler:dto-schema "App\DTOs\CreateUserDTO" --json
```

## Type System

### Readonly Promoted Properties

ZeroBoiler DTOs use PHP 8.1+ **readonly promoted properties** in the constructor.
This ensures immutability — once a DTO is created, its properties cannot be changed:

```php
class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,        // Must be set, validated as email, immutable

        #[DefaultValue('active')]
        public readonly string $status = 'active', // Optional with default, immutable

        #[Hidden]
        public readonly ?string $password = null,   // Nullable, excluded from output, immutable
    ) {}
}
```

All properties **must** be `public readonly`. The `readonly` keyword guarantees
immutability at the language level — not just by convention.

### Property Types

| Type | PHP Type | Validation | Serialization |
|------|----------|------------|---------------|
| **Scalar** | `string`, `int`, `float`, `bool` | Auto-inferred (`integer`, `numeric`, etc.) | As-is |
| **Nullable** | `?string`, `?int` | `sometimes` rule auto-added | `null` when empty |
| **Array** | `array` | None by default | JSON array |
| **BackedEnum** | `UserStatus` (backed enum) | Laravel `Enum` rule | Backed value |
| **ValueObject** | `Email`, `Money` (from zeroboiler/value-objects) | None | `toPrimitive()` or `toArray()` |
| **Nested DTO** | `AddressDTO` | Recursive hydration | `toArray()` recursively |
| **DateTime** | `\Carbon\Carbon` | Cast required: `#[Cast('date')]` | ISO 8601 (ATOM format) |

### Hydration Pipeline

When `fromArray()` or `fromRequest()` is called, each property value goes through
this transformation pipeline:

```
Raw Input Value
    │
    ├─ 1. Map source key (#[MapFrom('user_name')])
    ├─ 2. Apply default if key missing (#[DefaultValue('active')])
    ├─ 3. Cast type (#[Cast('integer')])
    ├─ 4. Instantiate ValueObject (detected from type)
    ├─ 5. Instantiate BackedEnum (detected from type)
    ├─ 6. Hydrate nested DTO (detected from type)
    ├─ 7. Hydrate nested array of DTOs (#[NestedArray/Collection])
    │
    ▼
Final DTO Instance (immutable)
```

Validation runs **before** hydration to reject invalid data early.

### Architecture

```
┌───────────────────────────────────────────────┐
│           Your DTO                             │
│  class CreateUserDTO extends DataTransferObject│
│  {                                              │
│      public function __construct(              │
│          #[Required, Email]                     │
│          public readonly string $email,          │
│          ...                                    │
│      ) {}                                      │
│  }                                              │
└──────────┬────────────────────────────────────┘
           │ resolves via
           ▼
┌───────────────────────────────────────────────┐
│  DtoMetadataResolver                           │
│  ├─ Reads constructor parameters (Reflection)  │
│  ├─ Reads property attributes                  │
│  ├─ Infers base rules from types               │
│  ├─ Builds validation rules + messages          │
│  ├─ Detects ValueObject/Enum/DTO types         │
│  └─ Caches result per class (TTL-based)       │
└──────────┬────────────────────────────────────┘
           │
           ▼
┌───────────────────────────────────────────────┐
│  DataTransferObject (abstract base)           │
│  ├─ fromArray() / fromRequest()               │  Hydration + validation
│  ├─ fromJson() / fromPartialArray()          │  JSON + PATCH semantics
│  ├─ fromPartialRequest() / with()            │  PATCH + immutable update
│  ├─ toArray() / toJson() / jsonSerialize()    │  Serialization
│  ├─ only() / except() / allValues()           │  Selective output
│  ├─ equals() / isEmpty() / isNotEmpty()     │  State checks
│  └─ rules() / rulesFor() / validateArray()    │  Standalone validation
└──────────┬────────────────────────────────────┘
           │
           ▼
┌───────────────────────────────────────────────┐
│  DtoCollection (typed array wrapper)          │
│  ├─ pluck() / pluckKey() / map() / filter()  │
│  ├─ count() / isEmpty() / first() / last()    │
│  ├─ push() / items() / toArray()              │
│  └─ ArrayAccess + IteratorAggregate             │
└───────────────────────────────────────────────┘
```

## Features

- **Attribute-based validation** — `#[Required]`, `#[Email]`, `#[Max]`, `#[Min]`, `#[Pattern]`, `#[Enum]`, `#[Uuid]`, `#[Url]`, `#[Date]`, `#[Integer]`, `#[Numeric]`, `#[Boolean]`, `#[In]`
- **Auto-hydration** — `fromArray()`, `fromRequest()` with zero boilerplate
- **Partial updates** — `fromPartialArray()`, `fromPartialRequest()` for PATCH semantics
- **Auto-validation** — rules derived from attributes, validated on hydration
- **Type casting** — `#[Cast('integer')]`, `#[Cast('array')]`, `#[Cast('boolean')]`, `#[Cast('date')]`
- **Field mapping** — `#[MapFrom('user_name')]` for source key aliasing
- **Hidden fields** — `#[Hidden]` excludes from `toArray()`/`toJson()`
- **Default values** — `#[DefaultValue('active')]` when source key is missing
- **Serialization** — `toArray()`, `toJson()`, `jsonSerialize()`
- **Immutable updates** — `$dto->with(['status' => 'inactive'])`
- **Selective output** — `$dto->only('email', 'name')`, `$dto->except('password')`
- **Equality** — `$dto1->equals($dto2)`
- **State checks** — `$dto->isEmpty()`, `$dto->isNotEmpty()`
- **JSON hydration** — `fromJson()` for decoding JSON strings
- **Collection helpers** — `pluck()`, `pluckKey()` for extracting fields from DTO collections
- **Eloquent casting** — store DTOs as JSON in database columns
- **OpenAPI schema** — auto-generate API docs from DTO definitions
- **Nested DTO schemas** — `$ref` to component schemas for nested DTOs
- **Union type support** — `oneOf` schemas for union types
- **Value Object integration** — auto-hydrate and serialize zeroboiler/value-objects
- **CLI tools** — `zeroboiler:dto-test`, `zeroboiler:dto-schema`

## Usage

### Basic DTO

```php
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[DefaultValue('active')]
        public readonly string $status,

        #[Cast('array')]
        public readonly array $tags = [],

        #[MapFrom('phone_number')]
        public readonly ?string $phone = null,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}
```

### Hydration

```php
// From request
$dto = CreateUserDTO::fromRequest($request);

// From array
$dto = CreateUserDTO::fromArray([
    'email'       => 'test@example.com',
    'name'        => 'Doruk',
    'phone_number'=> '+905****4567',
]);

// Skip validation
$dto = CreateUserDTO::fromArray($data, validate: false);

// From JSON string
$dto = CreateUserDTO::fromJson($request->getContent());
$dto = CreateUserDTO::fromJson('{"email":"test@example.com","name":"Doruk"}');

// fromJson throws DTOException on invalid input:
// - Invalid JSON syntax → DTOException::invalidJson('(root)', 'Syntax error')
// - Sequential array (JSON array, not object) → DTOException::invalidJson('(root)', 'Expected a JSON object')
// - Valid JSON object → normal validation errors via ValidationException
```

### Serialization

```php
$dto->toArray();
// ['email' => 'test@example.com', 'name' => 'Doruk', 'status' => 'active', ...]
// password is excluded (#[Hidden])

$dto->allValues();
// Same as toArray() but includes hidden fields
// ['email' => 'test@example.com', 'name' => 'Doruk', ..., 'password' => 'secret123']

$dto->toJson();
```

### Immutable Update

```php
$updated = $dto->with(['status' => 'inactive']);
// Original $dto is unchanged
// Validation always runs in with() to prevent invalid state
```

### Selective Output

```php
// Return only specific fields
$dto->only('email', 'name');
// ['email' => 'test@example.com', 'name' => 'Doruk']

// Exclude sensitive fields
$dto->except('password');
// All fields except 'password'

// Accepts string or array
$dto->only('email');     // single key as string
$dto->only('email', 'name'); // multiple keys
```

### Collection Helpers

```php
use ZeroBoiler\DTO\DtoCollection;

$collection = new DtoCollection([$dto1, $dto2, $dto3]);
// Or via static factory
$collection = DtoCollection::make([$dto1, $dto2]);

// Extract a single field from all DTOs
$emails = $collection->pluck('email');
// ['a@example.com', 'b@example.com', 'c@example.com']

// Build a key/value map
$map = $collection->pluckKey('email', 'name');
// ['a@example.com' => 'Alice', 'b@example.com' => 'Bob', ...]

// Map over items (returns plain array)
$names = $collection->map(fn (UserDTO $u): string => $u->name);
// ['Alice', 'Bob', 'Charlie']

// Filter items (returns new DtoCollection)
$active = $collection->filter(fn (UserDTO $u): bool => $u->status === 'active');

// Get raw DTO instances (without serialization)
$items = $collection->items();
// [$dto1, $dto2, $dto3]

// Basic access
$collection->count();       // 3
$collection->isEmpty();     // false
$collection->isNotEmpty();  // true
$collection->first();       // $dto1
$collection->last();        // $dto3
$collection->push($dto4);   // fluent, appends to end
```

### Partial Updates (PATCH)

```php
// Only validate and hydrate fields that are present
$patched = CreateUserDTO::fromPartialArray([
    'name' => 'Updated Name',
], validatePresent: true);
// Missing fields use defaults or type-appropriate empty values
// 'required' validation is relaxed to 'sometimes' for present fields only

// From a PATCH request
$patched = CreateUserDTO::fromPartialRequest($request);

// Validate only present fields separately
$valid = CreateUserDTO::validatePartialArray($data);
```

### Validation Rules

```php
CreateUserDTO::rules();
// ['email' => ['required', 'email'], 'name' => ['required', 'min:2', 'max:50'], ...]

// Standalone validation (without creating a DTO instance)
$validated = CreateUserDTO::validateArray($data);

// Validate only present fields (PATCH semantics)
$validated = CreateUserDTO::validatePartialArray($data);

// Action-scoped rules (override per action in subclass)
CreateUserDTO::rulesFor('update');
// Returns rules() by default; override in subclass for action-specific logic
```

### Nested DTOs

```php
use ZeroBoiler\DTO\Attributes\NestedArray;

class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderNumber,

        public readonly AddressDTO $shippingAddress,

        #[NestedArray(AddressDTO::class)]
        public readonly array $items = [],
    ) {}
}

// Nested DTO auto-hydrated
$order = OrderDTO::fromArray([
    'orderNumber' => 'ORD-001',
    'shippingAddress' => ['street' => '123 Main St', 'city' => 'Istanbul'],
    'items' => [
        ['street' => '456 Oak Ave', 'city' => 'Ankara'],
    ],
]);

$order->shippingAddress; // AddressDTO instance
$order->items;            // array of AddressDTO instances
```

### DTO Collections

```php
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\DtoCollection;

class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderNumber,

        #[Collection(ItemDTO::class)]
        public readonly DtoCollection $items,
    ) {}
}

// items array automatically wrapped in a DtoCollection of DTO instances
$order = OrderDTO::fromArray([
    'orderNumber' => 'ORD-001',
    'items' => [
        ['name' => 'Widget A', 'price' => 9.99],
        ['name' => 'Widget B', 'price' => 14.99],
    ],
]);

$order->items->count();     // 2
$order->items->pluck('name'); // ['Widget A', 'Widget B']
```

### Value Object Integration

Properties typed with a `ValueObject` class (from `zeroboiler/value-objects`) are
automatically instantiated during hydration and serialized to primitives on output.

```php
use ZeroBoiler\ValueObjects\Email;
use ZeroBoiler\ValueObjects\Money;
use ZeroBoiler\DTO\DataTransferObject;

class VoUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly Email $email,

        public readonly ?Url $website = null,

        public readonly ?Money $balance = null,
    ) {}
}

// Auto-instantiated from scalar/array/JSON
$dto = VoUserDTO::fromArray([
    'email' => 'test@example.com',
    'website' => 'https://zeroboiler.dev',
    'balance' => ['amount' => 2500, 'currency' => 'USD'],
], validate: false);

$dto->email;       // Email value object
$dto->balance;     // Money value object

// Serialized back to primitives
$dto->toArray();
// ['email' => 'test@example.com', 'website' => 'https://zeroboiler.dev', 'balance' => ['amount' => 2500, 'currency' => 'USD']]
```

### Eloquent Cast

```php
protected $casts = [
    'payload' => CreateUserDTO::class,
];
```

The `DTOCast` handles serialization/deserialization transparently:
- **get**: JSON string → DTO instance (no validation for stored data)
- **set**: DTO/array → JSON string (validates by default)

Disable validation on set for performance-critical paths:

```php
protected function casts(): array
{
    return ['payload' => new DTOCast(CreateUserDTO::class, validate: false)];
}
```

### CLI Commands

```bash
php artisan zeroboiler:dto-test "App\DTO\CreateUserDTO"
php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --json
```

### DTO Facade / Manager

```php
use ZeroBoiler\DTO\Facades\DTO;

// Create a DTO from data
$dto = DTO::make(CreateUserDTO::class, ['email' => 'test@example.com', 'name' => 'Doruk']);

// Validate data against a DTO class
$validated = DTO::validate(CreateUserDTO::class, $data);

// Create a DTO from JSON
$dto = DTO::makeFromJson(CreateUserDTO::class, $jsonString);

// Generate OpenAPI schema
$schema = DTO::schema(CreateUserDTO::class);
```

### OpenAPI Schema Generation

```php
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

// Basic schema (throws on nested DTOs)
$schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

// With component schemas for nested DTOs
$result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
// Returns:
// [
//     'schema' => [...],  // Main schema with $ref pointers
//     'components' => ['schemas' => ['AddressDTO' => [...], ...]]
// ]
```

Nested DTOs are automatically detected and generate `$ref` pointers to
component schemas. Union types produce `oneOf` schemas.

## Configuration

The DTO package requires no configuration for basic usage. The service provider
auto-registers via Laravel's package discovery.

### Dev/Test Cache Invalidation

In `local` and `testing` environments, metadata caches automatically expire after
2 seconds, so changes to DTO classes are picked up without manual intervention.

### Long-Lived Processes

For Octane, Swoole, or RoadRunner, the package automatically listens for
`octane.terminate` and `laravel.flush` events to clear the static metadata cache
between requests. This prevents stale metadata and unbounded memory growth.

You can also manually flush the cache:

```php
use ZeroBoiler\DTO\DataTransferObject;

// Flush cache for a specific DTO class
DataTransferObject::flushMetadataCache(MyDTO::class);

// Flush all cached metadata
DataTransferObject::flushMetadataCache();
```

## Attributes Reference

### Validation Attributes

| Attribute | Description |
|-----------|-------------|
| `#[Required]` | Field is required |
| `#[Email]` | Must be valid email |
| `#[Url]` | Must be valid URL |
| `#[Uuid]` | Must be valid UUID |
| `#[Min(n)]` | Minimum length/value |
| `#[Max(n)]` | Maximum length/value |
| `#[Size(n)]` | Must be exactly size n |
| `#[Between(min, max)]` | Value must be between min and max |
| `#[Pattern('/regex/')]` | Must match regex |
| `#[In(['a', 'b'])]` | Must be one of values |
| `#[Enum(EnumClass::class)]` | Must be valid enum value |
| `#[Integer]` | Must be integer |
| `#[Numeric]` | Must be numeric |
| `#[Boolean]` | Must be boolean |
| `#[Date]` | Must be valid date |
| `#[Date('Y-m-d')]` | Must match date format |
| `#[Json]` | Must be valid JSON string |
| `#[StartsWith('prefix')]` | Must start with given prefix(es) |
| `#[EndsWith('suffix')]` | Must end with given suffix(es) |
| `#[Accepted]` | Must be accepted (yes, on, 1, true) |
| `#[Declined]` | Must be declined (no, off, 0, false) |
| `#[Confirmed]` | Must have a matching `{field}_confirmation` |
| `#[Distinct]` | Array elements must be unique |
| `#[Prohibited]` | Field must not be present |
| `#[Present]` | Field must be present (even if empty) |
| `#[Sometimes]` | Only validate if field is present |
| `#[Nullable]` | Allows null value |
| `#[Same('field')]` | Must match another field's value |
| `#[Different('field')]` | Must differ from another field's value |
| `#[RequiredIf('field', value)]` | Required when another field has a value |
| `#[RequiredUnless('field', value)]` | Required unless another field has a value |
| `#[RequiredWith('field')]` | Required when another field is present |
| `#[RequiredWithAll('f1', 'f2')]` | Required when all specified fields are present |
| `#[RequiredWithout('field')]` | Required when another field is not present |
| `#[RequiredWithoutAll('f1', 'f2')]` | Required when all specified fields are absent |
| `#[ArrayRule]` | Must be an array |
| `#[ArrayRule(min: 1, max: 10)]` | Array with 1–10 elements |

### Metadata Attributes

| Attribute | Description |
|-----------|-------------|
| `#[Cast('type')]` | Cast value during hydration (`'integer'`, `'string'`, `'boolean'`, `'array'`, `'date'`, `'datetime'`) |
| `#[MapFrom('source_key')]` | Map from different source key |
| `#[Hidden]` | Exclude from `toArray()` / `toJson()` output |
| `#[DefaultValue(value)]` | Default value when key is missing |
| `#[NestedArray(DTOClass::class)]` | Hydrate array elements as nested DTO instances |
| `#[Collection(DTOClass::class)]` | Hydrate as `DtoCollection` of DTO instances |

All validation attributes accept an optional `message` parameter for custom error messages:

```php
#[Email(message: 'Please provide a valid email address')]
#[Min(8, message: 'Password must be at least 8 characters')]
```

## API Quick Reference

### DataTransferObject (abstract base)

| Method | Returns | Description |
|--------|---------|-------------|
| `::fromArray(array, bool)` | `static` | Create from array (validates by default) |
| `::fromJson(string, bool)` | `static` | Create from JSON string (validates by default) |
| `::fromRequest(Request, bool)` | `static` | Create from HTTP request |
| `::fromPartialArray(array, bool)` | `static` | Create from partial array (PATCH semantics) |
| `::fromPartialRequest(Request, bool)` | `static` | Create from partial request |
| `::validateArray(array)` | `array` | Validate data, return validated data |
| `::validatePartialArray(array)` | `array` | Validate only present fields |
| `::rules()` | `array<string, array>` | Get validation rules |
| `::rulesFor(string)` | `array<string, array>` | Get rules for a specific action |
| `::flushMetadataCache(?string)` | `void` | Clear metadata cache |
| `::setMetadataCacheTtl(float)` | `void` | Set metadata cache TTL (static) |
| `->toArray()` | `array` | Serialize (excludes hidden fields) |
| `->allValues()` | `array` | Serialize (includes hidden fields) |
| `->toJson(int)` | `string` | JSON serialization |
| `->jsonSerialize()` | `mixed` | JSONSerializable implementation |
| `->only(string\|array)` | `array` | Return only specified fields |
| `->except(string\|array)` | `array` | Return all except specified fields |
| `->with(array)` | `static` | Immutable copy with overrides (always validates) |
| `->equals(self)` | `bool` | Value equality check |
| `->isEmpty()` | `bool` | Check if all properties are empty/default |
| `->isNotEmpty()` | `bool` | Check if at least one property has a value |

### DtoCollection

| Method | Returns | Description |
|--------|---------|-------------|
| `::make(array)` | `self` | Static factory constructor |
| `->push(DataTransferObject)` | `self` | Append DTO (fluent) |
| `->first()` | `?DataTransferObject` | First item or null |
| `->last()` | `?DataTransferObject` | Last item or null |
| `->map(callable)` | `array` | Map over items (returns plain array) |
| `->filter(callable)` | `self` | Filter items (returns new collection) |
| `->pluck(string)` | `array` | Extract single field from all DTOs |
| `->pluckKey(string, ?string)` | `array` | Key/value map from fields |
| `->items()` | `array` | Raw DTO instances |
| `->toArray()` | `array` | All DTOs serialized via toArray() |
| `->allValues()` | `array` | All DTOs serialized including hidden |
| `->count()` | `int` | Item count |
| `->isEmpty()` | `bool` | Check if empty |
| `->isNotEmpty()` | `bool` | Check if not empty |

### DTOManager (via Facade)

| Facade Method | Description |
|---------------|-------------|
| `DTO::validate(string, array)` | Validate data against a DTO class |
| `DTO::make(string, array)` | Create DTO from data |
| `DTO::makeFromJson(string, string)` | Create DTO from JSON string |
| `DTO::schema(string)` | Generate OpenAPI schema |

### OpenApiSchemaGenerator

| Method | Returns | Description |
|--------|---------|-------------|
| `::generate(string)` | `array` | Basic schema (throws on nested DTOs) |
| `::generateWithComponents(string)` | `array{schema, components}` | Schema with component definitions |

## Design Principles

| Principle | Implementation |
|-----------|---------------|
| **Zero config** | No service provider config — works out of the box via auto-discovery |
| **Immutable** | All properties are `public readonly`; `with()` returns a new instance |
| **Attribute-first** | All validation and metadata declared via PHP 8 attributes |
| **Validated by default** | Validation runs automatically on hydration; opt-out with `validate: false` |
| **Strict typing** | `declare(strict_types=1)` in every file; PHPStan level 9 clean |
| **No mixed types** | Every property has an explicit type; every method has return types |
| **Fail fast** | Invalid JSON throws `DTOException`; invalid data throws `ValidationException` |
| **Final classes** | All attributes, services, collections, and resolvers are `final` |

### Exception Hierarchy

```
DTOException (extends Exception)
├─ invalidCast($property, $type, $value)  — Cast failure (#[Cast] attribute)
└─ invalidJson($property, $jsonError)    — JSON decode failure (#[Cast('array')] / fromJson)

ValidationException (Laravel)             — Thrown when attribute-derived rules fail
InvalidArgumentException                 — Thrown for type mismatches, invalid nested data
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a history of changes.

## Testing

```bash
# Run the full test suite
composer test

# Run PHPStan analysis (level 9, no baseline)
composer analyse

# Run code style checker
composer lint

# Run all quality checks at once
composer ci
```

All checks must pass before merging. The package targets PHPStan level 9 with a clean baseline (zero suppressed errors).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Ensure all CI checks pass (`composer ci`)
4. Commit with conventional commits (`feat:`, `fix:`, `refactor:`)
5. Push and open a Pull Request

### Code Standards

- **PHP 8.5 syntax** — use the latest language features
- **Strict types** — every file must have `declare(strict_types=1)`
- **PHPStan level 9** — zero errors, no baseline suppressions
- **Docblocks** — all public methods and properties documented
- **Typed properties** — no `mixed` types in source code
- **Readonly properties** — all DTO properties must be `public readonly`
- **Final classes** — all attributes, services, and collections are `final`

## License

Proprietary — © ZeroBoiler

## Security

See [SECURITY.md](SECURITY.md) for our security policy.
