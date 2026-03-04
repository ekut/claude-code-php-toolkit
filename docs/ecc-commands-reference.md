# ECC Commands Reference — PHP Toolkit Coverage Map

Cross-reference of all [everything-claude-code](https://github.com/affaan-m/everything-claude-code) commands against our PHP toolkit. Use this to identify gaps, plan new commands, and avoid duplicating effort.

> **Acknowledgment.** This project draws heavy inspiration from
> [everything-claude-code](https://github.com/affaan-m/everything-claude-code)
> by [@affaan-m](https://github.com/affaan-m). The command taxonomy, agent
> architecture, and plugin structure that ECC pioneered have shaped how we
> approach the PHP ecosystem. Thank you for setting the bar and making it
> open source.

**Last audit:** 2026-03-03 · **ECC commands counted:** 33 · **Our coverage:** 4 commands, 10 agents, 24 skills

## Status Legend

| Icon | Meaning                              |
|------|--------------------------------------|
| ✅    | Has analog in our toolkit            |
| 🔜   | Planned / on roadmap                 |
| 💡   | Idea worth adapting, not yet planned |
| ➖    | Not applicable to PHP                |

---

## 1. Code Quality & Review

Code review, refactoring, and static analysis commands.

| ECC Command        | Purpose                                                          | PHP Relevance                                                                                             | Status |
|--------------------|------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|--------|
| `code-review.md`   | Comprehensive security and quality review of uncommitted changes | Direct analog: `commands/php-review.md` covers PHP-specific review with PHPStan, security, and PSR rules. | ✅      |
| `refactor-clean.md`| Safely identify and remove dead code with test verification      | Foundation exists: `agents/php-refactor-cleaner.md`. Worth adding a `/php-refactor-clean` shortcut.       | 💡     |
| `python-review.md` | Python code review for PEP 8, type hints, security, and idioms  | Python-specific. Not applicable to PHP development.                                                       | ➖      |

## 2. Testing & Verification

TDD workflow, E2E testing, coverage analysis, and verification pipelines.

| ECC Command         | Purpose                                                           | PHP Relevance                                                                                                                         | Status |
|---------------------|-------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------|--------|
| `tdd.md`            | Enforce TDD with tests-first workflow and 80%+ coverage           | Direct analog: `commands/php-tdd.md` + `agents/php-tdd-guide.md` cover PHPUnit/Pest TDD.                                              | ✅      |
| `e2e.md`            | Generate and run Playwright E2E tests with artifact capture       | Direct analog: `agents/php-e2e-runner.md` covers Symfony/Laravel E2E with Panther and Dusk.                                           | ✅      |
| `eval.md`           | Manage eval-driven development with capability/regression evals   | Direct analog: `skills/eval-harness/` covers EDD philosophy, PHP graders, metrics, and 4-phase workflow.                              | ✅      |
| `test-coverage.md`  | Analyze coverage gaps and generate missing tests for 80%+ target  | Foundation exists: `skills/php-testing/` covers PHPUnit/Pest. Worth adding a `/php-test-coverage` shortcut.                           | 💡     |
| `verify.md`         | Run comprehensive verification: build, types, lint, tests, secrets | Foundation exists: `skills/php-verification/` covers the full pipeline. Worth adding a `/php-verify` shortcut.                        | 💡     |

## 3. Build & DevOps

Build error resolution, package management, and process management.

| ECC Command    | Purpose                                                         | PHP Relevance                                                                                            | Status |
|----------------|-----------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|--------|
| `build-fix.md` | Incrementally fix build and type errors with minimal, safe changes | Foundation exists: `agents/php-build-resolver.md`. Worth adding a `/php-build-fix` shortcut.            | 💡     |
| `setup-pm.md`  | Configure preferred package manager (npm/pnpm/yarn/bun)         | JS-specific. PHP uses Composer exclusively — no configuration command needed.                             | ➖      |
| `pm2.md`       | Generate PM2 service commands for managing multiple services     | Node.js process manager. Not applicable to PHP (use Supervisor, systemd, or FrankenPHP).                 | ➖      |

## 4. Planning & Orchestration

Implementation planning and multi-agent workflow coordination.

| ECC Command       | Purpose                                                             | PHP Relevance                                                                                              | Status |
|-------------------|---------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------|--------|
| `plan.md`         | Create comprehensive implementation plan before writing code         | Foundation exists: `agents/php-planner.md`. Worth adding a `/php-plan` shortcut.                           | 💡     |
| `orchestrate.md`  | Sequential agent workflow: plan → build → test → review → security  | All 10 agents exist. Worth adding a `/php-orchestrate` command to chain them.                              | 💡     |

## 5. Documentation

Documentation generation and synchronization with codebase.

| ECC Command          | Purpose                                                    | PHP Relevance                                                                                           | Status |
|----------------------|------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|--------|
| `update-docs.md`     | Sync documentation with codebase from source-of-truth files | Foundation exists: `agents/php-doc-updater.md`. Worth adding a `/php-update-docs` shortcut.             | 💡     |
| `update-codemaps.md` | Generate token-lean architecture documentation             | Foundation exists: same `agents/php-doc-updater.md`. Worth adding a `/php-update-codemaps` shortcut.    | 💡     |

## 6. Learning & Instincts

Session learning, pattern extraction, and instinct management.

| ECC Command           | Purpose                                                        | PHP Relevance                                                                                                 | Status |
|-----------------------|----------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|--------|
| `learn.md`            | Extract reusable patterns from the current session as skills   | Foundation exists: `skills/continuous-learning/`. Worth adding a `/php-learn` shortcut.                        | 💡     |
| `learn-eval.md`       | Extract patterns with self-evaluation before saving            | Foundation exists: same `skills/continuous-learning/`. Worth combining with `/php-learn`.                      | 💡     |
| `instinct-status.md`  | Display all learned instincts grouped by domain with confidence | Foundation exists: same skill. Worth adding a `/php-instinct-status` shortcut.                                | 💡     |
| `instinct-import.md`  | Import instincts from files, URLs, or teammates                | Foundation exists: same skill. Worth adding a `/php-instinct-import` shortcut.                                | 💡     |
| `instinct-export.md`  | Export learned instincts to YAML for sharing                   | Foundation exists: same skill. Worth adding a `/php-instinct-export` shortcut.                                | 💡     |
| `evolve.md`           | Cluster related instincts into skills, commands, or agents     | Foundation exists: same skill. Worth adding a `/php-evolve` shortcut.                                         | 💡     |

## 7. Session & Workflow

Session management, checkpoints, and skill creation.

| ECC Command       | Purpose                                                            | PHP Relevance                                                                                                  | Status |
|-------------------|--------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------|--------|
| `checkpoint.md`   | Create or verify workflow checkpoints for tracking progress         | Foundation exists: `skills/strategic-compact/`. Worth adding a `/php-checkpoint` shortcut.                      | 💡     |
| `sessions.md`     | Manage Claude Code session history with list, load, alias, and edit | ECC-internal session management tooling. Not applicable to our toolkit.                                        | ➖      |
| `claw.md`         | Start persistent AI agent REPL with conversation history to disk   | ECC-internal NanoClaw agent REPL. Not applicable to our toolkit.                                               | ➖      |
| `skill-create.md` | Analyze git history to extract coding patterns as SKILL.md files   | Covered by our `/skill-creator` skill which provides the same functionality.                                    | ➖      |

## 8. Multi-Model & Language-Specific

Multi-model orchestration (Codex/Gemini) and Go-specific commands.

| ECC Command          | Purpose                                                     | PHP Relevance                                                                                           | Status |
|----------------------|-------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|--------|
| `multi-plan.md`      | Multi-model collaborative planning with context retrieval   | ECC's multi-model architecture (Claude + Codex + Gemini). Not applicable — we target Claude Code only.  | ➖      |
| `multi-execute.md`   | Multi-model collaborative execution of approved plans       | Same multi-model architecture. Not applicable.                                                          | ➖      |
| `multi-backend.md`   | Backend-focused workflow with Codex as authority             | Same multi-model architecture. Not applicable.                                                          | ➖      |
| `multi-frontend.md`  | Frontend-focused workflow with Gemini as authority for UI/UX | Same multi-model architecture. Not applicable.                                                          | ➖      |
| `multi-workflow.md`  | 6-phase development workflow with intelligent model routing  | Same multi-model architecture. Not applicable.                                                          | ➖      |
| `go-build.md`        | Fix Go build errors with go-build-resolver agent            | Go-specific. Not applicable to PHP development.                                                         | ➖      |
| `go-review.md`       | Go code review for idioms, concurrency, and security        | Go-specific. Not applicable to PHP development.                                                         | ➖      |
| `go-test.md`         | Go TDD with table-driven tests and 80%+ coverage            | Go-specific. Not applicable to PHP development.                                                         | ➖      |

---

## Coverage Summary

| Category                       | Total  | ✅     | 🔜    | 💡     | ➖      |
|--------------------------------|--------|--------|-------|--------|--------|
| Code Quality & Review          | 3      | 1      | 0     | 1      | 1      |
| Testing & Verification         | 5      | 3      | 0     | 2      | 0      |
| Build & DevOps                 | 3      | 0      | 0     | 1      | 2      |
| Planning & Orchestration       | 2      | 0      | 0     | 2      | 0      |
| Documentation                  | 2      | 0      | 0     | 2      | 0      |
| Learning & Instincts           | 6      | 0      | 0     | 6      | 0      |
| Session & Workflow             | 4      | 0      | 0     | 1      | 3      |
| Multi-Model & Language-Specific | 8      | 0      | 0     | 0      | 8      |
| **Total**                      | **33** | **4**  | **0** | **15** | **14** |

### Our Originals (no ECC equivalent)

These commands exist in our toolkit without a corresponding ECC command:

| Our Command                  | Purpose                                            |
|------------------------------|----------------------------------------------------|
| `commands/php-analyze.md`    | PHP static analysis with PHPStan, Psalm, PHP-CS-Fixer |
| `commands/audit-packages.md` | Supply chain safety audit for Composer dependencies |

### Next Steps

The 15 💡 commands represent the highest-value opportunities. Most have agent or skill foundations already — they just need thin command wrappers (slash commands) to become one-step shortcuts. Priority order:

1. **Verification & testing** — `/php-verify`, `/php-test-coverage` (most impactful for daily workflow)
2. **Planning & orchestration** — `/php-plan`, `/php-orchestrate` (leverage all 10 existing agents)
3. **Learning** — `/php-learn`, `/php-evolve` (build on `continuous-learning` skill)
4. **Documentation** — `/php-update-docs`, `/php-update-codemaps` (build on doc-updater agent)
5. **Remaining shortcuts** — `/php-build-fix`, `/php-refactor-clean`, `/php-checkpoint`, instinct commands
