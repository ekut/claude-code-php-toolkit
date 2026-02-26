---
title: Development Workflow
scope: common
---

# Development Workflow

## Process: Plan > TDD > Review > Commit

### 1. Plan

- Understand requirements before writing code
- Identify affected files and components
- Consider edge cases and error scenarios
- Break large changes into small, reviewable increments

### 2. Test-Driven Development

- Write a failing test first (Red)
- Write the minimum code to make it pass (Green)
- Refactor while keeping tests green (Refactor)
- Repeat for each behavior

### 3. Review

- Self-review the diff before committing
- Check for security issues, performance regressions, and code smells
- Verify all tests pass and coverage is adequate
- Ensure code follows project conventions

### 4. Commit

- Stage only related changes
- Write a clear conventional commit message
- Run pre-commit hooks (formatting, linting, tests)

## Principles

- **Small PRs** — easier to review, less risk, faster merge
- **One concern per commit** — each commit should be a single logical change
- **Tests are not optional** — every behavior change needs a test
- **Read before write** — understand existing code before modifying it
- **Leave it better** — fix small issues you encounter (Boy Scout Rule), but keep refactors in separate commits
