---
description: Audit all package references in skills, commands, and rules against the verified-packages.json allowlist
---

# Audit Packages

Scan all skill, command, and rule files for package references and cross-reference them against the verified package registry.

## Steps

1. **Read the registry**: Load `verified-packages.json` from the project root. Build a set of all verified package names and vendor/bin binary names.

2. **Scan files for package references**: Search the following paths for package install patterns:
   - `skills/**/*.md`
   - `commands/*.md`
   - `rules/**/*.md`

   Patterns to match:
   - `composer require [--dev] {vendor}/{package}` — extract `{vendor}/{package}`
   - `vendor/bin/{binary}` — extract `{binary}`
   - `npx [-y] {package}` — extract `{package}`
   - `pip install {package}` — extract `{package}`

3. **Cross-reference each found package**:

   For Composer packages (`vendor/name`):
   - Look up in `verified-packages.json` → `packages[].name`
   - If found → **Verified**
   - If not found → check Packagist: `curl -s https://packagist.org/packages/{vendor}/{name}.json`
     - If package exists on Packagist but not in registry → **WARNING: Unverified** (suggest adding to registry)
     - If package does NOT exist on Packagist → **CRITICAL: Suspicious** (potential hallucination)

   For vendor/bin binaries:
   - Look up in `verified-packages.json` → `vendor_bin[].binary`
   - If found → **Verified**
   - If not found → **WARNING: Unknown binary**

4. **Generate report**:

```
## Package Audit Report

Date: {current date}
Files scanned: {count}
Unique packages found: {count}

### Verified ({count})
All packages below are in verified-packages.json:
- phpstan/phpstan (skills/php-static-analysis, skills/php-verification, ...)
- ...

### Unverified ({count})
Packages exist on Packagist but are not in the registry:
- WARNING: {vendor/package} — found in {file}, exists on Packagist ({downloads} downloads)
  → Action: verify and add to verified-packages.json

### Suspicious ({count})
Packages NOT found on Packagist — potential hallucinations:
- CRITICAL: {vendor/package} — found in {file}, DOES NOT EXIST on Packagist
  → Action: remove from skill file immediately

### Unknown Binaries ({count})
vendor/bin/ references not mapped in the registry:
- WARNING: vendor/bin/{binary} — found in {file}
  → Action: identify source package and add to vendor_bin in verified-packages.json
```

5. **Summary**: If any CRITICAL items are found, emphasize that these must be resolved before committing. If only warnings, recommend adding verified packages to the registry.
