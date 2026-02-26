# Plugin Manifest Schema Notes

Undocumented but enforced constraints of the Claude Code plugin manifest validator. Based on lessons learned from [everything-claude-code](https://github.com/affaan-m/everything-claude-code).

Read this before editing `.claude-plugin/plugin.json`.

---

## Required Fields

- `version` — mandatory, semver format. Validator rejects without it.

## Field Shape Rules

These fields **must always be arrays** (even for a single entry):

- `agents`
- `commands`
- `skills`
- `hooks` (if present)

### Invalid

```json
{ "agents": "./agents" }
```

### Valid

```json
{ "agents": ["./agents/php-reviewer.md"] }
```

## Path Resolution

### Agents: explicit file paths ONLY

Directory paths are rejected:

```json
// FAILS
{ "agents": ["./agents/"] }

// WORKS
{ "agents": ["./agents/php-reviewer.md", "./agents/php-tdd-guide.md"] }
```

### Skills and Commands: directory paths OK

```json
{ "skills": ["./skills/", "./commands/"] }
```

## The `hooks` Field: DO NOT ADD

Claude Code v2.1+ **auto-loads** `hooks/hooks.json` by convention. Declaring it in `plugin.json` causes:

```
Duplicate hooks file detected: ./hooks/hooks.json resolves to already-loaded file.
```

Only declare additional hook files beyond `hooks/hooks.json` (if any).

## Validation

```bash
claude plugin validate .claude-plugin/plugin.json
```

## Minimal Known-Good Example

```json
{
  "name": "my-plugin",
  "version": "1.0.0",
  "agents": [
    "./agents/my-agent.md"
  ],
  "skills": ["./skills/"],
  "commands": ["./commands/"]
}
```

No `hooks` field. `hooks/hooks.json` is loaded automatically.
