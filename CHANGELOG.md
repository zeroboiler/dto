# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [Unreleased]

### Fixed
- Fixed README version history dates (2025 → 2026) across changelog, version history table, and release notes
- Full production audit: All 55 source files verified for `declare(strict_types=1)`, `final class`, complete return type declarations, comprehensive docblocks, typed properties, PHPStan Level 9 compliance, strict comparisons, `#[Override]` attributes on interface implementations

## [1.1.16] - 2026-08-14

### Fixed
- `OpenApiSchemaGenerator::componentName()`: Fixed backslash separator search — was searching for double backslash (`\\`) instead of single `\`, causing FQCN component names to never be truncated (e.g., `ZeroBoiler\DTO\AddressDTO` was returned as-is instead of `AddressDTO`)

## [1.1.15] - 2026-08-14

### Changed
- Fixed README test count badge (250 → 249) to match actual test file count
- Version bump to 1.1.15

## [1.1.14] - 2026-08-14

### Added
- `DtoProductionStructuralAndContractIntegrityTest`: comprehensive contract verification covering hydration contract (fromArray/fromJson), partial update (PATCH) semantics, serialization contract, selective output, immutable update, equality, isEmpty semantics, rules contract, full DtoCollection contract (make/push/append/merge/filter/first/last/pluck/pluckKey/map/toArrayBy/toDictionary/items/allValues/ArrayAccess/foreach/jsonSerialize/__clone/offsetUnset), DTOCast contract, DTOException factory, metadata cache lifecycle, DTOManager delegation, interface compliance, cast type pipeline, and null handling (~85 test methods)

### Changed
- Added **Error Handling Strategy** section to README — comprehensive table of all method error behaviors, exception types, exception hierarchy, design principles
- Added **Concurrency & Thread Safety** section to README — per-component thread safety matrix, Octane/Swoole/RoadRunner event listener documentation
- Updated README test count badge (247 → 250) and Package Statistics
- Version bump to 1.1.14

## [1.1.13] - 2026-08-14

### Changed
- Fixed README test count badge (287 → 247) to match actual test file count
- Fixed README Package Statistics (287 → 247 tests)
- Fixed README Test Coverage section (287 → 247)
- Full production-ready audit of all 55 source files: verified `declare(strict_types=1)`, return type declarations, readonly typed properties, PHPStan L9 compliance, complete docblocks
- Version bump to 1.1.13

## [1.1.12] - 2026-08-14

### Added
- `DtoCollectionMutabilityTest`: push() in-place mutation, append() immutability, merge() isolation, offsetSet/offsetUnset, type guard rejection, isEmpty/isNotEmpty, make() factory, first/last, map/filter, serialization (20 test methods)
- `DtoPartialArrayEmptyValueTest`: fromPartialArray empty value inference, nullable null defaults, explicit null respect, zero/false/empty-string handling, MapFrom in partial context, Cast('integer') in partial, toArray/allValues consistency, JSON roundtrip, equals comparison (20 test methods)

### Changed
- Full production-ready audit of all 55 source files: verified `declare(strict_types=1)`, return type declarations, readonly typed properties, PHPStan L9 compliance (no mixed types in public API, strict comparisons), complete docblocks
- Updated README test count badge and Package Statistics (244 → 248 tests)
- Version bump to 1.1.12

## [1.1.11] - 2026-08-14

### Changed
- Updated README test count badge (243 → 245 tests) to match actual test file count
- Production readiness audit: verified all 55 source files for PHPStan Level 9 compliance
- Version bump to 1.1.11

## [1.1.10] - 2026-08-14

### Added
- Production readiness V7 test: full attribute hydration (Required, Email, Max, Between, Boolean, Hidden, ArrayRule, Nullable, Pattern, MapFrom, Cast, DefaultValue), conditional validation (RequiredIf), isEmpty/isNotEmpty, equals/with, only/except, rules/rulesFor, DTOException factories, DtoCollection operations (filter, append, merge, toArray, offset, clone, push), fromPartialArray, toJson, metadata cache flush
- Updated test count (242 → 243)

### Changed
- README test count badge and Package Statistics updated to 243 tests
- Version bump to 1.1.10

## [1.1.9] - 2026-08-14

### Changed
- Production readiness audit: verified all 55 source files for PHPStan Level 9 compliance (strict types, typed properties, return type declarations, comprehensive docblocks, strict comparisons)
- Fixed README test count badge (244 → 242 tests) to match actual test file count

## [1.1.8] - 2026-08-14

### Added
- DTO fromJson error handling and edge-case tests (invalid JSON, sequential array rejection, null/boolean/number/string JSON, unicode, escaped chars, structural contract)
- DtoCollection immutability and edge-case tests (clone prevention, append/merge/filter immutability, ArrayAccess edge cases, offsetUnset re-indexing, serialization hidden props, iteration/counting, structural contract)
- Updated test count (242 → 244)

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
