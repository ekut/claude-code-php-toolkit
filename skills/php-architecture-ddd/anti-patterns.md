# DDD Anti-Patterns

## Anti-Patterns

- **Anemic Domain Model** — entities with only getters/setters, all logic pushed to services. If your entities are data bags, you lose the core benefit of DDD
- **Framework coupling in Domain** — domain classes extending framework base classes, using ORM annotations in entity logic, or importing HTTP/Request objects
- **Aggregate references by object** — storing a full `Customer` entity inside `Order` instead of `CustomerId`. This breaks aggregate boundaries and creates loading/consistency issues
- **Domain events with infrastructure concerns** — events that contain database IDs, serialization logic, or queue metadata instead of pure domain concepts
- **Leaky abstractions** — domain layer aware of database columns, HTTP status codes, or queue names
- **God Aggregate** — an aggregate that tries to enforce consistency across too many entities. If it grows beyond ~5-7 child entities, consider splitting bounded contexts

## When to Migrate Away

- DDD ceremony **slows the team** — simple CRUD features require command, handler, event, DTO, repository interface, repository implementation for what could be a single service method
- **Most features are CRUD** with minimal business logic — the domain layer is mostly anemic despite effort to avoid it
- **No domain experts** available — the ubiquitous language is invented by developers, not validated by the business
- Team turnover is high and **new developers struggle** with the architecture for weeks

## Checklist

- [ ] Domain layer has zero framework imports
- [ ] All dependencies point inward (Infrastructure → Application → Domain)
- [ ] Entities encapsulate business rules (not just getters/setters)
- [ ] Value Objects are immutable and self-validating
- [ ] Aggregates enforce consistency boundaries
- [ ] Domain events are dispatched after persistence
- [ ] Repository interfaces defined in Domain, implemented in Infrastructure
- [ ] Bounded contexts communicate via events or shared IDs, not shared entities
- [ ] Commands and queries are separated (CQRS)
- [ ] Ubiquitous language reflected in class/method names
