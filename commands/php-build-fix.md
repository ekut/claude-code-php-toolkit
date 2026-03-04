---
description: Incrementally fix Composer conflicts, autoloading issues, and PHP build errors
---

# PHP Build Fix

Diagnose and fix PHP build errors incrementally — Composer dependency conflicts, autoloading failures, extension requirements, and version constraint issues.

> **Foundation:** Delegates to `agents/php-build-resolver.md` for diagnosis and resolution.

## Step 1: Identify the Error

Run the failing command and capture the error:

```bash
composer install 2>&1
# or
composer update 2>&1
```

## Step 2: Diagnose

Based on the error type:

**Dependency conflict:**
```bash
composer why-not vendor/package 2.0
composer depends vendor/package
```

**Autoloading failure:**
- Verify namespace matches directory structure (PSR-4)
- Check `composer.json` autoload configuration
- Run `composer dump-autoload`

**Missing extension:**
- Check `php -m` for installed extensions
- Install missing: `apt install php8.x-{ext}` or `pecl install {ext}`

**PHP version constraint:**
- Check `php -v` against `composer.json` require.php
- Update constraint or upgrade PHP

**Lock file out of sync:**
```bash
composer update --lock
```

## Step 3: Fix Incrementally

Apply the smallest fix that resolves the error:

1. Fix one error at a time
2. Re-run `composer install` after each fix
3. Verify no new errors introduced
4. Run `vendor/bin/phpunit` to confirm nothing broke

## Step 4: Report

```
## Build Fix Report

**Error:** [exact error message]
**Root Cause:** [what caused it]
**Fix Applied:** [what was changed]
**Prevention:** [how to avoid in future]
**Tests:** [PASS/FAIL after fix]
```
