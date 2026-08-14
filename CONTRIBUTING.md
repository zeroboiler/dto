# Contributing to ZeroBoiler DTO

Thank you for your interest in contributing! This document outlines the standards and workflow for this package.

## Code Standards

- **PHP 8.5+** — all source files must use `declare(strict_types=1)`
- **PHPStan Level 9** — zero warnings, no `mixed` types in public API
- **100% strict types** — every method has return type declarations, every parameter has type hints
- **Readonly promoted properties** — all DTO properties must be `public readonly` with explicit types
- **Docblocks** — every class, method, and property has a descriptive PHPDoc block with `@param`, `@return`, `@throws` annotations
- **`#[\Override]`** — use on all interface/parent method implementations

## Architecture

| Component | Responsibility |
|-----------|---------------|
| `DataTransferObject` (abstract) | Base class: `fromArray()`, `fromRequest()`, `fromJson()`, `toArray()`, `with()`, `equals()`, `rules()`, validation |
| `DtoCollection` | Type-safe collection wrapper with `pluck()`, `map()`, `filter()`, `ArrayAccess`, `IteratorAggregate` |
| `DtoMetadataResolver` | Reflection-based rule/metadata resolution (internal) |
| `OpenApiSchemaGenerator` | OpenAPI 3.0 schema generation from DTO definitions |
| `DTOManager` | Injectable/facade helper, delegates to DTO static methods |
| `DTOCast` | Eloquent cast — stores DTOs as JSON columns |
| `ValidationAttribute` | Interface for attributes that generate Laravel validation rules |
| `FromRequestDTO` | Interface for DTOs hydratable from HTTP requests |
| `ValidatableDTO` | Interface for DTOs providing validation rules |

## Hydration Pipeline

```
Raw Input Value
    ├─ 1. Map source key (#[MapFrom('user_name')])
    ├─ 2. Apply default if key missing (#[DefaultValue('active')])
    ├─ 3. Cast type (#[Cast('integer')])
    ├─ 4. Instantiate ValueObject (detected from type)
    ├─ 5. Instantiate BackedEnum (detected from type)
    ├─ 6. Hydrate nested DTO (detected from type)
    ├─ 7. Hydrate nested array of DTOs (#[NestedArray/Collection])
    ▼
Final DTO Instance (immutable)
```

## Adding a New Validation Attribute

1. Create `src/Attributes/YourAttribute.php` (final class)
2. Implement `ValidationAttribute` interface with `ruleKey()` method
3. Add named constructor parameters and optional `message` property
4. Register rule generation in `DtoMetadataResolver::applyValidationAttribute()`
5. Add OpenAPI schema mapping in `OpenApiSchemaGenerator::applyValidationAttributes()` (if applicable)
6. Add tests in `tests/`
7. Update README's Attributes Reference section

Example:

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
final class YourRule implements ValidationAttribute
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $message = null,
    ) {}

    public function ruleKey(): string
    {
        return 'your_rule';
    }
}
```

## Adding a New Metadata Attribute

Metadata attributes (like `MapFrom`, `Cast`, `Hidden`) don't implement `ValidationAttribute`.
They modify property metadata instead:

1. Create the attribute class in `src/Attributes/`
2. Add metadata extraction logic in `DtoMetadataResolver::applyMetaAttribute()`
3. Handle during hydration in `DataTransferObject::castAndHydrateValue()`
4. Handle during serialization in `DataTransferObject::convertProperties()` / `normalizeScalar()`

## Running Tests

```bash
composer test              # Run full Pest suite
composer test:coverage    # With coverage report
composer stan             # PHPStan Level 9 analysis
composer cs               # Pint code style fixer
composer analyse          # Full QA (stan + cs + test)
```

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add #[StartsWith] and #[EndsWith] validation attributes
fix: handle null values in MapFrom dot notation paths
refactor: extract hydration pipeline to castAndHydrateValue()
docs: update README with nested DTO examples
test: add coverage for union type hydration
```

## Pull Request Checklist

- [ ] `declare(strict_types=1)` in new/modified files
- [ ] Return types on all methods
- [ ] Docblocks with `@param`, `@return`, `@throws`
- [ ] PHPStan Level 9 passes with zero errors
- [ ] New features have test coverage
- [ ] README updated if public API changed
- [ ] No `mixed` types in public API
- [ ] All DTO properties are `public readonly`
