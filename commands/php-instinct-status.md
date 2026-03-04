---
description: Display all learned instincts grouped by domain with confidence scores and evidence summaries
---

# PHP Instinct Status

Show all learned instincts organized by domain, with confidence scores and key metadata.

> **Foundation:** See `skills/continuous-learning/SKILL.md` for the instinct model and confidence scoring.

## Step 1: Load Instincts

Read all instinct files from:
- `~/.claude/learning/instincts/personal/` — auto-learned
- `~/.claude/learning/instincts/inherited/` — imported from others

If the directories don't exist, report "No instincts found" and suggest running `/php-learn` first.

## Step 2: Parse and Group

Parse each instinct's YAML frontmatter and group by `domain`:

Domains: `code-style`, `testing`, `composer`, `debugging`, `framework`, `security`, `architecture`, `performance`

## Step 3: Display

For each domain, show instincts sorted by confidence (highest first):

```
## Instinct Status

### code-style (X instincts)
| Instinct | Confidence | Source | Tags |
|----------|------------|--------|------|
| prefer-constructor-injection | 0.9 | session | php, di |
| always-add-return-types | 0.8 | session | php, types |

### testing (X instincts)
| Instinct | Confidence | Source | Tags |
|----------|------------|--------|------|
| use-data-providers | 0.7 | session | phpunit, testing |

### Summary
Total instincts: X (personal: Y, inherited: Z)
Average confidence: 0.X
Domains covered: X
High confidence (>= 0.7): X
Low confidence (<= 0.3): X — consider pruning
```

## Step 4: Recommendations

- Flag instincts with confidence <= 0.3 as candidates for pruning
- Flag clusters of 3+ same-domain instincts as candidates for `/php-evolve`
- Note any domains with zero instincts as learning gaps
