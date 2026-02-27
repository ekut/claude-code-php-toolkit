---
name: doctrine-odm-patterns
description: Use this skill when designing MongoDB documents with Doctrine ODM, mapping references and embedded documents, writing queries with the Query Builder, building aggregation pipelines, or managing indexes and lifecycle callbacks.
origin: claude-code-php-toolkit
---

# Doctrine MongoDB ODM Patterns

Patterns for Doctrine MongoDB ODM 2.x targeting PHP 8.1+ with native attribute mapping. Covers document design, references, embedded documents, queries, aggregation, and performance tuning.

## When to Activate

- Designing or modifying MongoDB document classes
- Mapping references (ReferenceOne, ReferenceMany) or embedded documents
- Writing queries with the Doctrine ODM Query Builder
- Building MongoDB aggregation pipelines
- Managing indexes and lifecycle callbacks
- Choosing between embedding and referencing

## Module Index

| Topic              | File                                       | Use when…                                                    |
|--------------------|--------------------------------------------|--------------------------------------------------------------|
| Document Design    | [documents.md](documents.md)               | Mapping documents, fields, types, enums, embedded documents  |
| References         | [references.md](references.md)             | ReferenceOne, ReferenceMany, cascade, inverse side           |
| Queries            | [queries.md](queries.md)                   | Query Builder, aggregation pipelines, repository methods     |
| Performance        | [performance.md](performance.md)           | Indexes, read preferences, batch operations, schema design   |

## Key Design Decision: Embed vs Reference

| Factor                                   | Embed                | Reference                            |
|------------------------------------------|----------------------|--------------------------------------|
| Data accessed together                   | Yes — embed          | No — reference                       |
| Child has independent lifecycle          | No                   | Yes                                  |
| Child shared across parents              | No                   | Yes                                  |
| Array unbounded growth risk              | Avoid embedding      | Reference instead                    |
| Atomicity (single-document transactions) | Guaranteed           | Requires multi-document transactions |
| Read performance                         | Faster (single read) | Slower (extra query)                 |

**Default rule:** Embed when the child is part of the parent's aggregate. Reference when the child is an independent entity.

## Project Structure

```
src/
├── Document/            # ODM document classes (one per file)
├── Repository/          # Custom repository classes
└── Embeddable/          # Embedded document classes (value objects)

config/
└── packages/
    └── doctrine_mongodb.yaml   # Connection, mapping, and index config
```

## Anti-Patterns

- **Unbounded arrays** — embedding thousands of items in a single document hits the 16 MB BSON limit; use references instead
- **Deep nesting** — more than 2–3 levels of embedded documents makes queries and updates complex
- **Missing indexes** — MongoDB does collection scans without indexes; always index query fields
- **Using `$lookup` for everything** — if you always need the related data, embed it instead of referencing
- **Ignoring schema validation** — MongoDB is flexible, but documents should follow a consistent schema; use ODM mapping as your schema

## Quick Reference

```bash
# Generate indexes defined in document mappings
vendor/bin/doctrine-mongodb odm:schema:create --index

# Validate mapping
vendor/bin/doctrine-mongodb odm:schema:validate

# Update indexes (drop + recreate)
vendor/bin/doctrine-mongodb odm:schema:update --index
```

## Related

- Skill: `doctrine-orm-patterns` — relational database patterns (complementary skill)
- Skill: `symfony-patterns` — Symfony integration (DoctrineMongoDBBundle)
- Agent: `php-database-reviewer` — schema and query review
