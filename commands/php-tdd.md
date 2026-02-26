---
description: PHP TDD workflow — develop features test-first with PHPUnit or Pest using Red-Green-Refactor cycle
---

# PHP TDD Workflow

Guide test-driven development for PHP using the Red-Green-Refactor cycle:

1. **Detect test framework** — check `composer.json` for `pestphp/pest` (→ Pest) or `phpunit/phpunit` (→ PHPUnit)

2. **Clarify the behavior** — what should the code do? Identify inputs, outputs, edge cases.

3. **Red** — write a failing test:
   - PHPUnit: `vendor/bin/phpunit --filter="test_method_name"`
   - Pest: `vendor/bin/pest --filter="it does something"`
   - Test MUST fail. If it passes, the test is not testing new behavior.

4. **Green** — write the simplest code that makes the test pass. No more.

5. **Refactor** — improve the code while keeping tests green. Run full suite.

6. **Repeat** — for each new behavior or edge case.

**Coverage check:**
```bash
php -dpcov.enabled=1 vendor/bin/phpunit --coverage-text
```

Target: 80% line coverage minimum, 95% for critical paths.
