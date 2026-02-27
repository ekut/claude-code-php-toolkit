# ECC Skills Reference — PHP Toolkit Coverage Map

Cross-reference of all [everything-claude-code](https://github.com/affaan-m/everything-claude-code) skills against our PHP toolkit. Use this to identify gaps, plan new skills, and avoid duplicating effort.

> **Acknowledgment.** This project draws heavy inspiration from
> [everything-claude-code](https://github.com/affaan-m/everything-claude-code)
> by [@affaan-m](https://github.com/affaan-m). The skill taxonomy, agent
> architecture, and plugin structure that ECC pioneered have shaped how we
> approach the PHP ecosystem. Thank you for setting the bar and making it
> open source.

**Last audit:** 2026-02-27 · **ECC skills counted:** 50 · **Our coverage:** 8 skills, 10 agents, 6 rules

## Status Legend

| Icon | Meaning                              |
|------|--------------------------------------|
| ✅    | Has analog in our toolkit            |
| 🔜   | Planned / on roadmap                 |
| 💡   | Idea worth adapting, not yet planned |
| ➖    | Not applicable to PHP                |

---

## 1. Universal Patterns

Language-agnostic ideas directly applicable to PHP.

| ECC Skill                      | Purpose                                                          | PHP Relevance                                                                                                                              | Status |
|--------------------------------|------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|--------|
| `api-design`                   | REST API design: resources, status codes, pagination, versioning | PHP APIs (Symfony/Laravel) follow the same REST conventions. A dedicated skill would consolidate API-Platform, Laravel Resource patterns.  | 💡     |
| `backend-patterns`             | Backend architecture for Node.js/Express/Next.js                 | Core ideas (layered services, caching, error handling) transfer well. Partially covered by `skills/php-patterns/` and architecture skills. | 💡     |
| `coding-standards`             | Universal coding standards for TS/JS/React                       | Direct analog: `skills/php-coding-standards/` covers PSR-1/4/12, PER 2.0, PHP-CS-Fixer.                                                    | ✅      |
| `frontend-patterns`            | React/Next.js frontend patterns                                  | PHP is backend-focused. Not applicable unless building Livewire/Inertia guides.                                                            | ➖      |
| `database-migrations`          | Migration best practices, rollbacks, zero-downtime               | Directly applicable: Doctrine Migrations, Laravel Migrations, Phinx. Worth a dedicated skill.                                              | 💡     |
| `content-hash-cache-pattern`   | SHA-256 content-hash caching for file processing                 | Pattern transfers to PHP caching (OPcache, APCu, Redis, Symfony Cache). Could be a focused recipe.                                         | 💡     |
| `deployment-patterns`          | CI/CD pipelines, Docker, health checks, rollbacks                | Directly applicable: php-fpm, Swoole/FrankenPHP, Deployer, Envoyer. Worth a dedicated skill.                                               | 💡     |
| `docker-patterns`              | Docker Compose, container security, multi-service                | PHP-specific Dockerfiles (multi-stage, extension installation, OPcache tuning) are high-value.                                             | 💡     |
| `regex-vs-llm-structured-text` | Decision framework: regex vs LLM for text parsing                | Language-agnostic methodology. Low priority but transferable as-is.                                                                        | 💡     |
| `project-guidelines-example`   | Example project-specific skill template                          | We have `examples/CLAUDE.md` serving a similar purpose.                                                                                    | ✅      |

## 2. Testing & Quality

TDD methodology, verification loops, and E2E patterns.

| ECC Skill           | Purpose                                              | PHP Relevance                                                                                             | Status |
|---------------------|------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|--------|
| `tdd-workflow`      | TDD methodology, 80%+ coverage, Red-Green-Refactor   | Direct analog: `skills/php-testing/` + `agents/php-tdd-guide.md` cover PHPUnit/Pest TDD.                  | ✅      |
| `verification-loop` | Static analysis + tests + quality checks pipeline    | PHP equivalent: PHPStan → PHP-CS-Fixer → PHPUnit pipeline. Worth a dedicated verification skill.          | 💡     |
| `e2e-testing`       | Playwright E2E testing, Page Object Model            | `agents/php-e2e-runner.md` covers Symfony/Laravel E2E. Playwright-specific is less relevant for PHP APIs. | ✅      |
| `eval-harness`      | Formal evaluation framework for Claude Code sessions | Language-agnostic methodology. Could adapt for evaluating PHP agent quality.                              | 💡     |

## 3. Security & DevOps

Security review, scanning, and infrastructure patterns.

| ECC Skill         | Purpose                                          | PHP Relevance                                                                           | Status |
|-------------------|--------------------------------------------------|-----------------------------------------------------------------------------------------|--------|
| `security-review` | OWASP Top 10 checklist: SQLi, XSS, CSRF, secrets | Direct analog: `agents/php-security-reviewer.md` + `rules/php/security.md`.             | ✅      |
| `security-scan`   | Audit Claude Code config for vulnerabilities     | Could adapt for PHP: `composer audit`, Psalm taint analysis, Roave Security Advisories. | 💡     |

## 4. Java / Spring Boot

Closest analog to the PHP/Symfony ecosystem — highest idea-transfer value.

| ECC Skill                 | Purpose                                                  | PHP Relevance                                                                                                          | Status |
|---------------------------|----------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------|--------|
| `java-coding-standards`   | Java standards: naming, immutability, Optional, streams  | PHP analog exists: `skills/php-coding-standards/`. Java-specific conventions don't transfer directly.                  | ✅      |
| `springboot-patterns`     | Spring Boot architecture: REST, services, caching, async | Partial analog: `skills/symfony-patterns/` covers Symfony equivalents. Laravel patterns skill would complete coverage. | ✅      |
| `springboot-security`     | Spring Security: authn/authz, CSRF, rate limiting        | Ideas transfer well to Symfony Security / Laravel Sanctum+Gates. Could expand security skill.                          | 💡     |
| `springboot-tdd`          | Spring Boot TDD: JUnit 5, Mockito, Testcontainers        | `skills/php-testing/` covers PHPUnit/Pest TDD. Testcontainers idea worth adopting for PHP.                             | ✅      |
| `springboot-verification` | Build + analysis + tests + security scan pipeline        | Same concept as `verification-loop` — PHP pipeline (Composer → PHPStan → PHPUnit → `composer audit`).                  | 💡     |
| `jpa-patterns`            | JPA/Hibernate: entities, relationships, queries, pooling | Directly maps to Doctrine ORM patterns. High-value skill for PHP projects using Doctrine.                              | 🔜     |

## 5. Python / Django

Second-closest analog — web framework patterns transferable to Laravel.

| ECC Skill             | Purpose                                                     | PHP Relevance                                                                                                   | Status |
|-----------------------|-------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|--------|
| `python-patterns`     | Pythonic idioms, PEP 8, type hints                          | PHP has its own idioms covered by `skills/php-patterns/`. No direct transfer needed.                            | ✅      |
| `python-testing`      | pytest, fixtures, mocking, parametrization                  | PHP analog: `skills/php-testing/` covers PHPUnit/Pest equivalents (data providers ≈ parametrize).               | ✅      |
| `django-patterns`     | Django architecture: DRF, ORM, caching, signals, middleware | Ideas transfer to Laravel: Eloquent, middleware, events, caching. A Laravel patterns skill would capture these. | 💡     |
| `django-security`     | Django security: auth, CSRF, SQLi, XSS prevention           | Already covered by `agents/php-security-reviewer.md` + `rules/php/security.md`.                                 | ✅      |
| `django-tdd`          | Django testing: pytest-django, factory_boy, DRF tests       | Concepts covered by `skills/php-testing/`. Factory pattern → use with `fakerphp/faker`.                         | ✅      |
| `django-verification` | Django verification: migrations, linting, tests, security   | Same concept as verification-loop. PHP equivalent would be valuable.                                            | 💡     |

## 6. Other Languages

Go, C++, Swift — low direct relevance, occasional transferable ideas.

| ECC Skill                     | Purpose                                               | PHP Relevance                                                                                             | Status |
|-------------------------------|-------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|--------|
| `golang-patterns`             | Idiomatic Go: concurrency, error handling, interfaces | Go concurrency ideas don't transfer. PHP has Fibers but different model.                                  | ➖      |
| `golang-testing`              | Go testing: table-driven, benchmarks, fuzzing         | Table-driven test idea works in PHP (data providers). Already in `skills/php-testing/`.                   | ➖      |
| `cpp-coding-standards`        | C++ Core Guidelines: type/resource safety             | Not applicable to PHP development.                                                                        | ➖      |
| `cpp-testing`                 | GoogleTest/GoogleMock with CMake                      | Not applicable to PHP development.                                                                        | ➖      |
| `swift-concurrency-6-2`       | Swift async/await, actors, structured concurrency     | Not applicable to PHP. PHP Fibers are a different paradigm.                                               | ➖      |
| `swift-actor-persistence`     | Swift actor persistence with database backends        | Not applicable to PHP development.                                                                        | ➖      |
| `swift-protocol-di-testing`   | Swift DI and testing via protocols                    | DI concept is universal but Swift-specific implementation doesn't transfer. PHP has mature DI containers. | ➖      |
| `swiftui-patterns`            | SwiftUI state management, view composition            | Not applicable to backend PHP.                                                                            | ➖      |
| `foundation-models-on-device` | Apple FoundationModels on-device LLM                  | Not applicable to PHP server-side development.                                                            | ➖      |
| `liquid-glass-design`         | iOS 26 Liquid Glass UI design system                  | Not applicable to backend PHP.                                                                            | ➖      |

## 7. Meta-Skills

Claude Code workflow, session management, and learning patterns.

| ECC Skill                | Purpose                                                   | PHP Relevance                                                                                                 | Status |
|--------------------------|-----------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|--------|
| `search-first`           | Research-before-coding workflow                           | Language-agnostic methodology. Worth adapting: search Packagist/existing packages before writing custom code. | 💡     |
| `continuous-learning`    | Extract reusable patterns from sessions as skills         | Language-agnostic. Could adapt for PHP toolkit — auto-discover PHP patterns during sessions.                  | 💡     |
| `continuous-learning-v2` | Instinct-based learning with hooks and confidence scoring | Advanced version of above. Same applicability — language-agnostic system.                                     | 💡     |
| `iterative-retrieval`    | Progressive context retrieval for subagent problem        | Language-agnostic infrastructure pattern. Low priority for PHP-specific toolkit.                              | 💡     |
| `strategic-compact`      | Manual `/compact` at logical workflow breakpoints         | Language-agnostic session management. Useful for long PHP refactoring or migration sessions.                  | 💡     |
| `configure-ecc`          | Interactive ECC installer for skill selection             | Internal to ECC. Not applicable to our toolkit (we have `install.sh`).                                        | ➖      |
| `skill-stocktake`        | Audit skills for quality using checklist + AI judgment    | Could adapt to audit our own PHP skills quality. Useful for maintenance.                                      | 💡     |

## 8. Domain-Specific

Niche skills for specific use cases.

| ECC Skill                      | Purpose                                              | PHP Relevance                                                                                                              | Status |
|--------------------------------|------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------|--------|
| `nutrient-document-processing` | Document processing, OCR, redaction via Nutrient API | Domain-specific SaaS integration. Not PHP-specific.                                                                        | ➖      |
| `visa-doc-translate`           | Visa document translation to bilingual PDF           | Domain-specific workflow. Not PHP-specific.                                                                                | ➖      |
| `clickhouse-io`                | ClickHouse analytics database patterns               | Niche but usable from PHP. Low priority — few PHP projects use ClickHouse directly.                                        | ➖      |
| `postgres-patterns`            | PostgreSQL query optimization, schema, indexing      | Valuable for PHP. Partially covered by `agents/php-database-reviewer.md`. A Doctrine-focused patterns skill is on roadmap. | 🔜     |
| `cost-aware-llm-pipeline`      | LLM API cost optimization: model routing, budgets    | Language-agnostic AI engineering. Low PHP-specific value but transferable concepts.                                        | 💡     |

---

## Coverage Summary

| Category           | Total  | ✅      | 🔜    | 💡     | ➖      |
|--------------------|--------|--------|-------|--------|--------|
| Universal Patterns | 10     | 2      | 0     | 7      | 1      |
| Testing & Quality  | 4      | 3      | 0     | 1      | 0      |
| Security & DevOps  | 2      | 1      | 0     | 1      | 0      |
| Java / Spring Boot | 6      | 3      | 1     | 2      | 0      |
| Python / Django    | 6      | 4      | 0     | 2      | 0      |
| Other Languages    | 10     | 0      | 0     | 0      | 10     |
| Meta-Skills        | 7      | 0      | 0     | 5      | 2      |
| Domain-Specific    | 5      | 0      | 1     | 1      | 3      |
| **Total**          | **50** | **13** | **2** | **19** | **16** |

## High-Priority Gaps

Skills worth building next, ranked by impact:

1. **PHP Verification Loop** — PHPStan + PHP-CS-Fixer + PHPUnit pipeline as a single skill (from `verification-loop`, `springboot-verification`, `django-verification`)
2. **Doctrine ORM Patterns** — entity design, relationships, DQL, migrations (from `jpa-patterns`, `postgres-patterns`)
3. **Doctrine ODM Patterns** — MongoDB document mapping, references, query builder, aggregation pipelines, lifecycle callbacks (complements ORM skill for document-database projects)
4. **PHP Deployment Patterns** — php-fpm, Swoole, FrankenPHP, Docker, Deployer (from `deployment-patterns`, `docker-patterns`)
5. **Laravel Patterns** — routes, Eloquent, middleware, events, queues (from `springboot-patterns`, `django-patterns`)
6. **PHP API Design** — REST conventions, API Platform, Laravel API Resources (from `api-design`)
7. **PHP Security Scanning** — `composer audit`, Psalm taint analysis, Roave advisories (from `security-scan`)
8. **Search-First Workflow** — research Packagist, existing packages, and proven patterns before writing custom code (from `search-first`)
9. **Continuous Learning** — auto-extract reusable PHP patterns from Claude Code sessions and evolve them into skills (from `continuous-learning`, `continuous-learning-v2`)
10. **Skill Stocktake** — quality audit framework for our own skills: completeness, accuracy, code examples (from `skill-stocktake`)
