# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [Unreleased]

### Added
- `CONTRIBUTING.md` — Contribution guide with code standards, development setup, quality checks, architecture overview, and PR process
- `SECURITY.md` — Security policy with supported versions, reporting process, and security considerations
- Compatibility Matrix section in README (PHP/Laravel versions, feature usage table)
- `DTOProductionDeepAuditTest` — Comprehensive deep audit test covering all 30+ validation attributes (Email, Pattern, Boolean, Uuid, In, StartsWith, EndsWith, Size, ArrayRule, Distinct, Present, Prohibited, Sometimes, Json, Nullable, Confirmed, Different, Same, Accepted, Declined, RequiredIf, MapFrom, Hidden, Cast, DefaultValue), hydration pipeline edge cases (fromJson invalid JSON, nested array, collection), partial update semantics, serialization edge cases (toJson, equals, only, except, with), isEmpty/isNotEmpty, rules inference, metadata cache flush, exception factories, and DtoCollection edge cases (filter, map, offsetUnset re-index, type guard)

### Changed
- Improved `with()` docblock — added explicit `@deprecated` tag for `$validate` parameter, documented `@throws`
- Enriched README with Internal Components section (DtoMetadataResolver, OpenApiSchemaGenerator, Class Structure)
- Added Test Coverage table to README documenting 60+ test files across all categories

## [1.1.0] - 2026-08-06

### Changed
- Test fixture DTOs (`EmptyDTO`, `CreateUserDTO`) marked as `final` for consistency

### Added
- Base `DataTransferObject` with readonly promoted properties
- Attribute-based validation: 30+ validation attributes covering all Laravel validation rules
- Auto-hydration: `fromArray()`, `fromRequest()` with zero boilerplate
- Partial updates: `fromPartialArray()`, `fromPartialRequest()` for PATCH semantics
- Auto-validation on hydration with attribute-derived rules
- Type casting via `#[Cast]`: integer, string, boolean, array, date, datetime
- Field mapping via `#[MapFrom]`
- Hidden fields via `#[Hidden]` — excluded from serialization
- Default values via `#[DefaultValue]`
- Immutable updates: `with()` creates a validated copy
- Selective output: `only()`, `except()`, `allValues()`
- Value equality: `equals()`
- State checks: `isEmpty()`, `isNotEmpty()`
- JSON hydration: `fromJson()` for decoding JSON strings
- `DtoCollection` — typed array wrapper with `pluck()`, `pluckKey()`, `map()`, `filter()`
- Eloquent casting via `DTOCast` (JSON storage with optional validation)
- Nested DTO hydration: `#[NestedArray]`, `#[Collection]`
- BackedEnum auto-casting during hydration
- ValueObject auto-instantiation (from `zeroboiler/value-objects`)
- OpenAPI schema generation with nested `$ref` and `oneOf` for unions
- CLI commands: `zeroboiler:dto-test`, `zeroboiler:dto-schema`
- Facade (`DTO`) and manager (`DTOManager`)
- Dev/testing cache auto-invalidation (2s TTL)
- Octane/Swoole/RoadRunner cache flush via event listeners
- Full PHPStan level 9 compliance (no baseline errors)
- Comprehensive Pest test suite with 32+ test files
- Exception hierarchy documentation in README
- Enhanced `fromJson` error handling documentation

### Requirements
- PHP 8.5+
- Laravel 13+
- `zeroboiler/value-objects` ^1.0
