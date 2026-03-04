---
description: Create a phased PHP implementation plan before coding — read-only analysis with file-level changes, risks, and testing strategy
---

# PHP Plan

Create a phased implementation plan before writing any code. This command is **read-only** — it produces a plan but does not modify files.

> **Foundation:** Delegates to `agents/php-planner.md` for the full planning process.

## Step 1: Clarify the Requirement

Ask the user what feature or change to plan. Gather:

- What should it do? (inputs, outputs, side effects)
- Which modules or layers does it affect?
- Are there constraints? (PHP version, framework, deadline)

## Step 2: Explore the Codebase

- Read `composer.json` for PHP version, dependencies, autoload config
- Map `src/` layout and namespace hierarchy
- Identify the framework and its conventions
- Locate existing tests and CI configuration

## Step 3: Break into Phases

Split into sequential phases, each independently testable and deployable.

For each phase, provide:

| Action | File | Description |
|--------|------|-------------|
| CREATE | `src/Domain/Entity.php` | New class |
| MODIFY | `src/Service/Handler.php` | Add method |
| DELETE | `src/Legacy/Old.php` | Remove deprecated |

## Step 4: Assess Risks

Flag PHP-specific risks per phase:

- Composer conflicts with existing dependencies
- Database migration ordering and reversibility
- Backward compatibility for public APIs
- Cache invalidation requirements
- Queue compatibility with serialized payloads

## Step 5: Define Testing Strategy

| Level | Scope | Tools |
|-------|-------|-------|
| Unit | Classes, value objects | PHPUnit, Pest |
| Integration | Database, external services | PHPUnit + test DB |
| Functional | HTTP request/response | WebTestCase, HTTP tests |

## Step 6: Output the Plan

```
## Implementation Plan: [Feature Name]

### Overview
[1-2 sentence summary]

### Phase 1: [Name]
**Goal:** [Outcome]
[File table, risks, testing, deploy notes]

### Phase 2: [Name]
...

### Summary
[Phase count, file counts, risk levels]

### Open Questions
[Decisions needing user input]
```
