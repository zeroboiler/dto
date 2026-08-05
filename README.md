# ZeroBoiler DTO

Zero-boilerplate type-safe DTO system for Laravel.

## Installation

```bash
composer require zeroboiler/dto
```

The package auto-registers via Laravel's package discovery. No manual configuration needed.

**Requirements:**
- PHP 8.5+
- Laravel 13+
- `zeroboiler/value-objects` (installed automatically as a dependency)

## Features

- **Attribute-based validation** — `#[Required]`, `#[Email]`, `#[Max]`, `#[Min]`, `#[Pattern]`, `#[Enum]`, `#[Uuid]`, `#[Url]`, `#[Date]`, `#[Integer]`, `#[Numeric]`, `#[Boolean]`, `#[In]`
- **Auto-hydration** — `fromArray()`, `fromRequest()` with zero boilerplate
- **Partial updates** — `fromPartialArray()`, `fromPartialRequest()` for PATCH semantics
- **Auto-validation** — rules derived from attributes, validated on hydration
- **Type casting** — `#[Cast('integer')]`, `#[Cast('array')]`, `#[Cast('boolean')]`
- **Field mapping** — `#[MapFrom('user_name')]` for source key aliasing
- **Hidden fields** — `#[Hidden]` excludes from `toArray()`/`toJson()`
- **Default values** — `#[Default('active')]` when source key is missing
- **Serialization** — `toArray()`, `toJson()`, `jsonSerialize()`
- **Immutable updates** — `$dto->with(['status' => 'inactive'])`
- **Selective output** — `$dto->only('email', 'name')`, `$dto->except('password')`
- **Equality** — `$dto1->equals($dto2)`
- **Collection helpers** — `pluck()`, `pluckKey()` for extracting fields from DTO collections
- **Eloquent casting** — store DTOs as JSON in database columns
- **OpenAPI schema** — auto-generate API docs from DTO definitions
- **Nested DTO schemas** — `$ref` to component schemas for nested DTOs
- **Union type support** — `oneOf` schemas for union types
- **CLI tools** — `zeroboiler:dto-test`, `zeroboiler:dto-schema`

## Usage

```php
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Default;
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

        #[Default('active')]
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
    'phone_number'=> '+905551234567',
]);

// Skip validation
$dto = CreateUserDTO::fromArray($data, validate: false);
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
```

### Selective Output

```php
// Return only specific fields
$dto->only('email', 'name');
// ['email' => 'test@example.com', 'name' => 'Doruk']

// Exclude sensitive fields
$dto->except('password');
// All fields except 'password'
```

### Collection Helpers

```php
$collection = new DtoCollection([$dto1, $dto2, $dto3]);
// Or via static factory
$collection = DtoCollection::make([$dto1, $dto2]);

// Extract a single field from all DTOs
$emails = $collection->pluck('email');
// ['a@example.com', 'b@example.com', 'c@example.com']

// Build a key/value map
$map = $collection->pluckKey('email', 'name');
// ['a@example.com' => 'Alice', 'b@example.com' => 'Bob', ...]

// Filter and chain
$active = $collection->filter(fn (UserDTO $u): bool => $u->status === 'active')
    ->map(fn (UserDTO $u): string => $u->name);

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

        #[NestedArray(AddressDTO::class)]
        public readonly array $items = [],
    ) {}
}

// items array automatically hydrated as DTO instances
$order = OrderDTO::fromArray([
    'orderNumber' => 'ORD-001',
    'items' => [
        ['street' => '123 Main St', 'city' => 'Istanbul'],
        ['street' => '456 Oak Ave', 'city' => 'Ankara'],
    ],
]);

$order->items; // array of AddressDTO instances
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

// Generate OpenAPI schema
$schema = DTO::schema(CreateUserDTO::class);
```

### OpenAPI Schema Generation

```php
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

// Basic schema
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
| `#[ArrayRule]` | Must be an array (optionally with `min`/`max` count) |
| `#[ArrayRule(min: 1, max: 10)]` | Array with 1–10 elements |

### Metadata Attributes

| Attribute | Description |
|-----------|-------------|
| `#[Cast('type')]` | Cast value during hydration (`'integer'`, `'string'`, `'boolean'`, `'array'`, `'date'`, `'datetime'`) |
| `#[MapFrom('source_key')]` | Map from different source key |
| `#[Hidden]` | Exclude from `toArray()` / `toJson()` output |
| `#[Default(value)]` | Default value when key is missing |
| `#[NestedArray(DTOClass::class)]` | Hydrate array elements as nested DTO instances |
| `#[Collection(DTOClass::class)]` | Hydrate as `DtoCollection` of DTO instances |

All validation attributes accept an optional `message` parameter for custom error messages:

```php
#[Email(message: 'Please provide a valid email address')]
#[Min(8, message: 'Password must be at least 8 characters')]
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

## License

Proprietary — © ZeroBoiler
