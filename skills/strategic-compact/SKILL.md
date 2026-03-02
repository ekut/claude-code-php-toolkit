---
name: strategic-compact
description: Decision guide for timing /compact in Claude Code PHP sessions — phase transitions, survival map, context preservation best practices.
origin: claude-code-php-toolkit
---

# Strategic Compact

Use `/compact` at natural workflow breakpoints — not when forced by context limits, but proactively when transitioning between distinct phases of work. This preserves the most useful context and drops the noise.

## When to Activate

- Context window approaching limits during a long session
- Transitioning between distinct work phases (research → implementation, debugging → new feature)
- Before starting a fresh logical unit of work
- Session feels sluggish or responses seem to lose earlier context

---

## 1. Decision Guide — PHP Phase Transitions

Not every moment is a good time to compact. The best moments are **phase boundaries** — when you're done with one type of work and about to start another.

### High-Value Compact Points

| Transition | Why Compact Here |
|-----------|-----------------|
| Composer research → Architecture design | Research context (Packagist results, docs) is no longer needed |
| PHPStan fix session → New feature | Error-fix context is resolved; new feature needs fresh space |
| Doctrine debugging → Next entity/feature | Schema investigation details can be dropped |
| Test writing → Refactoring | Test results are committed; refactoring needs different context |
| Code review → Applying fixes | Review discussion is captured in notes; focus shifts to implementation |
| Migration writing → Seeder/fixture | Migration SQL is done; seeder needs different schema awareness |
| API endpoint → Next endpoint | Previous endpoint is complete; new one has different concerns |

### Low-Value Compact Points (Avoid)

| Moment | Why Not |
|--------|---------|
| Mid-debugging (haven't found root cause) | You'll lose the diagnostic chain and start over |
| Between related migrations | Foreign key dependencies need earlier migration context |
| During multi-file refactoring | Cross-file relationships are critical context |
| Right after reading a complex codebase | The understanding you built is the most valuable context |

---

## 2. Survival Map — What Persists vs. What's Lost

Understanding what survives `/compact` helps you decide when it's safe.

| Survives | Lost |
|----------|------|
| CLAUDE.md instructions | Detailed conversation history |
| File contents you explicitly re-read | Earlier file reads and search results |
| Task list (TodoWrite) | Reasoning chains and decision rationale |
| Working directory and git state | Intermediate debugging output |
| System prompt and skill content | Exploratory dead ends (useful to avoid repeating) |
| Auto-memory (`MEMORY.md`) | Verbal agreements ("you said you'd...") |

**Key insight:** Anything you want preserved across a compact should be **written to a file** — CLAUDE.md project notes, ADR documents, or inline code comments.

---

## 3. Pre-Compact Checklist

Before running `/compact`, make sure critical context is saved:

- [ ] **Commit or stash** current work — don't lose uncommitted changes in the confusion
- [ ] **Update CLAUDE.md** if you discovered project conventions during the session
- [ ] **Write an ADR** if you made an architectural decision that needs rationale preserved
- [ ] **Add TODO comments** in code for incomplete work: `// TODO: finish validation for edge case X`
- [ ] **Update task list** — mark completed tasks, note blockers on in-progress tasks
- [ ] **Save key findings** to auto-memory if they're cross-session relevant

### Quick Context Dump Template

If pressed for time, add a section to your project's CLAUDE.md:

```markdown
## Current Session Context (compact checkpoint)

- Working on: [brief description]
- Key files: [list of files being modified]
- Decisions made: [any architectural choices]
- Next steps: [what to do after compact]
- Blockers: [any known issues]
```

Remove this section once the work is complete.

---

## 4. Hook Configuration (Optional)

You can set up a pre-compact hook to remind yourself to save context. This is **not** added to the project's `hooks.json` — it's a personal workflow aid.

**User-level setup in `~/.claude/settings.json`:**

```json
{
  "hooks": {
    "preCompact": [
      {
        "type": "command",
        "command": "echo '⚠️  Pre-compact reminder: Save any important context to CLAUDE.md or auto-memory before proceeding.'"
      }
    ]
  }
}
```

This prints a reminder before every `/compact`, giving you a chance to save context.

---

## 5. Best Practices

### Do

- Compact at **phase transitions**, not arbitrary moments
- Save decisions and discoveries to **durable files** before compacting
- Use `/compact` **proactively** when you notice the session shifting focus — don't wait for degraded responses
- Keep CLAUDE.md's "Current Session Context" section updated during long sessions
- Trust that committed code and test results persist — they're on disk

### Don't

- Don't compact mid-investigation — you'll repeat the same exploration
- Don't rely on the AI "remembering" verbal agreements after compact — write them down
- Don't compact just because the session is long — length alone isn't the problem; **irrelevant context** is
- Don't add compact hooks to shared project `hooks.json` — this is personal workflow preference
- Don't panic-compact — if responses are degrading, it's better to save context first, then compact
