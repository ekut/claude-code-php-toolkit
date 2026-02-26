---
title: Git Workflow
scope: common
---

# Git Workflow

## Commit Messages

Follow the Conventional Commits specification:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Types

- `feat` — new feature
- `fix` — bug fix
- `docs` — documentation only
- `style` — formatting, no code change
- `refactor` — restructuring without behavior change
- `perf` — performance improvement
- `test` — adding or updating tests
- `build` — build system or dependencies
- `ci` — CI configuration
- `chore` — maintenance tasks

### Rules

- Use imperative mood: "add feature" not "added feature"
- Keep the subject line under 72 characters
- Reference issue numbers in the footer: `Closes #123`
- One logical change per commit

## Branch Naming

```
<type>/<short-description>
```

Examples:
- `feat/user-authentication`
- `fix/null-pointer-in-parser`
- `refactor/extract-service-layer`

## Pull Request Workflow

1. Create a feature branch from `main`
2. Make small, focused commits
3. Write a clear PR description with context and test plan
4. Request review from relevant team members
5. Address review feedback in new commits (do not force-push during review)
6. Squash-merge to `main` when approved

## Pre-commit Checks

Before committing, ensure:
- All tests pass
- Static analysis passes (PHPStan, Psalm)
- Code formatting is applied (PHP-CS-Fixer, Pint)
- No unresolved merge conflicts
