---
name: php-static-analysis
description: Use this skill when configuring or fixing PHPStan, Psalm, PHP-CS-Fixer, or Rector errors. Covers levels, baselines, rule sets, CI integration, and automated refactoring.
origin: claude-code-php-toolkit
---

# PHP Static Analysis

## When to Activate

- Setting up static analysis in a PHP project
- Fixing PHPStan or Psalm errors
- Configuring PHP-CS-Fixer rules
- Managing analysis baselines
- Integrating analysis into CI pipelines

## PHPStan

### Installation

```bash
composer require --dev phpstan/phpstan
```

### Configuration (`phpstan.neon`)

```neon
parameters:
    level: 8
    paths:
        - src
    excludePaths:
        - src/Legacy
    tmpDir: .phpstan-cache
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    reportUnmatchedIgnoredErrors: true
```

### Levels

| Level | What it checks |
|-------|---------------|
| 0 | Basic checks: unknown classes, functions, methods |
| 1 | Possibly undefined variables, unknown magic methods |
| 2 | Unknown methods on `object`, validating PHPDocs |
| 3 | Return types, property types |
| 4 | Dead code, unreachable branches |
| 5 | Argument types for methods and functions |
| 6 | Missing typehints |
| 7 | Union types partially matched |
| 8 | Nullability checks, strict mixed type |
| 9 | Mixed type restrictions (strictest) |

**Recommendation:** Start at level 5, incrementally raise to 8 or 9.

### Extensions

```bash
# Framework-specific
composer require --dev phpstan/phpstan-symfony
composer require --dev larastan/larastan
composer require --dev phpstan/phpstan-doctrine

# Quality
composer require --dev phpstan/phpstan-strict-rules
composer require --dev phpstan/phpstan-deprecation-rules
```

### Baseline

Generate a baseline for legacy code to adopt PHPStan without fixing everything immediately:

```bash
vendor/bin/phpstan analyse --generate-baseline
```

This creates `phpstan-baseline.neon`. Include it in your config:

```neon
includes:
    - phpstan-baseline.neon
```

New code must pass without baseline entries. Reduce the baseline over time.

### Running

```bash
vendor/bin/phpstan analyse                 # Full analysis
vendor/bin/phpstan analyse src/Domain      # Specific directory
vendor/bin/phpstan analyse --memory-limit=512M  # Increase memory
```

## Psalm

### Installation

```bash
composer require --dev vimeo/psalm
vendor/bin/psalm --init
```

### Configuration (`psalm.xml`)

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="2"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    findUnusedBaselineEntry="true"
    findUnusedCode="true"
>
    <projectFiles>
        <directory name="src"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>
</psalm>
```

### Error Levels

| Level | Strictness |
|-------|-----------|
| 1 | Strictest — all issues reported |
| 2 | Very strict — recommended for new projects |
| 3 | Moderate |
| 4 | Lenient |
| 5-8 | Progressively more lenient |

### Psalm-specific Features

```php
/** @psalm-immutable */
final class Point
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
    ) {}
}

/** @psalm-assert string $value */
function assertString(mixed $value): void
{
    if (!is_string($value)) {
        throw new InvalidArgumentException('Expected string');
    }
}

/** @template T */
interface Collection
{
    /** @return T */
    public function first(): mixed;

    /** @param T $item */
    public function add(mixed $item): void;
}
```

### Baseline

```bash
vendor/bin/psalm --set-baseline=psalm-baseline.xml
vendor/bin/psalm --update-baseline  # Remove fixed issues
```

## PHP-CS-Fixer

### Dry Run & Fix

```bash
# Check without fixing
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix all files
vendor/bin/php-cs-fixer fix

# Fix specific file
vendor/bin/php-cs-fixer fix src/Domain/User.php
```

### Common Rule Sets

| Rule Set | Description |
|----------|-------------|
| `@PER-CS2.0` | PER Coding Style 2.0 |
| `@PSR12` | PSR-12 |
| `@Symfony` | Symfony coding standards |
| `@PhpCsFixer` | PHP-CS-Fixer recommended rules |

## Rector

Automated refactoring and code upgrades:

### Installation

```bash
composer require --dev rector/rector
```

### Configuration (`rector.php`)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    );
```

### Running

```bash
vendor/bin/rector process --dry-run  # Preview changes
vendor/bin/rector process            # Apply changes
```

## CI Integration

### GitHub Actions Example

```yaml
name: Static Analysis

on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-progress
      - run: vendor/bin/phpstan analyse

  php-cs-fixer:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-progress
      - run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Checklist

- [ ] PHPStan configured at level 5 or higher
- [ ] Psalm configured at level 3 or lower (1 = strictest)
- [ ] PHP-CS-Fixer or Pint configured with PER-CS 2.0
- [ ] Baselines generated for legacy code
- [ ] Static analysis runs in CI on every push/PR
- [ ] Rector configured for PHP version upgrades
- [ ] No new baseline entries allowed in new code
- [ ] IDE integration configured (PHPStan plugin, etc.)
