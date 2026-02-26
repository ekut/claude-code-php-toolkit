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

### Agents (4)

| Agent | Description |
|-------|-------------|
| [php-reviewer](agents/php-reviewer.md) | PHP code review — PSR compliance, type safety, security, performance |
| [php-tdd-guide](agents/php-tdd-guide.md) | TDD specialist — Red-Green-Refactor with PHPUnit and Pest |
| [php-security-reviewer](agents/php-security-reviewer.md) | Security audit — OWASP Top 10, SQL injection, XSS, CSRF |
| [php-build-resolver](agents/php-build-resolver.md) | Composer & PHP build error resolution |

### Skills (4)

| Skill | Description |
|-------|-------------|
| [php-patterns](skills/php-patterns/SKILL.md) | Modern PHP 8.1+ idioms — enums, readonly, match, fibers |
| [php-coding-standards](skills/php-coding-standards/SKILL.md) | PSR-1, PSR-4, PSR-12, PER-CS 2.0, PHP-CS-Fixer, Pint |
| [php-testing](skills/php-testing/SKILL.md) | PHPUnit 10+, Pest 2+, mocking, coverage, data providers |
| [php-static-analysis](skills/php-static-analysis/SKILL.md) | PHPStan, Psalm, PHP-CS-Fixer, Rector |

### Commands (3)

| Command | Description |
|---------|-------------|
| `/php-review` | Comprehensive PHP code review |
| `/php-tdd` | TDD workflow with PHPUnit or Pest |
| `/php-analyze` | Run PHPStan + PHP-CS-Fixer + Psalm |

### Rules (6)

| Rule | Scope |
|------|-------|
| [git-workflow](rules/common/git-workflow.md) | Conventional commits, branching, PR workflow |
| [development-workflow](rules/common/development-workflow.md) | Plan > TDD > Review > Commit |
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
├── common/          # Generic rules (git, workflow)
├── php/             # Core PHP rules (current)
├── symfony/         # Symfony rules (future)
└── laravel/         # Laravel rules (future)

skills/
├── php-patterns/            # Core PHP (current)
├── php-coding-standards/    # Core PHP (current)
├── symfony-services/        # Symfony (future)
└── laravel-eloquent/        # Laravel (future)
```

## Roadmap

- [ ] Symfony support (routing, services, Doctrine, Twig)
- [ ] Laravel support (Eloquent, Blade, Artisan, queues)
- [ ] API Platform support
- [ ] Doctrine ORM patterns
- [ ] PHP package development skill (library authors)
- [ ] Docker/Compose integration hooks
- [ ] CI/CD pipeline templates (GitHub Actions, GitLab CI)

## License

[MIT](LICENSE)
