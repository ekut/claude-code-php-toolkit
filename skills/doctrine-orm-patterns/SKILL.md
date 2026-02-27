---
name: doctrine-orm-patterns
description: Use this skill when designing Doctrine ORM entities, mapping relationships, writing DQL/QueryBuilder queries, managing migrations, or optimizing database performance. Covers Doctrine ORM 3.x with PHP 8.1+ attributes.
origin: claude-code-php-toolkit
---

# Doctrine ORM Patterns

Patterns for Doctrine ORM 3.x targeting PHP 8.1+ with native attribute mapping. Covers entity design, relationships, queries, migrations, and performance tuning for MySQL and PostgreSQL.

## When to Activate

- Designing or modifying Doctrine ORM entities
- Mapping relationships (OneToMany, ManyToOne, ManyToMany, OneToOne)
- Writing DQL queries or using QueryBuilder
- Managing database migrations (Doctrine Migrations)
- Preventing N+1 queries and optimizing read/write paths
- Configuring second-level cache or batch processing

## Module Index

| Topic         | File                                 | Use when…                                                  |
|---------------|--------------------------------------|------------------------------------------------------------|
| Entity Design | [entities.md](entities.md)           | Mapping entities, columns, types, embeddables, inheritance |
| Relationships | [relationships.md](relationships.md) | OneToMany, ManyToOne, ManyToMany, cascade, orphan removal  |
| Queries       | [queries.md](queries.md)             | DQL, QueryBuilder, native SQL, projections, pagination     |
| Migrations    | [migrations.md](migrations.md)       | Creating, running, rolling back, zero-downtime strategies  |
| Performance   | [performance.md](performance.md)     | N+1 prevention, batch processing, caching, indexing        |

## Project Structure

```
src/
├── Entity/              # Doctrine entities (one class per file)
├── Repository/          # Custom repository classes
├── Migration/           # Generated migration files (via doctrine:migrations)
└── Embeddable/          # Value objects mapped as embeddables

config/
└── packages/
    └── doctrine.yaml    # Connection, mapping, and cache config
```

## Anti-Patterns

- **Public entity properties** — use readonly or getter methods; entities are not DTOs
- **Business logic in entities** — keep entities focused on state and invariants, not orchestration
- **EAGER fetch on collections** — causes N+1; use LAZY + explicit JOIN FETCH in queries
- **Bidirectional relationships everywhere** — only add the inverse side when you actually query from that direction
- **Auto-DDL in production** — never use `auto_generate_proxy_classes: true` or `schema:update --force` in production; use migrations
- **Entity manager in entities** — entities must not depend on the EntityManager; use services
- **Missing indexes on foreign keys** — Doctrine does not auto-create indexes on FK columns for all engines

## Quick Reference

```bash
# Generate migration from entity changes
vendor/bin/doctrine-migrations diff

# Run pending migrations
vendor/bin/doctrine-migrations migrate

# Validate mapping
vendor/bin/doctrine orm:validate-schema

# Generate entity stubs (if using generator)
vendor/bin/doctrine orm:generate-entities src/
```

## Related

- Skill: `symfony-patterns` — Symfony integration (DoctrineBundle, autowiring repositories)
- Skill: `doctrine-odm-patterns` — MongoDB document mapping (complementary skill)
- Skill: `php-static-analysis` — PHPStan + phpstan-doctrine extension for type-safe queries
- Agent: `php-database-reviewer` — schema and query review
