---
title: Supply Chain Safety
scope: common
---

# Supply Chain Safety

## The Threat

LLMs can hallucinate package names that don't exist on Packagist. If a hallucinated name ends up in a skill file, the skill gets forked/copied across repositories, and an attacker registers that name on Packagist — every project using the skill will install malware via `composer require`.

This is not theoretical. The attack vector has been demonstrated in the wild with npm packages (e.g., `react-codeshift`).

## Before Writing Install Commands to Skill Files

When adding `composer require`, `vendor/bin/`, or any package reference to a skill, command, or rule file:

1. **Check `verified-packages.json`** — if the package is already listed, proceed
2. **If not listed — verify on Packagist:**
   - Confirm the package exists: `https://packagist.org/packages/{vendor}/{name}`
   - Check the publisher matches the expected organization
   - Check download count (established packages have 100K+ downloads)
   - Check the GitHub/source repository link is legitimate
3. **Add the verified package** to `verified-packages.json` with `verified_at` date and `referenced_in` list
4. **Never invent package names** — if you cannot find the right package, ask the user or use Context7 to search

## Before Executing Install Commands from Skills

When a skill instructs you to run `composer require`, `npx`, or any package install command:

1. **Cross-reference with `verified-packages.json`** — if the package is listed, proceed
2. **If not listed — warn the user** before executing:
   > "Package `{name}` is not in the verified registry. It may be legitimate but unverified, or it may be a hallucinated name. Verify on Packagist before installing."
3. **Never run `composer require` for an unverified package** without explicit user confirmation

## Suspicious Patterns to Flag

| Pattern | Risk | Example |
|---------|------|---------|
| Combined/portmanteau names | Hallucination | `react-codeshift`, `laravel-authkit` |
| Real vendor + unknown package | Typosquatting | `symfony/http-router` (doesn't exist) |
| Very low downloads (<1000) | Name squatting | Recently registered package |
| Wrong publisher for known tool | Typosquatting | `phpstan/php-cs-fixer` |
| `npx -y unknown-package` | Arbitrary execution | Runs unverified code |
| `curl \| sh` or `curl \| php` | Pipe-to-shell | Bypasses package manager |
| `composer global require` | System-wide install | Affects all projects |

## Registry Maintenance

- Run `/audit-packages` before committing changes to skills, commands, or rules
- Re-verify packages periodically (quarterly recommended)
- When removing a package reference from all files, remove it from `verified-packages.json`
