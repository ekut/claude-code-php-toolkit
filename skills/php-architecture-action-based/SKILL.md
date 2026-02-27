---
name: php-architecture-action-based
description: Action-Based / ADR architecture for PHP — single-action controllers, invokable handlers, Command/Query separation, Action-Domain-Responder pattern.
origin: claude-code-php-toolkit
---

# Action-Based / ADR Architecture

Action-Based architecture organizes code around single-responsibility actions: each class handles exactly one use case. Instead of a controller with 7 methods, you have 7 small classes with one `__invoke()` method each. This maps naturally to how HTTP works — one URL, one handler.

The pattern is also known as ADR (Action-Domain-Responder), an alternative to MVC proposed by Paul M. Jones. ADR separates the HTTP-specific action from the domain logic and the response formatting, giving each responsibility a dedicated class.

## When to Use

- The application is **request-response oriented** — web APIs, HTTP services, queue workers
- Each endpoint does **one distinct thing** — no complex multi-step wizards sharing state
- The team prefers **small, focused classes** over large controllers with many methods
- You want **CQRS-lite** without the full DDD ceremony — separate read and write paths
- The project is an **API-first** application or a set of **microservices**
- You value high testability through **small, isolated units**
- The team finds large controller classes hard to navigate and review

## Module Index

| Topic               | File                                             | Use when…                                                                        |
|---------------------|--------------------------------------------------|----------------------------------------------------------------------------------|
| Core Principles     | [core-principles.md](core-principles.md)         | Explaining one-class-one-action, `__invoke()`, no god services                   |
| Directory Structure | [directory-structure.md](directory-structure.md) | Setting up a new project; choosing standard layout vs ADR variant                |
| Building Blocks     | [building-blocks.md](building-blocks.md)         | Implementing actions, command/query handlers, ADR responders, route registration |
| Cross-Cutting       | [cross-cutting.md](cross-cutting.md)             | Logging, auth, validation, and error handling in an action-based context         |
| Anti-Patterns       | [anti-patterns.md](anti-patterns.md)             | Code review, migration decisions, "When to Migrate Away", checklist              |
