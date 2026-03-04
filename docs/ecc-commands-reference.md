# ECC Commands Reference — PHP Toolkit Coverage Map

Cross-reference of all [everything-claude-code](https://github.com/affaan-m/everything-claude-code) commands against our PHP toolkit. Use this to identify gaps, plan new commands, and avoid duplicating effort.

> **Acknowledgment.** This project draws heavy inspiration from
> [everything-claude-code](https://github.com/affaan-m/everything-claude-code)
> by [@affaan-m](https://github.com/affaan-m). The command taxonomy, agent
> architecture, and plugin structure that ECC pioneered have shaped how we
> approach the PHP ecosystem. Thank you for setting the bar and making it
> open source.

**Last audit:** 2026-03-04 · **ECC commands counted:** 33 · **Our coverage:** 18 commands, 10 agents, 24 skills

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
| `refactor-clean.md`| Safely identify and remove dead code with test verification      | Direct analog: `commands/php-refactor-clean.md` wraps `agents/php-refactor-cleaner.md`.                   | ✅      |
| `python-review.md` | Python code review for PEP 8, type hints, security, and idioms  | Python-specific. Not applicable to PHP development.                                                       | ➖      |

## 2. Testing & Verification

TDD workflow, E2E testing, coverage analysis, and verification pipelines.

| ECC Command         | Purpose                                                           | PHP Relevance                                                                                                                         | Status |
|---------------------|-------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------|--------|
| `tdd.md`            | Enforce TDD with tests-first workflow and 80%+ coverage           | Direct analog: `commands/php-tdd.md` + `agents/php-tdd-guide.md` cover PHPUnit/Pest TDD.                                              | ✅      |
| `e2e.md`            | Generate and run Playwright E2E tests with artifact capture       | Direct analog: `agents/php-e2e-runner.md` covers Symfony/Laravel E2E with Panther and Dusk.                                           | ✅      |
| `eval.md`           | Manage eval-driven development with capability/regression evals   | Direct analog: `skills/eval-harness/` covers EDD philosophy, PHP graders, metrics, and 4-phase workflow.                              | ✅      |
| `test-coverage.md`  | Analyze coverage gaps and generate missing tests for 80%+ target  | Direct analog: `commands/php-test-coverage.md` wraps `skills/php-testing/`.                                                           | ✅      |
| `verify.md`         | Run comprehensive verification: build, types, lint, tests, secrets | Direct analog: `commands/php-verify.md` wraps `skills/php-verification/`.                                                             | ✅      |

## 3. Build & DevOps

Build error resolution, package management, and process management.

| ECC Command    | Purpose                                                         | PHP Relevance                                                                                            | Status |
|----------------|-----------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|--------|
| `build-fix.md` | Incrementally fix build and type errors with minimal, safe changes | Direct analog: `commands/php-build-fix.md` wraps `agents/php-build-resolver.md`.                        | ✅      |
| `setup-pm.md`  | Configure preferred package manager (npm/pnpm/yarn/bun)         | JS-specific. PHP uses Composer exclusively — no configuration command needed.                             | ➖      |
| `pm2.md`       | Generate PM2 service commands for managing multiple services     | Node.js process manager. Not applicable to PHP (use Supervisor, systemd, or FrankenPHP).                 | ➖      |

## 4. Planning & Orchestration

Implementation planning and multi-agent workflow coordination.

| ECC Command       | Purpose                                                             | PHP Relevance                                                                                              | Status |
|-------------------|---------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------|--------|
| `plan.md`         | Create comprehensive implementation plan before writing code         | Direct analog: `commands/php-plan.md` wraps `agents/php-planner.md`.                                       | ✅      |
| `orchestrate.md`  | Sequential agent workflow: plan → build → test → review → security  | Direct analog: `commands/php-orchestrate.md` chains all 10 agents.                                         | ✅      |

## 5. Documentation

Documentation generation and synchronization with codebase.

| ECC Command          | Purpose                                                    | PHP Relevance                                                                                           | Status |
|----------------------|------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|--------|
| `update-docs.md`     | Sync documentation with codebase from source-of-truth files | Direct analog: `commands/php-update-docs.md` wraps `agents/php-doc-updater.md`.                         | ✅      |
| `update-codemaps.md` | Generate token-lean architecture documentation             | Direct analog: `commands/php-update-codemaps.md` wraps `agents/php-doc-updater.md`.                     | ✅      |

## 6. Learning & Instincts

Session learning, pattern extraction, and instinct management.

| ECC Command           | Purpose                                                        | PHP Relevance                                                                                                 | Status |
|-----------------------|----------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|--------|
| `learn.md`            | Extract reusable patterns from the current session as skills   | Direct analog: `commands/php-learn.md` wraps `skills/continuous-learning/`.                                    | ✅      |
| `learn-eval.md`       | Extract patterns with self-evaluation before saving            | Combined with `/php-learn` — self-evaluation rubric included as step 4.                                        | ✅      |
| `instinct-status.md`  | Display all learned instincts grouped by domain with confidence | Direct analog: `commands/php-instinct-status.md` wraps `skills/continuous-learning/`.                         | ✅      |
| `instinct-import.md`  | Import instincts from files, URLs, or teammates                | Direct analog: `commands/php-instinct-import.md` wraps `skills/continuous-learning/`.                         | ✅      |
| `instinct-export.md`  | Export learned instincts to YAML for sharing                   | Direct analog: `commands/php-instinct-export.md` wraps `skills/continuous-learning/`.                         | ✅      |
| `evolve.md`           | Cluster related instincts into skills, commands, or agents     | Direct analog: `commands/php-evolve.md` wraps `skills/continuous-learning/`.                                  | ✅      |

## 7. Session & Workflow

Session management, checkpoints, and skill creation.

| ECC Command       | Purpose                                                            | PHP Relevance                                                                                                  | Status |
|-------------------|--------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------|--------|
| `checkpoint.md`   | Create or verify workflow checkpoints for tracking progress         | Direct analog: `commands/php-checkpoint.md` wraps `skills/strategic-compact/`.                                  | ✅      |
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
| Code Quality & Review          | 3      | 2      | 0     | 0      | 1      |
| Testing & Verification         | 5      | 5      | 0     | 0      | 0      |
| Build & DevOps                 | 3      | 1      | 0     | 0      | 2      |
| Planning & Orchestration       | 2      | 2      | 0     | 0      | 0      |
| Documentation                  | 2      | 2      | 0     | 0      | 0      |
| Learning & Instincts           | 6      | 6      | 0     | 0      | 0      |
| Session & Workflow             | 4      | 1      | 0     | 0      | 3      |
| Multi-Model & Language-Specific | 8      | 0      | 0     | 0      | 8      |
| **Total**                      | **33** | **19** | **0** | **0**  | **14** |

### Our Originals (no ECC equivalent)

These commands exist in our toolkit without a corresponding ECC command:

| Our Command                  | Purpose                                            |
|------------------------------|----------------------------------------------------|
| `commands/php-analyze.md`    | PHP static analysis with PHPStan, Psalm, PHP-CS-Fixer |
| `commands/audit-packages.md` | Supply chain safety audit for Composer dependencies |

### Coverage Complete

All 15 💡 commands have been implemented as thin command wrappers. The `learn-eval` command was combined with `/php-learn` (self-evaluation rubric included as step 4). Zero gaps remaining — all adaptable ECC commands now have PHP toolkit analogs.
