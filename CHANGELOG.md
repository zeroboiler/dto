# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [Unreleased]

### Added
- `DataTransferObject::__debugInfo()` — var_dump/print_r shows toArray() output (hidden properties excluded)
- `DtoCollection::__debugInfo()` — var_dump/print_r shows item count and first 3 items (truncated for large collections)
- V35 production readiness test suite for __debugInfo on DTO and DtoCollection

### Changed
- Updated README test count badge (285 → 287), version badge (1.1.44 → 1.1.45), and package statistics to match actual test file count

### Added
- `DtoV33TypeSystemAndNestedHydrationContractTest`: V33 type system and nested hydration contract — nested single DTO hydration (OrderDTO with AddressDTO), nested array DTO hydration (NestedArray with OrderItemDTO), recursive serialization (toArray preserves nested structure), DtoCollection with nested DTOs (toArray allValues), date casting from string (Carbon instance) and null, fromJson round-trip preserves all scalar data, fromArray with validate:false accepts invalid data, EmptyDTO from empty array, AllDefaultsDTO uses defaults, equals symmetry and reflexivity, with() preserves hidden in allValues, except() does not leak hidden fields, DtoCollection edge cases (filter empty, map empty, pluck empty, merge with empty, toArrayBy/toDictionary empty), metadata cache flush allows re-resolution, DTOException __toString format consistency, fromPartialArray single field update, nested DTO allValues includes nested structure (~28 test methods)

### Added
|- `DtoV30ProductionBehaviorContractTest`: V30 production behavior contract — basic hydration and serialization (fromArray/toArray/toJson/fromJson roundtrip, allValues includes hidden, Hidden exclusion from toArray), MapFrom key aliasing (source→property mapping, serializes using property name), DefaultValue behavior (applies default for absent key, preserves explicit empty values), Cast type conversion (JSON string→array), selective output (only/except), immutable update (with), equality and state checks (equals/isEmpty/isNotEmpty), partial updates (fromPartialArray), validation rules generation (rules/rulesFor), DtoCollection operations (make/push/append/pluck/filter/toArray/first/last/map/jsonSerialize), DTOCast Eloquent integration (get/set/serialize/rejects invalid types), DTOException named constructors (invalidCast/invalidJson/__toString), DTOManager delegation (validate/make/rules/rulesFor), cross-fixture consistency (all extend DataTransferObject, roundtrip toArray↔fromArray) (~40 test methods)
- Version bump to 1.1.40 [dto]
- `DtoV29FullTypeSafetyAndDocblockAuditTest`: V29 comprehensive type safety audit — attribute contract compliance (37 validation attributes implement ValidationAttribute, TARGET_PROPERTY flag, readonly promoted constructors), metadata-only attributes (Hidden/MapFrom/DefaultValue non-ValidationAttribute), service class structure (DataTransferObject abstract/5 contracts, DtoCollection final/multiple interfaces, DTOManager final readonly, DTOException named constructors, DTOCast/ServiceProvider/Facade final, DtoMetadataResolver/DtoRule final readonly), contract interface completeness (ValidatableDTO/FromRequestDTO/ValidationAttribute), return type completeness (DataTransferObject/DtoCollection/DTOManager all methods), fixture DTO structure (final, extends DataTransferObject, public readonly), DtoCollection contract tests (make/push/append/merge/pluck/pluckKey/filter/ArrayAccess/Countable/IteratorAggregate), DTOException named constructors, strict types enforcement, validation rule generation, serialization exclusion (Hidden), selective output (only/except), MapFrom key alias, Cast type conversion, DefaultValue, DTO state checks (equals/isEmpty) (~60 test methods)
- `EnumV27SourceCodeStructuralIntegrityAuditTest`: V27 structural integrity audit — source file count verification (55+ files), `declare(strict_types=1)` enforcement, newline consistency, class/interface structure (DataTransferObject abstract/4 interfaces, DtoCollection final/4 interfaces, DTOManager final readonly, DTOException final/named constructors, DTOCast/ServiceProvider final), return type declaration audit (DataTransferObject, DtoCollection, DTOManager all methods), attribute class structure (37 validation attributes implement ValidationAttribute, 5 metadata attributes final, Hidden no-constructor), docblock quality (@internal tags on DtoMetadataResolver/OpenApiSchemaGenerator, phpstan types), `#[\Override]` compliance (DTOCast get/set, DtoCollection 7 interface methods, DataTransferObject 5 interface methods, ServiceProvider register/boot, Facade getFacadeAccessor, DTOException __toString), type safety audit (no bare mixed returns in DTO/Manager public API), console command structure (final, handle returns int), composer.json consistency (PHP ^8.5, illuminate ^13.0, zeroboiler/value-objects ^1.0, version 1.1.33), contract interface completeness (FromRequestDTO, ValidatableDTO, ValidationAttribute) (~50 test methods)
- `EnumV27SourceCodeStructuralIntegrityAuditTest`: V27 structural integrity audit — source file count verification (20+ files), `declare(strict_types=1)` enforcement, newline consistency, class/interface structure (HasEnumMetadata trait method completeness, EnumCache singleton/final, EnumManager final readonly, InvalidEnumException final/named constructors, EnumRule final readonly/ValidationRule, EnumsServiceProvider final), return type declaration audit, attribute class structure (8 classes final), docblock quality checks, `#[\Override]` compliance, type safety audit, composer.json consistency (~40 test methods)

### Changed
- Refactored `DTOManager::makeFromJson()` to delegate to `fromJson()` for consistency (eliminates duplicate code path)
- Updated README test count badge (277 → 319) and package statistics to match actual test file count
- Updated `DtoV27SourceCodeStructuralIntegrityAuditTest` composer.json version check (1.1.35 → 1.1.36)
- Version bump to 1.1.36
- Updated README test count accuracy (321 → 322), version history entry, badge update
- Version bump to 1.1.39
- Updated README test count badge (324 → 325), version badge (1.1.38 → 1.1.39), version history entry
- Added DtoV28PHPStanL9StrictTypeSafetyAuditTest: strict_types enforcement, DTOManager final readonly + no mixed returns, DtoCollection final + 4 interfaces, DataTransferObject abstract + 5 interfaces, DTOCast/CastsAttributes override compliance, DTOException named constructors, all 37 validation attributes implement ValidationAttribute, metadata attributes final/readonly, service provider override compliance, contract interfaces, composer.json consistency
- Added DtoV28EndToEndHydrationAndSerializationContractTest: full DTO lifecycle (fromArray/fromPartialArray/toArray/toJson), MapFrom key aliasing, Cast type casting, DefaultValue, Hidden exclusions, only/except selective output, with() immutable update, equals/isEmpty state checks, rules() generation, DtoCollection operations (pluck/pluckKey/map/filter/append/merge/push), ArrayAccess/Iterator, nested DTO hydration, conditional validation attributes (RequiredWith/RequiredWithout/etc.)

### Verified
- Full production audit: All 55+ source files verified for `declare(strict_types=1)`, `final class`, complete return type declarations, comprehensive docblocks, typed properties, PHPStan Level 9 compliance, strict comparisons, `#[Override]` attributes on interface implementations

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
