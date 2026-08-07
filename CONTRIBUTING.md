# Contributing to ZeroBoiler DTO

Thank you for your interest in contributing! This document outlines the process.

## Code Standards

- **PHP 8.5 syntax** — use the latest language features
- **Strict types** — every file must have `declare(strict_types=1)`
- **PHPStan level 9** — zero errors, no baseline suppressions
- **Docblocks** — all public methods and properties documented
- **Typed properties** — no `mixed` types in source code
- **Final classes** — all attributes, services, collections, and resolvers are `final`
- **Immutable** — all properties are `public readonly`; `with()` returns a new instance

## Development Setup

```bash
composer install
```

**Note:** This package requires `zeroboiler/value-objects` as a dependency (resolved via path repository).

## Quality Checks

All checks must pass before merging:

```bash
# Run the full test suite
composer test

# Run PHPStan analysis (level 9, no baseline)
composer analyse

# Run code style checker
composer lint

# Run all quality checks at once
composer ci
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Ensure all CI checks pass (`composer ci`)
4. Commit with [conventional commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`)
5. Push and open a Pull Request

## Architecture Overview

```
src/
├── Attributes/     — 30+ validation & metadata attribute classes
├── Casts/          — Eloquent DTO cast (JSON storage)
├── Console/        — Artisan commands (test generation, OpenAPI schema)
├── Contracts/      — ValidatableDTO, FromRequestDTO, ValidationAttribute
├── Exceptions/     — DTOException
├── Facades/         — DTO facade
├── Support/         — DtoMetadataResolver, OpenApiSchemaGenerator
├── DataTransferObject.php — Abstract base class (all public API)
├── DtoCollection.php — Type-safe collection wrapper
├── DTOManager.php  — Runtime DTO helper (facade-backed)
└── DTOSServiceProvider.php — Auto-discovery service provider
```

## Testing

Tests use [Pest](https://pestphp.com/). Fixtures live in `tests/Fixtures/`.

When adding new DTO features:
- Add a fixture DTO in `tests/Fixtures/`
- Add tests covering the new behavior
- Ensure PHPStan level 9 compliance

## Adding New Validation Attributes

1. Create the attribute class in `src/Attributes/` implementing `ValidationAttribute`
2. Add the `ruleKey()` method returning the Laravel rule name
3. Add the rule resolution in `DtoMetadataResolver::applyValidationAttribute()`
4. Add the attribute to the OpenAPI schema generator if applicable
5. Add documentation to the README attributes reference
