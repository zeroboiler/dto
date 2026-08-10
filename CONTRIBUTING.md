# Contributing to ZeroBoiler DTO

Thank you for your interest in contributing! This guide covers the development workflow.

## Requirements

- **PHP 8.5+**
- **Composer** for dependency management
- **Laravel 13+** (for integration testing)
- **zeroboiler/value-objects** (installed automatically as a dependency)

## Development Setup

```bash
git clone git@github.com:zeroboiler/dto.git
cd dto
composer install
```

## Code Quality

All contributions must pass the full CI pipeline:

```bash
# Run all quality checks at once
composer ci

# Or individually:
composer test       # Pest test suite
composer analyse     # PHPStan level 9 (zero errors, no baseline)
composer lint        # Laravel Pint (PSR-12)
composer rector      # Rector (automated refactoring)
```

### PHPStan Level 9

This package targets **PHPStan level 9 with zero ignored errors**. All code must:
- Use `declare(strict_types=1)` in every file
- Declare return types on all methods
- Use typed properties (no `mixed` without explicit annotation)
- Use strict comparisons (`===`, `!==`)
- Use `public readonly` properties in DTOs for language-level immutability

### Code Style

We follow **PSR-12** via Laravel Pint. Run `composer lint` before committing.

## Architecture

```
src/
├── Attributes/       — 40+ validation & metadata attributes (final, readonly)
├── Casts/            — Eloquent cast (DTOCast)
├── Console/Commands/ — Artisan commands (dto-test, dto-schema)
├── Contracts/        — ValidatableDTO, FromRequestDTO, ValidationAttribute
├── Exceptions/       — DTOException
├── Facades/          — Laravel facade (DTO)
├── Support/          — DtoMetadataResolver, OpenApiSchemaGenerator
├── DataTransferObject.php — Abstract base class
├── DtoCollection.php     — Type-safe collection wrapper
├── DTOManager.php        — Runtime helper (facade-backed)
└── DTOSServiceProvider.php — Auto-discovery
```

## Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add new validation attribute
fix: resolve nested DTO hydration edge case
refactor: improve DtoMetadataResolver type safety
test: add collection helper edge case tests
docs: update README with nested DTO examples
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/my-feature`)
3. Make changes with tests
4. Ensure `composer ci` passes
5. Open a pull request with a clear description

## Testing

Tests use [Pest](https://pestphp.com/). Test fixtures are in `tests/Fixtures/`.

```bash
# Run all tests
composer test

# Run a specific test file
vendor/bin/pest tests/DTOSerializationRoundtripTest.php
```

## License

Proprietary — see LICENSE file.
