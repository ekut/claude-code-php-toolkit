---
name: laravel-patterns
description: Laravel 10+/11+ architecture patterns — routing, Eloquent ORM, middleware, events, queues, validation, service layer, caching, and testing for production PHP applications.
origin: claude-code-php-toolkit
---

# Laravel Development Patterns

Laravel is a full-featured PHP framework with an expressive, elegant syntax. These patterns target Laravel 10.x LTS and 11.x.

## When to Activate

- Building or maintaining a Laravel application (10.x, 11.x)
- Writing controllers, routes, or middleware
- Working with Eloquent models, relationships, or queries
- Dispatching events, jobs, or configuring queues
- Writing form requests, custom validation rules
- Structuring services, repositories, or action classes
- Configuring cache drivers or invalidation strategies
- Writing feature or unit tests with Laravel's test helpers

## Module Index

| Topic              | File                                   | Use when...                                                       |
|--------------------|----------------------------------------|-------------------------------------------------------------------|
| Routing            | [routing.md](routing.md)               | Routes, model binding, resource controllers, groups, rate limiting |
| Eloquent ORM       | [eloquent.md](eloquent.md)             | Relationships, scopes, accessors, eager loading, soft deletes     |
| Middleware          | [middleware.md](middleware.md)          | Custom middleware, groups, terminable middleware                   |
| Events & Queues    | [events-queues.md](events-queues.md)   | Events/listeners, jobs, ShouldQueue, failed jobs, scheduling      |
| Validation          | [validation.md](validation.md)         | FormRequest, custom rules, conditional, array validation          |
| Services           | [services.md](services.md)             | Service layer, DI, repository pattern, transactions, actions      |
| Caching            | [caching.md](caching.md)              | Cache facade, tagged cache, drivers, invalidation                 |
| Testing            | [testing.md](testing.md)              | Feature/unit tests, factories, HTTP testing, fakes                |

## Project Structure

```
my-app/
├── app/
│   ├── Actions/              # Single-purpose action classes
│   ├── Console/              # Artisan commands, Kernel scheduling
│   ├── Events/               # Event classes
│   ├── Exceptions/           # Custom exceptions, Handler
│   ├── Http/
│   │   ├── Controllers/      # HTTP layer only — thin, no business logic
│   │   ├── Middleware/        # Request/response filters
│   │   └── Requests/         # FormRequest validation classes
│   ├── Jobs/                 # Queued job classes
│   ├── Listeners/            # Event listener classes
│   ├── Models/               # Eloquent models
│   ├── Providers/            # Service providers
│   └── Services/             # Application services / domain logic
├── config/                   # Configuration files (one per concern)
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── routes/
│   ├── api.php               # API routes
│   └── web.php               # Web routes
├── tests/
│   ├── Feature/              # HTTP, integration, and end-to-end tests
│   └── Unit/                 # Isolated unit tests
└── .env                      # Environment variables (never commit)
```

> **Keep controllers thin.** Business logic belongs in services or action classes, not in controller methods.

## Anti-Patterns

- **Fat controllers** — business logic in controller methods; extract to services or actions
- **N+1 queries** — always use `with()` for eager loading in collection endpoints
- **Raw queries in controllers** — use Eloquent or query builder with parameterized bindings
- **Overusing facades in domain logic** — inject dependencies via constructor for testability
- **Ignoring queues for slow operations** — email, reports, third-party API calls inline hurt UX
- **Secrets in `.env.example`** — keep real values in `.env` (gitignored), placeholders in `.env.example`
- **Massive service providers** — split registrations by domain; use deferred providers for rarely-used services
- **Skipping FormRequest** — raw `$request->input()` without validation risks mass assignment

## Eloquent Integration

> See dedicated skills: relational databases -> `doctrine-orm-patterns`, MongoDB -> `doctrine-odm-patterns`.

Laravel ships with Eloquent ORM. For projects using Doctrine instead, the dedicated Doctrine skills cover that workflow. This skill focuses on Eloquent-native patterns.
