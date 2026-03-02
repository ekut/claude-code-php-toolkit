---
name: php-verification
description: Use this skill to run a full verification pipeline before PRs, after refactoring, or at regular intervals during long sessions. Covers Composer validation, syntax, code style, static analysis, tests with coverage, security scanning, and diff review.
origin: claude-code-php-toolkit
---

# PHP Verification Loop

A 6-phase verification pipeline for PHP projects. Run the full loop before opening PRs, after significant refactoring, and at regular checkpoints during long sessions.

## When to Activate

- Before opening a pull request
- After completing a feature or significant refactoring
- After dependency upgrades (`composer update`)
- Pre-deployment verification
- At regular intervals during long coding sessions (every 15–30 min)

## Phase 1: Composer & Syntax

Validate the project manifest and check PHP files for syntax errors.

```bash
# Validate composer.json (strict mode catches common issues)
composer validate --strict

# Check autoload mapping
composer dump-autoload --dry-run

# Syntax-check all PHP files in src/ and tests/
find src tests -name '*.php' -print0 | xargs -0 -P$(nproc) php -l
```

**Pass criteria:** Zero parse errors, valid `composer.json`.

If syntax check fails, stop and fix before continuing — nothing else will work.

## Phase 2: Code Style

Run the project's code style fixer in dry-run mode to detect violations without changing files.

```bash
# PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no

# OR Laravel Pint
vendor/bin/pint --test
```

**Pass criteria:** Zero style violations.

Fix with `vendor/bin/php-cs-fixer fix` or `vendor/bin/pint` before continuing.

## Phase 3: Static Analysis

Run type checking and automated refactoring analysis.

```bash
# PHPStan (primary)
vendor/bin/phpstan analyse --memory-limit=512M

# Psalm (if configured)
vendor/bin/psalm --no-cache

# Rector dry-run (detect pending automated refactorings)
vendor/bin/rector process --dry-run
```

**Pass criteria:** Zero errors at the project's configured level. Baseline violations in legacy code are acceptable if no new entries are added.

> **Cross-reference:** See `php-static-analysis` skill for PHPStan/Psalm configuration, levels, baselines, and extensions.

## Phase 4: Tests & Coverage

Run the full test suite with coverage collection.

```bash
# PHPUnit with PCOV (recommended for speed)
php -dpcov.enabled=1 vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

# OR with Xdebug
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

# Pest (if used instead of PHPUnit)
php -dpcov.enabled=1 vendor/bin/pest --coverage --min=80
```

**Pass criteria:**
- All tests pass (zero failures, zero errors)
- Line coverage >= 80% (project-specific thresholds may be higher)

Report format:

```
Tests:     X passed, Y failed, Z errors
Coverage:  XX.X% lines (target: 80%+)
```

> **Cross-reference:** See `php-testing` skill for PHPUnit/Pest setup, mocking, data providers, and coverage configuration.

## Phase 5: Security Scan

> See `php-security-scanning` skill for a comprehensive 5-phase security pipeline (dependency audit, taint analysis, secrets check, debug detection, OWASP pattern scan).

Quick check for the verification loop:

```bash
composer audit && grep -rn "var_dump\|print_r\|dd(\|dump(" src/ --include="*.php"
```

**Pass criteria:** No known CVEs, no debug statements in `src/`.

## Phase 6: Diff Review

Review the actual changes before committing.

```bash
# Summary of changes
git diff --stat

# Full diff for review
git diff

# If already staged
git diff --cached --stat
git diff --cached
```

Checklist for each changed file:

- [ ] No unintended changes or debugging leftovers
- [ ] Error handling present for new I/O operations
- [ ] Edge cases considered (null, empty, boundary values)
- [ ] New public methods have type declarations
- [ ] Database queries use parameterized statements
- [ ] No N+1 query patterns introduced
- [ ] Config changes documented (env vars, services, etc.)

## Output Template

After running all phases, produce a summary report:

```
VERIFICATION REPORT
===================
Composer:  [PASS/FAIL]
Syntax:    [PASS/FAIL]
Style:     [PASS/FAIL] (X violations)
Analysis:  [PASS/FAIL] (PHPStan: X errors, Psalm: X errors)
Tests:     [PASS/FAIL] (X/Y passed, Z% coverage)
Security:  [PASS/FAIL] (CVEs: X, debug: X, secrets: X)
Diff:      [X files changed, +Y/-Z lines]

Overall:   [READY / NOT READY] for PR

Issues to Fix:
1. ...
2. ...
```

## Quick Reference

| Phase       | Tool         | Command                                                   |
|-------------|--------------|-----------------------------------------------------------|
| 1. Composer | composer     | `composer validate --strict`                              |
| 1. Syntax   | php          | `find src tests -name '*.php' -print0 \| xargs -0 php -l` |
| 2. Style    | php-cs-fixer | `vendor/bin/php-cs-fixer fix --dry-run --diff`            |
| 2. Style    | pint         | `vendor/bin/pint --test`                                  |
| 3. Analysis | phpstan      | `vendor/bin/phpstan analyse`                              |
| 3. Analysis | psalm        | `vendor/bin/psalm --no-cache`                             |
| 3. Analysis | rector       | `vendor/bin/rector process --dry-run`                     |
| 4. Tests    | phpunit      | `vendor/bin/phpunit --coverage-text`                      |
| 4. Tests    | pest         | `vendor/bin/pest --coverage --min=80`                     |
| 5. Security | composer     | `composer audit`                                          |
| 5. Security | psalm        | `vendor/bin/psalm --taint-analysis`                       |
| 6. Diff     | git          | `git diff --stat`                                         |

## Continuous Mode

For long coding sessions, run a quick verification loop every 15–30 minutes:

1. **Quick loop** (every 15 min): Phase 1 (syntax only) + Phase 3 (PHPStan) + Phase 4 (unit tests only)
2. **Full loop** (every 30–60 min or before commit): All 6 phases

```bash
# Quick loop one-liner
php -l src/**/*.php && vendor/bin/phpstan analyse && vendor/bin/phpunit --testsuite=Unit
```

Keep the feedback cycle tight — fast feedback beats late surprises.

## Checklist

- [ ] `composer validate --strict` passes
- [ ] All PHP files parse without syntax errors
- [ ] Code style check passes (php-cs-fixer or pint)
- [ ] PHPStan passes at the project's configured level
- [ ] All tests pass with 80%+ coverage
- [ ] `composer audit` reports no known CVEs
- [ ] No debug statements in `src/`
- [ ] No hardcoded secrets in source files
- [ ] Diff reviewed for unintended changes
- [ ] Verification report generated and shared
