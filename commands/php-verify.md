---
description: Full PHP verification pipeline — Composer, syntax, style, analysis, tests, security, diff review
---

# PHP Verify

Run the full 6-phase verification pipeline on the current PHP project. Stop at any failing phase and fix before continuing.

> **Foundation:** See `skills/php-verification/SKILL.md` for detailed phase descriptions and tool configurations.

## Phase 1: Composer & Syntax

```bash
composer validate --strict
composer dump-autoload --dry-run
find src tests -name '*.php' -print0 | xargs -0 -P$(nproc) php -l
```

Stop if any parse errors — nothing else will work.

## Phase 2: Code Style

Run the project's formatter in dry-run mode:

```bash
# PHP-CS-Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no
# OR Laravel Pint
vendor/bin/pint --test
```

Fix violations before continuing.

## Phase 3: Static Analysis

```bash
vendor/bin/phpstan analyse --memory-limit=512M
# Psalm (if configured)
vendor/bin/psalm --no-cache
# Rector dry-run (if configured)
vendor/bin/rector process --dry-run
```

## Phase 4: Tests & Coverage

```bash
php -dpcov.enabled=1 vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
# OR Pest
php -dpcov.enabled=1 vendor/bin/pest --coverage --min=80
```

Target: all tests pass, 80%+ line coverage.

## Phase 5: Security

```bash
composer audit
grep -rn "var_dump\|print_r\|dd(\|dump(" src/ --include="*.php"
```

No known CVEs, no debug statements in `src/`.

## Phase 6: Diff Review

```bash
git diff --stat
git diff
```

Check for unintended changes, debug leftovers, missing error handling, N+1 queries.

## Output

Produce a verification report:

```
VERIFICATION REPORT
===================
Composer:  [PASS/FAIL]
Syntax:    [PASS/FAIL]
Style:     [PASS/FAIL] (X violations)
Analysis:  [PASS/FAIL] (PHPStan: X errors, Psalm: X errors)
Tests:     [PASS/FAIL] (X/Y passed, Z% coverage)
Security:  [PASS/FAIL] (CVEs: X, debug: X)
Diff:      [X files changed, +Y/-Z lines]

Overall:   [READY / NOT READY] for PR
```
