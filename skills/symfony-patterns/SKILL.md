---
name: symfony-patterns
description: Symfony 6+/7+ architecture patterns — service container, autowiring, controllers, events, Messenger, forms, configuration, caching, and anti-patterns for production PHP applications.
origin: claude-code-php-toolkit
---

# Symfony Development Patterns

Symfony is an opinionated PHP framework built on reusable components. These patterns target Symfony 6.4 LTS and 7.x.

## When to Activate

- Building or maintaining a Symfony application (6.4+, 7.x)
- Configuring services, autowiring, or the DI container
- Writing controllers, form types, or event subscribers
- Using Symfony Messenger for async processing
- Structuring routes, configuration, or environment variables

## Module Index

| Topic                  | File                                 | Use when…                                                      |
|------------------------|--------------------------------------|----------------------------------------------------------------|
| Service Container & DI | [di-container.md](di-container.md)   | Autowiring, interface binding, `#[Autowire]`, tagged services  |
| Controllers            | [controllers.md](controllers.md)     | HTTP actions, `#[MapEntity]`, `#[MapRequestPayload]`, JSON API |
| Events & Messenger     | [messenger.md](messenger.md)         | EventSubscriber, async messages, transport config              |
| Forms                  | [forms.md](forms.md)                 | FormType, DTO data classes, validation constraints             |
| Configuration          | [configuration.md](configuration.md) | Env vars, parameters, secrets vault, package config            |
| Performance            | [performance.md](performance.md)     | Symfony Cache, HTTP caching, OPcache preloading                |
| Security               | [security.md](security.md)           | Firewalls, `#[IsGranted]`, Voters                              |
| Testing                | [testing.md](testing.md)             | WebTestCase, KernelTestCase                                    |

## Project Structure

```
my-app/
├── config/
│   ├── packages/               # Bundle/component config (one file per package)
│   ├── services.yaml           # DI container — _defaults, interface bindings, parameters
│   └── bundles.php
├── src/
│   ├── Controller/             # HTTP layer only — thin, no business logic
│   ├── Entity/                 # Doctrine ORM entities
│   ├── Form/                   # FormType classes
│   ├── Message/                # Messenger message DTOs
│   ├── MessageHandler/         # Messenger handlers
│   ├── Repository/             # Doctrine repositories
│   ├── Service/                # Application services / domain logic
│   └── EventSubscriber/        # Symfony event subscribers
└── .env                        # Base env vars (committed, no secrets)
```

> **Never create bundles to organize application code.** Use PHP namespaces (`App\Payment\`, `App\Reporting\`) to group related classes.

## Anti-Patterns

- **Fat controllers** — business logic in controller methods; services exist for a reason
- **Direct repository use in controllers** — inject a typed service instead
- **Service locator abuse** — injecting `ContainerInterface`; use constructor injection or `#[Autowire]`
- **Manual YAML wiring when autowiring is enabled** — re-declaring auto-discovered classes adds noise
- **Ignoring Messenger for long-running work** — email, reports, third-party API calls inline hurt reliability
- **Secrets in `.env`** — use `.env.local` for dev, `secrets:set` vault for production
- **N+1 queries in loops** — use Doctrine `JOIN FETCH` or eager-loading repository methods
- **Bypassing the form layer** — raw `$request->request->get(...)` without validation risks mass assignment

## Doctrine Integration

> See dedicated skills: relational databases → `doctrine-orm`, MongoDB → `doctrine-odm`.

Bundle registers `EntityManagerInterface`, `ManagerRegistry`, and all repositories as autowirable services automatically. Config lives in `config/packages/doctrine.yaml`.
