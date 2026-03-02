---
name: search-first
description: Research-before-coding workflow for PHP. Search Packagist, Spatie, League, framework ecosystems, and existing packages before writing custom code. Includes decision matrix and evaluation criteria.
origin: claude-code-php-toolkit
---

# Search-First Workflow

Systematizes the "search for existing solutions before implementing" workflow, adapted for the PHP ecosystem.

## When to Activate

- Starting a new feature that likely has existing solutions
- Adding a dependency or integration
- The user asks "add X functionality" and you're about to write code
- Before creating a new utility, helper, or abstraction
- When evaluating build-vs-buy decisions for PHP components

## Workflow

```
┌─────────────────────────────────────────────┐
│  1. NEED ANALYSIS                           │
│     Define what functionality is needed      │
│     Identify PHP version & framework         │
├─────────────────────────────────────────────┤
│  2. PARALLEL SEARCH                         │
│     ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│     │Packagist │ │  Spatie  │ │ Context7  │  │
│     │  / PHP   │ │ / League │ │ / GitHub  │  │
│     └──────────┘ └──────────┘ └──────────┘  │
├─────────────────────────────────────────────┤
│  3. EVALUATE                                │
│     Score candidates (functionality, maint, │
│     community, docs, license, PHP version)  │
├─────────────────────────────────────────────┤
│  4. DECIDE                                  │
│     ┌────────┐ ┌────────┐ ┌────────┐        │
│     │ Adopt  │ │ Extend │ │ Build  │        │
│     │ as-is  │ │ / Wrap │ │ Custom │        │
│     └────────┘ └────────┘ └────────┘        │
├─────────────────────────────────────────────┤
│  5. IMPLEMENT                               │
│     composer require / Configure /           │
│     Write minimal custom code               │
└─────────────────────────────────────────────┘
```

## Decision Matrix

| Signal | Action |
|--------|--------|
| Exact match, well-maintained, MIT/Apache | **Adopt** — `composer require` and use directly |
| Partial match, good foundation | **Extend** — install + write thin wrapper or adapter |
| Multiple weak matches | **Compose** — combine 2-3 small packages |
| Nothing suitable found | **Build** — write custom, but informed by research |

### Decision Factors

| Factor | Weight | Notes |
|--------|--------|-------|
| Functionality match | High | Does it solve 80%+ of the requirement? |
| PHP version support | High | Must support project's minimum PHP version |
| Maintenance activity | High | Commits in last 6 months, open issues triaged |
| Downloads / adoption | Medium | Packagist monthly downloads as popularity signal |
| License compatibility | High | MIT, Apache-2.0, BSD preferred; avoid GPL for libraries |
| Dependency count | Medium | Fewer transitive dependencies = less risk |
| Framework agnostic | Medium | Prefer framework-agnostic unless framework-specific needed |
| Documentation quality | Medium | README, examples, API docs |

## PHP Search Shortcuts

### Packagist / Composer

The primary source for PHP packages.

```bash
# Search Packagist directly
composer search "pdf generation"
composer search "image manipulation"

# Check package details
composer show spatie/laravel-medialibrary --all
```

