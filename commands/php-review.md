---
description: Comprehensive PHP code review — PSR compliance, type safety, security, performance, Composer best practices
---

# PHP Code Review

Perform a comprehensive PHP code review on the current changes:

1. Get changed PHP files: `git diff --name-only HEAD -- '*.php'`

2. For each changed file, check for:

**PSR Compliance:**
- `declare(strict_types=1)` present
- PSR-12/PER-CS formatting
- PSR-4 autoloading (namespace matches directory)
- Imports grouped and sorted

**Type Safety:**
- All parameters, returns, and properties have type declarations
- Enums for fixed value sets (not class constants)
- Readonly properties for immutable data
- No unnecessary `mixed` types

**Security (CRITICAL):**
- SQL queries use prepared statements (PDO parameter binding)
- Output escaped with `htmlspecialchars()` or template engine
- No `eval()`, `exec()`, `shell_exec()` with user input
- File uploads validated (MIME type, extension, size)
- Passwords hashed with `password_hash()`

**Performance:**
- No N+1 queries
- Generators for large datasets
- Efficient string/array operations

**Architecture:**
- Final classes by default
- Dependency injection (constructor)
- Single responsibility
- Composition over inheritance

**Composer:**
- Correct `require` vs `require-dev`
- Version constraints use `^` (caret)

3. Output findings as: Critical Issues, Improvements, Minor Notes, Positive Observations
