---
description: Detect and remove dead PHP code — unused imports, classes, methods, and Composer packages with test verification
---

# PHP Refactor Clean

Detect and safely remove dead code from the PHP codebase. Every removal is verified with tests and static analysis.

> **Foundation:** Delegates to `agents/php-refactor-cleaner.md` for the full cleanup process.

## Step 1: Detect Unused Imports

```bash
vendor/bin/php-cs-fixer fix --rules=no_unused_imports --dry-run --diff
```

## Step 2: Detect Unused Composer Packages

```bash
vendor/bin/composer-unused
```

Verify each reported package — check config files, DI containers, and string references.

## Step 3: Detect Dead Code

```bash
vendor/bin/psalm --find-unused-code
vendor/bin/phpstan analyse --level=max
```

## Step 4: Safe Removal

For each candidate:

1. **Verify** — search for all references: `grep -r "ClassName" src/ config/ tests/`
2. **Check dynamic usage** — event listeners, service providers, middleware, DQL, reflection
3. **Remove** — delete the code
4. **Test** — run `vendor/bin/phpunit` (or `vendor/bin/pest`)
5. **Analyze** — run `vendor/bin/phpstan analyse`
6. **Commit** — one logical removal per commit

## Step 5: Report

```
## Cleanup Report

### Unused Imports
[count] removed from [count] files

### Unused Packages
| Package | Reason |
|---------|--------|

### Dead Code Removed
| Type | Location | Confidence |
|------|----------|------------|

### Test Results
All tests passing: [yes/no]
Static analysis clean: [yes/no]
```
