---
description: Export learned instincts to YAML markdown files for sharing with teammates
---

# PHP Instinct Export

Export instincts from `~/.claude/learning/instincts/` to shareable markdown files.

> **Foundation:** See `skills/continuous-learning/SKILL.md` for the privacy model — only abstracted patterns are exported, never raw session data.

## Step 1: Select Instincts

Ask the user what to export:

- **All** — export everything from `personal/` and `inherited/`
- **By domain** — e.g., only `testing` instincts
- **By confidence** — e.g., only instincts with confidence >= 0.7
- **By tag** — e.g., only instincts tagged `laravel`

## Step 2: Filter and Prepare

For each selected instinct:

1. Read the `.md` file from `~/.claude/learning/instincts/personal/` or `inherited/`
2. Strip any session-specific evidence (file paths, timestamps) if the user wants anonymized export
3. Keep the instinct format intact: frontmatter + action + evidence summary

## Step 3: Export

Write the selected instincts to the user-specified output:

- **Single file** — concatenate all instincts into one `.md` file with `---` separators
- **Directory** — one `.md` file per instinct in the target directory
- **stdout** — print to the conversation for copy-paste

Default output: `./exported-instincts/` in the current working directory.

```bash
mkdir -p ./exported-instincts
```

## Step 4: Summary

```
EXPORT REPORT
=============
Instincts selected:  X
Instincts exported:  X
Output:              [path or stdout]
Domains covered:     [list]
Avg confidence:      0.X

Share these files with teammates who can import them using /php-instinct-import.
```
