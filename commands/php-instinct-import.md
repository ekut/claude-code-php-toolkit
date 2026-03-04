---
description: Import instincts from files or URLs with duplicate detection and conflict resolution
---

# PHP Instinct Import

Import instincts from external files or URLs into `~/.claude/learning/instincts/inherited/`.

> **Foundation:** See `skills/continuous-learning/SKILL.md` for the instinct format and directory structure.

## Step 1: Read Source

Accept instincts from:
- **Local file path** — single `.md` file or directory of `.md` files
- **URL** — fetch and parse markdown content
- **Inline YAML** — pasted directly in the conversation

Validate each instinct has required frontmatter: `id`, `trigger`, `confidence`, `domain`.

## Step 2: Detect Duplicates

For each instinct to import, check both directories:
- `~/.claude/learning/instincts/personal/`
- `~/.claude/learning/instincts/inherited/`

Match by `id` field. If a duplicate is found:

| Scenario | Action |
|----------|--------|
| Same id, same trigger | Skip — already exists |
| Same id, different trigger | Conflict — ask user to rename or overwrite |
| Different id, similar trigger | Warn — possible duplicate, let user decide |

## Step 3: Validate

Check each instinct passes minimum quality:
- Has `id`, `trigger`, `confidence`, `domain` in frontmatter
- Confidence is between 0.1 and 1.0
- Has a non-empty `## Action` section
- Domain is a recognized value

## Step 4: Import

```bash
mkdir -p ~/.claude/learning/instincts/inherited
```

Write each approved instinct to `~/.claude/learning/instincts/inherited/{id}.md`.

Add `imported_from` and `imported_at` to frontmatter for provenance tracking.

## Output

```
IMPORT REPORT
=============
Source:     [file/URL/inline]
Parsed:     X instincts
Imported:   X
Skipped:    X (duplicates)
Conflicts:  X (resolved by user)
Invalid:    X (missing required fields)
```
