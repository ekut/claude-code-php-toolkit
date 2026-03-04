---
description: Generate token-lean architecture documentation — namespace hierarchy, entry points, and dependency maps
---

# PHP Update Codemaps

Generate or refresh a token-lean codemap of the PHP project's architecture. Codemaps help Claude Code understand the codebase structure without reading every file.

> **Foundation:** Delegates to `agents/php-doc-updater.md` for codemap generation.

## Step 1: Scan Project Structure

- Read `composer.json` for autoload namespaces
- Map the full `src/` directory tree
- Identify the framework (Symfony, Laravel, or framework-agnostic)
- Locate config files, routes, and service definitions

## Step 2: Build Namespace Hierarchy

```
## Codemap: [Project Name]

### Domain Layer (`src/Domain/`)
- `Order/` — Order aggregate (Order, OrderLine, OrderStatus)
- `Product/` — Product catalog (Product, Category, Price)

### Application Layer (`src/Application/`)
- `Command/` — Write operations (PlaceOrderCommand, PlaceOrderHandler)
- `Query/` — Read operations (GetOrderQuery, GetOrderHandler)

### Infrastructure Layer (`src/Infrastructure/`)
- `Persistence/` — Doctrine repositories, migrations
- `Http/` — Controllers, API clients
- `Messaging/` — Queue producers/consumers
```

## Step 3: Identify Key Entry Points

List main controllers, console commands, queue consumers, and event listeners — the "doors" into the application.

## Step 4: Map External Integrations

List API clients, queue connections, cache adapters, and third-party SDK usage.

## Step 5: Output

Write the codemap to `CODEMAP.md` in the project root (or update if it exists). Keep it concise — the goal is a token-lean overview, not exhaustive documentation.

Format: one line per namespace/directory with a dash-separated summary of key classes. Aim for the entire codemap to fit within 100–200 lines.
