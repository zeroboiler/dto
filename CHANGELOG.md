# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

## [Unreleased]

### Added
- `DTOFinalProductionAuditV2Test` — 45+ production readiness tests covering: strict_types audit, fromJson error cases, MapFrom resolution, Hidden/DefaultValue/Cast behavior, only/except/equals/isEmpty/toJson/jsonSerialize, DtoCollection (make/pluck/pluckKey/filter/append/merge/first/last/toArray/array_access), nested DTO hydration and serialization, with() immutable updates, fromPartialArray, DTOException factory methods, metadata cache lifecycle, rules/rulesFor contract
- `composer.json` now includes `support` field with issue tracker and source URLs

### Improved
- `OpenApiSchemaGenerator::componentName()` now uses string parsing instead of reflection for extracting short class names
- `RequiredWith*` attribute docblocks now document the `string|array` parameter acceptance
- Better `DefaultValue` handling in `fromPartialArray()` — `DefaultValue` attribute takes priority over PHP constructor defaults
- `DtoHydrationPipelineContractTest` — comprehensive contract tests for hydration pipeline (MapFrom, Cast, DefaultValue, Hidden), immutable updates, equality, selective output, fromJson, validation rules, collection immutability, and metadata cache lifecycle
- CHANGELOG.md deduplicated — merged scattered `[Unreleased]` sections into single coherent entry

## [1.1.0] - 2025-08-08

### Added
- Abstract base class `DataTransferObject` with zero-boilerplate hydration and validation
- 30+ validation attributes: `#[Required]`, `#[Email]`, `#[Max]`, `#[Min]`, `#[Pattern]`, `#[Enum]`, `#[Uuid]`, `#[Url]`, `#[Date]`, `#[Integer]`, `#[Numeric]`, `#[Boolean]`, `#[In]`, `#[Accepted]`, `#[Declined]`, `#[Confirmed]`, `#[Different]`, `#[Same]`, `#[Between]`, `#[Prohibited]`, `#[Present]`, `#[Distinct]`, `#[Size]`, `#[StartsWith]`, `#[EndsWith]`, `#[Json]`, `#[Nullable]`, `#[Sometimes]`
- Conditional validation attributes: `#[RequiredIf]`, `#[RequiredUnless]`, `#[RequiredWith]`, `#[RequiredWithAll]`, `#[RequiredWithout]`, `#[RequiredWithoutAll]`
- Metadata attributes: `#[Hidden]`, `#[MapFrom]`, `#[Cast]`, `#[DefaultValue]`, `#[NestedArray]`, `#[Collection]`
- Hydration: `fromArray()`, `fromRequest()`, `fromJson()`, `fromPartialArray()`, `fromPartialRequest()`
- Serialization: `toArray()`, `allValues()`, `toJson()`, `jsonSerialize()`
- Immutable update: `with()`
- Selective output: `only()`, `except()`
- State checks: `equals()`, `isEmpty()`, `isNotEmpty()`
- Validation: `rules()`, `rulesFor()`, `validateArray()`, `validatePartialArray()`
- `DtoCollection` with `pluck()`, `pluckKey()`, `map()`, `filter()`, `append()`, `merge()`
- `DTOCast` Eloquent cast attribute
- `DTOManager` for runtime access via facade
- `DTO` facade for convenient DTO operations
- `OpenApiSchemaGenerator` for automatic API docs
- CLI commands: `zeroboiler:dto-test`, `zeroboiler:dto-schema`
- PHPStan level 9 compliance across all source files

### Design
- Zero-boilerplate: extend `DataTransferObject`, add attributes, done
- Language-level immutability: `public readonly` properties
- Validation always runs in `with()` to prevent invalid state
- PATCH semantics via `fromPartialArray()` / `fromPartialRequest()`
- Static metadata cache per class with TTL-based dev invalidation
- Reflection-based property access in `DtoCollection` for PHPStan safety

[1.1.0]: https://github.com/zeroboiler/dto/releases/tag/v1.1.0
