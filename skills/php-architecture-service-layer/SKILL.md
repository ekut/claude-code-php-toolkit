---
name: php-architecture-service-layer
description: Service-Oriented architecture for PHP — Service Layer, Transaction Scripts, DTOs, thin models, repository pattern. The pragmatic middle ground for moderate-complexity applications.
origin: claude-code-php-toolkit
---

# Service-Oriented Architecture

The Service-Oriented approach organizes code around services that own business logic, with thin models serving as data containers and persistence mappers. It is the most common architecture in PHP applications — pragmatic, easy to learn, and well-supported by all major frameworks.

This architecture works well when the domain is moderate in complexity: more than basic CRUD but without the deep invariants and bounded contexts that justify full DDD. It is the natural evolution of framework-centric code when the application grows beyond simple resource management.

## When to Use

- Domain complexity is **moderate** — business rules exist but are not deeply interconnected
- The team prefers **procedural logic** organized in service classes
- The application is **CRUD-heavy** with pockets of complex logic
- **Rapid onboarding** is important — new developers are productive within days
- You are migrating a **brownfield** codebase from fat controllers or framework-centric architecture
- The project has a **moderate lifespan** (1-5 years) and steady feature growth
- Multiple developers work on the project but formal bounded contexts are overkill

## Module Index

| Topic               | File                                             | Use when…                                                                     |
|---------------------|--------------------------------------------------|-------------------------------------------------------------------------------|
| Core Principles     | [core-principles.md](core-principles.md)         | Explaining the service-owns-logic model, DTOs, repositories, transactions     |
| Directory Structure | [directory-structure.md](directory-structure.md) | Setting up a new project or reviewing layer organisation                      |
| Building Blocks     | [building-blocks.md](building-blocks.md)         | Implementing services, transaction scripts, DTOs, thin entities, repositories |
| Cross-Cutting       | [cross-cutting.md](cross-cutting.md)             | Logging, auth, validation, and error handling in a service-oriented context   |
| Anti-Patterns       | [anti-patterns.md](anti-patterns.md)             | Code review, migration decisions, "When to Migrate Away", checklist           |
