# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `LICENSE` file — proprietary license text
- `DtoCollection::toArrayBy()` — re-key collection by a property value (returns `{value: array}`)
- `DtoCollection::toDictionary()` — build lookup maps from two properties (returns `{key: value}`)
- `@internal` annotations on `DtoMetadataResolver` and `OpenApiSchemaGenerator` support classes
- DtoCollection toArrayBy/toDictionary/pluck/pluckKey edge case tests for full coverage
- `DTOManager::rules()` and `DTOManager::rulesFor()` — access validation rules via manager and `DTO` facade
- `DTOManagerTest` and `DTOManagerRulesAndRulesForTest` — facade delegation tests for rules/rulesFor
- GitHub issue templates (bug report, feature request) and pull request template
- `DtoSourceCodeProductionReadinessAuditTest` — comprehensive PHPStan Level 9 structural audit verifying strict_types, final classes, return types, typed properties, readonly enforcement, attribute contracts, and docblock completeness across all source files

### Changed
- Fixed `CHANGELOG.md` — moved `[Unreleased]` section to top per Keep a Changelog convention
- Updated README test count badge to reflect 239 test files (was 206)

## [1.1.0] - 2025-08-11

### Added
- `DataTransferObject` abstract base class with zero-boilerplate hydration and validation
  - `fromArray()`, `fromRequest()`, `fromJson()` — full hydration
  - `fromPartialArray()`, `fromPartialRequest()` — PATCH semantics
  - `toArray()`, `allValues()`, `toJson()`, `jsonSerialize()` — serialization
  - `only()`, `except()` — selective output
  - `with()` — immutable update (always validates)
  - `equals()`, `isEmpty()`, `isNotEmpty()` — state checks
  - `rules()`, `rulesFor()`, `validateArray()`, `validatePartialArray()` — standalone validation
  - `flushMetadataCache()`, `setMetadataCacheTtl()` — cache management
- `DtoCollection` — type-safe array wrapper with `pluck()`, `pluckKey()`, `map()`, `filter()`, `push()`, `append()`, `merge()`
- 39 validation attributes: `Required`, `Email`, `Max`, `Min`, `Pattern`, `Url`, `Uuid`, `In`, `Integer`, `Numeric`, `Boolean`, `Date`, `StartsWith`, `EndsWith`, `Between`, `Size`, `ArrayRule`, `Json`, `Enum`, `Accepted`, `Declined`, `Confirmed`, `Distinct`, `Prohibited`, `Present`, `Sometimes`, `Nullable`, `Same`, `Different`, `RequiredIf`, `RequiredUnless`, `RequiredWith`, `RequiredWithAll`, `RequiredWithout`, `RequiredWithoutAll`
- 6 metadata attributes: `Cast`, `MapFrom`, `Hidden`, `DefaultValue`, `NestedArray`, `Collection`
- `DTOCast` — Eloquent cast for DTO ↔ JSON column with optional validation on set
- `DTOManager` — runtime helper accessible via `DTO` facade
- `DTO` facade — `DTO::make()`, `DTO::validate()`, `DTO::makeFromJson()`, `DTO::schema()`
- `DtoMetadataResolver` — reflection-based rule & metadata resolution with deduplication
- `OpenApiSchemaGenerator` — auto-generate OpenAPI 3.0 schemas from DTO definitions
- `DTOException` — named constructors for cast and JSON failures
- `DTOSServiceProvider` — auto-discovery with Octane cache flush and dev TTL support
- Contracts: `FromRequestDTO`, `ValidatableDTO`, `ValidationAttribute`
- Artisan commands: `zeroboiler:dto-test`, `zeroboiler:dto-schema`
- PHPStan level 9 compliance across all source files
- Comprehensive test suite with 170+ test files covering all edge cases
- Full README with usage examples, architecture diagrams, migration guide, and API reference

### Changed
- `DTO` facade added (was missing in earlier releases)

## [1.0.0] - 2025-07-01

### Added
- Initial release of ZeroBoiler DTO package
