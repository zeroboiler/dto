# Changelog

All notable changes to the ZeroBoiler DTO package will be documented in this file.

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
