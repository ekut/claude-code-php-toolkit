---
description: Run PHP static analysis and formatting — PHPStan, PHP-CS-Fixer/Pint, Psalm, Rector
---

# PHP Static Analysis

Run static analysis and code formatting tools on PHP code:

1. **Detect available tools** in `composer.json`:
   - `vendor/bin/php-cs-fixer` or `vendor/bin/pint` — formatting
   - `vendor/bin/phpstan` — static type analysis
   - `vendor/bin/psalm` — additional analysis
   - `vendor/bin/rector` — automated refactoring

2. **Run formatting** (fix first, then check):
   ```bash
   # PHP-CS-Fixer
   vendor/bin/php-cs-fixer fix --dry-run --diff
   # Laravel Pint
   vendor/bin/pint --test
   ```

3. **Run PHPStan:**
   ```bash
   vendor/bin/phpstan analyse
   ```

4. **Run Psalm** (if installed):
   ```bash
   vendor/bin/psalm
   ```

5. **Report results** — summarize findings, suggest fixes for each error
