# claude-code-php-toolkit

## Overview

Production-ready agents, skills, commands, rules, and hooks for PHP development with Claude Code. This project brings the quality of [everything-claude-code](https://github.com/affaan-m/everything-claude-code) to the PHP ecosystem.

## Project Structure

- `.claude-plugin/` — Plugin manifest (`plugin.json`) and marketplace catalog (`marketplace.json`)
- `agents/` — Specialized AI agents (frontmatter: `name`, `description`, `tools`, `model`)
- `skills/` — Knowledge bases, each in a subdirectory with `SKILL.md` (frontmatter: `name`, `description`, `origin`)
- `commands/` — Slash commands as `.md` files (frontmatter: `description` only)
- `rules/` — Coding rules (NOT part of plugin system, installed via `install.sh`)
- `hooks/` — `hooks.json` auto-loaded by Claude Code v2.1+ (NOT declared in plugin.json)
- `contexts/` — Session behavior presets injected via `--system-prompt` (NO frontmatter, NOT in plugin.json)
- `examples/` — Template CLAUDE.md for PHP projects

## Conventions

- All content is in English
- PHP 8.1+ is the minimum target version
- Framework-agnostic core — future framework support goes in `rules/{framework}/`, `skills/{framework}-*/`
- Files use YAML frontmatter for metadata
- Skills use `SKILL.md` filename (uppercase, ECC convention)

## Plugin System Rules

- `agents` in plugin.json: explicit file paths only (no directory paths)
- `skills` and `commands` in plugin.json: directory paths OK
- Never add `hooks` to plugin.json — auto-loaded by convention, causes duplicate error
- See `.claude-plugin/PLUGIN_SCHEMA_NOTES.md` for full validator constraints

## MCP Tools & References

- **Context7** — Always use `mcp context7` (resolve-library-id → query-docs) when researching capabilities, APIs, or usage patterns of any library, framework, or package. Do not rely solely on training data — fetch up-to-date documentation first.
- **Sequential Thinking** — Always use `mcp sequential-thinking` when planning any task and when solving complex or multi-step problems. Structure your reasoning into numbered thought steps before acting.
- **everything-claude-code** — When planning solutions, consult the sibling `../everything-claude-code` repository for proven patterns, hints, examples, and best practices. Use it as a reference for high-quality implementations.

## Supply Chain Safety

- Verify every package name on Packagist before writing `composer require` to skill files
- Add verified packages to `verified-packages.json`
- Run `/audit-packages` before committing changes to skills
- See `rules/common/supply-chain-safety.md` for the full protocol

## Contributing

- Follow conventional commits
- Keep files focused and concise
- Test new agents/skills/commands against real PHP projects
- Run `claude plugin validate .claude-plugin/plugin.json` before committing
- When adding agents: add explicit file path to `plugin.json` agents array
- When adding skills: put `SKILL.md` in a new `skills/{name}/` directory
- When adding or removing agents, skills, commands, contexts, rules, or hooks: update README.md counts, tables, and architecture tree to match
