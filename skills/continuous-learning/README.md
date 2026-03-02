# Continuous Learning Skill

Automatically captures tool use observations from Claude Code sessions and signals for pattern extraction at session end. Over time, this builds a personal knowledge base of PHP patterns, project conventions, and debugging techniques.

## Requirements

- PHP 8.1+
- Claude Code v2.1+ (hooks support)
- No external dependencies (no Composer packages, no Python, no jq)

## How It Works

The skill consists of two independent hooks:

| Hook                   | Script                 | Fires                            | Purpose                                                |
|------------------------|------------------------|----------------------------------|--------------------------------------------------------|
| **PostToolUse**        | `observe.php post`     | After every successful tool call | Records what tool was used with what input/output      |
| **PostToolUseFailure** | `observe.php failure`  | After every failed tool call     | Records failures for error pattern detection           |
| **Stop**               | `evaluate-session.php` | Once at session end              | Signals Claude to extract patterns from the transcript |

Both hooks are **non-blocking** — they always exit with code 0 and never interfere with Claude Code operation.

## Installation

Add hooks to your `~/.claude/settings.json`. The path format depends on how you installed the toolkit.

### Plugin install (recommended)

If you installed via `claude plugin add`:

```json
{
  "hooks": {
    "Stop": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php \"${CLAUDE_PLUGIN_ROOT}/skills/continuous-learning/evaluate-session.php\""
      }]
    }],
    "PostToolUse": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php \"${CLAUDE_PLUGIN_ROOT}/skills/continuous-learning/observe.php\" post"
      }]
    }],
    "PostToolUseFailure": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php \"${CLAUDE_PLUGIN_ROOT}/skills/continuous-learning/observe.php\" failure"
      }]
    }]
  }
}
```

### Manual install

If you copied files to `~/.claude/skills/`:

```json
{
  "hooks": {
    "Stop": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/evaluate-session.php"
      }]
    }],
    "PostToolUse": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/observe.php post"
      }]
    }],
    "PostToolUseFailure": [{
      "matcher": "*",
      "hooks": [{
        "type": "command",
        "command": "php ~/.claude/skills/continuous-learning/observe.php failure"
      }]
    }]
  }
}
```

### Partial install

You can install only the parts you need:

- **Only observation** (v2) — add just the `PostToolUse` and `PostToolUseFailure` entries
- **Only session evaluation** (v1) — add just the `Stop` entry
- **Both** (recommended) — add all three entries

## Where Data Is Stored

All data is written to `~/.claude/learning/` — personal, cross-project, and local to your machine.

```
~/.claude/learning/
├── observations.jsonl              # Tool use log (written by observe.php)
├── observations.archive/           # Rotated log files (auto-created at 10 MB)
│   └── observations-20260302-143000.jsonl
├── config.json                     # Your config overrides (optional)
├── disabled                        # Touch this file to disable hooks
└── skills/
    └── learned/                    # Extracted patterns (created by evaluate-session.php)
```

Scripts and config ship with the skill (`skills/continuous-learning/`), but runtime data is always written to `~/.claude/learning/`.

## Configuration

Default config is in `config.json` alongside the scripts. To override, create `~/.claude/learning/config.json` with only the keys you want to change — they merge recursively.

### Available options

| Key                            | Default                                 | Description                                 |
|--------------------------------|-----------------------------------------|---------------------------------------------|
| `observation.enabled`          | `true`                                  | Enable/disable observation recording        |
| `observation.store_path`       | `~/.claude/learning/observations.jsonl` | Where to write JSONL observations           |
| `observation.max_file_size_mb` | `10`                                    | Rotate file when it reaches this size       |
| `evaluate.enabled`             | `true`                                  | Enable/disable session evaluation           |
| `evaluate.min_session_length`  | `10`                                    | Minimum user messages to trigger evaluation |
| `evaluate.learned_skills_path` | `~/.claude/learning/skills/learned/`    | Where to save extracted patterns            |
| `patterns_to_detect`           | *(see config.json)*                     | Pattern types to look for during evaluation |

### Example override

To change the minimum session length and disable observation:

```json
{
  "observation": {
    "enabled": false
  },
  "evaluate": {
    "min_session_length": 5
  }
}
```

Save this to `~/.claude/learning/config.json`.

## Enable / Disable

**Disable temporarily** (affects both hooks):

```bash
touch ~/.claude/learning/disabled
```

**Re-enable:**

```bash
rm ~/.claude/learning/disabled
```

**Disable via config** (granular):

```json
{
  "observation": { "enabled": false },
  "evaluate": { "enabled": false }
}
```

## Observation Format

Each line in `observations.jsonl` is a JSON object:

```json
{"timestamp":"2026-03-02T14:30:01+00:00","event":"tool_complete","session_id":"abc123","tool":"Edit","input":"{\"file_path\":\"src/Service.php\"}","output":"File edited successfully","cwd":"/home/user/project"}
```

| Field        | Description                                       |
|--------------|---------------------------------------------------|
| `timestamp`  | ISO 8601 with timezone                            |
| `event`      | `tool_complete`, `tool_failure`, or `parse_error` |
| `session_id` | Claude Code session identifier                    |
| `tool`       | Tool name (Edit, Bash, Read, Write, Grep, etc.)   |
| `input`      | Tool input, truncated to 5000 chars               |
| `output`     | Tool output, truncated to 5000 chars              |
| `cwd`        | Working directory at time of call                 |

