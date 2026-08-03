# ZeroBoiler DTO

Zero-boilerplate type-safe DTO system for Laravel.

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

// Extract a single field from all DTOs
$emails = $collection->pluck('email');
// ['a@example.com', 'b@example.com', 'c@example.com']

// Build a key/value map
$map = $collection->pluckKey('email', 'name');
// ['a@example.com' => 'Alice', 'b@example.com' => 'Bob', ...]
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

## Attributes Reference

| Attribute | Description |
|-----------|-------------|
| `#[Required]` | Field is required |
| `#[Email]` | Must be valid email |
| `#[Url]` | Must be valid URL |
| `#[Uuid]` | Must be valid UUID |
| `#[Min(n)]` | Minimum length/value |
| `#[Max(n)]` | Maximum length/value |
| `#[Pattern('/regex/')]` | Must match regex |
| `#[In(['a', 'b'])]` | Must be one of values |
| `#[Enum(EnumClass::class)]` | Must be valid enum value |
| `#[Integer]` | Must be integer |
| `#[Numeric]` | Must be numeric |
| `#[Boolean]` | Must be boolean |
| `#[Date]` | Must be valid date |
| `#[Cast('type')]` | Cast value during hydration |
| `#[MapFrom('source_key')]` | Map from different source key |
| `#[Hidden]` | Exclude from output |
| `#[Default(value)]` | Default value when missing |

## License

Proprietary — © ZeroBoiler
