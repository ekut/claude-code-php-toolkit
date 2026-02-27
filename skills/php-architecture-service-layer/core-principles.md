# Service-Oriented Core Principles

## Services Own Business Logic

All business rules live in service classes. Models/entities are data containers that map to the database — they hold state but not behavior. This is a conscious architectural choice, not a deficiency.

## DTOs Between Layers

Data Transfer Objects carry data between the controller layer and the service layer. Services never accept framework Request objects or return framework Response objects.

## Repository for Data Access

Repositories encapsulate queries and persistence. Services call repository methods rather than using the ORM directly. This keeps query logic organized and testable.

## Constructor Injection Everywhere

Services declare their dependencies via constructor parameters. No service locator, no static calls, no `new` inside service methods for collaborating objects.

## Transaction Boundaries in Services

Services own transaction boundaries. A single service method typically represents one unit of work. If it fails partway through, the transaction rolls back.
