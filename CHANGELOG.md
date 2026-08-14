# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-08-14

### Added
- `DataTransferObject` — abstract base class with `fromArray()`, `fromRequest()`, `fromJson()`, `fromPartialArray()`, `fromPartialRequest()`, `toArray()`, `toJson()`, `jsonSerialize()`, `with()`, `equals()`, `isEmpty()`, `isNotEmpty()`, `only()`, `except()`, `allValues()`, `rules()`, `rulesFor()`, `validateArray()`, `validatePartialArray()`, `flushMetadataCache()`, `setMetadataCacheTtl()`
- `DtoCollection` — type-safe collection with `make()`, `push()`, `append()`, `merge()`, `filter()`, `map()`, `pluck()`, `pluckKey()`, `toArrayBy()`, `toDictionary()`, `toArray()`, `allValues()`, `items()`, `first()`, `last()`, `count()`, `isEmpty()`, `isNotEmpty()`
- `DTOManager` — runtime helper (injectable/facade) with `validate()`, `make()`, `makeFromJson()`, `fromJson()`, `fromPartialArray()`, `fromPartialRequest()`, `rules()`, `rulesFor()`, `schema()`
- `DTO` facade — Laravel facade for `DTOManager`
- `DTOSServiceProvider` — auto-discovery service provider with Octane cache flush support and dev TTL configuration
- 37 validation attributes: `Required`, `Email`, `Max`, `Min`, `Between`, `Pattern`, `In`, `Url`, `Uuid`, `Integer`, `Numeric`, `Boolean`, `Date`, `ArrayRule`, `Json`, `Enum`, `Confirmed`, `Same`, `Different`, `Distinct`, `Prohibited`, `Accepted`, `Declined`, `Present`, `Nullable`, `Sometimes`, `Size`, `StartsWith`, `EndsWith`, `RequiredIf`, `RequiredUnless`, `RequiredWith`, `RequiredWithAll`, `RequiredWithout`, `RequiredWithoutAll`
- 4 metadata attributes: `Hidden`, `MapFrom`, `Cast`, `DefaultValue`
- 2 nested type attributes: `NestedArray`, `Collection`
- `DtoMetadataResolver` — reflection-based metadata resolution from constructor parameters and attributes
- `OpenApiSchemaGenerator` — OpenAPI 3.0 schema generation with nested DTO support, union type `oneOf`, and component schemas
- `DTOCast` — Eloquent cast for storing DTOs as JSON columns with optional validation
- `DTOException` — named constructors `invalidCast()` and `invalidJson()`
- Contracts: `ValidationAttribute`, `FromRequestDTO`, `ValidatableDTO`
- `MakeDtoTestCommand` — `php artisan zeroboiler:dto-test` Pest test generator
- `MakeDtoSchemaCommand` — `php artisan zeroboiler:dto-schema` OpenAPI schema generator
- Per-class static metadata caching with TTL-based invalidation
- Auto-detection of ValueObject, BackedEnum, and nested DTO types
- Hydration pipeline: MapFrom → DefaultValue → Cast → ValueObject → Enum → Nested DTO → Collection
- PATCH semantics via `fromPartialArray()` and `fromPartialRequest()`
- Type casting: `integer`, `string`, `boolean`, `array`, `date/datetime`

### Quality
- 100% `declare(strict_types=1)` coverage across all 55 source files
- PHPStan Level 9 compatible — zero `mixed` return types in public API
- All classes marked `final` where appropriate
- All attributes use `readonly` promoted constructor properties
- Comprehensive PHPDoc with `@param`, `@return`, `@throws` annotations
- 259 test files (226 unit tests + 33 fixtures)

## [1.0.0] - 2025-08-01

### Added
- Initial release with core DTO functionality
- Basic `fromArray()`, `toArray()`, `toJson()` support
- Validation attribute resolution
- Eloquent cast support

## [Unreleased]

_No unreleased changes._
