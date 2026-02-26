# Project: [Your Project Name]

## Overview

[Brief description of what this project does]

## Tech Stack

- **Language:** PHP 8.1+ (strict types enabled)
- **Framework:** [Symfony / Laravel / none]
- **Testing:** PHPUnit 10+ / Pest 2+
- **Static Analysis:** PHPStan (level 8), PHP-CS-Fixer (PER-CS 2.0)
- **Dependencies:** Composer

## Project Structure

```
src/              # Application source code (PSR-4: App\)
tests/
  Unit/           # Isolated unit tests
  Integration/    # Tests with real dependencies
config/           # Configuration files
public/           # Web server document root
```

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit
vendor/bin/pest

# Run specific test
vendor/bin/phpunit --filter="test_method_name"

# Static analysis
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix formatting
vendor/bin/php-cs-fixer fix

# Full check
composer check  # runs tests + phpstan + php-cs-fixer
```

## Coding Standards

- Follow PSR-12 and PER Coding Style 2.0
- Every PHP file must have `declare(strict_types=1);`
- All parameters, return types, and properties must have type declarations
- Use `final` classes by default
- Use `readonly` properties for immutable data
- Use enums for fixed value sets
- Prefer composition over inheritance

## Testing Requirements

- Write tests before implementation (TDD preferred)
- Minimum 80% line coverage
- Use data providers for multiple scenarios
- Mock only at boundaries (HTTP, database, filesystem)
- Follow Arrange-Act-Assert pattern

## Security Rules

- Use PDO prepared statements for all database queries
- Escape all output with `htmlspecialchars()` or template engine
- Use `password_hash()` and `password_verify()` for passwords
- Validate file uploads server-side
- Run `composer audit` before releases

## Git Workflow

- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`
- One logical change per commit
- Run `composer check` before committing
