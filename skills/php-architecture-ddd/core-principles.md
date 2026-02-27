# DDD Core Principles

## Dependency Direction Inward

Dependencies always point inward: Infrastructure → Application → Domain. The Domain layer has zero external dependencies — no framework imports, no ORM annotations, no HTTP concerns.

## Ubiquitous Language

Code uses the same terms as domain experts. If the business says "Place an Order," the code has `Order::place()`, not `OrderService::process()`. Class names, method names, and event names all reflect business vocabulary.

## Bounded Contexts

Each sub-domain has its own models and vocabulary. A `Product` in the Catalog context is different from a `Product` in Inventory. Contexts communicate through explicit contracts (events, shared IDs), never by sharing entities.

## Aggregate Consistency

An aggregate is a cluster of objects treated as a unit for data changes. Only the aggregate root is directly accessible. All mutations go through the root, which enforces business invariants before accepting the change.
