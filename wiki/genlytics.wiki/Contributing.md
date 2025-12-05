# Contributing Guide

Thank you for considering contributing to Genlytics! This guide will help you get started.

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported
2. Use the bug report template
3. Include:
   - Clear description
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details (PHP, Laravel versions)
   - Error messages/logs

### Suggesting Features

1. Check if the feature has been suggested
2. Use the feature request template
3. Explain:
   - Use case
   - Benefits
   - Possible implementation approach

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Follow coding standards
5. Add tests if applicable
6. Update documentation
7. Commit with clear messages
8. Push to your fork
9. Create a Pull Request

## Development Setup

### Prerequisites

- PHP 8.1+
- Composer
- Laravel 9.0+
- Git

### Setup Steps

1. **Fork and Clone**

```bash
git clone https://github.com/your-username/genlytics.git
cd genlytics
```

2. **Install Dependencies**

```bash
composer install
```

3. **Run Tests**

```bash
composer test
# or
vendor/bin/pest
```

4. **Check Code Style**

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Coding Standards

### PHP Standards

- Follow PSR-12 coding standard
- Use type hints everywhere
- Add PHPDoc comments
- Follow clean code practices

### Code Style

Run PHP CS Fixer before committing:

```bash
vendor/bin/php-cs-fixer fix
```

### Naming Conventions

- Classes: `PascalCase`
- Methods: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Variables: `camelCase`

## Architecture Guidelines

### Design Principles

- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Implementations are substitutable
- **Interface Segregation**: Focused, specific interfaces
- **Dependency Inversion**: Depend on abstractions

### Design Patterns

- **Repository Pattern**: For data access
- **Service Layer**: For business logic
- **Event-Driven**: For decoupled communication
- **Factory Pattern**: For object creation

### File Structure

```
src/
├── Commands/       # Artisan commands
├── Contracts/     # Interfaces
├── Events/         # Event classes
├── Jobs/          # Queue jobs
├── Listeners/     # Event listeners
├── Repositories/  # Data access
├── Services/      # Business logic
└── ...
```

## Writing Tests

### Test Structure

```php
<?php

namespace Tests;

use Tests\TestCase;
use zfhassaan\genlytics\Genlytics;

class GenlyticsTest extends TestCase
{
    public function test_can_run_report()
    {
        // Arrange
        $analytics = new Genlytics();
        
        // Act
        $result = $analytics->runReports(...);
        
        // Assert
        $this->assertNotNull($result);
    }
}
```

### Running Tests

```bash
# All tests
composer test

# Specific test
vendor/bin/pest tests/Feature/GenlyticsTest.php

# With coverage
vendor/bin/pest --coverage
```

## Documentation

### Code Documentation

- Add PHPDoc to all public methods
- Include parameter descriptions
- Document return types
- Add `@throws` annotations

### Example

```php
/**
 * Run a report with specified parameters
 *
 * @param array $dateRange Date range with 'start_date' and 'end_date'
 * @param array $dimensions Array of dimension arrays
 * @param array $metrics Array of metric arrays
 * @param array $options Additional options
 * @param bool $forceRefresh Force refresh from API
 * @return JsonResponse
 * @throws \Exception
 */
public function runReports(...): JsonResponse
```

### Wiki Documentation

When adding features, update relevant wiki pages:
- [[Usage-Guide|Usage Guide]]
- [[Configuration-Guide|Configuration Guide]]
- [[Troubleshooting|Troubleshooting]]

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add new feature
fix: fix bug
docs: update documentation
style: code style changes
refactor: code refactoring
test: add tests
chore: maintenance tasks
```

### Examples

```
feat: add batch report processing
fix: resolve cache key generation issue
docs: update installation guide
refactor: improve error handling
test: add repository tests
```

## Pull Request Process

### Before Submitting

1. ✅ Code follows standards
2. ✅ Tests pass
3. ✅ Documentation updated
4. ✅ No linter errors
5. ✅ Backward compatibility maintained (if applicable)

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
How was this tested?

## Checklist
- [ ] Code follows style guidelines
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

## Review Process

1. Maintainers will review your PR
2. Address any feedback
3. Once approved, maintainers will merge

## Questions?

- Open an issue for discussion
- Check existing issues/PRs
- Review documentation

## Recognition

Contributors will be:
- Listed in CONTRIBUTORS.md
- Mentioned in release notes
- Credited in the project

Thank you for contributing to Genlytics! 🎉

