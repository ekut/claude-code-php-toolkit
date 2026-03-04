---
description: Extract reusable PHP patterns from the current session and save as instincts with self-evaluation
---

# PHP Learn

Extract reusable PHP patterns from the current session, evaluate their quality, and save them as instincts to `~/.claude/learning/instincts/personal/`.

> **Foundation:** See `skills/continuous-learning/SKILL.md` for the instinct model, confidence scoring, and directory structure.

## Step 1: Review Session Activity

Scan the current session for learnable moments:

- **User corrections** — when the user corrected your code or approach
- **Error resolutions** — how specific PHP errors were debugged and fixed
- **Repeated workflows** — patterns that appeared multiple times
- **Composer resolutions** — dependency conflict fixes
- **PHPStan fixes** — common static analysis error resolutions
- **Framework quirks** — Laravel/Symfony gotchas and workarounds
- **Project conventions** — project-specific coding standards discovered

## Step 2: Draft Instincts

For each pattern, draft an instinct file:

```yaml
---
id: descriptive-kebab-case-id
trigger: "when [specific situation]"
confidence: 0.5
domain: "code-style|testing|composer|debugging|framework|security"
source: "session-observation"
tags: ["php", "relevant-tag"]
---

# [Title]

## Action
[What to do when the trigger fires]

## Evidence
- [What was observed in this session]
```

## Step 3: Check for Duplicates

Before saving, check `~/.claude/learning/instincts/personal/` for existing instincts with similar triggers. If found:

- **Same pattern** — increase confidence by 0.1, add new evidence
- **Conflicting pattern** — flag for user review, do not overwrite

## Step 4: Self-Evaluation

Rate each extracted instinct on 5 dimensions (all must score >= 3/5):

| Dimension | Score | Criteria |
|-----------|-------|----------|
| **Specificity** | /5 | Trigger is precise, not vague |
| **Actionability** | /5 | Action is concrete, not generic advice |
| **Evidence** | /5 | Based on observed behavior, not assumption |
| **PHP-relevance** | /5 | Specific to PHP ecosystem, not general programming |
| **Reusability** | /5 | Applies across sessions, not one-off |

Discard instincts scoring below 3 on any dimension.

## Step 5: Save

Write approved instincts to `~/.claude/learning/instincts/personal/{id}.md`.

Create the directory if it doesn't exist:
```bash
mkdir -p ~/.claude/learning/instincts/personal
```

## Output

```
LEARNING REPORT
===============
Patterns detected:  X
Instincts drafted:  X
Passed evaluation:  X
Duplicates merged:  X
Discarded:          X

Saved instincts:
- {id} (confidence: 0.X, domain: {domain})
- ...
```
