# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [1.1.0] - 2026-08-06

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
