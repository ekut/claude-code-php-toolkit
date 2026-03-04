---
description: Create, verify, and list workflow checkpoints for tracking progress during long sessions
---

# PHP Checkpoint

Create or manage workflow checkpoints during long coding sessions. Checkpoints capture the current state so you can resume safely after `/compact` or across sessions.

> **Foundation:** See `skills/strategic-compact/SKILL.md` for phase transition decisions and context preservation.

## Actions

### Create a Checkpoint

Save the current session state:

1. **Capture context** — gather current working state:
   - Current branch and uncommitted changes (`git status`, `git diff --stat`)
   - Files being actively modified
   - Current task/feature being worked on
   - Key decisions made during this session
   - Known blockers or next steps

2. **Write checkpoint** to CLAUDE.md (or project notes):

```markdown
## Checkpoint: [YYYY-MM-DD HH:MM] — [brief description]

- **Working on:** [feature/task description]
- **Branch:** [current branch]
- **Key files:** [list of files being modified]
- **Decisions:** [architectural choices made]
- **Next steps:** [what to do next]
- **Blockers:** [any known issues]
- **Uncommitted changes:** [yes/no, summary]
```

3. **Commit work** if there are meaningful uncommitted changes — checkpoint is safest with committed code.

### Verify a Checkpoint

Confirm the current state matches an existing checkpoint:

- Compare current branch with checkpoint branch
- Check if listed files still exist and are being modified
- Verify next steps are still relevant
- Report any drift from the checkpoint state

### List Checkpoints

Scan CLAUDE.md for all `## Checkpoint:` sections and display them:

```
CHECKPOINTS
===========
1. [2026-03-04 14:30] — API endpoint implementation (branch: feat/orders-api)
2. [2026-03-04 16:00] — Test coverage for OrderService (branch: feat/orders-api)

Active: #2 (most recent)
```

## Notes

- Checkpoints are stored in CLAUDE.md so they survive `/compact`
- Remove checkpoint sections from CLAUDE.md when the work is complete
- Create a checkpoint before running `/compact` to preserve context
- Checkpoints complement git commits — they capture intent, not just code state
