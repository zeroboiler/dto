## Summary
Brief description of what this PR does.

## Type
- [ ] Bug fix
- [ ] Feature
- [ ] Refactor
- [ ] Test
- [ ] Docs

## Checklist
- [ ] `declare(strict_types=1)` on all new files
- [ ] All methods have return type declarations
- [ ] All properties are typed (no `mixed` without annotation)
- [ ] Docblocks on all public methods/classes
- [ ] `final` on all new classes
- [ ] `readonly` on all DTO promoted properties
- [ ] PHPStan level 9 passes with zero errors
- [ ] Tests added/updated
- [ ] Conventional commit message

## Testing
```bash
composer test
composer analyse
```
