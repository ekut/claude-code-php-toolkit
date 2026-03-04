---
description: Analyze PHP test coverage gaps and generate missing tests to reach 80%+ line coverage
---

# PHP Test Coverage

Analyze test coverage gaps in the current PHP project and generate missing tests to reach 80%+ line coverage.

> **Foundation:** See `skills/php-testing/SKILL.md` for PHPUnit/Pest setup, mocking, data providers, and coverage configuration.

## Step 1: Detect Test Framework

Check `composer.json` for `pestphp/pest` (→ Pest) or `phpunit/phpunit` (→ PHPUnit).

## Step 2: Run Coverage Report

```bash
# PHPUnit with PCOV
php -dpcov.enabled=1 vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

# OR Pest
php -dpcov.enabled=1 vendor/bin/pest --coverage --min=80

# OR with Xdebug
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

## Step 3: Identify Gaps

From the coverage report, identify:

1. **Uncovered classes** — files with 0% coverage (highest priority)
2. **Low-coverage classes** — files below 80% (fill gaps)
3. **Uncovered branches** — conditional logic without both-path tests
4. **Missing edge cases** — null, empty, boundary values, exception paths

## Step 4: Generate Missing Tests

For each gap, write tests following these rules:

- **Arrange-Act-Assert** pattern
- **Descriptive names** — `it_rejects_negative_amount`, not `testCase1`
- **Data providers** for multiple input scenarios
- **Mock only at boundaries** — I/O, external services, not internal classes
- **One assertion per behavior** — test one thing at a time

## Step 5: Verify Coverage Improvement

Re-run coverage after adding tests:

```bash
php -dpcov.enabled=1 vendor/bin/phpunit --coverage-text
```

## Output

```
COVERAGE REPORT
===============
Before:  XX.X% line coverage
After:   XX.X% line coverage
Target:  80.0%

Tests Added: X
Files Covered: X new, X improved

Remaining Gaps:
- src/Path/To/File.php — XX% (reason: complex branching)
```