Or search on [packagist.org](https://packagist.org/) for browsing.

### Trusted PHP Ecosystems

Check these high-quality sources before general search:

| Ecosystem | Focus | URL |
|-----------|-------|-----|
| **Spatie** | Laravel packages, PHP utilities | [spatie.be/open-source](https://spatie.be/open-source) |
| **The PHP League** | Framework-agnostic packages | [thephpleague.com](https://thephpleague.com/) |
| **Symfony Components** | Standalone components, usable anywhere | [symfony.com/components](https://symfony.com/components) |
| **Laravel** | Framework-specific packages | [laravel.com/docs](https://laravel.com/docs) |
| **Laminas** | Enterprise PHP components (ex-Zend) | [getlaminas.org](https://getlaminas.org/) |

### Common Problem → Package Mapping

| Problem | First Check | Package |
|---------|-------------|---------|
| PDF generation | League | `dompdf/dompdf`, `tecnickcom/tcpdf` |
| Image manipulation | League | `intervention/image` |
| Excel/CSV import/export | Spatie | `maatwebsite/excel`, `openspout/openspout` |
| HTTP client | Symfony | `symfony/http-client`, `guzzlehttp/guzzle` |
| Validation (standalone) | Symfony/Respect | `respect/validation`, `symfony/validator` |
| UUID generation | Symfony | `symfony/uid`, `ramsey/uuid` |
| Date/time handling | League | `nesbot/carbon`, `brick/date-time` |
| Markdown processing | League | `league/commonmark` |
| Filesystem abstraction | League | `league/flysystem` |
| Collection utilities | Laravel | `illuminate/collections` (standalone) |
| Logging | PSR-3 | `monolog/monolog` |
| Caching | PSR-6/16 | `symfony/cache`, `phpfastcache/phpfastcache` |
| Queue/messaging | Symfony | `symfony/messenger`, `php-enqueue/enqueue` |
| Permission management | Spatie | `spatie/laravel-permission` |
| Settings / config | Spatie | `spatie/laravel-settings` |
| Media / file uploads | Spatie | `spatie/laravel-medialibrary` |
| Data transfer objects | Spatie | `spatie/laravel-data` |
| Activity logging | Spatie | `spatie/laravel-activitylog` |
| Query building (API) | Spatie | `spatie/laravel-query-builder` |
| Slugs | Spatie | `spatie/laravel-sluggable` |

### Context7 (Up-to-Date Docs)

Always use Context7 MCP to get current documentation for shortlisted packages:

```
1. mcp context7 resolve-library-id → get library ID
2. mcp context7 query-docs → fetch up-to-date usage examples
```

This avoids relying on potentially outdated training data for package APIs.

## Evaluation Template

When comparing candidates, use this structure:

```
Package: vendor/package-name
Version: X.Y.Z
PHP:     >=8.1
License: MIT
Downloads: X/month
Last commit: YYYY-MM-DD
Dependencies: X direct

✅ Matches: [what it covers]
❌ Gaps: [what's missing]
⚠️ Risks: [maintenance, breaking changes, heavy deps]

Score: X/10
Recommendation: Adopt / Extend / Skip
```

## Integration Points

### With planner agent

The planner should run search-first before implementation planning:

1. Researcher identifies available packages
2. Planner incorporates them into the implementation plan
3. Avoids reinventing the wheel in the plan

### With architect agent

The architect should consult search-first for:

- Technology stack decisions
- Integration pattern discovery
- Package evaluation for architectural components

## Examples

### Example 1: "Add PDF invoice generation"

```
Need: Generate PDF invoices from order data
Framework: Laravel 11, PHP 8.3
Constraint: Must support tables, images, custom fonts

Search: Packagist "pdf generation"
Candidates:
  - dompdf/dompdf (210M downloads, MIT, HTML→PDF)
  - barryvdh/laravel-dompdf (Laravel wrapper, 98M downloads)
  - tecnickcom/tcpdf (low-level, 45M downloads)
  - mpdf/mpdf (HTML→PDF, 35M downloads)

Evaluation: barryvdh/laravel-dompdf scores highest
  - Laravel integration out of the box
  - HTML/Blade templates for layout (team already knows Blade)
  - Active maintenance, huge adoption

Decision: ADOPT — composer require barryvdh/laravel-dompdf
Result: Zero custom PDF logic, use Blade templates for layout
```

### Example 2: "Add role-based access control"

```
Need: Users have roles and permissions, gate checks in controllers
Framework: Laravel 11, PHP 8.3

Search: Spatie ecosystem first
Found: spatie/laravel-permission (130M downloads, MIT)
  - Roles, permissions, middleware, Blade directives
  - Database-backed, cacheable
  - Exact match for the requirement

Decision: ADOPT — composer require spatie/laravel-permission
Result: Full RBAC with zero custom code
```

### Example 3: "Add CSV import with validation"

```
Need: Import large CSV files with row-level validation, error reporting
Framework: Symfony 7, PHP 8.2

Search: Packagist "csv import validation"
Candidates:
  - openspout/openspout (fast, low-memory, MIT)
  - league/csv (PSR-compliant, framework-agnostic)
  - maatwebsite/excel (Laravel-specific, too heavy for Symfony)

Evaluation: league/csv scores highest for Symfony
  - Framework-agnostic
  - Stream-based (handles large files)
  - No validation built-in

Decision: EXTEND — composer require league/csv + custom validation layer
Result: 1 package + ~50 lines of validation logic
```

## Anti-Patterns

- **Jumping to code** — writing a utility without checking if one exists on Packagist
- **NIH syndrome** — refusing to use packages because "we can build it better"
- **Ignoring ecosystem packages** — not checking Spatie/League/Symfony components first
- **Dependency bloat** — installing a massive package for one small feature (check if a lighter alternative exists)
- **Over-wrapping** — wrapping a library so heavily it loses its benefits and hinders upgrades
- **Outdated assumptions** — assuming a package doesn't exist because it didn't 2 years ago (PHP ecosystem moves fast)

## Checklist

- [ ] Defined the specific functionality needed
- [ ] Searched Packagist for existing packages
- [ ] Checked Spatie, League, and Symfony components
- [ ] Used Context7 for up-to-date docs on shortlisted packages
- [ ] Evaluated candidates using the scoring template
- [ ] Verified license compatibility (MIT/Apache/BSD preferred)
- [ ] Confirmed PHP version support
- [ ] Checked maintenance activity (last commit, open issues)
- [ ] Made an explicit Adopt/Extend/Compose/Build decision
- [ ] Documented the decision rationale for the team
