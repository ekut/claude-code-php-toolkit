# ECC Skills Reference — PHP Toolkit Coverage Map

Cross-reference of all [everything-claude-code](https://github.com/affaan-m/everything-claude-code) skills against our PHP toolkit. Use this to identify gaps, plan new skills, and avoid duplicating effort.

> **Acknowledgment.** This project draws heavy inspiration from
> [everything-claude-code](https://github.com/affaan-m/everything-claude-code)
> by [@affaan-m](https://github.com/affaan-m). The skill taxonomy, agent
> architecture, and plugin structure that ECC pioneered have shaped how we
> approach the PHP ecosystem. Thank you for setting the bar and making it
> open source.

**Last audit:** 2026-03-02 · **ECC skills counted:** 50 · **Our coverage:** 24 skills, 10 agents, 7 rules

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

| ECC Skill                      | Purpose                                                          | PHP Relevance                                                                                                                                                                | Status |
|--------------------------------|------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|
| `api-design`                   | REST API design: resources, status codes, pagination, versioning | Direct analog: `skills/php-api-design/` covers REST conventions, Laravel API Resources, API Platform.                                                                        | ✅      |
| `backend-patterns`             | Backend architecture for Node.js/Express/Next.js                 | Remaining gaps (error handling, structured logging) covered by `skills/php-error-handling/`. Other topics already covered by existing architecture, caching, and API skills. | ✅      |
| `coding-standards`             | Universal coding standards for TS/JS/React                       | Direct analog: `skills/php-coding-standards/` covers PSR-1/4/12, PER 2.0, PHP-CS-Fixer.                                                                                      | ✅      |
| `frontend-patterns`            | React/Next.js frontend patterns                                  | PHP is backend-focused. Not applicable unless building Livewire/Inertia guides.                                                                                              | ➖      |
| `database-migrations`          | Migration best practices, rollbacks, zero-downtime               | Covered by `skills/doctrine-orm-patterns/migrations.md` (zero-downtime strategies, rollbacks).                                                                               | ✅      |
| `content-hash-cache-pattern`   | SHA-256 content-hash caching for file processing                 | Direct analog: `skills/content-hash-cache/` covers core pattern, file-based storage, PSR-16 integration, service wrapper.                                                    | ✅      |
| `deployment-patterns`          | CI/CD pipelines, Docker, health checks, rollbacks                | Direct analog: `skills/php-deployment/` covers Docker, CI/CD, Deployer, health checks, zero-downtime.                                                                        | ✅      |
| `docker-patterns`              | Docker Compose, container security, multi-service                | Direct analog: `skills/php-deployment/` covers multi-stage Dockerfiles, Compose, OPcache tuning.                                                                             | ✅      |
| `regex-vs-llm-structured-text` | Decision framework: regex vs LLM for text parsing                | Language-agnostic methodology. Low priority but transferable as-is.                                                                                                          | ➖      |
| `project-guidelines-example`   | Example project-specific skill template                          | We have `examples/CLAUDE.md` serving a similar purpose.                                                                                                                      | ✅      |

## 2. Testing & Quality

TDD methodology, verification loops, and E2E patterns.

| ECC Skill           | Purpose                                              | PHP Relevance                                                                                                                         | Status |
|---------------------|------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------|--------|
| `tdd-workflow`      | TDD methodology, 80%+ coverage, Red-Green-Refactor   | Direct analog: `skills/php-testing/` + `agents/php-tdd-guide.md` cover PHPUnit/Pest TDD.                                              | ✅      |
| `verification-loop` | Static analysis + tests + quality checks pipeline    | Direct analog: `skills/php-verification/` covers the full Composer → PHPStan → PHPUnit → audit pipeline.                              | ✅      |
| `e2e-testing`       | Playwright E2E testing, Page Object Model            | `agents/php-e2e-runner.md` covers Symfony/Laravel E2E. Playwright-specific is less relevant for PHP APIs.                             | ✅      |
| `eval-harness`      | Formal evaluation framework for Claude Code sessions | Direct analog: `skills/eval-harness/` covers EDD philosophy, PHP graders (PHPUnit, PHPStan, PHP-CS-Fixer), metrics, 4-phase workflow. | ✅      |

## 3. Security & DevOps

Security review, scanning, and infrastructure patterns.

| ECC Skill         | Purpose                                          | PHP Relevance                                                                                                                    | Status |
|-------------------|--------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|--------|
| `security-review` | OWASP Top 10 checklist: SQLi, XSS, CSRF, secrets | Direct analog: `agents/php-security-reviewer.md` + `rules/php/security.md`.                                                      | ✅      |
| `security-scan`   | Audit Claude Code config for vulnerabilities     | Adapted for PHP: `skills/php-security-scanning/` covers Composer audit, Psalm taint, PHPStan security, secrets, debug detection. | ✅      |

## 4. Java / Spring Boot

Closest analog to the PHP/Symfony ecosystem — highest idea-transfer value.

| ECC Skill                 | Purpose                                                  | PHP Relevance                                                                                                                                                 | Status |
|---------------------------|----------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|
| `java-coding-standards`   | Java standards: naming, immutability, Optional, streams  | PHP analog exists: `skills/php-coding-standards/`. Java-specific conventions don't transfer directly.                                                         | ✅      |
| `springboot-patterns`     | Spring Boot architecture: REST, services, caching, async | Partial analog: `skills/symfony-patterns/` covers Symfony equivalents. Laravel patterns skill would complete coverage.                                        | ✅      |
| `springboot-security`     | Spring Security: authn/authz, CSRF, rate limiting        | Direct analog: `skills/php-security-patterns/` covers authentication (Sanctum, Symfony Security, JWT), authorization (Gates, Voters), CORS, security headers. | ✅      |
| `springboot-tdd`          | Spring Boot TDD: JUnit 5, Mockito, Testcontainers        | `skills/php-testing/` covers PHPUnit/Pest TDD. Testcontainers idea worth adopting for PHP.                                                                    | ✅      |
| `springboot-verification` | Build + analysis + tests + security scan pipeline        | Direct analog: `skills/php-verification/` covers the equivalent PHP pipeline.                                                                                 | ✅      |
| `jpa-patterns`            | JPA/Hibernate: entities, relationships, queries, pooling | Direct analog: `skills/doctrine-orm-patterns/` covers entities, relationships, DQL, migrations, performance.                                                  | ✅      |

## 5. Python / Django

Second-closest analog — web framework patterns transferable to Laravel.

| ECC Skill             | Purpose                                                     | PHP Relevance                                                                                                | Status |
|-----------------------|-------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|--------|
| `python-patterns`     | Pythonic idioms, PEP 8, type hints                          | PHP has its own idioms covered by `skills/php-patterns/`. No direct transfer needed.                         | ✅      |
| `python-testing`      | pytest, fixtures, mocking, parametrization                  | PHP analog: `skills/php-testing/` covers PHPUnit/Pest equivalents (data providers ≈ parametrize).            | ✅      |
| `django-patterns`     | Django architecture: DRF, ORM, caching, signals, middleware | Covered by `skills/laravel-patterns/` — Eloquent, middleware, events, queues, caching, validation, services. | ✅      |
| `django-security`     | Django security: auth, CSRF, SQLi, XSS prevention           | Already covered by `agents/php-security-reviewer.md` + `rules/php/security.md`.                              | ✅      |
| `django-tdd`          | Django testing: pytest-django, factory_boy, DRF tests       | Concepts covered by `skills/php-testing/`. Factory pattern → use with `fakerphp/faker`.                      | ✅      |
| `django-verification` | Django verification: migrations, linting, tests, security   | Direct analog: `skills/php-verification/` covers the equivalent PHP pipeline.                                | ✅      |

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

| ECC Skill                | Purpose                                                   | PHP Relevance                                                                                                                       | Status |
|--------------------------|-----------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|--------|
| `search-first`           | Research-before-coding workflow                           | Direct analog: `skills/search-first/` adapted for PHP — Packagist, Spatie, League, Symfony components.                              | ✅      |
| `continuous-learning`    | Extract reusable patterns from sessions as skills         | Direct analog: `skills/continuous-learning/` adapted for PHP — session evaluation, PHP pattern types.                               | ✅      |
| `continuous-learning-v2` | Instinct-based learning with hooks and confidence scoring | Covered by `skills/continuous-learning/` which combines v1 and v2 approaches with PHP-specific instincts.                           | ✅      |
| `iterative-retrieval`    | Progressive context retrieval for subagent problem        | Direct analog: `skills/iterative-retrieval/` covers 4-phase DISPATCH-EVALUATE-REFINE-LOOP, PHP search patterns, agent integration.  | ✅      |
| `strategic-compact`      | Manual `/compact` at logical workflow breakpoints         | Direct analog: `skills/strategic-compact/` covers PHP phase transitions, survival map, pre-compact checklist, context preservation. | ✅      |
| `configure-ecc`          | Interactive ECC installer for skill selection             | Internal to ECC. Not applicable to our toolkit (we have `install.sh`).                                                              | ➖      |
| `skill-stocktake`        | Audit skills for quality using checklist + AI judgment    | Direct analog: `skills/skill-stocktake/` adapted for PHP toolkit — Quick Scan, Full Stocktake, PHP checks.                          | ✅      |

## 8. Domain-Specific

Niche skills for specific use cases.

| ECC Skill                      | Purpose                                              | PHP Relevance                                                                                           | Status |
|--------------------------------|------------------------------------------------------|---------------------------------------------------------------------------------------------------------|--------|
| `nutrient-document-processing` | Document processing, OCR, redaction via Nutrient API | Domain-specific SaaS integration. Not PHP-specific.                                                     | ➖      |
| `visa-doc-translate`           | Visa document translation to bilingual PDF           | Domain-specific workflow. Not PHP-specific.                                                             | ➖      |
| `clickhouse-io`                | ClickHouse analytics database patterns               | Niche but usable from PHP. Low priority — few PHP projects use ClickHouse directly.                     | ➖      |
| `postgres-patterns`            | PostgreSQL query optimization, schema, indexing      | Partially covered by `skills/doctrine-orm-patterns/performance.md` + `agents/php-database-reviewer.md`. | ✅      |
| `cost-aware-llm-pipeline`      | LLM API cost optimization: model routing, budgets    | Language-agnostic AI engineering. Low PHP-specific value but transferable concepts.                     | ➖      |

---

## Coverage Summary

| Category           | Total  | ✅      | 🔜    | 💡    | ➖      |
|--------------------|--------|--------|-------|-------|--------|
| Universal Patterns | 10     | 8      | 0     | 0     | 2      |
| Testing & Quality  | 4      | 4      | 0     | 0     | 0      |
| Security & DevOps  | 2      | 2      | 0     | 0     | 0      |
| Java / Spring Boot | 6      | 6      | 0     | 0     | 0      |
| Python / Django    | 6      | 6      | 0     | 0     | 0      |
| Other Languages    | 10     | 0      | 0     | 0     | 10     |
| Meta-Skills        | 7      | 6      | 0     | 0     | 1      |
| Domain-Specific    | 5      | 1      | 0     | 0     | 4      |
| **Total**          | **50** | **33** | **0** | **0** | **17** |

All transferable ECC skills now have PHP analogs. Remaining ➖ entries are language-specific (Swift, C++, Go) or domain-specific (Nutrient, Visa, ClickHouse) and not applicable to the PHP ecosystem.
