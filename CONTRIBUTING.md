# Contributing to ZeroBoiler DTO

Thank you for your interest in contributing! This document provides guidelines for contributing to this package.

## Development Setup

```bash
# Clone the repository
git clone git@github.com:zeroboiler/dto.git
cd dto

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis (PHPStan level 9)
composer analyse

# Run code style fixer
composer lint

# Run all CI checks
composer ci
```

## Code Standards

- **PHP 8.5+** — All code must target PHP 8.5 or later
- **Strict types** — Every PHP file must have `declare(strict_types=1)`
- **PHPStan Level 9** — All code must pass PHPStan level 9 analysis with zero errors
- **Final classes** — All attributes, services, collections, resolvers, and managers must be `final`
- **Readonly properties** — All DTO properties must be `public readonly`; all attribute constructor parameters must be `readonly`
- **`#[Override]`** — Applied to all interface/parent method implementations (`CastsAttributes`, `JsonSerializable`, `Arrayable`, `ValidatorContract`)
- **Docblocks** — All public methods, classes, and properties must have docblocks
- **No `mixed` types in public API** — `mixed` is only acceptable in internal cast/hydration pipelines with explicit `@param` annotations

## Pull Request Process

1. Create a feature branch from `main` (`feat/your-feature`)
2. Write/update tests for your changes
3. Ensure all CI checks pass (`composer ci`)
4. Submit a PR with a clear description of the change
5. PRs require at least one approval before merge

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add fromPartialArray for PATCH semantics
fix: resolve nested DTO hydration with null values
refactor: simplify castValue type inference
docs: update README with nested DTO example
test: add edge case tests for DtoCollection immutability
```

## Testing

- Use [Pest](https://pestphp.com/) for all tests
- Place fixtures in `tests/Fixtures/`
- Tests must cover: happy path, edge cases, error paths, type safety, immutability
- Generated test command output should be valid Pest syntax
- Test both validated and unvalidated hydration paths

## Adding New Validation Attributes

1. Create the attribute class in `src/Attributes/` implementing `ValidationAttribute`
2. Implement `ruleKey()` to return the primary Laravel validation rule key
3. Add the rule mapping in `DtoMetadataResolver::applyValidationAttribute()`
4. Add OpenAPI schema enrichment in `OpenApiSchemaGenerator::applyValidationAttributes()`
5. Add tests for the attribute (validation, OpenAPI schema, edge cases)

## License

By contributing, you agree that your contributions will be licensed under the proprietary license of this package.
