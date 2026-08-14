# ZeroBoiler DTO

[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4)](https://php.net)
[![Laravel 13+](https://img.shields.io/badge/Laravel-13%2B-FF2D20)](https://laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-blue)](https://phpstan.org)
|[![Tests: 220](https://img.shields.io/badge/Tests-220-brightgreen)]()
|[![Version 1.1.0](https://img.shields.io/badge/Version-1.1.0-green)](https://github.com/zeroboiler/dto/releases)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-yellow)]()

Zero-boilerplate type-safe DTO system for Laravel — attribute-based validation,
auto-hydration, serialization, request mapping, and OpenAPI schema generation.

## Table of Contents

- [Installation](#installation)
- [Source Code Index](#source-code-index)
- [Quick Start](#quick-start)
- [Quick Reference Card](#quick-reference-card)
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
- [Extending](#extending)
  - [Custom DTO Methods](#custom-dto-methods)
  - [Action-Scoped Rules](#action-scoped-rules)
  - [Bypass Validation (Advanced)](#bypass-validation-advanced)
  - [Custom Cast Types](#custom-cast-types)
- [Testing](#testing)
- [Test Fixtures](#test-fixtures)
- [Full-Stack Example](#full-stack-example)
- [Performance Considerations](#performance-considerations)
- [Contributing](#contributing)
- [Migration Guide](#migration-guide)
  - [From Form Requests](#from-form-requests)
  - [From Manual Array Casting](#from-manual-array-casting)
  - [From Eloquent Accessors/Casts](#from-eloquent-accessorscasts)
- [Cross-Package Integration](#cross-package-integration)
  - [Using Enum Properties in DTOs](#using-enum-properties-in-dtos)
  - [Enum Roundtrip in with()](#enum-roundtrip-in-with)
  - [Using Enum Metadata in Controllers](#using-enum-metadata-in-controllers)
  - [Eloquent Model with Both Casts](#eloquent-model-with-both-casts)
- [Changelog](#changelog)
- [Version History](#version-history)
- [Compatibility](#compatibility)
- [Laravel Compatibility](#laravel-compatibility)
- [Security](#security)
- [Quality Assurance](#quality-assurance)
  - [Static Analysis Compliance (PHPStan Level 9)](#static-analysis-compliance-phpstan-level-9)
  - [Code Quality Checklist](#code-quality-checklist)
  - [Design Decisions](#design-decisions)
- [Type Safety & PHPStan Level 9](#type-safety--phpstan-level-9)
  - [What This Means](#what-this-means)
  - [Running PHPStan](#running-phpstan)
- [Quick Start Integration](#quick-start-integration)
- [Source Code Audit — Attribute Contract Compliance](#source-code-audit--attribute-contract-compliance)
  - [Validation Attributes (37 total)](#validation-attributes-37-total)
  - [Metadata Attributes (4 total)](#metadata-attributes-4-total)
  - [Service & Infrastructure Classes](#service--infrastructure-classes)
- [Source Code Structure](#source-code-structure)
  - [Attribute Type Signatures](#attribute-type-signatures)
  - [Directory Layout](#directory-layout)

## Why ZeroBoiler DTO?

| Problem | ZeroBoiler Solution |
|---------|-------------------|
| Repetitive Form Request validation rules | **Attribute-driven validation** — `#[Required]`, `#[Email]`, `#[Max]` directly on properties |
| Manual array-to-object hydration in controllers | **`fromArray()` / `fromRequest()`** — auto-hydration with zero boilerplate |
| No PATCH/partial update support | **`fromPartialArray()` / `fromPartialRequest()`** — PATCH semantics built-in |
| Inconsistent serialization across DTOs | **`toArray()` / `toJson()` / `jsonSerialize()`** — uniform output with `#[Hidden]` support |
| Manual OpenAPI schema maintenance | **`DTO::schema()`** / `zeroboiler:dto-schema` — auto-generated from attributes |
| No immutable update pattern | **`with()`** — returns a new DTO instance, always validates |
| Missing test coverage for DTOs | **`zeroboiler:dto-test`** — generates comprehensive Pest tests automatically |
| Nested DTO/array handling is complex | **`#[NestedArray]` / `#[Collection]`** — recursive hydration with type-safe collections |
| Source key mismatches (camelCase ↔ snake_case) | **`#[MapFrom('user_name')]`** — maps any source key to your property |

**Zero ceremony. Zero boilerplate. Production-grade from day one.**

## Installation

```bash
composer require zeroboiler/dto
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+
- `zeroboiler/value-objects` (installed automatically as a dependency)

**Package Statistics:**
- 55 source files in `src/` (37 validation attributes, 4 metadata attributes, 14 infrastructure)
- 220 test files in `tests/` (33 fixtures)
- PHPStan Level 9 (`phpstan.neon`)
- 100% `declare(strict_types=1)` coverage
- Zero `mixed` return types in public API

## Source Code Index

| Class | Namespace | Purpose |
|-------|-----------|---------|
| `DataTransferObject` | Root | Abstract base class — provides fromArray, fromRequest, fromJson, fromPartialArray, toArray, with, equals, rules, validation |
| `DtoCollection` | Root | Type-safe collection wrapper with pluck, pluckKey, map, filter, push, append, merge, ArrayAccess, IteratorAggregate |
| `DTOManager` | Root | Runtime helper (injectable/facade) — validate, make, makeFromJson, schema |
| `DTO` | `Facades` | Laravel facade for `DTOManager` — `DTO::make(...)`, `DTO::validate(...)` |
| `DTOSServiceProvider` | Root | Registers singleton, artisan commands, dev cache TTL, and Octane flush listeners |
| `DtoMetadataResolver` | `Support` | Resolves validation rules + property metadata from constructor reflection and attributes |
| `OpenApiSchemaGenerator` | `Support` | Generates OpenAPI 3.0 schemas from DTO definitions (supports nested DTOs, union types) |
| `DTOCast` | `Casts` | Eloquent cast — stores DTOs as JSON columns in the database |
| `DTOException` | `Exceptions` | Thrown for invalid casts and JSON decode failures |
| `FromRequestDTO` | `Contracts` | Interface for DTOs that can be hydrated from HTTP requests |
| `ValidatableDTO` | `Contracts` | Interface for DTOs providing validation rules (rules + rulesFor) |
| `ValidationAttribute` | `Contracts` | Interface for attributes that participate in validation (ruleKey method) |
| `Required` | `Attributes` | `#[Required]` — marks property as required |
| `Email` | `Attributes` | `#[Email]` — validates as email address |
| `Max`, `Min` | `Attributes` | `#[Max(n)]` / `#[Min(n)]` — max/min length or numeric bound |
| `Between` | `Attributes` | `#[Between(min, max)]` — numeric or string length between bounds |
| `Pattern` | `Attributes` | `#[Pattern('/regex/')]` — regex validation |
| `In` | `Attributes` | `#[In([...])` — value must be in allowed list |
| `Url`, `Uuid` | `Attributes` | `#[Url]` / `#[Uuid]` — format validation |
| `Integer`, `Numeric`, `Boolean` | `Attributes` | Type validation — `#[Integer]`, `#[Numeric]`, `#[Boolean]` |
| `Date` | `Attributes` | `#[Date]` / `#[Date('Y-m-d')]` — date/date_format validation |
| `ArrayRule` | `Attributes` | `#[ArrayRule]` / `#[ArrayRule(min:1, max:10)]` — array with optional count bounds |
| `Json` | `Attributes` | `#[Json]` — validates as JSON string |
| `Enum` | `Attributes` | `#[Enum(MyBackedEnum::class)]` — validates against backed enum values |
| `MapFrom` | `Attributes` | `#[MapFrom('source_key')]` — maps source key to DTO property (supports dot notation) |
| `Cast` | `Attributes` | `#[Cast('integer')]` — type casting during hydration (int, string, bool, array, date) |
| `DefaultValue` | `Attributes` | `#[DefaultValue('active')]` — default when source key is absent |
| `Hidden` | `Attributes` | `#[Hidden]` — excludes from toArray/toJson output |
| `Nullable`, `Sometimes`, `Present` | `Attributes` | Field presence control — `#[Nullable]`, `#[Sometimes]`, `#[Present]` |
| `Prohibited`, `Accepted`, `Declined` | `Attributes` | Field value control — `#[Prohibited]`, `#[Accepted]`, `#[Declined]` |
| `Confirmed`, `Same`, `Different`, `Distinct` | `Attributes` | Cross-field validation — confirmed, same, different, distinct array elements |
| `RequiredIf`, `RequiredUnless`, `RequiredWith`, etc. | `Attributes` | Conditional requirement — 7 conditional required attributes |
| `Size`, `StartsWith`, `EndsWith` | `Attributes` | Size constraint, string prefix/suffix validation |
| `NestedArray` | `Attributes` | `#[NestedArray(ItemDTO::class)]` — array of nested DTO instances |
| `Collection` | `Attributes` | `#[Collection(ItemDTO::class)]` — DtoCollection-wrapped nested DTOs |
| `MakeDtoTestCommand` | `Console\Commands` | `php artisan zeroboiler:dto-test` — generates Pest test file for a DTO |
| `MakeDtoSchemaCommand` | `Console\Commands` | `php artisan zeroboiler:dto-schema` — generates OpenAPI schema for a DTO |

## Quick Start

Create your first type-safe DTO in under a minute:

```php
// app/DTO/CreateUserDTO.php
use ZeroBoiler\DTO\Attributes\Email;
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

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}

// In your controller
$dto = CreateUserDTO::fromRequest($request);
// $dto->email, $dto->name, $dto->password — all typed, all validated

// Serialize to JSON
$dto->toArray();  // ['email' => '...', 'name' => '...', 'password' => '...']
$dto->toJson();   // '{"email":"...","name":"...","password":"..."}'

// Validation rules (auto-generated from attributes)
CreateUserDTO::rules();
// ['email' => ['required', 'email'], 'name' => ['required', 'min:2', 'max:50'], ...]
```

## Quick Reference Card

A cheat sheet for the most common operations:

```php
use ZeroBoiler\DTO\Attributes\{Email, Hidden, MapFrom, Max, Min, Required};
use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, NestedArray, Collection};
use ZeroBoiler\DTO\DataTransferObject;

// ── Define ──────────────────────────────────────────────────
class UserDTO extends DataTransferObject
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

        #[Cast('date')]
        public readonly ?\Carbon\Carbon $createdAt = null,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}

// ── Create ─────────────────────────────────────────────────
$dto = UserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice']);
$dto = UserDTO::fromRequest($request);
$dto = UserDTO::fromJson('{"email":"a@b.com","name":"Alice"}');

// ── Serialize ──────────────────────────────────────────────
$dto->toArray();    // public fields only (password excluded)
$dto->allValues();  // includes hidden fields
$dto->toJson();     // JSON string

// ── Selective Output ───────────────────────────────────────
$dto->only('email', 'name');   // ['email' => 'a@b.com', 'name' => 'Alice']
$dto->except('age');          // all except 'age'

// ── Immutable Update ───────────────────────────────────────
$updated = $dto->with(['name' => 'Bob']);  // always validates

// ── Equality & State ──────────────────────────────────────
$dto->equals($other);     // true if toArray() matches
$dto->isEmpty();          // all properties empty/default
$dto->isNotEmpty();       // at least one property has value

// ── Partial Update (PATCH) ──────────────────────────────────
$patched = UserDTO::fromPartialArray(['name' => 'Updated']);
$patched = UserDTO::fromPartialRequest($request);

// ── Validation ─────────────────────────────────────────────
UserDTO::rules();              // ['email' => ['required', 'email'], ...]
UserDTO::validateArray($data); // returns validated data, throws on fail
UserDTO::rulesFor('update');   // action-scoped rules

// ── Collections ─────────────────────────────────────────────
use ZeroBoiler\DTO\DtoCollection;
$col = DtoCollection::make([$dto1, $dto2]);
$col->pluck('email');         // ['a@b.com', 'c@d.com']
$col->pluckKey('email', 'name'); // ['a@b.com' => 'Alice', ...]
$col->map(fn($d) => $d->name);    // ['Alice', 'Charlie']
$col->filter(fn($d) => $d->age > 18);
$col->append($dto3);            // new collection with dto3 added (immutable)
$col->merge($otherCollection);  // new collection combining both (immutable)
$col->push($dto3);             // mutates in-place, returns $col
$col->count(); $col->isEmpty(); $col->first(); $col->last();

// ── Facade ─────────────────────────────────────────────────
use ZeroBoiler\DTO\Facades\DTO;
DTO::make(UserDTO::class, $data);
DTO::validate(UserDTO::class, $data);
DTO::makeFromJson(UserDTO::class, $json);
DTO::fromJson(UserDTO::class, $json);
DTO::fromPartialArray(UserDTO::class, $data);   // PATCH semantics
DTO::fromPartialRequest(UserDTO::class, $request);
DTO::rules(UserDTO::class);
DTO::rulesFor(UserDTO::class, 'update');
DTO::schema(UserDTO::class);

// ── CLI ────────────────────────────────────────────────────
php artisan zeroboiler:dto-test "App\DTOs\UserDTO"
php artisan zeroboiler:dto-schema "App\DTOs\UserDTO" --json
php artisan zeroboiler:dto-schema "App\DTOs\UserDTO" --with-components --json
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
$collection->push($dto4);   // mutates in-place, returns $collection

// ── Immutable alternatives ───────────────────────────────────
$newCol = $collection->append($dto4);  // returns NEW collection (immutable)
$newCol = $collection->merge($other);  // returns NEW collection combining both

// ── Re-keying ────────────────────────────────────────────
// toArrayBy: re-key by a property value
$keyed = $collection->toArrayBy('id');
// ['42' => ['id' => 42, 'name' => 'Alice'], ...]

// toDictionary: map one property to another
$dict = $collection->toDictionary('id', 'name');
// [42 => 'Alice', 43 => 'Bob', ...]
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
# Generate Pest tests for a DTO class
php artisan zeroboiler:dto-test "App\DTO\CreateUserDTO"
php artisan zeroboiler:dto-test "App\DTO\CreateUserDTO" --dir=tests/Unit/DTO

# Generate OpenAPI schema for a DTO class
php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --json
php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --output=schemas/
php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --with-components --json
```

### DTO Facade / Manager

The `DTO` facade provides a runtime interface for DTO operations
(validation, creation, JSON hydration, and OpenAPI schema generation)
without directly calling static methods on DTO classes.
Internally it delegates to the `DTOManager` singleton (registered via the
service provider as `zeroboiler.dto`).

```php
use ZeroBoiler\DTO\Facades\DTO;

// Create a DTO from data (auto-validates)
$dto = DTO::make(CreateUserDTO::class, [
    'email' => 'test@example.com',
    'name' => 'Doruk',
]);

// Validate data against a DTO class (without creating an instance)
$validated = DTO::validate(CreateUserDTO::class, $rawData);
// Returns validated data array, throws ValidationException on failure

// Create a DTO from JSON string
$dto = DTO::makeFromJson(CreateUserDTO::class, $jsonString);
// Throws DTOException on invalid JSON, ValidationException on invalid data

// Get validation rules (useful for form builders, API clients)
$rules = DTO::rules(CreateUserDTO::class);
$rules = DTO::rulesFor(CreateUserDTO::class, 'update');

// Generate OpenAPI schema for API documentation
$schema = DTO::schema(CreateUserDTO::class);
// Returns OpenAPI 3.0 property schema array
```

The DTOManager can also be injected directly from the container:

```php
use ZeroBoiler\DTO\DTOManager;

$manager = app(DTOManager::class);
$dto = $manager->make(CreateUserDTO::class, $data);
$validated = $manager->validate(CreateUserDTO::class, $data);
$schema = $manager->schema(CreateUserDTO::class);
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

All validation attributes accept an optional `message` parameter for custom Laravel error messages:

```php
#[Email(message: 'Please provide a valid email address')]
#[Min(8, message: 'Password must be at least 8 characters')]
```

### Validation Attributes

| Attribute | Rule Generated | Description |
|-----------|---------------|-------------|
| `#[Required]` | `required` | Field must be present and non-empty |
| `#[Email]` | `email` | Must be a valid email address |
| `#[Url]` | `url` | Must be a valid URL |
| `#[Uuid]` | `uuid` | Must be a valid UUID |
| `#[Min(n)]` | `min:n` | Minimum value (numeric) or length (string) |
| `#[Max(n)]` | `max:n` | Maximum value (numeric) or length (string) |
| `#[Size(n)]` | `size:n` | Exact size — length for strings, count for arrays |
| `#[Between(min, max)]` | `between:min,max` | Value/length must be between two bounds |
| `#[Pattern('/regex/')]` | `regex:...` | Must match a regular expression |
| `#[In(['a', 'b'])]` | `in:a,b` | Must be one of the given values |
| `#[Enum(MyEnum::class)]` | Laravel `Enum` rule | Must be a valid backed enum value |
| `#[Integer]` | `integer` | Must be an integer |
| `#[Numeric]` | `numeric` | Must be numeric |
| `#[Boolean]` | `boolean` | Must be a boolean |
| `#[Date]` | `date` | Must be a valid date |
| `#[Date('Y-m-d')]` | `date_format:Y-m-d` | Must match a specific date format |
| `#[Json]` | `json` | Must be a valid JSON string |
| `#[StartsWith('pre')]` | `starts_with:pre` | Must start with prefix (single or array) |
| `#[EndsWith('suf')]` | `ends_with:suf` | Must end with suffix (single or array) |
| `#[Accepted]` | `accepted` | Must be yes, on, 1, or true |
| `#[Declined]` | `declined` | Must be no, off, 0, or false |
| `#[Confirmed]` | `confirmed` | Must have a matching `{field}_confirmation` |
| `#[Distinct]` | `distinct` | Array elements must be unique |
| `#[Prohibited]` | `prohibited` | Field must not be present |
| `#[Present]` | `present` | Field must be present (even if empty) |
| `#[Sometimes]` | `sometimes` | Only validate when field is present |
| `#[Nullable]` | `nullable` | Allows null values to pass |
| `#[Same('field')]` | `same:field` | Must match another field's value |
| `#[Different('field')]` | `different:field` | Must differ from another field |
| `#[RequiredIf('field', val)]` | `required_if:...` | Required when another field equals a value |
| `#[RequiredUnless('field', val)]` | `required_unless:...` | Required unless another field equals a value |
| `#[RequiredWith('field')]` | `required_with:...` | Required when another field is present |
| `#[RequiredWithAll('f1', 'f2')]` | `required_with_all:...` | Required when all specified fields are present |
| `#[RequiredWithout('field')]` | `required_without:...` | Required when another field is absent |
| `#[RequiredWithoutAll('f1')]` | `required_without_all:...` | Required when all specified fields are absent |
| `#[ArrayRule]` | `array` | Must be an array |
| `#[ArrayRule(min: 1, max: 10)]` | `array`, `min:1`, `max:10` | Array with element count bounds |

### Attribute Usage Examples

Basic validation:

```php
class RegisterDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8), Max(128)]
        public readonly string $password,

        #[Accepted]
        public readonly bool $terms,
    ) {}
}
```

Numeric and date constraints:

```php
class ProductDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Between(0.01, 99999.99)]
        public readonly float $price,

        #[Integer, Min(0)]
        public readonly int $stock,

        #[Date('Y-m-d')]
        public readonly string $releasedAt,

        #[Size(13)]
        public readonly string $isbn,
    ) {}
}
```

String pattern and conditional validation:

```php
class AddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Pattern('/^[A-Z]{2}\d{4}[A-Z]{2}$/')]
        public readonly string $postalCode,

        #[Required, StartsWith('https://')]
        public readonly string $website,

        #[RequiredIf('country', 'TR')]
        public readonly ?string $taxId = null,

        #[RequiredWith('phone')]
        public readonly ?string $phonePrefix = null,
    ) {}
}
```

Array and enum validation:

```php
class ArticleDTO extends DataTransferObject
{
    public function __construct(
        #[Required, In(['draft', 'published', 'archived'])]
        public readonly string $status,

        #[Enum(UserRole::class)]
        public readonly string $authorRole,

        #[ArrayRule(min: 1, max: 10), Distinct]
        public readonly array $tags = [],
    ) {}
}
```

### Metadata Attributes

| Attribute | Target | Description |
|-----------|--------|-------------|
| `#[Cast('type')]` | Property | Cast value during hydration (`'integer'`, `'string'`, `'boolean'`, `'array'`, `'date'`, `'datetime'`) |
| `#[MapFrom('source_key')]` | Property | Map from a different source key (supports dot notation) |
| `#[Hidden]` | Property | Exclude from `toArray()` / `toJson()` output |
| `#[DefaultValue(value)]` | Property | Default value when the source key is missing from input |
| `#[NestedArray(DTOClass::class)]` | Property | Hydrate array elements as nested DTO instances |
| `#[Collection(DTOClass::class)]` | Property | Hydrate as `DtoCollection` of DTO instances |

Metadata attribute examples:

```php
class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $orderNumber,

        #[MapFrom('customer_email')]
        #[Required, Email]
        public readonly string $email,

        #[Cast('integer')]
        public readonly int $totalCents = 0,

        #[DefaultValue('pending')]
        public readonly string $status = 'pending',

        #[Cast('date')]
        public readonly ?\Carbon\Carbon $shippedAt = null,

        #[Hidden]
        public readonly ?string $internalNotes = null,

        #[NestedArray(AddressDTO::class)]
        public readonly array $addresses = [],

        #[Collection(LineItemDTO::class)]
        public readonly DtoCollection $items,
    ) {}
}
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
| `->push(DataTransferObject)` | `self` | Append DTO in place (fluent) |
| `->append(DataTransferObject)` | `self` | Return new collection with added DTO (immutable) |
| `->merge(self)` | `self` | Return new collection combining both collections (immutable) |
| `->first()` | `?DataTransferObject` | First item or null |
| `->last()` | `?DataTransferObject` | Last item or null |
| `->map(callable)` | `array` | Map over items (returns plain array) |
| `->filter(callable)` | `self` | Filter items (returns new collection) |
| `->pluck(string)` | `array` | Extract single field from all DTOs |
| `->pluckKey(string, ?string)` | `array` | Key/value map from fields |
| `->toArrayBy(string)` | `array` | Re-key collection by a property value (alias for pluckKey) |
| `->toDictionary(string, string)` | `array` | Build lookup map from two properties |
| `->items()` | `array` | Raw DTO instances |
| `->toArray()` | `array` | All DTOs serialized via toArray() |
| `->allValues()` | `array` | All DTOs serialized including hidden |
| `->count()` | `int` | Item count (Countable interface) |
| `->isEmpty()` | `bool` | Check if empty |
| `->isNotEmpty()` | `bool` | Check if not empty |
| `->offsetExists(mixed)` | `bool` | ArrayAccess: check if index exists |
| `->offsetGet(mixed)` | `?DataTransferObject` | ArrayAccess: get item at index |
| `->offsetSet(mixed, mixed)` | `void` | ArrayAccess: set item at index |
| `->offsetUnset(mixed)` | `void` | ArrayAccess: remove item (re-indexes) |
| `->jsonSerialize()` | `array` | JsonSerializable: returns toArray() |
| `->getIterator()` | `Traversable` | IteratorAggregate: enables foreach |

### DTOManager (via Facade)

| Facade Method | Description |
|---------------|-------------|
| `DTO::validate(string, array)` | Validate data against a DTO class |
| `DTO::make(string, array)` | Create DTO from data |
| `DTO::makeFromJson(string, string)` | Create DTO from JSON string |
| `DTO::fromJson(string, string)` | Create DTO from JSON string (alias) |
| `DTO::fromPartialArray(string, array)` | Create DTO from partial data (PATCH semantics) |
| `DTO::fromPartialRequest(string, Request)` | Create DTO from partial request (PATCH semantics) |
| `DTO::rules(string)` | Get validation rules for a DTO class |
| `DTO::rulesFor(string, string)` | Get action-scoped validation rules |
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

## Extending

### Custom DTO Methods

Add domain logic directly to your DTO classes:

```php
class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}

    /**
     * Check if this user is a high-security account (long password).
     */
    public function isHighSecurity(): bool
    {
        return strlen($this->password) >= 16;
    }

    /**
     * Get the email domain for org-level routing.
     */
    public function emailDomain(): string
    {
        return substr(strrchr($this->email, '@'), 1);
    }
}
```

### Action-Scoped Rules

Override `rulesFor()` in subclasses to provide different validation per action:

```php
class UpdateUserDTO extends DataTransferObject
{
    public static function rulesFor(string $action): array
    {
        return match ($action) {
            'update' => [
                'email' => ['sometimes', 'email', 'max:255'],
                'name' => ['sometimes', 'string', 'max:50'],
            ],
            default => self::rules(),
        };
    }
}
```

### Bypass Validation (Advanced)

For performance-critical paths with pre-validated data:

```php
// Skip validation entirely (use with caution)
$dto = MyDTO::fromArray($data, validate: false);

// The with() method ALWAYS validates (deprecated $validate param has no effect)
// This is intentional — it prevents invalid state propagation (#2).
$updated = $dto->with(['status' => 'active']);
```

### Custom Cast Types

Extend `castValue()` by using `#[Cast('custom_type')]` and handling it in a subclass,
or use the ValueObject integration for complex types:

```php
// For simple types, use Cast:
#[Cast('integer')]
public readonly int $age;

// For complex types, use ValueObjects:
use ZeroBoiler\ValueObjects\Money;

#[Required]
public readonly Money $salary;
// Auto-hydrated from ['amount' => 5000, 'currency' => 'USD']
// Auto-serialized to ['amount' => 5000, 'currency' => 'USD']
```

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| `Class "X" not found` extending DataTransferObject | DTO class not loaded / missing autoloader | Run `composer dump-autoload` to regenerate the class map |
| `ValidationException` on `fromArray()` | Input data fails attribute-derived rules | Check `DTO::rules()` to see generated rules; add `validate: false` to skip |
| `DTOException: Cannot decode JSON` | `fromJson()` received invalid JSON | Validate JSON before calling `fromJson()`, or catch `DTOException` |
| `DTOException: Expected a JSON object` | `fromJson()` received a JSON array (`[...]`) | The package only accepts JSON objects (`{...}`), not arrays |
| `fromPartialArray()` throws required validation | Missing required fields not handled | Required fields use `'sometimes'` in partial mode, but may still need explicit handling |
| `toArray()` includes `password` | `#[Hidden]` attribute missing | Add `#[Hidden]` to sensitive properties; use `allValues()` to explicitly include all |
| `with()` ignores `$validate: false` | Validation always runs in `with()` | This is intentional — `with()` prevents invalid state propagation |
| Nested DTO not auto-hydrated | Type not recognized as DTO | Ensure the nested class extends `DataTransferObject`; use `#[NestedArray(...)]` for arrays |
| `MapFrom` not working | Key mismatch in source data | Ensure the source key matches the `#[MapFrom('key')]` value exactly (supports dot notation) |
| `Collection` items not DtoCollection | Missing `#[Collection(...)]` attribute | Add `#[Collection(ItemDTO::class)]` to the property |

### FAQ

**Q: Can I use DTOs without Laravel?**
A: The `DataTransferObject` base class requires Laravel's `Validator` facade for validation. The serialization, hydration, and immutability features technically work without Laravel if you disable validation (`validate: false`), but full functionality requires the framework.

**Q: How does `with()` differ from modifying properties directly?**
A: DTOs use `readonly` properties, so direct modification is impossible at the language level. `with()` creates a new instance with merged data and always validates to prevent invalid state.

**Q: Can DTOs have computed/derived properties?**
A: Yes — add public methods to your DTO class. Computed properties won't appear in `toArray()`/`toJson()` automatically, but you can override `toArray()` if needed.

**Q: How do I handle optional fields with `fromPartialArray()`?**
A: Fields present in the input are validated; missing fields use defaults or type-appropriate empty values. Use `validatePresent: true` (default) to validate only present fields.

**Q: What's the difference between `#[NestedArray]` and `#[Collection]`?**
A: `#[NestedArray(DTOClass::class)]` hydrates array elements as plain DTO instances (returns `array`). `#[Collection(DTOClass::class)]` wraps them in a `DtoCollection` with additional helpers (`pluck`, `map`, `filter`).

**Q: Can I use union types in DTO properties?**
A: Yes. Union types (e.g., `string|int`) are supported for hydration. For OpenAPI schemas, union types produce `oneOf` definitions.

## Full-Stack Example

A complete request lifecycle showing how DTOs integrate with controllers,
services, and enums:

### DTO with Enum Property

When a DTO property is typed as a BackedEnum, ZeroBoiler DTO auto-hydrates
and auto-serializes it using the backed value:

```php
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use App\Enums\UserStatus;

class UpdateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        public readonly UserStatus $status,  // BackedEnum property
    ) {}
}

// From request (auto-validates enum value)
$dto = UpdateUserDTO::fromArray([
    'name' => 'Alice',
    'status' => 'active',   // string → UserStatus::ACTIVE
]);

$dto->status->label();      // 'Active'
$dto->status->color();      // 'success'
$dto->toArray();            // ['name' => 'Alice', 'status' => 'active']
$dto->status->value;        // 'active' (string backed)

// Immutable update with enum
$updated = $dto->with(['status' => 'banned']);
$updated->status->is(UserStatus::BANNED);  // true
```

### Complete Controller Example

```php
// app/DTOs/UpdateProfileDTO.php
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

class UpdateProfileDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Required, Email]
        public readonly string $email,

        #[Nullable, Max(255)]
        public readonly ?string $bio = null,

        #[Hidden]
        public readonly ?string $password = null,
    ) {}
}

// app/Http/Controllers/ProfileController.php
class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $dto = UpdateProfileDTO::fromRequest($request);

        // Use DTO values directly — all validated
        $user->update($dto->except('password')->toArray());

        // Immutable update with specific overrides
        $audit = $dto->with(['_audit_reason' => 'profile_update']);

        return response()->json([
            'user' => $dto->toArray(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json([
            'user' => $user->payload->toArray(),  // Eloquent DTO cast
        ]);
    }
}

// app/Models/User.php
class User extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => UpdateProfileDTO::class,  // Auto cast JSON ↔ DTO
            'status'  => UserStatus::class,        // ZeroBoiler Enum cast
        ];
    }
}
```

## Performance Considerations

| Operation | First Call | Subsequent Calls | Notes |
|-----------|-----------|-----------------|-------|
| `fromArray()` | Reflection + metadata build | O(1) hash lookup | Metadata cached per-class statically |
| `fromArray()` with validation | + Validator instantiation | + Validator instantiation | Validation always runs unless explicitly disabled |
| `toArray()` / `toJson()` | N property reads + normalization | Same | No caching — creates new array each call |
| `fromPartialArray()` | Metadata lookup + partial validation | Same | Uses `sometimes` instead of `required` |
| `DtoCollection::pluck()` | N × ReflectionProperty | N × ReflectionProperty | Uses reflection for PHPStan compatibility |
| `fromJson()` | json_decode + fromArray | Same | Rejects sequential arrays |
| `DTOCast::get()` | json_decode + fromArray | Same | Validation skipped for stored data |
| `DTOCast::set()` | json_encode + optional validation | Same | Validates by default |

**Tips for high-performance applications:**
- Metadata is cached after the first `fromArray()` call — no reflection overhead on subsequent calls
- For Octane/Swoole: the package auto-flushes metadata on `octane.terminate`
- In `local`/`testing` environments, metadata TTL is set to 2 seconds for hot-reload support
- Use `validate: false` for pre-validated data (e.g., reading from trusted storage)
- `DtoCollection::pluck()` uses reflection — if you need maximum performance, use `$collection->map(fn($dto) => $dto->field)` instead
- `with()` always validates — for performance-critical paths, batch updates via `fromArray()` with `validate: false` then validate manually
- `toArray()` is called on every serialization — cache the result if used repeatedly

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a history of changes.

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| 1.0.0 | 2025-08 | Initial release — DataTransferObject, DtoCollection, validation attributes, OpenAPI schema |
| 1.1.0 | 2025-08 | Nested DTO hydration, Collection attribute, fromPartialArray, ValueObject integration, extended test suite |
| 1.1.2 | 2025-08-14 | Test count update (240 files), new facade contract tests, README accuracy fix |
| 1.1.3 | 2025-08-14 | CHANGELOG.md added, README test count accuracy, full source audit — production ready |

## Internal Components

### DtoMetadataResolver

The `DtoMetadataResolver` reads constructor parameters via reflection, detects
attribute types (ValueObject, BackedEnum, nested DTO), infers base validation
rules from PHP types, and collects validation attribute rules. Results are
cached by `DataTransferObject::resolveMetadata()` with TTL-based invalidation.

```php
use ZeroBoiler\DTO\Support\DtoMetadataResolver;

$metadata = DtoMetadataResolver::resolve(CreateUserDTO::class);
// Returns:
// [
//     'properties' => [
//         'email' => ['map_from' => null, 'default' => null, 'has_default' => false, ...],
//         ...
//     ],
//     'rules' => [
//         'email' => ['required', 'email'],
//         'name'  => ['required', 'min:2', 'max:50'],
//     ],
//     'messages' => [],
// ]
```

Direct usage is typically only needed for custom tooling or extensions.
The resolver automatically detects:
- **ValueObject** types (from `zeroboiler/value-objects`)
- **BackedEnum** types for auto-casting and validation rule generation
- **Nested DTO** types for recursive hydration
- **Union types** with special handling for each member

### OpenApiSchemaGenerator

Generates OpenAPI 3.0 schemas from DTO class definitions with full support for:

- Scalar types (`string`, `int`, `float`, `bool`)
- Nullable properties (`nullable: true`)
- Validation constraints (`minimum`, `maximum`, `minLength`, `maxLength`, `pattern`, `format`)
- Enum constraints (`enum: [values]`)
- Nested DTOs (`$ref` to component schemas)
- Union types (`oneOf` schemas)
- ValueObject types (inferred from `columnType()`)
- Required field detection (from type nullability, defaults, and `#[Required]`)

```php
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

// Basic schema (no nested DTOs)
$schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

// With component schemas for nested DTOs
$result = OpenApiSchemaGenerator::generateWithComponents(OrderDTO::class);
// ['schema' => [...], 'components' => ['schemas' => ['AddressDTO' => [...]]]]
```

### Class Structure

```
src/
├── Attributes/
│   ├── Required.php       #[Required]              — Required field
│   ├── Email.php          #[Email]                  — Email validation
│   ├── Max.php            #[Max(255)]               — Maximum length/value
│   ├── Min.php            #[Min(2)]                 — Minimum length/value
│   ├── Pattern.php        #[Pattern('/regex/')]     — Regex pattern
│   ├── Enum.php           #[Enum(Status::class)]    — Backed enum validation
│   ├── In.php             #[In(['a', 'b'])]         — Whitelist values
│   ├── Url.php            #[Url]                    — URL validation
│   ├── Uuid.php           #[Uuid]                   — UUID validation
│   ├── Between.php        #[Between(1, 10)]         — Range constraint
│   ├── Boolean.php        #[Boolean]                — Boolean validation
│   ├── Integer.php        #[Integer]                — Integer validation
│   ├── Numeric.php        #[Numeric]                — Numeric validation
│   ├── Date.php           #[Date] / #[Date('Y-m-d')] — Date validation
│   ├── StartsWith.php     #[StartsWith('prefix')]   — Prefix constraint
│   ├── EndsWith.php       #[EndsWith('suffix')]     — Suffix constraint
│   ├── Json.php           #[Json]                   — JSON string validation
│   ├── Accepted.php       #[Accepted]               — Must be accepted
│   ├── Declined.php       #[Declined]               — Must be declined
│   ├── Confirmed.php      #[Confirmed]              — Confirmation field
│   ├── Distinct.php       #[Distinct]               — Unique array elements
│   ├── Prohibited.php     #[Prohibited]             — Must not be present
│   ├── Present.php        #[Present]                — Must be present
│   ├── Sometimes.php      #[Sometimes]              — Validate only if present
│   ├── Nullable.php       #[Nullable]               — Allows null
│   ├── Same.php           #[Same('field')]           — Must match another field
│   ├── Different.php      #[Different('field')]     — Must differ from field
│   ├── RequiredIf.php     #[RequiredIf('field', val)] — Conditional required
│   ├── RequiredUnless.php #[RequiredUnless(...)]      — Conditional required
│   ├── RequiredWith.php   #[RequiredWith('field')]   — Conditional required
│   ├── RequiredWithAll.php #[RequiredWithAll(...)]   — Conditional required
│   ├── RequiredWithout.php #[RequiredWithout(...)]   — Conditional required
│   ├── RequiredWithoutAll.php #[RequiredWithoutAll(...)] — Conditional required
│   ├── ArrayRule.php      #[ArrayRule] / #[ArrayRule(min:1, max:10)] — Array constraint
│   ├── Size.php           #[Size(5)]                — Exact size constraint
│   ├── Cast.php           #[Cast('integer')]         — Type casting during hydration
│   ├── MapFrom.php        #[MapFrom('source_key')]  — Source key aliasing
│   ├── Hidden.php         #[Hidden]                 — Exclude from serialization
│   ├── DefaultValue.php   #[DefaultValue('active')] — Default when missing
│   ├── NestedArray.php    #[NestedArray(DTO::class)] — Array of nested DTOs
│   └── Collection.php     #[Collection(DTO::class)]  — DtoCollection of DTOs
├── Casts/
│   └── DTOCast.php        — Eloquent cast for DTO ↔ JSON
├── Console/Commands/
│   ├── MakeDtoTestCommand.php  — Test generation CLI
│   └── MakeDtoSchemaCommand.php — OpenAPI schema CLI
├── Contracts/
│   ├── FromRequestDTO.php     — Hydration from HTTP request
│   ├── ValidatableDTO.php     — Validation rules contract
│   └── ValidationAttribute.php — Validation attribute contract
├── Exceptions/
│   └── DTOException.php   — Named constructors for DTO errors
├── Facades/
│   └── DTO.php            — Laravel facade for DTOManager
├── Support/
│   ├── DtoMetadataResolver.php     — Reflection-based metadata resolution
│   └── OpenApiSchemaGenerator.php  — OpenAPI 3.0 schema generation
├── DataTransferObject.php — Abstract base class with all public API
├── DtoCollection.php      — Type-safe collection of DTO instances
├── DTOManager.php         — Runtime DTO helper (facade-backed)
└── DTOSServiceProvider.php — Auto-discovery service provider
```

## Test Fixtures

The test suite uses a set of representative DTO fixtures covering all supported
features and edge cases:

| Fixture | Features | Tests |
|---------|----------|-------|
| `CreateUserDTO` | `Required`, `Email`, `Hidden`, `Min`, `Max` | Basic validation, hidden fields, serialization |
| `AddressDTO` | `Required`, `Pattern`, `Min`, nested in OrderDTO | Nested DTO hydration, regex validation |
| `OrderDTO` | `Required`, nested `AddressDTO`, `NestedArray` | Nested DTO auto-hydration, recursive `toArray()` |
| `OrderItemDTO` | `Required`, `Min`, used with `Collection` | DtoCollection element type |
| `ProductDTO` | `Required`, `Between`, `Integer`, `Min` | Numeric constraints, type inference |
| `ActionScopedDTO` | `Required`, `Email`, `Min`, `DefaultValue`, `Nullable` + `rulesFor()` | Action-scoped validation (create/update) |
| `MixedAttributesDTO` | `Required`, `Min`, `Max`, `Pattern`, `MapFrom`, `Cast`, `DefaultValue`, `Hidden`, `array`, `bool` | Mixed attribute types integration |
| `MinimalDTO` | `Required` only | Edge case: simplest possible DTO |
| `EmptyDTO` | No properties | Edge case: empty DTO |
| `UnionTypeDTO` | `Required`, union type `int|string` | Union type support, OpenAPI `oneOf` |
| `ValidationTestDTO` | `Required`, `Max`, `Email` | Validation rule generation |
| `DeepNestedDTO` | Multi-level nesting | Deep nesting edge cases |
| `DateCastDTO` | `Cast('date')`, `DateTime` | Date/datetime casting |
| `DateTimeCastDTO` | `Cast('datetime')` | Datetime casting variant |
| `ArrayCastDTO` | `Cast('array')`, `Json` | Array/JSON cast edge cases |
| `ScalarConstraintsDTO` | `Integer`, `Boolean`, `Numeric`, `Min`, `Max` | Scalar type constraints |
| `MultiConstraintDTO` | `Required`, `Email`, `Min`, `Max`, `Pattern` | Multiple constraints per field |
| `ConstraintCompositeDTO` | `Distinct`, `ArrayRule(min, max)`, `In` | Composite/array constraints |
| `OpenApiValidationDTO` | `Required`, `Email`, `Max`, `Pattern`, `Between` | OpenAPI schema generation |
| `RegistrationDTO` | `Required`, `Email`, `Min`, `Max`, `Confirmed`, `Accepted`, `Same` | Registration flow validation |
| `TaskListDTO` | `Required`, `ArrayRule`, `Collection` | Array + collection integration |
| `VoUserDTO` | ValueObject properties (`Email`, `Url`, `Money`) | ValueObject auto-instantiation and serialization |
| `WithBypassDTO` | `validate: false` usage | Validation bypass paths |

```php
// Example: MixedAttributesDTO exercises the full attribute surface
use ZeroBoiler\DTO\Tests\Fixtures\MixedAttributesDTO;

$dto = MixedAttributesDTO::fromArray([
    'username' => 'alice',         // Required, Min(3), Max(100)
    'hexCode' => 'a1b2c3',        // Required, Pattern('/^[a-f0-9]{6}$/')
    'user_email' => 'a@b.com',    // MapFrom('user_email') → $email
    'age' => '25',                // Cast('integer') → int
    // 'role' omitted → DefaultValue('user')
    'token' => 'secret',         // Hidden — excluded from toArray()
    'isActive' => '1',            // bool, CastToBool
    'tags' => ['php', 'laravel'], // array
]);

$dto->email;    // 'a@b.com'
$dto->age;      // 25 (int)
$dto->role;     // 'user'
$dto->toArray(); // token excluded
```

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

### Test Coverage

The test suite includes **255 test files** (222 unit tests + 33 fixtures) covering:

| Category | Tests | What's Covered |
|----------|-------|----------------|
| **Core** | `DTOTest`, `DataTransferObjectTest` | `fromArray()`, `fromRequest()`, `fromJson()`, `toArray()`, `toJson()` |
| **Serialization** | `SelectiveOutputTest`, `DTOJsonAndEmptyTest`, `DTOStateAndEdgeCaseTest` | `only()`, `except()`, `allValues()`, `isEmpty()`, `isNotEmpty()`, state checks |
| **Immutability** | Various | `with()`, `equals()`, readonly enforcement |
| **Partial Updates** | `PartialUpdateTest` | `fromPartialArray()`, `fromPartialRequest()`, `validatePartialArray()` |
| **Validation** | `WithValidationTest`, `ValidationExecutionTest`, `ConstraintCompositeTest` | Attribute rules, custom messages, composite rules |
| **Nested DTOs** | `NestedDtoAndValidationTest` | Auto-hydration, recursive `toArray()` |
| **Collections** | `DtoCollectionTest`, `DtoCollectionComprehensiveTest`, `DtoCollectionPluckAndHelpersTest` | `pluck()`, `pluckKey()`, `map()`, `filter()`, `push()`, `offsetUnset`, `jsonSerialize` |
| **Value Objects** | `ValueObjectIntegrationTest` | Auto-instantiation, serialization to primitives |
| **Enum Integration** | `EnumRoundtripTest` | Backed enum casting and roundtrip |
| **JSON** | `InvalidJsonCastTest`, `ArrayCastEdgeCasesTest` | JSON decode errors, sequential array rejection |
| **OpenAPI** | `OpenApiSchemaTest`, `OpenApiNestedAndUnionTest` | Schema generation, `$ref`, `oneOf` |
| **Eloquent** | `DTOCastTest`, `DTOCastValidationTest` | get/set/serialize with type validation |
| **CLI** | `ConsoleCommandsTest` | `dto-test` and `dto-schema` commands |
| **Edge Cases** | `DTOEdgeCasesAndStanTest`, `DTOComprehensiveEdgeCaseTest` | Empty DTOs, union types, action-scoped rules |
| **PHPStan** | `DTOPhpStanComplianceTest`, `DTOStanComplianceTest`, `PhpStanCleanTest` | No mixed types, strict comparisons |
| **Fixtures** | `CreateUserDTO`, `AddressDTO`, `OrderDTO`, `ProductDTO`, `MixedAttributesDTO`, `DeepNestedDTO`, etc. | Various DTO patterns and configurations |

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

## Compatibility

| Dependency | Minimum Version | Maximum Version | Notes |
|------------|----------------|-----------------|-------|
| PHP | 8.5 | — | Uses `readonly` promoted properties, attributes, match expressions |
| Laravel | 13.0 | — | Requires `illuminate/contracts`, `illuminate/http`, `illuminate/support`, `illuminate/validation` |
| `zeroboiler/value-objects` | 1.0 | — | ValueObject integration for auto-instantiation and serialization |
| Pest (dev) | 3.0 | — | Test framework — not required for production |
| PHPStan (dev) | 2.0 | — | Static analysis — targets level 9 |

**PHP 8.5 Feature Usage:**

| Feature | Where Used | Purpose |
|---------|-----------|---------|
| `readonly` promoted properties | All DTO classes, `DataTransferObject` | Language-level immutability |
| `Attribute` | All 30+ validation/metadata attributes | PHP 8 attribute system |
| `match` expressions | `DtoMetadataResolver`, `DataTransferObject::castValue()` | Type-safe pattern matching |
| `named arguments` | Attribute constructors, `fromArray()`, `with()` | Explicit parameter naming |
| `first-class callable syntax` | `DtoCollection::map()`, `filter()` | `array_map(fn ..., $items)` |
| `#[Override]` | Service provider, `DTOCast`, DTO contracts | Explicit interface/parent implementation |
| `array_is_list` | `DataTransferObject::fromJson()` | Sequential array detection |
| `array_any` | `OpenApiSchemaGenerator::hasAttribute()` | Predicate-based array search |

## Laravel Compatibility

The package uses only stable Laravel contracts (`CastsAttributes`, `ValidationRule`,
`ServiceProvider`, `Facade`, `Validator`). No bleeding-edge or internal APIs are
used, ensuring forward compatibility with future Laravel releases.

| Laravel API | Usage | Contract |
|-------------|-------|----------|
| `CastsAttributes` | `DTOCast` — Eloquent get/set/serialize | `illuminate/contracts` |
| `Validator` | `Validator::make()` — attribute-derived rules | `illuminate/validation` |
| `ValidationRule` | Not used (DTO uses `Validator` facade directly) | `illuminate/contracts` |
| `ServiceProvider` | `DTOSServiceProvider` — auto-discovery | `illuminate/support` |
| `Facade` | `DTO` facade — runtime access | `illuminate/support` |
| `Request` | `FromRequestDTO` contract | `illuminate/http` |

## License

Proprietary — © ZeroBoiler

## Migration Guide

### From Form Requests

If you're migrating from Laravel Form Requests with manual validation:

**Before (Form Request):**

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:255'],
            'name'     => ['required', 'string', 'min:2', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'phone'    => ['nullable', 'string'],
        ];
    }
}

class UserController extends Controller
{
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::create($validated);

        return response()->json($user);
    }
}
```

**After (ZeroBoiler DTO):**

```php
class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Required, Min(8)]
        #[Hidden]
        public readonly string $password,

        public readonly ?string $phone = null,
    ) {}
}

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dto = CreateUserDTO::fromRequest($request);
        $user = User::create($dto->toArray());

        return response()->json([
            'user' => $dto->except('password')->toArray(),
        ]);
    }
}
```

Benefits of the DTO approach:
- Validation rules live next to the data definition (single source of truth)
- Hidden fields excluded from output automatically
- Type-safe access (`$dto->email` is `string`, not `array-access`)
- Reusable across controllers (API + web)
- PATCH semantics via `fromPartialRequest()`
- OpenAPI schema generation from the same class

### From Manual Array Casting

```php
// Before — manual array DTO with no validation
class UserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['email'], $data['name']);
    }

    public function toArray(): array
    {
        return ['email' => $this->email, 'name' => $this->name];
    }
}

// After — ZeroBoiler handles hydration, validation, serialization
class UserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,
    ) {}
}
```

### From Eloquent Accessors/Casts

```php
// Before — manual JSON casting + accessor logic
class User extends Model
{
    protected $casts = ['metadata' => 'array'];

    public function getMetadataAttribute($value): ?array
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }
}

// After — ZeroBoiler DTO cast (auto-validates on set)
class User extends Model
{
    protected function casts(): array
    {
        return ['metadata' => UserMetadataDTO::class];
    }
}
```

## Quality Assurance

### Static Analysis Compliance (PHPStan Level 9)

Every source file in this package passes PHPStan level 9 analysis with zero errors
and no baseline suppressions. The following checklist is maintained manually:

| File | `strict_types` | `final` | Typed Props | Return Types | Docblocks |
|------|:---:|:---:|:---:|:---:|:---:|
| `DataTransferObject.php` | ✅ | abstract | N/A (static) | ✅ all | ✅ |
| `DtoCollection.php` | ✅ | ✅ | ✅ | ✅ all | ✅ |
| `DTOManager.php` | ✅ | ✅ | N/A (methods) | ✅ all | ✅ |
| `DTOException.php` | ✅ | ✅ | N/A | ✅ all | ✅ |
| `DTOCast.php` | ✅ | ✅ | ✅ readonly | ✅ all | ✅ |
| `DtoMetadataResolver.php` | ✅ | ✅ | N/A (static) | ✅ all | ✅ |
| `OpenApiSchemaGenerator.php` | ✅ | ✅ | N/A (static) | ✅ all | ✅ |
| `DTOSServiceProvider.php` | ✅ | ✅ | N/A | ✅ all | ✅ |
| `DTO.php` (Facade) | ✅ | ✅ | N/A | ✅ | ✅ |
| `FromRequestDTO.php` (Interface) | ✅ | N/A | N/A | ✅ | ✅ |
| `ValidatableDTO.php` (Interface) | ✅ | N/A | N/A | ✅ | ✅ |
| `ValidationAttribute.php` (Interface) | ✅ | N/A | N/A | ✅ | ✅ |
| 37 Validation Attributes | ✅ | ✅ all | ✅ readonly | ✅ `ruleKey()` | ✅ |
| 4 Metadata Attributes | ✅ | ✅ all | ✅ readonly | N/A | ✅ |
| CLI Commands (2) | ✅ | ✅ | N/A | ✅ | ✅ |

### Code Quality Checklist

- [x] **`declare(strict_types=1)`** — Present in every PHP file
- [x] **No `mixed` types in public API** — All public method parameters and returns are typed; `mixed` only in internal cast/hydration pipelines with explicit `@param` annotations
- [x] **Strict comparisons** — `===` used everywhere (no `==` for value comparison)
- [x] **`final` classes** — All attributes, services, collections, resolvers, and managers are `final`
- [x] **`readonly` properties** — All DTO properties must be `public readonly`; all attribute constructors use `readonly` promoted properties
- [x] **`#[Override]`** — Applied to all interface/parent method implementations (`CastsAttributes`, `JsonSerializable`, `Arrayable`, `ValidatorContract`)
- [x] **Docblocks** — All public methods, classes, and properties documented
- [x] **`@phpstan-type`** — Complex array shapes (`DtoPropertyMeta`, `DtoResolvedMetadata`) documented with PHPStan type aliases
- [x] **Exception safety** — All error paths throw typed exceptions (`DTOException`, `ValidationException`, `InvalidArgumentException`)
- [x] **Immutable by default** — `readonly` properties + `with()` returns new instance
- [x] **Hidden field safety** — `#[Hidden]` excludes from `toArray()`/`toJson()`; `allValues()` available for explicit inclusion
- [x] **Validation always runs in `with()`** — Prevents invalid state propagation (design decision documented with `@deprecated` note on `$validate` param)

### Design Decisions

| Decision | Rationale |
|----------|-----------|
| Abstract base class (not interface) | Shared hydration/serialization logic in one place; subclasses only define properties |
| `fromArray()` with `validate: bool` | Opt-out for trusted data (e.g., reading from DB via `DTOCast::get()`) |
| `with()` always validates | Prevents invalid state propagation — the `$validate` param is kept for backward compatibility but ignored |
| `fromPartialArray()` for PATCH semantics | Relaxes `required` → `sometimes` for present fields only; missing fields use defaults |
| Static metadata cache (not singleton) | Each DTO class manages its own cache; no cross-class state |
| TTL-based cache in dev environments | 2-second TTL in `local`/`testing` for hot-reload support |
| `DtoCollection` separate from `DataTransferObject` | Collections are not DTOs — they hold DTOs but aren't DTOs themselves |
| Reflection-based `pluck()`/`pluckKey()` | Avoids dynamic property access that triggers PHPStan warnings on `readonly` properties |
| `#[ValidationAttribute]` marker interface | Enables custom message collection via `ruleKey()` — eliminates fragile class-name parsing |
| `OpenApiSchemaGenerator` as separate class | Single responsibility; schema generation is an orthogonal concern to hydration |

## Source Code Audit — Attribute Contract Compliance

### Validation Attributes (37 total)

All validation attributes are `final`, implement `ValidationAttribute`, and have
`#[Attribute(Attribute::TARGET_PROPERTY)]` targeting. Every attribute has
an optional `?string $message` constructor parameter for custom Laravel
error messages and implements `ruleKey()`.

| Attribute | `final` | `ValidationAttribute` | Rule Generated | Constructor Params |
|-----------|:-------:|:---------------------:|---------------|-------------------|
| `Required` | ✅ | ✅ | `required` | `?string $message` |
| `Email` | ✅ | ✅ | `email` | `?string $message` |
| `Max` | ✅ | ✅ | `max:N` | `int\|float $value`, `?string $message` |
| `Min` | ✅ | ✅ | `min:N` | `int\|float $value`, `?string $message` |
| `Url` | ✅ | ✅ | `url` | `?string $message` |
| `Uuid` | ✅ | ✅ | `uuid` | `?string $message` |
| `Pattern` | ✅ | ✅ | `regex:...` | `string $regex`, `?string $message` |
| `In` | ✅ | ✅ | `in:a,b` | `array $values`, `?string $message` |
| `Enum` | ✅ | ✅ | Laravel `Enum` rule | `string $enumClass`, `?string $message` |
| `Integer` | ✅ | ✅ | `integer` | `?string $message` |
| `Numeric` | ✅ | ✅ | `numeric` | `?string $message` |
| `Boolean` | ✅ | ✅ | `boolean` | `?string $message` |
| `Date` | ✅ | ✅ | `date` / `date_format:F` | `?string $format`, `?string $message` |
| `StartsWith` | ✅ | ✅ | `starts_with:...` | `string\|array $prefix`, `?string $message` |
| `EndsWith` | ✅ | ✅ | `ends_with:...` | `string\|array $suffix`, `?string $message` |
| `Between` | ✅ | ✅ | `between:N,M` | `int\|float $min`, `int\|float $max`, `?string $message` |
| `Size` | ✅ | ✅ | `size:N` | `int\|float $value`, `?string $message` |
| `ArrayRule` | ✅ | ✅ | `array` (+ min/max) | `?int $min`, `?int $max`, `?string $message` |
| `Json` | ✅ | ✅ | `json` | `?string $message` |
| `Accepted` | ✅ | ✅ | `accepted` | `?string $message` |
| `Declined` | ✅ | ✅ | `declined` | `?string $message` |
| `Confirmed` | ✅ | ✅ | `confirmed` | `?string $message` |
| `Distinct` | ✅ | ✅ | `distinct` | `?string $message` |
| `Prohibited` | ✅ | ✅ | `prohibited` | `?string $message` |
| `Present` | ✅ | ✅ | `present` | `?string $message` |
| `Sometimes` | ✅ | ✅ | `sometimes` | `?string $message` |
| `Nullable` | ✅ | ✅ | `nullable` | `?string $message` |
| `Same` | ✅ | ✅ | `same:field` | `string $field`, `?string $message` |
| `Different` | ✅ | ✅ | `different:field` | `string $field`, `?string $message` |
| `RequiredIf` | ✅ | ✅ | `required_if:...` | `string $field`, `mixed $value`, `?string $message` |
| `RequiredUnless` | ✅ | ✅ | `required_unless:...` | `string $field`, `mixed $value`, `?string $message` |
| `RequiredWith` | ✅ | ✅ | `required_with:...` | `list<string> $fields`, `?string $message` |
| `RequiredWithAll` | ✅ | ✅ | `required_with_all:...` | `list<string> $fields`, `?string $message` |
| `RequiredWithout` | ✅ | ✅ | `required_without:...` | `list<string> $fields`, `?string $message` |
| `RequiredWithoutAll` | ✅ | ✅ | `required_without_all:...` | `list<string> $fields`, `?string $message` |
| `NestedArray` | ✅ | ✅ | `array` | `string $dtoClass`, `?string $message` |
| `Collection` | ✅ | ✅ | `array` | `string $dtoClass`, `?string $message` |

### Metadata Attributes (4 total)

Metadata attributes provide hydration and serialization behavior, not validation:

| Attribute | `final` | `readonly` | Target | Purpose |
|-----------|:-------:|:----------:|--------|---------|
| `Cast` | ✅ | ✅ | `TARGET_PROPERTY` | Type casting during hydration |
| `MapFrom` | ✅ | ✅ | `TARGET_PROPERTY` | Source key aliasing (supports dot notation) |
| `Hidden` | ✅ | — (no props) | `TARGET_PROPERTY` | Exclude from `toArray()`/`toJson()` |
| `DefaultValue` | ✅ | ✅ | `TARGET_PROPERTY \| TARGET_PARAMETER` | Default when source key is absent |

### Service & Infrastructure Classes

| Class | Type | `final` | `readonly` | Key Methods |
|-------|------|:-------:|:----------:|-------------|
| `DataTransferObject` | `abstract class` | — | — (static cache) | `fromArray()`, `fromRequest()`, `fromJson()`, `fromPartialArray()`, `toArray()`, `toJson()`, `only()`, `except()`, `with()`, `equals()`, `isEmpty()`, `rules()`, `rulesFor()`, `validateArray()`, `validatePartialArray()`, `flushMetadataCache()`, `setMetadataCacheTtl()` |
| `DtoCollection` | `final class` | ✅ | — | `make()`, `push()`, `append()`, `merge()`, `first()`, `last()`, `map()`, `filter()`, `pluck()`, `pluckKey()`, `items()`, `toArray()`, `allValues()`, `count()`, `isEmpty()`, `isNotEmpty()`, `offsetExists/Get/Set/Unset()`, `getIterator()`, `jsonSerialize()` |
| `DTOManager` | `final readonly class` | ✅ | ✅ | `validate()`, `make()`, `makeFromJson()`, `fromJson()`, `fromPartialArray()`, `fromPartialRequest()`, `rules()`, `rulesFor()`, `schema()` |
| `DTOCast` | `final class` | ✅ | ✅ | `get()`, `set()`, `serialize()` |
| `DTOException` | `final class` | ✅ | — | `invalidCast()`, `invalidJson()` |
| `DTO` (Facade) | `final class` | ✅ | — | `getFacadeAccessor()` |
| `DTOSServiceProvider` | `final class` | ✅ | — | `register()`, `boot()` |
| `FromRequestDTO` | `interface` | — | — | `fromRequest()` |
| `ValidatableDTO` | `interface` | — | — | `rules()`, `rulesFor()` |
| `ValidationAttribute` | `interface` | — | — | `ruleKey()` |
| `DtoMetadataResolver` | `final class` | ✅ | — (static) | `resolve()`, `inferBaseRules()`, `detectValueObjectClass()`, `detectEnumClass()`, `detectDtoClass()` |
| `OpenApiSchemaGenerator` | `final class` | ✅ | — (static) | `generate()`, `generateWithComponents()` |
| `MakeDtoTestCommand` | `final class` | ✅ | — | `handle()`, `generateFakeData()` |
| `MakeDtoSchemaCommand` | `final class` | ✅ | — | `handle()` |

## Type Safety & PHPStan Level 9

This package is designed for **PHPStan level 9** compliance out of the box.

### What This Means

| Guarantee | Implementation |
|-----------|---------------|
| **No `mixed` in public API** | All public method parameters and returns are explicitly typed; `mixed` only in internal cast/hydration pipelines |
| **No dynamic property access** | `DtoCollection::pluck()`/`pluckKey()` use `ReflectionProperty` to read `readonly` properties |
| **Strict identity comparisons** | `===` used everywhere — no loose `==` for value comparison |
| **Typed array shapes** | `@phpstan-type` aliases for `DtoPropertyMeta` and `DtoResolvedMetadata` |
| **`#[Override]` on all overrides** | `CastsAttributes`, `JsonSerializable`, `Arrayable`, `ValidatorContract` methods annotated |
| **Final classes throughout** | All attributes, resolvers, managers, collections, and exceptions are `final` |
| **`readonly` promoted properties** | All DTO properties must be `public readonly`; all attribute constructors use `readonly` |
| **Immutable by default** | `readonly` language keyword + `with()` returns new instance |
| **Validation always runs in `with()`** | Prevents invalid state propagation — `$validate` param kept for backward compat |

### Running PHPStan

```bash
vendor/bin/phpstan analyse        # uses phpstan.neon (level 9)
vendor/bin/phpstan analyse src/  # explicit path
```

The `phpstan.neon` configuration:
- Level 9 (maximum strictness)
- PHP 8.5 target version
- Larastan bootstrap enabled
- Tests excluded from analysis

## Quick Start Integration

Add ZeroBoiler DTO to an existing Laravel project in three steps:

### Step 1: Install

```bash
composer require zeroboiler/dto
```

### Step 2: Create a DTO

```php
// app/DTOs/CreateOrderDTO.php
use ZeroBoiler\DTO\Attributes\{Email, Hidden, Max, Min, Required};
use ZeroBoiler\DTO\DataTransferObject;

class CreateOrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $customerEmail,

        #[Required, Min(1), Max(100)]
        public readonly int $quantity,

        #[Hidden]
        public readonly ?string $internalNote = null,
    ) {}
}
```

### Step 3: Use Everywhere

```php
// In controllers
$dto = CreateOrderDTO::fromRequest($request);
// Auto-validates: email required & valid, quantity 1-100

// In service classes
$dto = CreateOrderDTO::fromArray([
    'customerEmail' => 'buyer@example.com',
    'quantity' => 5,
]);
$dto->customerEmail; // 'buyer@example.com'
$dto->toArray();     // ['customerEmail' => '...', 'quantity' => 5] — hidden excluded

// In Eloquent models
protected $casts = ['payload' => CreateOrderDTO::class];

// In Form Requests (reuse validation rules)
CreateOrderDTO::rules();
// ['customerEmail' => ['required', 'email'], 'quantity' => ['required', 'min:1', 'max:100']]
```

No service provider registration, no configuration files — it just works.

## Cross-Package Integration

ZeroBoiler DTO integrates seamlessly with [ZeroBoiler Enums](https://github.com/zeroboiler/enums) for
end-to-end type safety. BackedEnum properties are auto-hydrated and auto-serialized.

### Using Enum Properties in DTOs

```php
use ZeroBoiler\Enums\Concerns\HasEnumMetadata;
use ZeroBoiler\Enums\Attributes\EnumColor;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;

#[EnumColor(success: ['active'], danger: ['banned'])]
enum UserStatus: string
{
    use HasEnumMetadata;
    case ACTIVE = 'active';
    case BANNED = 'banned';
}

class UpdateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[Required]
        public readonly UserStatus $status,
    ) {}
}

// String → BackedEnum (auto-hydrated via castValueToEnum)
$dto = UpdateUserDTO::fromArray(['name' => 'Alice', 'status' => 'active']);
$dto->status;            // UserStatus::ACTIVE
$dto->status->label();   // 'Active'
$dto->status->color();   // 'success'

// BackedEnum → string (auto-serialized via normalizeScalar)
$dto->toArray();
// ['name' => 'Alice', 'status' => 'active']

// Immutable update with enum
$updated = $dto->with(['status' => 'banned']);
$updated->status->is(UserStatus::BANNED);  // true
```

### Enum Roundtrip in with()

The `with()` method correctly handles enum roundtrips:
- `toArray()` serializes enums to their **backed value** (string/int)
- `fromArray()` reconstructs enum instances from backed values
- This ensures `$dto->with(['status' => 'active'])` works identically to direct construction

### Using Enum Metadata in Controllers

```php
class UserController extends Controller
{
    public function update(Request $request, int $id): JsonResponse
    {
        $dto = UpdateUserDTO::fromRequest($request);

        // Enum comparison + metadata — zero magic strings
        if ($dto->status->is(UserStatus::BANNED)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $user->update($dto->toArray());

        return response()->json([
            'user'      => $dto->toArray(),
            'statuses'  => UserStatus::forSelect(),
        ]);
    }
}
```

### Eloquent Model with Both Casts

```php
class User extends Model
{
    protected function casts(): array
    {
        return [
            'status'  => UserStatus::class,     // ZeroBoiler Enum cast
            'profile' => UpdateUserDTO::class,   // ZeroBoiler DTO cast
        ];
    }
}
```

Both casts work independently: `status` serializes to a scalar (string/int),
while `profile` serializes to/from JSON.

## Source Code Structure

### Attribute Type Signatures

All validation attributes share a common structure:

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
final class <Name> implements ValidationAttribute
{
    public function __construct(
        public readonly <typed_params>,
        public readonly ?string $message = null,  // optional custom message
    ) {}

    public function ruleKey(): string { return '<rule>'; }
}
```

| Category | Attribute | Constructor Signature | Rule Generated |
|----------|-----------|----------------------|---------------|
| **Presence** | `Required` | `()` | `required` |
| | `Sometimes` | `()` | `sometimes` |
| | `Present` | `()` | `present` |
| | `Prohibited` | `()` | `prohibited` |
| | `Nullable` | `()` | `nullable` |
| **String** | `Email` | `()` | `email` |
| | `Url` | `()` | `url` |
| | `Uuid` | `()` | `uuid` |
| | `Pattern` | `(string $regex)` | `regex:...` |
| | `Min` | `(int\|float $value)` | `min:N` |
| | `Max` | `(int\|float $value)` | `max:N` |
| | `Size` | `(int $value)` | `size:N` |
| | `Between` | `(int\|float $min, int\|float $max)` | `between:N,M` |
| | `StartsWith` | `(string\|array $prefix)` | `starts_with:...` |
| | `EndsWith` | `(string\|array $suffix)` | `ends_with:...` |
| **Numeric** | `Integer` | `()` | `integer` |
| | `Numeric` | `()` | `numeric` |
| | `Boolean` | `()` | `boolean` |
| **Enum/Set** | `In` | `(array $values)` | `in:a,b,...` |
| | `Enum` | `(string $enumClass)` | Laravel `Enum` rule |
| **Array** | `ArrayRule` | `(?int $min, ?int $max)` | `array` + optional min/max |
| | `Distinct` | `()` | `distinct` |
| | `Json` | `()` | `json` |
| **Date** | `Date` | `(?string $format)` | `date` or `date_format:F` |
| **Confirmation** | `Accepted` | `()` | `accepted` |
| | `Declined` | `()` | `declined` |
| | `Confirmed` | `()` | `confirmed` |
| | `Same` | `(string $field)` | `same:field` |
| | `Different` | `(string $field)` | `different:field` |
| **Conditional** | `RequiredIf` | `(string $field, mixed $value)` | `required_if:...` |
| | `RequiredUnless` | `(string $field, mixed $value)` | `required_unless:...` |
| | `RequiredWith` | `(list<string> $fields)` | `required_with:...` |
| | `RequiredWithAll` | `(list<string> $fields)` | `required_with_all:...` |
| | `RequiredWithout` | `(list<string> $fields)` | `required_without:...` |
| | `RequiredWithoutAll` | `(list<string> $fields)` | `required_without_all:...` |
| **Metadata** | `Cast` | `(string $type)` | *(none — hydration only)* |
| | `MapFrom` | `(string $key)` | *(none — hydration only)* |
| | `Hidden` | `()` | *(none — serialization only)* |
| | `DefaultValue` | `(mixed $value)` | *(none — hydration only)* |
| | `NestedArray` | `(string $dtoClass)` | `array` |
| | `Collection` | `(string $dtoClass)` | `array` |

### Directory Layout

```
src/
├── Attributes/              # PHP 8 attribute classes for validation & metadata
│   ├── Required.php         # → 'required' rule
│   ├── Email.php            # → 'email' rule
│   ├── Max.php              # → 'max:N' rule
│   ├── Min.php              # → 'min:N' rule
│   ├── Pattern.php          # → 'regex:...' rule
│   ├── Url.php              # → 'url' rule
│   ├── Uuid.php             # → 'uuid' rule
│   ├── Integer.php          # → 'integer' rule
│   ├── Numeric.php          # → 'numeric' rule
│   ├── Boolean.php          # → 'boolean' rule
│   ├── In.php               # → 'in:...' rule
│   ├── Between.php          # → 'between:N,M' rule
│   ├── Size.php             # → 'size:N' rule
│   ├── ArrayRule.php        # → 'array' + optional min/max rules
│   ├── Json.php             # → 'json' rule
│   ├── Enum.php             # → Laravel Enum rule (auto-detected from type)
│   ├── Cast.php             # Type casting (int, string, bool, array, date)
│   ├── MapFrom.php          # Source key aliasing (supports dot notation)
│   ├── Hidden.php           # Excludes property from toArray()/toJson()
│   ├── DefaultValue.php     # Default value when source key is missing
│   ├── Nullable.php         # → 'nullable' rule
│   ├── NestedArray.php      # Array of nested DTO instances
│   ├── Collection.php       # Type-safe DtoCollection of nested DTO instances
│   ├── Confirmed.php        # → 'confirmed' rule
│   ├── Declined.php         # → 'declined' rule
│   ├── Accepted.php         # → 'accepted' rule
│   ├── Prohibited.php       # → 'prohibited' rule
│   ├── Present.php          # → 'present' rule
│   ├── Sometimes.php        # → 'sometimes' rule
│   ├── Distinct.php         # → 'distinct' rule (adds wildcard field.* rule)
│   ├── Date.php             # → 'date' or 'date_format:...' rule
│   ├── Same.php             # → 'same:field' rule
│   ├── Different.php       # → 'different:field' rule
│   ├── StartsWith.php       # → 'starts_with:...' rule
│   ├── EndsWith.php         # → 'ends_with:...' rule
│   ├── RequiredIf.php       # → 'required_if:...' conditional rule
│   ├── RequiredUnless.php   # → 'required_unless:...' conditional rule
│   ├── RequiredWith.php     # → 'required_with:...' conditional rule
│   ├── RequiredWithAll.php  # → 'required_with_all:...' conditional rule
│   ├── RequiredWithout.php  # → 'required_without:...' conditional rule
│   └── RequiredWithoutAll.php # → 'required_without_all:...' conditional rule
├── Casts/
│   └── DTOCast.php          # Eloquent cast: DTO ↔ JSON column
├── Console/Commands/
│   ├── MakeDtoTestCommand.php   # artisan zeroboiler:dto-test
│   └── MakeDtoSchemaCommand.php # artisan zeroboiler:dto-schema
├── Contracts/
│   ├── FromRequestDTO.php    # Interface: fromRequest() contract
│   ├── ValidatableDTO.php   # Interface: rules() + rulesFor() contract
│   └── ValidationAttribute.php # Interface: ruleKey() for message generation
├── Exceptions/
│   └── DTOException.php     # Named constructors: invalidCast(), invalidJson()
├── Facades/
│   └── DTO.php              # DTO facade (delegates to DTOManager)
├── Support/
│   ├── DtoMetadataResolver.php      # Reflection-based rule & metadata resolution
│   └── OpenApiSchemaGenerator.php   # OpenAPI 3.0 schema from DTO definitions
├── DataTransferObject.php    # Abstract base class (all public API)
├── DtoCollection.php         # Type-safe array wrapper (pluck, map, filter, etc.)
├── DTOManager.php            # Runtime helper (validate, make, makeFromJson, schema)
└── DTOSServiceProvider.php   # Registers singleton, commands, cache listeners
```

**Key design decisions:**
- `DataTransferObject` is `abstract` with static methods — subclass it, never instantiate directly
- All properties must be `public readonly` in the constructor (immutability at language level)
- Validation runs **before** hydration — invalid data is rejected early
- `DtoCollection` wraps DTO instances with type-safe access (ArrayAccess + IteratorAggregate)
- `DtoMetadataResolver` is a static-only class — resolution results are cached by the caller
- All validation attributes implement `ValidationAttribute` with `ruleKey()` for message generation
- `Hidden`, `MapFrom`, `Cast`, `DefaultValue` are metadata attributes (no validation rule generated)

## Security

See [SECURITY.md](SECURITY.md) for our security policy.

### Built-In Security Features

| Feature | Implementation | Description |
|---------|---------------|-------------|
| **Input validation** | Attribute-based rules + Laravel Validator | All DTO inputs validated before hydration — invalid data rejected early |
| **Output filtering** | `#[Hidden]` attribute | Sensitive fields (passwords, tokens) excluded from `toArray()`/`toJson()` |
| **Type enforcement** | `public readonly` + strict types | Properties cannot be modified after construction; strict type coercion |
| **JSON injection prevention** | `json_decode(..., true, 512, JSON_THROW_ON_ERROR)` | `fromJson()` rejects malformed JSON with `DTOException` |
| **Enum validation** | `#[Enum]` attribute + backing type check | `EnumRule` verifies type match before `tryFrom()` — no TypeError leakage |
| **No magic methods** | Explicit API only | No `__get`/`__set` — all access through typed properties |
| **Immutable data** | `readonly` + `with()` returns new instance | DTOs cannot be mutated in-place — prevents state tampering |

### Safe by Default

```php
// Sensitive data never leaks to output
class LoginDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required]
        #[Hidden]  // ← excluded from toArray(), toJson(), jsonSerialize()
        public readonly string $password,
    ) {}
}

$dto = LoginDTO::fromArray(['email' => 'a@b.com', 'password' => 'secret']);
$dto->toArray();       // ['email' => 'a@b.com'] — password excluded
$dto->allValues();     // ['email' => 'a@b.com', 'password' => 'secret'] — internal only
$dto->password;        // 'secret' — direct property access still works
```

## Real-World Patterns

### DataTransferObject — Complete Method Reference

```php
use ZeroBoiler\DTO\DataTransferObject;

// ── Hydration Methods ─────────────────────────────────────────
$dto = CreateUserDTO::fromArray($data);               // array → DTO (validates)
$dto = CreateUserDTO::fromArray($data, validate: false); // skip validation
$dto = CreateUserDTO::fromRequest($request);            // HTTP request → DTO
$dto = CreateUserDTO::fromRequest($request, validate: false);

$dto = CreateUserDTO::fromJson('{"email":"a@b.com","name":"Alice"}');
// Throws DTOException on invalid JSON or sequential arrays

// ── Partial Hydration (PATCH) ──────────────────────────────────
$patched = CreateUserDTO::fromPartialArray(['name' => 'Updated']);
// Missing fields use defaults or type-appropriate empty values
// 'required' is relaxed to 'sometimes' for present fields only

$patched = CreateUserDTO::fromPartialRequest($request);

// ── Validation ─────────────────────────────────────────────────
CreateUserDTO::rules();
// ['email' => ['required', 'email'], 'name' => ['required', 'min:2', 'max:50'], ...]

$validated = CreateUserDTO::validateArray($data);        // returns validated data
$validated = CreateUserDTO::validatePartialArray($data); // PATCH validation

CreateUserDTO::rulesFor('create');  // action-scoped (default: same as rules())
CreateUserDTO::rulesFor('update');  // override in subclass

// ── Serialization ──────────────────────────────────────────────
$dto->toArray();    // public fields only (#[Hidden] excluded)
$dto->allValues();  // includes hidden fields
$dto->toJson();     // JSON string (hidden excluded)
$dto->toJson(JSON_PRETTY_PRINT); // formatted JSON

// ── Selective Output ──────────────────────────────────────────
$dto->only('email', 'name');  // ['email' => '...', 'name' => '...']
$dto->only('email');           // single string key
$dto->except('password');       // all except 'password'
$dto->except('age', 'tags');   // multiple keys

// ── Immutable Update ───────────────────────────────────────────
$updated = $dto->with(['name' => 'Bob']);  // new instance (always validates)
// Original $dto is unchanged

// ── Equality & State ───────────────────────────────────────────
$dto->equals($other);    // true if toArray() matches exactly
$dto->isEmpty();         // true if all properties are default/empty
$dto->isNotEmpty();      // at least one property has a value

// ── Cache Management (static) ──────────────────────────────────
CreateUserDTO::setMetadataCacheTtl(2.0);    // dev: short TTL
CreateUserDTO::flushMetadataCache();          // flush all
CreateUserDTO::flushMetadataCache(CreateUserDTO::class); // flush one
```

### DtoCollection — Full Method Reference

```php
use ZeroBoiler\DTO\DtoCollection;

$collection = DtoCollection::make([$dto1, $dto2, $dto3]);

// ── Iteration ──────────────────────────────────────────────────
foreach ($collection as $dto) { ... }  // IteratorAggregate
$collection[0];        // ArrayAccess
$collection[0] = $dto; // ArrayAccess set (validated: must be DTO)
isset($collection[0]); // offsetExists
unset($collection[0]); // offsetUnset (re-indexes automatically)

// ── Extraction ────────────────────────────────────────────────
$emails = $collection->pluck('email');
// ['a@example.com', 'b@example.com']

$map = $collection->pluckKey('email', 'name');
// ['a@example.com' => 'Alice', 'b@example.com' => 'Bob']

$keyed = $collection->toArrayBy('id');
// ['42' => ['id' => 42, 'name' => 'Alice'], ...]

$dict = $collection->toDictionary('id', 'name');
// [42 => 'Alice', 43 => 'Bob']

// ── Transformation ──────────────────────────────────────────────
$names = $collection->map(fn ($dto) => $dto->name);
// ['Alice', 'Bob', 'Charlie']

$active = $collection->filter(fn ($dto) => $dto->status === 'active');
// Returns new DtoCollection (immutable)

// ── Mutation ────────────────────────────────────────────────────
$collection->push($dto4);     // mutates in-place, returns $collection
$new = $collection->append($dto4);   // returns NEW collection (immutable)
$merged = $collection->merge($other); // returns NEW collection

// ── State ──────────────────────────────────────────────────────
$collection->count();       // 3
$collection->isEmpty();     // false
$collection->isNotEmpty();  // true
$collection->first();       // $dto1
$collection->last();        // $dto3
$collection->items();       // raw DTO array (no serialization)

// ── Serialization ───────────────────────────────────────────────
$collection->toArray();     // array of toArray() results
$collection->allValues();   // array of allValues() results (includes hidden)
json_encode($collection);  // uses jsonSerialize() → toArray()
```

### DTOManager & Facade — Dependency Injection

```php
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Facades\DTO;

// ── Facade Usage ────────────────────────────────────────────────
$dto = DTO::make(CreateUserDTO::class, $data);
$dto = DTO::makeFromJson(CreateUserDTO::class, $json);
DTO::validate(CreateUserDTO::class, $data);
DTO::rules(CreateUserDTO::class);
DTO::rulesFor(CreateUserDTO::class, 'update');
$schema = DTO::schema(CreateUserDTO::class);

// ── DI Usage (controller/service injection) ────────────────────
class UserController extends Controller
{
    public function __construct(
        private readonly DTOManager $dtoManager,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $dto = $this->dtoManager->make(CreateUserDTO::class, $request->all());
        // ... create user from DTO
    }
}
```

### DTOCast — Eloquent JSON Column

```php
use ZeroBoiler\DTO\Casts\DTOCast;

// Store DTOs as JSON columns
protected $casts = [
    'profile' => UserProfileDTO::class,
    'settings' => new UserSettingsDTO::class(validate: false),
];

// Reading: JSON string → DTO instance
$user->profile;  // UserProfileDTO instance (or null)

// Writing: DTO instance → JSON string
$user->profile = new UserProfileDTO(email: 'a@b.com', name: 'Alice');
$user->save();
// Database column: '{"email":"a@b.com","name":"Alice"}'

// Writing: array → validated + serialized
$user->profile = ['email' => 'new@b.com', 'name' => 'Bob'];
$user->save();
// Goes through fromArray() → validation → JSON encode

// Serialization for API responses
$user->toArray();  // profile is serialized via DTOCast::serialize()
```

### DTOException — Error Handling

```php
use ZeroBoiler\DTO\Exceptions\DTOException;

// fromJson() throws on invalid JSON
try {
    $dto = MyDTO::fromJson('{invalid json}');
} catch (DTOException $e) {
    // "Cannot decode JSON for property (root): Syntax error"
}

// __toString() for logging
(string) $e;
// "ZeroBoiler\DTO\Exceptions\DTOException: Cannot decode JSON..."

// invalidCast() factory (internal, thrown by cast pipeline)
$e = DTOException::invalidCast('age', 'integer', 'not_a_number');
// "Cannot cast property [age] value [string] to [integer]."

// invalidJson() factory
$e = DTOException::invalidJson('settings', 'Syntax error');
// "Cannot decode JSON for property [settings]: Syntax error"
```

### Advanced: Nested DTOs with Collections

```php
use ZeroBoiler\DTO\Attributes\{Required, Email, Min, Max, NestedArray, Collection};

// ── Nested DTO ─────────────────────────────────────────────────
class AddressDTO extends DataTransferObject
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

// ── Parent DTO with NestedArray ────────────────────────────────
class OrderDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $customerEmail,

        #[NestedArray(AddressDTO::class)]
        public readonly array $shippingAddresses = [],
    ) {}
}

// Usage
$dto = OrderDTO::fromArray([
    'customerEmail' => 'buyer@example.com',
    'shippingAddresses' => [
        ['street' => '123 Main', 'city' => 'Ankara', 'country' => 'TR'],
        ['street' => '456 Oak', 'city' => 'Istanbul', 'country' => 'TR'],
    ],
]);

$dto->shippingAddresses;  // array of AddressDTO instances
$dto->toArray();
// ['customerEmail' => '...', 'shippingAddresses' => [...toArray()...]]
```

### Advanced: Action-Scoped Rules

```php
class RegistrationDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,

        #[Same('password')]
        public readonly ?string $passwordConfirmation = null,
    ) {}

    // Override for different actions
    public static function rulesFor(string $action): array
    {
        return match ($action) {
            'create' => static::rules(),  // all rules
            'update' => [
                'email' => ['sometimes', 'email'],
                'password' => ['sometimes', 'min:8'],
            ],
            default => static::rules(),
        };
    }
}

// In controller
$rules = RegistrationDTO::rulesFor('update');
$validated = RegistrationDTO::validateArray(array_merge($existing, $request->only(['email'])));
```

### Advanced: MapFrom with Dot Notation

```php
use ZeroBoiler\DTO\Attributes\MapFrom;

class PaymentDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        #[MapFrom('payment.method')]
        public readonly string $method,

        #[Required]
        #[MapFrom('payment.amount')]
        public readonly float $amount,

        #[MapFrom('payment.details.card_last_four')]
        public readonly ?string $cardLastFour = null,
    ) {}
}

// Deeply nested input → flat DTO properties
$dto = PaymentDTO::fromArray([
    'payment' => [
        'method' => 'credit_card',
        'amount' => 99.99,
        'details' => [
            'card_last_four' => '4242',
        ],
    ],
]);

$dto->method;       // 'credit_card'
$dto->amount;       // 99.99
$dto->cardLastFour; // '4242'
```

## Attribute Type Signatures

### Validation Attributes (37 total)

All validation attributes implement `ValidationAttribute` (providing `ruleKey()`)
except metadata attributes. All are `final` classes with promoted `readonly` properties.

#### Presence & Field Control

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Required` | `(?string $message = null)` | `required` | `required` |
| `Nullable` | `(?string $message = null)` | `nullable` | `nullable` |
| `Sometimes` | `(?string $message = null)` | `sometimes` | `sometimes` |
| `Present` | `(?string $message = null)` | `present` | `present` |
| `Prohibited` | `(?string $message = null)` | `prohibited` | `prohibited` |
| `Accepted` | `(?string $message = null)` | `accepted` | `accepted` |
| `Declined` | `(?string $message = null)` | `declined` | `declined` |

#### String Validation

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Email` | `(?string $message = null)` | `email` | `email` |
| `Url` | `(?string $message = null)` | `url` | `url` |
| `Uuid` | `(?string $message = null)` | `uuid` | `uuid` |
| `Pattern` | `(string $regex, ?string $message = null)` | `regex:{regex}` | `regex` |
| `StartsWith` | `(string\|array $prefix, ?string $message = null)` | `starts_with:...` | `starts_with` |
| `EndsWith` | `(string\|array $suffix, ?string $message = null)` | `ends_with:...` | `ends_with` |

#### Numeric & Size Validation

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Max` | `(int\|float $value, ?string $message = null)` | `max:{value}` | `max` |
| `Min` | `(int\|float $value, ?string $message = null)` | `min:{value}` | `min` |
| `Between` | `(int\|float $min, int\|float $max, ?string $message = null)` | `between:{min},{max}` | `between` |
| `Size` | `(int\|float $value, ?string $message = null)` | `size:{value}` | `size` |
| `Integer` | `(?string $message = null)` | `integer` | `integer` |
| `Numeric` | `(?string $message = null)` | `numeric` | `numeric` |
| `Boolean` | `(?string $message = null)` | `boolean` | `boolean` |

#### Date, Array & Format

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Date` | `(?string $format = null, ?string $message = null)` | `date` or `date_format:{format}` | `date_format` / `date` |
| `ArrayRule` | `(?int $min = null, ?int $max = null, ?string $message = null)` | `array` + optional `min`/`max` | `array` |
| `Json` | `(?string $message = null)` | `json` | `json` |
| `In` | `(array $values, ?string $message = null)` | `in:val1,val2,...` | `in` |

#### Enum Validation

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Enum` | `(string $enumClass, ?string $message = null)` | Laravel `Rule::enum($enumClass)` | `enum` |

> `$enumClass` must be a `class-string<\BackedEnum>`. The attribute also enables auto-casting
> during hydration — the DTO property will hold the actual enum case instance.

#### Cross-Field Validation

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `Confirmed` | `(?string $message = null)` | `confirmed` | `confirmed` |
| `Same` | `(string $field, ?string $message = null)` | `same:{field}` | `same` |
| `Different` | `(string $field, ?string $message = null)` | `different:{field}` | `different` |
| `Distinct` | `(?string $message = null)` | `distinct` + `field.*` wildcard | `distinct` |

#### Conditional Requirement (7 attributes)

| Attribute | Constructor Signature | Generated Rule | Rule Key |
|-----------|----------------------|----------------|----------|
| `RequiredIf` | `(string $field, mixed $value, ?string $message = null)` | `required_if:{field},{value}` | `required_if` |
| `RequiredUnless` | `(string $field, mixed $value, ?string $message = null)` | `required_unless:{field},{value}` | `required_unless` |
| `RequiredWith` | `(string $fields, ?string $message = null)` | `required_with:{fields}` | `required_with` |
| `RequiredWithAll` | `(string $fields, ?string $message = null)` | `required_with_all:{fields}` | `required_with_all` |
| `RequiredWithout` | `(string $fields, ?string $message = null)` | `required_without:{fields}` | `required_without` |
| `RequiredWithoutAll` | `(string $fields, ?string $message = null)` | `required_without_all:{fields}` | `required_without_all` |

> `$fields` is a comma-separated string of field names (e.g., `'email,phone'`).

### Metadata Attributes (4 total)

Metadata attributes do **not** implement `ValidationAttribute` — they control
hydration, serialization, and source key mapping behavior.

| Attribute | Constructor Signature | Purpose |
|-----------|----------------------|---------|
| `MapFrom` | `(string $key)` | Maps source key to property name (supports dot notation) |
| `Cast` | `(string $type)` | Type casting during hydration: `int`, `integer`, `string`, `bool`, `boolean`, `float`, `double`, `array`, `date`, `datetime` |
| `DefaultValue` | `(mixed $value)` | Default value when source key is absent |
| `Hidden` | `()` | Excludes property from `toArray()`, `toJson()`, `jsonSerialize()`, OpenAPI schema |
| `Collection` | `(string $dtoClass, ?string $message = null)` | Array of nested DTOs wrapped in `DtoCollection` |
| `NestedArray` | `(string $dtoClass, ?string $message = null)` | Array of nested DTO instances (plain array, not DtoCollection) |

### PHP 8.5 Features Used

| Feature | Where Used | Benefit |
|---------|-------------|---------|
| `final readonly class` | `DTOManager` | Compile-time immutability and non-extensibility guarantee |
| `#[\Override]` | `DataTransferObject`, `DtoCollection`, `DTOSServiceProvider`, `DTO` facade, `DTOException`, `DTOCast` | Explicit override intent, catches base class method signature changes |
| Promoted `readonly` properties | All DTO properties, all Attribute constructors | Language-level immutability guarantee |
| `readonly` modifier on constructor promotion | `public readonly string $email` | Single-keyword immutability in DTO definitions |
| `never` return type | N/A (no never-return methods) | — |
| Match expressions | `DataTransferObject::castValue()`, `emptyValueForType()`, `DtoMetadataResolver::applyValidationAttribute()`, `OpenApiSchemaGenerator` | Exhaustive pattern matching with compiler optimization |
| Named arguments | Test generators, Attribute instantiation | Improves readability of complex constructor calls |
| Union types in generics | `DtoCollection<T>`, `CastsAttributes<T\|null, ...>` | Type-safe generic wrappers |

## Production Readiness Checklist

This package is production-ready. Every source file passes the following checks:

| Check | Status | Detail |
|-------|--------|--------|
| `declare(strict_types=1)` | ✅ All 55 files | 100% strict types coverage |
| Final classes | ✅ All classes | `DataTransferObject` (abstract), `DtoCollection`, `DTOManager`, `DTOCast`, `DTOException`, all Attributes, all Commands |
| Readonly properties | ✅ Public API | `DTOManager` is `final readonly`, `DtoCollection` items typed, all Attribute constructors use promoted `readonly`, all DTO properties are `public readonly` |
| Return type declarations | ✅ All methods | Every method has explicit return type (`void`, `bool`, `string`, `array`, `self`, `static`, `mixed` only in interface contracts) |
| Docblocks | ✅ All public methods | PHPDoc with `@param`, `@return`, `@throws` on every public/protected method, `@internal` on support classes |
| PHPStan Level 9 | ✅ Passing | Zero untyped `mixed` in source API, `@phpstan-type` annotations on complex shapes, strict comparisons throughout |
| Typed properties | ✅ All | `DataTransferObject::$_zbMetadataCache`, `$_zbMetadataCacheTimestamps`, `$_zbMetadataCacheTtl` — all typed; `DtoCollection::$items` typed as `array<int, T>` |
| Interface compliance | ✅ | `DTOCast` implements `CastsAttributes<T\|null, T\|array\|null>`, `DtoCollection` implements `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable` |
| Contract interfaces | ✅ | `ValidatableDTO` (rules + rulesFor), `FromRequestDTO` (fromRequest), `ValidationAttribute` (ruleKey) |
| Exception safety | ✅ | Custom `DTOException` with named constructors (`invalidCast()`, `invalidJson()`), `__toString()` override |
| Nested DTO support | ✅ | `#[NestedArray(ItemDTO::class)]` and `#[Collection(ItemDTO::class)]` with recursive hydration |
| Enum integration | ✅ | `#[Enum(BackedEnum::class)]` attribute + auto-casting via `castValueToEnum()` — works with zeroboiler/enums |
| Value Object integration | ✅ | Auto-detection of `ValueObject` implementations, `fromPrimitive()` support, `columnType()`-aware serialization |
| MapFrom dot notation | ✅ | `#[MapFrom('meta.phone')]` resolves nested array keys via `Arr::has()` / `Arr::get()` |
| Hidden properties | ✅ | `#[Hidden]` excludes from `toArray()`, `toJson()`, `jsonSerialize()`, OpenAPI schema |
| PATCH semantics | ✅ | `fromPartialArray()`, `fromPartialRequest()` — `required` relaxed to `sometimes`, type-appropriate empty defaults |
| Octane/Swoole safe | ✅ | Listens for `octane.terminate` and `laravel.flush` events to flush static metadata cache |
| Dev cache invalidation | ✅ | TTL-based (2s) in local/testing environments, 0 (disabled) in production |
| Laravel auto-discovery | ✅ | Service provider auto-registered, facade alias `DTO` auto-registered |
