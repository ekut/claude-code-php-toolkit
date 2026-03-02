---
name: skill-stocktake
description: Audit skills for quality using a structured checklist and AI judgment. Supports Quick Scan (changed skills only) and Full Stocktake modes with inventory, evaluation, summary, and consolidation phases.
origin: claude-code-php-toolkit
---

# Skill Stocktake

Audit all skills and commands for quality using a structured checklist combined with holistic AI judgment. Supports two modes: Quick Scan for recently changed skills, and Full Stocktake for a complete review.

## When to Activate

- Periodic quality review of the skill library (monthly recommended)
- After adding multiple new skills
- Before a major release or version bump
- When skill count exceeds 15-20 and overlap risk increases
- When investigating stale or underperforming skills
- After upgrading PHP versions or framework major versions (skills may reference outdated APIs)

## Scope

The stocktake targets the following paths:

| Path | Description |
|------|-------------|
| `skills/` | All skills in the toolkit repository |
| `~/.claude/skills/` | Global user skills (if present) |
| `{cwd}/.claude/skills/` | Project-level skills (if present) |

At the start of Phase 1, explicitly list which paths were found and scanned.

## Modes

| Mode | Trigger | Duration |
|------|---------|----------|
| Quick Scan | `results.json` exists (default) | 5-10 min |
| Full Stocktake | `results.json` absent, or explicit `full` request | 20-30 min |

**Results cache:** `skills/skill-stocktake/results.json`

## Quick Scan Flow

Re-evaluate only skills that have changed since the last run.

1. Read `skills/skill-stocktake/results.json`
2. Compare file modification times against stored `mtime` values
3. If no changes: report "No changes since last run" and stop
4. Re-evaluate only changed files using Phase 2 criteria
5. Carry forward unchanged skills from previous results
6. Output only the diff
7. Save updated results

## Full Stocktake Flow

### Phase 1 — Inventory

Enumerate all skill files, extract frontmatter metadata, and collect modification times.

```
Scanning:
  ✓ skills/                     (18 skills)
  ✗ ~/.claude/skills/           (not found)
  ✗ {cwd}/.claude/skills/      (not found)
```

| Skill | Files | Last Modified | Description |
|-------|-------|---------------|-------------|
| php-patterns | 1 | 2026-01-15 | Modern PHP 8.1+ idioms |
| laravel-patterns | 9 | 2026-03-02 | Laravel 10+/11+ architecture |
| ... | ... | ... | ... |

### Phase 2 — Quality Evaluation

Evaluate each skill against the quality checklist. Use holistic AI judgment — not a numeric rubric.

Each skill is evaluated against this checklist:

```
- [ ] Content overlap with other skills checked
- [ ] Overlap with CLAUDE.md / MEMORY.md checked
- [ ] Freshness of technical references verified
- [ ] Code examples use PHP 8.1+ syntax
- [ ] Frontmatter complete (name, description, origin)
- [ ] Actionable content (code examples, commands, or steps)
```

**Guiding dimensions:**

| Dimension | What to Check |
|-----------|--------------|
| Actionability | Code examples, commands, or steps that let you act immediately |
| Scope fit | Name, trigger, and content are aligned; not too broad or narrow |
| Uniqueness | Value not replaceable by CLAUDE.md, another skill, or a rule |
| Currency | Technical references work in the current PHP/framework environment |
| PHP version | Code examples use 8.1+ syntax (enums, readonly, match, fibers) |

**Per-skill output:**

```json
{
  "verdict": "Keep",
  "reason": "Concrete, actionable, unique value for X workflow"
}
```

### Verdict Types

| Verdict | Meaning | When to Use |
|---------|---------|-------------|
| **Keep** | Useful and current | Skill provides unique, actionable, up-to-date content |
| **Improve** | Worth keeping, specific changes needed | Good foundation but has identifiable gaps or bloat |
| **Update** | Referenced technology is outdated | API calls, CLI flags, or version-specific advice is stale |
| **Retire** | Low quality, stale, or redundant | Content fully covered elsewhere or no longer relevant |
| **Merge into [X]** | Substantial overlap with another skill | Name the merge target and what content to integrate |

### Reason Quality Requirements

The `reason` field must be self-contained and decision-enabling:

**For Keep:**
> Good: "Unique PHPStan configuration guide with level-by-level migration path. No overlap with other skills. PHP 8.3 examples current."
> Bad: "Unchanged"

**For Improve:**
> Good: "276 lines; Section 'Framework Comparison' (L80-140) duplicates php-architecture-ddd; delete it to reach ~150 lines."
> Bad: "Too long"

