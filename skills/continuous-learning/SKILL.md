---
name: continuous-learning
description: Automatically extract reusable PHP patterns from Claude Code sessions and evolve them into skills. Combines v1 session-end evaluation with v2 instinct-based hooks and confidence scoring.
origin: claude-code-php-toolkit
---

# Continuous Learning

Extract reusable PHP patterns from Claude Code sessions and evolve them into skills. This skill combines the v1 session-end evaluation approach with the v2 instinct-based architecture using hooks and confidence scoring.

## When to Activate

- Setting up automatic pattern extraction from Claude Code sessions
- Configuring hooks for session observation
- Reviewing or curating learned patterns
- Tuning confidence thresholds for learned behaviors
- Evolving instincts into full skills, commands, or agents

## Architecture Overview

```
Session Activity
      │
      │ Hooks capture tool use (100% reliable)
      ▼
┌─────────────────────────────────────────┐
│         observations.jsonl              │
│   (prompts, tool calls, outcomes)       │
└─────────────────────────────────────────┘
      │
      │ Observer agent reads (background, Haiku)
      ▼
┌─────────────────────────────────────────┐
│          PATTERN DETECTION              │
│   • User corrections → instinct         │
│   • Error resolutions → instinct        │
│   • Repeated workflows → instinct       │
└─────────────────────────────────────────┘
      │
      │ Creates/updates
      ▼
┌─────────────────────────────────────────┐
│         instincts/personal/             │
│   • prefer-constructor-injection.md     │
│   • always-add-return-types.md          │
│   • use-spatie-data-for-dtos.md         │
└─────────────────────────────────────────┘
      │
      │ /evolve clusters related instincts
      ▼
┌─────────────────────────────────────────┐
│              evolved/                   │
│   • skills/laravel-conventions.md       │
│   • commands/php-review-checklist.md    │
└─────────────────────────────────────────┘
```

## The Instinct Model

An instinct is a small learned behavior — the atomic unit of knowledge.

```yaml
---
id: prefer-constructor-injection
trigger: "when injecting dependencies in PHP classes"
confidence: 0.8
domain: "code-style"
source: "session-observation"
tags: ["php", "dependency-injection", "best-practice"]
---

# Prefer Constructor Injection

## Action
Use constructor injection with readonly promoted properties instead of setter injection or service locator.

## Evidence
- Observed 8 instances where constructor injection was preferred
- User corrected setter injection to constructor injection on 2026-02-15
- Consistent with PSR-11 and Symfony/Laravel DI best practices
```

### Instinct Properties

- **Atomic** — one trigger, one action
- **Confidence-weighted** — 0.3 = tentative, 0.9 = near certain
- **Domain-tagged** — code-style, testing, composer, debugging, framework, etc.
- **Evidence-backed** — tracks what observations created it

## PHP-Specific Pattern Types

| Pattern Type | Description | Example |
|-------------|-------------|---------|
| `composer_resolution` | Dependency conflict fixes, version constraints | "Use `--with-all-dependencies` for platform conflicts" |
| `phpstan_fixes` | Common PHPStan error resolutions at each level | "Level 8: use `@phpstan-ignore-next-line` for dynamic properties" |
| `framework_quirks` | Laravel/Symfony gotchas and workarounds | "Clear config cache after `.env` changes in Laravel" |
| `testing_patterns` | Test setup, mocking, assertion patterns | "Use `RefreshDatabase` trait only in feature tests" |
| `error_resolution` | How specific PHP errors were debugged and fixed | "Memory exhaustion: use generators for large dataset processing" |
| `user_corrections` | When user corrects Claude's PHP code | "User prefers `final class` by default" |
| `debugging_techniques` | Effective PHP debugging approaches | "Use `symfony/var-dumper` server mode for AJAX debugging" |
| `project_conventions` | Project-specific coding standards | "This project uses Action classes instead of services" |

## Confidence Scoring

Confidence evolves over time:

| Score | Meaning | Behavior |
|-------|---------|----------|
| 0.3 | Tentative | Suggested but not enforced |
| 0.5 | Moderate | Applied when relevant |
| 0.7 | Strong | Auto-approved for application |
| 0.9 | Near-certain | Core behavior, rarely questioned |

**Confidence increases** when:
- Pattern is repeatedly observed across sessions
- User doesn't correct the suggested behavior
- Similar instincts from other sources agree
- Pattern aligns with established PHP best practices (PSR, framework docs)

