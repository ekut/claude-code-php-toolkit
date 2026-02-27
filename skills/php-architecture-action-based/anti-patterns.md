# Action-Based Anti-Patterns

## Anti-Patterns

- **Multi-responsibility actions** — an action that handles both creation and update, or reads and writes in one class. Each action does one thing
- **Shared mutable state** — actions storing data in instance properties between requests. Actions are stateless; use `readonly` classes to enforce this
- **Bypassing DI** — creating dependencies inside action methods with `new` instead of injecting them via constructor. This breaks testability
- **Over-granular one-line actions** — creating an action class for trivial operations that have no logic (e.g., a health check endpoint with just `return new JsonResponse(['ok' => true])`). Some pragmatism is needed; group truly trivial endpoints
- **Command/Query mixing** — a command handler that returns complex data, or a query handler that has side effects. Keep the separation clean
- **Fat input validation** — putting business rules in form request classes. Form requests validate format (required, string, max:255); business rules belong in handlers

## When to Migrate Away

- **Complex invariants span multiple actions** — the same business rules are duplicated across create, update, and cancel actions, suggesting the domain needs richer objects
- **Too many small files** become hard to navigate — if you have 200+ action classes and no clear grouping strategy, consider consolidating related actions
- **Orchestration needs emerge** — multi-step processes that span several actions need a higher-level coordinator (saga, workflow), at which point DDD or service-oriented patterns may fit better
- **Domain model complexity grows** — when entities need to enforce their own invariants and you find handlers doing what should be entity methods

## Checklist

- [ ] Each action class has exactly one `__invoke()` method
- [ ] Action classes are `readonly` (stateless)
- [ ] Commands and queries are separate (no read-write mixing)
- [ ] Command handlers return minimal data (ID or void)
- [ ] Query handlers have no side effects
- [ ] Route registration uses invokable controller syntax
- [ ] Dependencies injected via constructor, not created inline
- [ ] Input validation separated from business validation
- [ ] Actions organized by domain concept (Order/, Product/), not by HTTP method
- [ ] ADR responders used when response formatting is complex or reusable
