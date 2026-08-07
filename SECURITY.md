# Security Policy

## Supported Versions

| Version | Status | PHP Version | Laravel Version |
|---------|--------|-------------|-----------------|
| 1.1.x   | ✅ Supported | >= 8.5 | >= 13.0 |
| 1.0.x   | ❌ End of Life | — | — |

## Reporting a Vulnerability

If you discover a security vulnerability in ZeroBoiler DTO, please report it
responsibly by contacting the maintainers directly.

**Do NOT** open a public GitHub issue for security vulnerabilities.

### How to Report

1. Send an email to the ZeroBoiler security team with the subject line
   `[SECURITY] ZeroBoiler DTO Vulnerability Report`.
2. Include a detailed description of the vulnerability, affected versions,
   and steps to reproduce.
3. A maintainer will acknowledge receipt within 48 hours (business days).

### What to Expect

- We will investigate the report and confirm the vulnerability.
- A patch will be developed and released as a new version.
- Credit will be given to the reporter (unless anonymity is requested).
- Public disclosure will occur after the patch is available.

## Security Considerations

### DTO Hydration

The `DataTransferObject` base class hydrates properties from user input arrays
or HTTP requests. All hydration goes through:

1. **Validation** — Attribute-derived rules are applied before any property
   is set. Invalid data throws `ValidationException` before the DTO is created.
2. **Type Casting** — `#[Cast]` and auto-detected types ensure properties
   receive correctly typed values. The `castValue()` method sanitizes inputs
   to the target type.
3. **Readonly Properties** — Once created, DTO properties cannot be modified
   at the language level (`public readonly`), preventing post-hydration mutation.

### Hidden Fields

`#[Hidden]` properties are excluded from `toArray()`, `toJson()`, and
`jsonSerialize()` output. However, they are still accessible on the DTO
instance itself and included in `allValues()`. Do not rely on `#[Hidden]`
as a security boundary — it is a serialization convenience, not an access
control mechanism.

### JSON Deserialization

`fromJson()` rejects sequential arrays (JSON arrays `[...]`) and only accepts
JSON objects (`{...}`). This prevents accidental hydration from list payloads.
Invalid JSON throws `DTOException` before any validation or hydration.

### Eloquent Cast

`DTOCast` stores DTOs as JSON in database columns. By default, data is
validated before storage. For pre-validated data, validation can be
disabled via `new DTOCast(MyDTO::class, validate: false)`.

### OpenAPI Schema Generation

The `OpenApiSchemaGenerator` reads DTO class definitions via reflection.
It only introspects classes that exist in the application codebase and does
not accept external input as class names in production contexts.

### Validation Messages

Custom validation messages (via the `message` parameter on validation
attributes) are stored as strings and rendered by Laravel's validator.
They are not processed through any template engine and are safe from
injection.

## Dependencies

This package depends on:

- `illuminate/contracts` ^13.0
- `illuminate/http` ^13.0
- `illuminate/support` ^13.0
- `illuminate/validation` ^13.0
- `zeroboiler/value-objects` ^1.0

Review the [Laravel Security Policy](https://github.com/laravel/framework/security)
and the [ZeroBoiler Value Objects Security](https://github.com/zeroboiler/value-objects/security)
for dependency vulnerability disclosures.