### Log rotation

When `observations.jsonl` reaches 10 MB (configurable), it is moved to `observations.archive/observations-YYYYMMDD-HHMMSS.jsonl` and a fresh file begins. This happens automatically — no manual cleanup needed.

## Session Evaluation

`evaluate-session.php` runs at session end (Stop hook). It:

1. Reads the transcript path from stdin JSON (`transcript_path` field) or `$CLAUDE_TRANSCRIPT_PATH` env var
2. Counts user messages in the transcript
3. If count >= `min_session_length` (default 10), outputs to stderr:
   - `[ContinuousLearning] Session has N messages — evaluate for extractable patterns`
   - `[ContinuousLearning] Look for: error_resolution, user_corrections, ...`
   - `[ContinuousLearning] Save learned skills to: ~/.claude/learning/skills/learned/`

This stderr output is visible to Claude in the next session, prompting pattern extraction. Short sessions (< 10 messages) are skipped silently.

## Verification

After installing, verify that hooks work:

```bash
# 1. Test observation (PostToolUse)
echo '{"session_id":"test","tool_name":"Edit","tool_input":{"file_path":"test.php"},"tool_output":{"output":"ok"}}' | \
  php skills/continuous-learning/observe.php post
cat ~/.claude/learning/observations.jsonl
# Expected: one JSONL line with event "tool_complete"

# 2. Test failure observation (PostToolUseFailure)
echo '{"session_id":"test","tool_name":"Bash","tool_input":{"command":"npm test"},"tool_output":{"error":"exit code 1"}}' | \
  php skills/continuous-learning/observe.php failure
tail -1 ~/.claude/learning/observations.jsonl
# Expected: JSONL line with event "tool_failure"

# 3. Test empty input (should exit silently, nothing written)
echo '' | php skills/continuous-learning/observe.php post
echo "Exit code: $?"
# Expected: 0

# 4. Test disabled flag
touch ~/.claude/learning/disabled
echo '{"session_id":"test","tool_name":"Bash"}' | php skills/continuous-learning/observe.php post
wc -l ~/.claude/learning/observations.jsonl
# Expected: still 2 lines (no new entry)
rm ~/.claude/learning/disabled

# 5. Cleanup
rm -rf ~/.claude/learning/
```

## Important Nuances

### These hooks are NOT added to `hooks/hooks.json`

The project's `hooks/hooks.json` is for PHP formatting hooks (php-cs-fixer, phpstan). Continuous learning hooks are **user-configured** in `~/.claude/settings.json` because they are opt-in and personal.

### Scripts never block Claude Code

Both scripts catch all errors internally and always `exit(0)`. Even if PHP is misconfigured, the observation file is unwritable, or stdin contains garbage — Claude Code will not be affected.

### Input/output is truncated

Tool input and output are capped at 5000 characters each. This prevents the observation log from growing uncontrollably when tools produce large output (e.g., reading big files, long test results).

### Parse errors are logged, not silently lost

If `observe.php` receives malformed JSON, it logs a `parse_error` event with the first 2000 chars of raw input. This helps debug hook configuration issues.

### Config merges, not replaces

User config at `~/.claude/learning/config.json` is merged recursively with the defaults. You only need to specify the keys you want to change — everything else keeps its default value.

### No PreToolUse hook

Unlike the ECC reference implementation, this toolkit does **not** use `PreToolUse`. PostToolUse already provides both `tool_input` and `tool_output` — full context in one event. PreToolUse would only add redundant input data without output. PostToolUseFailure handles the failure case separately.

### File locking prevents data corruption

`observe.php` uses `LOCK_EX` when writing to `observations.jsonl`. This prevents interleaved writes if multiple Claude Code instances run concurrently (e.g., in different terminal tabs).

### Data is cross-project

Observations from all projects are written to the same `~/.claude/learning/observations.jsonl`. The `cwd` field in each record lets you filter by project later. This is intentional — patterns often apply across projects.

## Troubleshooting

| Problem                                            | Solution                                                                                            |
|----------------------------------------------------|-----------------------------------------------------------------------------------------------------|
| No observations being written                      | Check `~/.claude/learning/disabled` doesn't exist. Run the verification commands above.             |
| `php: command not found` in hook                   | Use the full path to PHP: `/usr/bin/php` or `/usr/local/bin/php` instead of `php`                   |
| Hooks not firing at all                            | Verify `~/.claude/settings.json` syntax is valid JSON. Claude Code v2.1+ required.                  |
| Observations file growing too large                | Default 10 MB rotation handles this. Adjust `observation.max_file_size_mb` if needed.               |
| Session evaluation never triggers                  | Default threshold is 10 user messages. Lower `evaluate.min_session_length` in your config override. |
| Permission denied writing to `~/.claude/learning/` | Run `mkdir -p ~/.claude/learning && chmod 755 ~/.claude/learning`                                   |

## What's Next (Planned)

The following features are not yet implemented and will come in future phases:

- **Background observer agent** — Haiku-based agent that reads observations and creates instincts
- **Instinct CLI** (`/instinct-status`, `/evolve`) — commands for managing learned behaviors
- **Evolution pipeline** — clustering related instincts into full skills
- **Import/export** — sharing instincts between team members
