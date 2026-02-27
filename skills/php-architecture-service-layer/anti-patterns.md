# Service-Oriented Anti-Patterns

## Anti-Patterns

- **God Service** — a service class that grows beyond 500 lines and handles unrelated operations. Split by sub-domain: `OrderService`, `OrderCancellationService`, `OrderReportingService`
- **Fat controllers** — business logic in controllers instead of services. Controllers should only: validate input, call a service, return a response
- **Deep service chains** — Service A calls Service B which calls Service C which calls Service D. Keep call depth shallow (max 2-3 levels). If deeper, reconsider boundaries
- **Passing Request into services** — services accept framework `Request` objects. Use DTOs so services remain framework-agnostic
- **Direct ORM usage in controllers** — bypassing the repository to query the database from controllers. Queries belong in repositories
- **Shared mutable state** — services storing state in instance properties between calls. Services should be stateless; all state flows through method parameters

## When to Migrate Away

- Business rules become **too complex** for flat service methods — multiple services enforce the same invariant, leading to duplication
- Services grow beyond **500 lines** routinely despite splitting — the domain needs richer objects to manage complexity
- You find yourself writing **defensive checks** in multiple services for the same entity state transitions — this is a sign the entity should own its rules (→ DDD)
- **Multiple bounded contexts** emerge and you need explicit boundaries — service-oriented architecture has no mechanism for this

## Checklist

- [ ] All business logic lives in service classes, not controllers
- [ ] Services use constructor injection (no `new` for collaborators, no service locator)
- [ ] DTOs used between controller and service layers
- [ ] Repositories encapsulate all data access
- [ ] Services are stateless (no instance property state between calls)
- [ ] Transaction boundaries managed in services
- [ ] Controllers only validate, delegate, and respond
- [ ] No framework Request/Response objects in service signatures
- [ ] Services under 500 lines; split by responsibility if larger
- [ ] Domain-specific exceptions thrown, not generic ones
