---
name: php-architecture-ddd
description: Domain-Driven Design architecture for PHP — Rich Domain Model, Hexagonal Architecture, CQRS, Bounded Contexts, Aggregates, Value Objects, Domain Events.
origin: claude-code-php-toolkit
---

# Domain-Driven Design (DDD) Architecture

Domain-Driven Design places the business domain at the center of the software. The code mirrors the language and rules of the business, and the architecture enforces a strict dependency direction: infrastructure depends on the domain, never the reverse.

DDD is not a universal solution — it earns its complexity when the domain itself is complex. Projects with many business invariants, bounded contexts, and long lifespans benefit most. For simpler CRUD apps, the ceremony adds overhead without proportional value.

## When to Use

- The domain has **complex business rules** — invariants, state machines, multi-step processes
- Multiple **bounded contexts** exist (e.g., Orders, Inventory, Billing are separate sub-domains)
- **Domain experts** are available and the team invests in a ubiquitous language
- The team has **DDD experience** or is willing to invest in learning
- The application is **long-lived** (3+ years) and will evolve significantly
- **Multiple interfaces** consume the same domain logic (web, CLI, API, async workers)
- Business rules change more often than infrastructure

## Module Index

| Topic               | File                                             | Use when…                                                                           |
|---------------------|--------------------------------------------------|-------------------------------------------------------------------------------------|
| Core Principles     | [core-principles.md](core-principles.md)         | Explaining dependency direction, ubiquitous language, bounded contexts, aggregates  |
| Directory Structure | [directory-structure.md](directory-structure.md) | Setting up a new project or reviewing layer organisation                            |
| Building Blocks     | [building-blocks.md](building-blocks.md)         | Implementing Value Objects, Entities, Aggregates, Domain Events, CQRS, Repositories |
| Cross-Cutting       | [cross-cutting.md](cross-cutting.md)             | Logging, auth, validation, and error handling in a DDD context                      |
| Anti-Patterns       | [anti-patterns.md](anti-patterns.md)             | Code review, migration decisions, "When to Migrate Away", checklist                 |
