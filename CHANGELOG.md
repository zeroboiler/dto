# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [1.1.7] - 2026-08-14

### Added
- OpenApiSchemaGenerator advanced type inference tests (union types, VOT inference, pattern constraints, starts_with/ends_with, between/min/max, hidden properties, structural integrity)
- Updated test count (271 → 272)

## [1.1.6] - 2026-08-14

### Changed
- Updated test count badge (233 → 267 tests, 36 fixtures)
- Updated Package Statistics section with accurate test and fixture counts

## [1.1.5] - 2026-08-14

### Changed
- Enriched README with comprehensive Nested DTOs example including recursive serialization, #[Hidden] exclusion, and nested array hydration
- Expanded DTO Collections example with full method reference (pluck, pluckKey, toArrayBy, filter, map, push, append, iteration, ArrayAccess, JSON serialization)
- Updated test count badge (227 → 225) and version badge (1.1.4 → 1.1.5)
- Updated composer.json version to 1.1.5

## [1.1.0] - 2025-08-14

### Added
- `DataTransferObject` abstract base class with full API: `fromArray()`, `fromRequest()`, `fromJson()`, `fromPartialArray()`, `fromPartialRequest()`, `toArray()`, `allValues()`, `toJson()`, `jsonSerialize()`, `with()`, `only()`, `except()`, `equals()`, `isEmpty()`, `isNotEmpty()`, `rules()`, `rulesFor()`, `validateArray()`, `flushMetadataCache()`, `setMetadataCacheTtl()`
- 37 validation attributes: `Required`, `Email`, `Max`, `Min`, `Between`, `Pattern`, `In`, `Url`, `Uuid`, `Integer`, `Numeric`, `Boolean`, `Date`, `ArrayRule`, `Json`, `Enum`, `MapFrom`, `Cast`, `DefaultValue`, `Hidden`, `Nullable`, `Sometimes`, `Present`, `Prohibited`, `Accepted`, `Declined`, `Confirmed`, `Same`, `Different`, `Distinct`, `RequiredIf`, `RequiredUnless`, `RequiredWith`, `RequiredWithAll`, `RequiredWithout`, `RequiredWithoutAll`, `Size`, `StartsWith`, `EndsWith`
- Nested DTO hydration with `#[NestedArray]` and `#[Collection]`
- ValueObject and BackedEnum auto-hydration during casting
- `DtoCollection` type-safe collection with `pluck()`, `pluckKey()`, `map()`, `filter()`, `append()`, `merge()`, `toArrayBy()`, `toDictionary()`, ArrayAccess, Countable, IteratorAggregate
- `DTOManager` injectable helper with facade support
- `DTOCast` Eloquent cast for JSON column storage
- `DtoMetadataResolver` for reflection-based rule and property resolution with caching
- `OpenApiSchemaGenerator` for OpenAPI 3.0 schema generation with nested DTO and union type support
- `DTOException` with named constructors `invalidCast()` and `invalidJson()`
- Interfaces: `FromRequestDTO`, `ValidatableDTO`, `ValidationAttribute`
- CLI commands: `zeroboiler:dto-test` and `zeroboiler:dto-schema`
- `MakeDtoTestCommand` with reflection-based fake data generation
- `MakeDtoSchemaCommand` with `--with-components` and `--json` output options
- PHPStan Level 9 compliance across all source files
- 258 test files covering all edge cases and contract compliance