**For Update:**
> Good: "References PHPUnit 9 `@dataProvider` annotation; should use PHPUnit 10+ `#[DataProvider]` attribute."
> Bad: "Outdated"

**For Retire:**
> Good: "Content fully covered by php-verification Phase 5 (security scan). No unique value remains."
> Bad: "Superseded"

**For Merge:**
> Good: "42-line thin content; Section 'Cache Invalidation' already covered in laravel-patterns/caching.md. Integrate the 'tagged cache patterns' tip as a note there."
> Bad: "Overlaps with X"

### Phase 3 — Summary Table

| Skill | Files | Verdict | Reason |
|-------|-------|---------|--------|
| php-patterns | 1 | Keep | Unique PHP 8.1+ idioms reference... |
| laravel-patterns | 9 | Keep | Comprehensive Laravel coverage... |
| ... | ... | ... | ... |

Statistics:
```
Keep:    X skills
Improve: X skills
Update:  X skills
Retire:  X skills
Merge:   X skills
```

### Phase 4 — Consolidation

1. **Retire / Merge**: present detailed justification per file before confirming with user:
   - What specific problem was found (overlap, staleness, broken references)
   - What alternative covers the same functionality
   - Impact of removal (any dependent skills, references, or workflows affected)

2. **Improve**: present specific improvement suggestions with rationale:
   - What to change and why (e.g., "trim Section X because it duplicates skill Y")
   - User decides whether to act

3. **Update**: present updated content with verified sources:
   - Use Context7 to verify current API/CLI syntax
   - Show before/after for changed sections

4. **CLAUDE.md check**: review CLAUDE.md for stale skill references

## PHP-Specific Quality Checks

In addition to the general checklist, PHP skills should pass these checks:

| Check | What to Verify |
|-------|---------------|
| PHP version | Examples use PHP 8.1+ syntax (not 7.x patterns) |
| Composer commands | `composer` CLI syntax matches Composer 2.x |
| PHPUnit syntax | Uses PHPUnit 10+ attributes, not annotations |
| Framework versions | Laravel 10+/11+, Symfony 6.4+/7.x references |
| PSR compliance | References current PSR standards (PSR-12, PER-CS 2.0) |
| Tool versions | PHPStan, Psalm, PHP-CS-Fixer CLI flags are current |
| Package references | All `composer require` packages verified in `verified-packages.json` |

## Results File Schema

`skills/skill-stocktake/results.json`:

```json
{
  "evaluated_at": "2026-03-02T14:30:00Z",
  "mode": "full",
  "batch_progress": {
    "total": 18,
    "evaluated": 18,
    "status": "completed"
  },
  "skills": {
    "php-patterns": {
      "path": "skills/php-patterns/SKILL.md",
      "verdict": "Keep",
      "reason": "Unique PHP 8.1+ idioms reference with enums, readonly, match, fibers. No overlap.",
      "mtime": "2026-01-15T08:30:00Z"
    },
    "laravel-patterns": {
      "path": "skills/laravel-patterns/SKILL.md",
      "verdict": "Keep",
      "reason": "Comprehensive Laravel 10+/11+ coverage across 8 modules. Active framework, current examples.",
      "mtime": "2026-03-02T10:00:00Z"
    }
  }
}
```

**`evaluated_at`**: Must be set to actual UTC time of evaluation completion. Obtain via: `date -u +%Y-%m-%dT%H:%M:%SZ`.

## Resume Detection

If `results.json` contains `"status": "in_progress"`, resume from the first unevaluated skill instead of restarting. This handles interrupted evaluations gracefully.

## Notes

- Evaluation is blind: the same checklist applies to all skills regardless of origin
- Archive/delete operations always require explicit user confirmation
- No verdict branching by skill origin — ECC-adapted, custom, and auto-extracted skills are treated equally
- Run a Full Stocktake after adding 3+ skills to catch overlap early

## Checklist

- [ ] Chose mode: Quick Scan (changed only) or Full Stocktake (all)
- [ ] Phase 1 inventory completed — all skill paths scanned
- [ ] Phase 2 evaluation completed — every skill has a verdict and reason
- [ ] Reasons are self-contained and decision-enabling (no "unchanged" or "outdated")
- [ ] Phase 3 summary table generated with statistics
- [ ] Phase 4 consolidation actions presented to user
- [ ] Retire/Merge actions confirmed by user before execution
- [ ] Results saved to `results.json` with accurate `evaluated_at` timestamp
- [ ] PHP-specific quality checks applied (version, syntax, tool compatibility)
- [ ] CLAUDE.md reviewed for stale skill references
