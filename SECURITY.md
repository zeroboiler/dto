# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability within ZeroBoiler DTO, please send an email to security@zeroboiler.com.

All security vulnerabilities will be promptly addressed. We request that you do not publicly disclose the vulnerability until we have issued a fix.

## Supported Versions

Only the latest version of `zeroboiler/dto` receives security patches.

| Version | Supported |
| ------- | ---------- |
| 1.x     | ✅        |

## Scope

This package provides attribute-based validation, hydration, and serialization for DTOs. It does not:

- Handle user authentication or authorization
- Process or store user input directly (validation is delegated to Laravel's validator)
- Make network requests or interact with databases
- Execute shell commands or eval'd code
- Modify global state (static metadata cache is per-class, not cross-process)

The primary security surfaces are:

1. **Input validation** — All hydration paths (`fromArray()`, `fromRequest()`, `fromJson()`) delegate validation to Laravel's validator. The `fromJson()` method additionally validates JSON structure before hydration, rejecting sequential arrays to prevent type confusion.
2. **Type casting** — The `Cast` attribute uses PHP native type casting. The `castToDate()` method validates input types before creating Carbon instances.
3. **Metadata resolution** — The `DtoMetadataResolver` uses PHP Reflection API to read attribute metadata. It does not execute any code from attribute constructor arguments.
4. **Eloquent casting** — The `DTOCast` validates input types on `set()`, rejecting non-DTO/non-array/non-null values to prevent silent data corruption.
5. **OpenAPI schema** — The `OpenApiSchemaGenerator` reads only class structure (types, attributes). It does not execute any code or access runtime data.