**Confidence decreases** when:
- User explicitly corrects the behavior
- Pattern isn't observed for extended periods (decay rate: 0.05/week)
- Contradicting evidence appears
- PHP/framework version changes invalidate the pattern

## Hook Setup

### v1: Session-End Evaluation (Stop Hook)

Lightweight — runs once when a session ends, reviews the full transcript.

```json
{
  "hooks": {
    "Stop": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/evaluate-session.php"
      }]
    }]
  }
}
```

### v2: Real-Time Observation (PreToolUse/PostToolUse Hooks)

Comprehensive — captures every tool call for pattern detection.

```json
{
  "hooks": {
    "PreToolUse": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/observe.php pre"
      }]
    }],
    "PostToolUse": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/observe.php post"
      }]
    }]
  }
}
```

### Why Hooks?

> v1 relied on skills to observe. Skills are probabilistic — they fire ~50-80% of the time based on Claude's judgment. Hooks fire **100% of the time**, deterministically.

## Directory Structure

```
~/.claude/learning/
├── config.json                # Thresholds, paths, observer settings
├── observations.jsonl         # Current session observations (v2)
├── observations.archive/      # Processed observations
├── instincts/
│   ├── personal/              # Auto-learned instincts
│   │   ├── prefer-constructor-injection.md
│   │   ├── always-add-return-types.md
│   │   └── use-spatie-data-for-dtos.md
│   └── inherited/             # Imported from others
└── evolved/
    ├── skills/                # Generated skills from instinct clusters
    └── commands/              # Generated commands
```

## Configuration

```json
{
  "version": "2.0",
  "observation": {
    "enabled": true,
    "store_path": "~/.claude/learning/observations.jsonl",
    "max_file_size_mb": 10,
    "archive_after_days": 7
  },
  "instincts": {
    "personal_path": "~/.claude/learning/instincts/personal/",
    "inherited_path": "~/.claude/learning/instincts/inherited/",
    "min_confidence": 0.3,
    "auto_approve_threshold": 0.7,
    "confidence_decay_rate": 0.05
  },
  "observer": {
    "enabled": true,
    "model": "haiku",
    "run_interval_minutes": 5,
    "patterns_to_detect": [
      "user_corrections",
      "error_resolutions",
      "repeated_workflows",
      "composer_resolution",
      "phpstan_fixes",
      "framework_quirks",
      "testing_patterns"
    ]
  },
  "evolution": {
    "cluster_threshold": 3,
    "evolved_path": "~/.claude/learning/evolved/"
  }
}
```

## v1 vs v2 Comparison

| Feature | v1 (Stop Hook) | v2 (Instinct Hooks) |
|---------|----------------|---------------------|
| Observation | Session end only | Every tool call (100% reliable) |
| Analysis | Main context | Background agent (Haiku) |
| Granularity | Full skills | Atomic instincts |
| Confidence | None | 0.3-0.9 weighted, with decay |
| Evolution | Direct to skill | Instincts -> cluster -> skill/command |
| Sharing | None | Export/import instincts |
| Overhead | Minimal | Low (Haiku background agent) |

**Recommendation:** Start with v1 (simpler), graduate to v2 when you want finer-grained learning.

## Evolution Path

When enough related instincts cluster together, they can evolve into higher-order artifacts:

1. **3+ related instincts** -> candidate for a new skill
2. **Repeated workflow instincts** -> candidate for a new command
3. **Domain-specific instinct cluster** -> candidate for a specialized agent

Example evolution:

```
Instincts:
  - always-run-phpstan-before-commit (0.9)
  - run-php-cs-fixer-after-edit (0.8)
  - check-composer-audit-weekly (0.7)

→ Evolved into: skills/php-quality-workflow.md
  (combines all three into a structured quality pipeline)
```

## Privacy Model

- Observations stay **local** on your machine
- Only **instincts** (abstracted patterns) can be exported
- No actual code or conversation content is shared
- You control what gets exported via `instinct-export`

## Checklist

- [ ] Decided on v1 (Stop hook) or v2 (PreToolUse/PostToolUse hooks)
- [ ] Hook configured in `~/.claude/settings.json`
- [ ] Directory structure created (`~/.claude/learning/`)
- [ ] Configuration file created with appropriate thresholds
- [ ] PHP-specific pattern types configured in observer settings
- [ ] Reviewed existing instincts for accuracy (monthly)
- [ ] Pruned low-confidence instincts that haven't been reinforced
- [ ] Exported and shared useful instincts with team (optional)
- [ ] Evolved high-confidence instinct clusters into skills
