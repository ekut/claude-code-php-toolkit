---
description: Sync PHPDoc annotations, environment docs, and Composer scripts with the current codebase
---

# PHP Update Docs

Synchronize documentation with the current codebase — PHPDoc annotations, environment variable docs, and Composer script descriptions.

> **Foundation:** Delegates to `agents/php-doc-updater.md` for PHPDoc standards and codemap generation.

## Step 1: Scan for PHPDoc Gaps

Find PHP files with missing or outdated documentation:

- Public methods and classes without PHPDoc
- `@param`/`@return` that conflict with type declarations
- Missing `@throws` annotations
- Deprecated methods without `@deprecated` tags

## Step 2: Update PHPDoc

Add PHPDoc only where it adds value beyond native types:

- **Add:** generics (`list<T>`, `array<K,V>`), array shapes, `@throws`, `@deprecated`
- **Skip:** trivial getters, constructor promotion, types duplicating the signature

## Step 3: Sync Environment Docs

- Compare `.env.example` with actual `$_ENV`/`getenv()` usage in the codebase
- Add missing variables to `.env.example` with comments
- Remove variables no longer referenced in code
- Update README environment section if it exists

## Step 4: Update Composer Scripts

- Verify `composer.json` scripts match available tooling
- Ensure `test`, `analyse`, `format` scripts are present if tools are installed
- Document custom scripts in README or `composer.json` description

## Step 5: Report

```
## Documentation Update Report

### PHPDoc
- [X] files updated
- [Y] annotations added (generics, @throws, @deprecated)
- [Z] redundant annotations removed

### Environment
- [X] variables added to .env.example
- [Y] stale variables removed

### Composer Scripts
- [status of script synchronization]
```
