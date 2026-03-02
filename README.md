# claude-code-php-toolkit

Production-ready agents, skills, commands, rules, and hooks for PHP development with [Claude Code](https://docs.anthropic.com/en/docs/claude-code).

Inspired by [everything-claude-code](https://github.com/affaan-m/everything-claude-code), adapted and extended for the PHP ecosystem.

## Quick Start

### Step 1: Install the Plugin

```bash
# Add marketplace
/plugin marketplace add ekut/claude-code-php-toolkit

# Install plugin
/plugin install claude-code-php-toolkit@claude-code-php-toolkit
```

This installs agents, skills, commands, and hooks automatically.

#### Choosing the Right Scope

The plugin can be installed at different scopes depending on your setup:

| Scope | Flag | Where the setting is stored | Best for |
|-------|------|-----------------------------|----------|
| `user` | default | `~/.claude/settings.json` | **PHP-only developers.** The plugin is available in all your projects. |
| `project` | `--scope project` | `.claude/settings.json` (committed to git) | **Teams using Claude Code.** Every team member gets the plugin automatically. |
| `local` | `--scope local` | `.claude/settings.local.json` (gitignored) | **Solo Claude Code user in a team.** Plugin is active in this project but doesn't affect teammates. |

**How to choose:**

- You work only on PHP projects → install with default `user` scope
- You work on PHP, Go, Rust, etc. → install with `project` or `local` scope in your PHP projects only
- Your whole team uses Claude Code → install with `project` scope so it's shared via git
- You're the only Claude Code user on the team → install with `local` scope

Hooks are safe at any scope: they only trigger on `*.php` files and check for tools (`vendor/bin/...`) before running.

### Step 2: Install Rules

Claude Code plugins cannot distribute rules automatically. Install them manually:

```bash
# Clone the repo
git clone https://github.com/ekut/claude-code-php-toolkit.git
cd claude-code-php-toolkit

# Run the installer (installs to ~/.claude/rules/ — global scope)
./install.sh
```

This copies rules to `~/.claude/rules/common/` and `~/.claude/rules/php/`. Rules installed this way apply to all projects. If you want rules only in a specific project, copy them to your project's `.claude/rules/` instead:

```bash
# Project-scoped rules (alternative)
cp -r claude-code-php-toolkit/rules/ /path/to/your/project/.claude/rules/
```

### Step 3: Start Using

```
/php-review    # Review PHP code for PSR compliance, security, performance
/php-tdd       # TDD workflow with PHPUnit or Pest
/php-analyze   # Run PHPStan + PHP-CS-Fixer + Psalm
```

### Optional: Project CLAUDE.md

Copy the example CLAUDE.md template into your PHP project:

```bash
cp claude-code-php-toolkit/examples/CLAUDE.md /path/to/your/php-project/CLAUDE.md
```

Customize it for your project's specific needs.

## What's Included

### Agents (10)

| Agent | Description |
|-------|-------------|
| [php-reviewer](agents/php-reviewer.md) | PHP code review — PSR compliance, type safety, security, performance |
| [php-tdd-guide](agents/php-tdd-guide.md) | TDD specialist — Red-Green-Refactor with PHPUnit and Pest |
| [php-security-reviewer](agents/php-security-reviewer.md) | Security audit — OWASP Top 10, SQL injection, XSS, CSRF |
| [php-build-resolver](agents/php-build-resolver.md) | Composer & PHP build error resolution |
| [php-architect](agents/php-architect.md) | System architecture — neutral assessment, 3 architectural schools, two-phase recommendation |
| [php-planner](agents/php-planner.md) | Feature planning — phased implementation, risks, deployment |
| [php-database-reviewer](agents/php-database-reviewer.md) | Database review — schemas, migrations, queries, ORM (MySQL & PostgreSQL) |
| [php-doc-updater](agents/php-doc-updater.md) | Documentation — PHPDoc, codemaps, API stubs |
| [php-refactor-cleaner](agents/php-refactor-cleaner.md) | Dead code cleanup — unused imports, packages, refactoring |
| [php-e2e-runner](agents/php-e2e-runner.md) | E2E & integration testing — Symfony, Laravel, framework-agnostic |

### Skills (24)

| Skill | Description |
|-------|-------------|
| [php-patterns](skills/php-patterns/SKILL.md) | Modern PHP 8.1+ idioms — enums, readonly, match, fibers |
| [php-coding-standards](skills/php-coding-standards/SKILL.md) | PSR-1, PSR-4, PSR-12, PER-CS 2.0, PHP-CS-Fixer, Pint |
| [php-testing](skills/php-testing/SKILL.md) | PHPUnit 10+, Pest 2+, mocking, coverage, data providers |
| [php-static-analysis](skills/php-static-analysis/SKILL.md) | PHPStan, Psalm, PHP-CS-Fixer, Rector |
| [php-verification](skills/php-verification/SKILL.md) | 6-phase verification pipeline — Composer, style, analysis, tests, security, diff |
| [php-architecture-ddd](skills/php-architecture-ddd/SKILL.md) | DDD — Rich Domain, Hexagonal, CQRS, Bounded Contexts |
| [php-architecture-service-layer](skills/php-architecture-service-layer/SKILL.md) | Service-Oriented — Service Layer, Transaction Scripts, DTOs |
| [php-architecture-action-based](skills/php-architecture-action-based/SKILL.md) | Action-Based — Single-action controllers, ADR, Command/Query |
| [doctrine-orm-patterns](skills/doctrine-orm-patterns/SKILL.md) | Doctrine ORM 3.x — entities, relationships, DQL, migrations, performance |
| [doctrine-odm-patterns](skills/doctrine-odm-patterns/SKILL.md) | Doctrine MongoDB ODM — documents, references, query builder, aggregation |
| [php-deployment](skills/php-deployment/SKILL.md) | Docker, php-fpm, Swoole, FrankenPHP, Deployer, CI/CD, zero-downtime |
| [symfony-patterns](skills/symfony-patterns/SKILL.md) | Symfony 6+/7+ architecture — service container, autowiring, controllers, events, Messenger |
| [laravel-patterns](skills/laravel-patterns/SKILL.md) | Laravel 10+/11+ architecture — routing, Eloquent, middleware, events, queues, validation |
| [php-api-design](skills/php-api-design/SKILL.md) | REST API design — resource naming, status codes, pagination, versioning, rate limiting |
| [php-security-scanning](skills/php-security-scanning/SKILL.md) | Security scanning pipeline — Composer audit, Psalm taint, PHPStan security, secrets, debug detection |
| [php-security-patterns](skills/php-security-patterns/SKILL.md) | Security implementation — authentication, authorization, CORS, security headers, PII protection |
| [php-error-handling](skills/php-error-handling/SKILL.md) | Error handling — exception hierarchies, RFC 9457 Problem Details, structured logging, retry patterns |
| [content-hash-cache](skills/content-hash-cache/SKILL.md) | SHA-256 content-hash caching — deterministic cache keys, file-based storage, PSR-16 integration |
| [eval-harness](skills/eval-harness/SKILL.md) | Evaluation framework — PHP graders, metrics (pass@k), 4-phase EDD workflow |
| [strategic-compact](skills/strategic-compact/SKILL.md) | Strategic /compact — phase transitions, survival map, context preservation |
| [iterative-retrieval](skills/iterative-retrieval/SKILL.md) | Progressive context retrieval — 4-phase DISPATCH-EVALUATE-REFINE-LOOP, PHP search patterns |
| [search-first](skills/search-first/SKILL.md) | Research-before-coding workflow — search Packagist, Spatie, League before writing custom code |
| [continuous-learning](skills/continuous-learning/SKILL.md) | Auto-extract PHP patterns from sessions — instinct model, confidence scoring, hooks |
| [skill-stocktake](skills/skill-stocktake/SKILL.md) | Skill quality audit — Quick Scan and Full Stocktake modes, verdicts, consolidation |

### Commands (4)

| Command | Description |
|---------|-------------|
| `/php-review` | Comprehensive PHP code review |
| `/php-tdd` | TDD workflow with PHPUnit or Pest |
| `/php-analyze` | Run PHPStan + PHP-CS-Fixer + Psalm |
| `/audit-packages` | Audit package references against verified-packages.json allowlist |

### Rules (7)

| Rule | Scope |
|------|-------|
| [git-workflow](rules/common/git-workflow.md) | Conventional commits, branching, PR workflow |
| [development-workflow](rules/common/development-workflow.md) | Plan > TDD > Review > Commit |
| [supply-chain-safety](rules/common/supply-chain-safety.md) | Package verification, hallucination prevention, allowlist |
| [coding-style](rules/php/coding-style.md) | PSR-12/PER-CS, strict types, final by default |
| [testing](rules/php/testing.md) | PHPUnit/Pest requirements, 80% coverage |
| [security](rules/php/security.md) | PDO, htmlspecialchars, password_hash, CSRF |
| [performance](rules/php/performance.md) | OPcache, generators, N+1 prevention |

### Hooks (2)

| Hook | Trigger |
|------|---------|
| PHP-CS-Fixer on edit | Auto-formats PHP files after Edit/Write |
| PHPStan on edit | Type-checks PHP files after Edit/Write |

Hooks are auto-loaded from `hooks/hooks.json` by Claude Code (v2.1+). They are **not** declared in `plugin.json` to avoid the duplicate hooks error.

### Contexts (5)

Session behavior presets — injected via `--system-prompt` to switch Claude's working mode for the entire session.

| Context | Mode |
|---------|------|
| [php-dev](contexts/php-dev.md) | Active development — type-safe, test-first, Composer workflow |
| [php-review](contexts/php-review.md) | Code review — security, types, PSR compliance, severity-grouped output |
| [php-refactor](contexts/php-refactor.md) | Refactoring — structural changes with test-guarded discipline |
| [php-debug](contexts/php-debug.md) | Debugging — reproduce, diagnose, fix cycle |
| [php-legacy](contexts/php-legacy.md) | Legacy modernization — incremental migration to PHP 8.1+ |

**Usage:**

```bash
claude --system-prompt "$(cat contexts/php-dev.md)"
```

Or create shell aliases:

```bash
alias claude-php='claude --system-prompt "$(cat contexts/php-dev.md)"'
alias claude-review='claude --system-prompt "$(cat contexts/php-review.md)"'
```

Contexts are NOT part of the plugin system — they are standalone markdown files used via CLI flag.

## Requirements

- **Claude Code** v1.0.33+ (plugin system), v2.1+ recommended (auto-loading hooks)
- **PHP** 8.1 or higher
- **Composer**

### Recommended PHP Tools

```bash
composer require --dev phpunit/phpunit
composer require --dev phpstan/phpstan
composer require --dev friendsofphp/php-cs-fixer
# or for Laravel projects:
composer require --dev laravel/pint
```

## Architecture

The toolkit is **framework-agnostic** at its core. The directory structure supports future expansion:

```
rules/
├── common/          # Generic rules (git, workflow, supply-chain safety)
├── php/             # Core PHP rules (current)
├── symfony/         # Symfony rules (future)
└── laravel/         # Laravel rules (future)

skills/
├── php-patterns/                  # Core PHP idioms
├── php-coding-standards/          # PSR, PER-CS, formatting
├── php-testing/                   # PHPUnit, Pest
├── php-static-analysis/           # PHPStan, Psalm, Rector
├── php-verification/              # 6-phase verification pipeline
├── php-architecture-ddd/          # DDD, Hexagonal, CQRS
├── php-architecture-service-layer/ # Service Layer, Transaction Scripts
├── php-architecture-action-based/ # Action-Based, ADR
├── doctrine-orm-patterns/         # Doctrine ORM entities, DQL, migrations
├── doctrine-odm-patterns/        # Doctrine MongoDB ODM documents, queries
├── php-deployment/                # Docker, runtimes, CI/CD, Deployer
├── symfony-patterns/              # Symfony 6+/7+ patterns
├── laravel-patterns/             # Laravel 10+/11+ patterns
├── php-api-design/               # REST API design patterns
├── php-security-scanning/        # Security scanning pipeline
├── php-security-patterns/        # Security implementation patterns
├── php-error-handling/           # Error handling & structured logging
├── content-hash-cache/           # Content-hash caching pattern
├── eval-harness/                 # Evaluation framework for AI sessions
├── strategic-compact/            # Strategic /compact guide
├── iterative-retrieval/          # Progressive context retrieval
├── search-first/                 # Research-before-coding workflow
├── continuous-learning/          # Auto-extract patterns from sessions
└── skill-stocktake/             # Skill quality audit framework

contexts/
├── php-dev.md             # Development mode
├── php-review.md          # Code review mode
├── php-refactor.md        # Refactoring mode
├── php-debug.md           # Debugging mode
└── php-legacy.md          # Legacy modernization mode
```

## Roadmap

- [x] Symfony support (routing, services, Doctrine, Twig)
- [x] Laravel support (Eloquent, Blade, Artisan, queues)
- [x] Doctrine ORM patterns
- [ ] API Platform support
- [ ] PHP package development skill (library authors)
- [ ] Docker/Compose integration hooks
- [ ] CI/CD pipeline templates (GitHub Actions, GitLab CI)

## License

[MIT](LICENSE)
