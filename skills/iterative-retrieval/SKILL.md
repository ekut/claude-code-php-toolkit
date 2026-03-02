---
name: iterative-retrieval
description: Progressive context retrieval for PHP codebases — 4-phase DISPATCH-EVALUATE-REFINE-LOOP cycle with PHP search patterns and agent integration.
origin: claude-code-php-toolkit
---

# Iterative Retrieval

A structured approach to finding the right code context in PHP projects. Instead of a single search-and-hope, use a progressive loop: search broadly, evaluate results, refine the query, and repeat until you have enough context to act.

## When to Activate

- Investigating a bug in an unfamiliar PHP codebase
- Finding all code affected by a refactoring
- Understanding how a feature is implemented across multiple files
- Subagent tasks that require deep codebase exploration
- Any search where the first query doesn't give you enough context

---

## 1. The 4-Phase Loop

```
┌─────────────────────────────────────────────┐
│                                             │
│   DISPATCH ──→ EVALUATE ──→ REFINE ──→ LOOP │
│       ↑                              │      │
│       └──────────────────────────────┘      │
│                                             │
│   Max 3 cycles, then act on best available  │
└─────────────────────────────────────────────┘
```

### Phase 1: DISPATCH — Cast a Broad Net

Start with the most obvious search. Don't overthink — get initial results fast.

**PHP search strategies (try in order):**

1. **Class/interface name** — `Glob` for `**/{ClassName}.php`
2. **Method name** — `Grep` for `function methodName(`
3. **Usage** — `Grep` for `->methodName(` or `ClassName::`
4. **Namespace** — `Grep` for `namespace App\Domain\Order`
5. **Config/routing** — `Grep` in `config/`, `routes/`, `config/routes/`

```
Search: Glob("**/OrderService.php")
Result: src/Service/OrderService.php, tests/Service/OrderServiceTest.php
```

### Phase 2: EVALUATE — Is This Enough?

After each search, ask:

| Question | If No → Action |
|----------|---------------|
| Did I find the right file? | Broaden search: try interface name, or `Grep` for a known string in the code |
| Do I understand the method signature? | Read the file, check parameter types and return type |
| Do I know what calls this code? | Search for usages: `->methodName(` or `ClassName::` |
| Do I know what this code calls? | Read imports, check injected dependencies |
| Do I have enough context to make the change? | If yes → stop. If no → REFINE and loop. |

**Sufficiency criteria for common PHP tasks:**

| Task | Minimum Context Needed |
|------|----------------------|
| Fix a bug | The buggy method + its callers + its test |
| Add a feature | The service + its interface + related controller + existing tests |
| Refactor | All usages of the target + dependent classes + tests |
| Review | The PR diff + surrounding context of changed methods |

### Phase 3: REFINE — Adjust the Query

Based on EVALUATE findings, make a more targeted search.

**Refinement strategies:**

- **Too many results** → Add namespace filter: `Grep` with `glob: "src/Domain/Order/**/*.php"`
- **Too few results** → Broaden: search for parent class, interface, or trait
- **Wrong results** → Change search type: class name → method name, or string literal → type hint
- **Missing context** → Follow the dependency chain: read constructor, check DI container config

### Phase 4: LOOP (Max 3 Cycles)

Repeat DISPATCH → EVALUATE → REFINE up to 3 times. If you haven't found enough context after 3 cycles:

- **Act on best available** — make the change with what you have
- **State assumptions explicitly** — "I'm assuming OrderService is the only caller of this method"
- **Flag uncertainty** — "I couldn't find all usages; there may be dynamic calls via `$container->get()`"

---

## 2. PHP-Specific Search Patterns

### PSR-4 Namespace Discovery

Read `composer.json` to understand the namespace-to-directory mapping:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/",
      "App\\Tests\\": "tests/"
    }
  }
}
```

This tells you:
- `App\Service\OrderService` → `src/Service/OrderService.php`
- `App\Tests\Service\OrderServiceTest` → `tests/Service/OrderServiceTest.php`

### Common Search Patterns

| Looking For | Search Pattern | Tool |
|-------------|---------------|------|
| Class definition | `**/ClassName.php` | Glob |
| Interface implementations | `implements InterfaceName` | Grep |
| Trait usages | `use TraitName;` or `use TraitName {` | Grep |
| Service injection | `ClassName $varName` in constructor | Grep |
| Route handler | `ClassName::class` in `routes/` or `config/routes/` | Grep |
| Event listener | `#[AsEventListener` or `EventSubscriberInterface` | Grep |
| Doctrine entity | `#[ORM\Entity` or `#[ORM\Table` | Grep |
| Laravel model | `extends Model` in `app/Models/` | Grep with glob filter |
| Config value usage | `config('key.name')` or `%env(KEY)%` | Grep |
| Migration for table | `'table_name'` in `migrations/` or `database/migrations/` | Grep |

---

## 3. PHP Examples

### Example 1: Doctrine Entity Bug

**Task:** "OrderTotal is sometimes null when it shouldn't be"

**Cycle 1 — DISPATCH:**
```
Glob("**/Order.php") → src/Entity/Order.php
Read Order.php → see $total is nullable, has calculateTotal() method
```

**EVALUATE:** Found the entity but don't know who calls `calculateTotal()`.

**Cycle 2 — REFINE:**
```
Grep("->calculateTotal(") → src/Service/OrderService.php:45, src/EventListener/OrderListener.php:23
Read both files → OrderService calculates on creation, OrderListener recalculates on item change
```

**EVALUATE:** Found the callers. The listener might not fire in certain code paths.

**Cycle 3 — REFINE:**
```
Grep("#[AsEventListener" in src/EventListener/) → OrderListener listens to OrderItemAdded
Grep("OrderItemAdded" in src/) → dispatched in OrderService::addItem() but NOT in OrderService::importFromCsv()
```

**EVALUATE:** Root cause found. `importFromCsv()` adds items without dispatching the event. Context sufficient — fix or flag.

### Example 2: API Rate Limiting

**Task:** "Add rate limiting to the /api/orders endpoint"

**Cycle 1 — DISPATCH:**
```
Grep("orders" in routes/) → routes/api.php:15 → OrderController
Read OrderController.php → standard CRUD, no middleware beyond auth
```

**EVALUATE:** Found the route. Need to know how other endpoints handle rate limiting.

**Cycle 2 — REFINE:**
```
Grep("throttle\|RateLimiter\|rate_limit" in routes/ and app/) →
  routes/api.php:3 has ->middleware('throttle:api') on the group
  app/Providers/RouteServiceProvider.php configures 'api' limiter at 60/min
```

**EVALUATE:** Rate limiting already exists at group level. Need per-endpoint override. Context sufficient.

### Example 3: CQRS Refactoring

**Task:** "Separate read and write operations for Products"

**Cycle 1 — DISPATCH:**
```
Glob("**/Product*.php") → 8 files: Entity, Repository, Service, Controller, DTO, Resource, Test, Factory
Read ProductService.php → mixed read/write: findAll, findById, create, update, delete
```

**EVALUATE:** Found the service but need to understand all callers before splitting.

**Cycle 2 — REFINE:**
```
Grep("ProductService" in src/) → Controller, 2 Commands, 1 EventListener, AdminController
Read each → Controller uses reads+writes, Commands use writes, AdminController uses reads
```

**EVALUATE:** Clear read/write split possible. Controller needs both. Context sufficient for refactoring plan.

---

## 4. Agent Integration

### With `php-architect` Agent

The `php-architect` agent benefits from iterative retrieval when evaluating existing architecture:

1. **Before calling php-architect:** Use iterative retrieval to gather the current structure
2. **Pass context:** Include discovered files, dependency chains, and patterns in the prompt
3. **After recommendation:** Use iterative retrieval again to find all files affected by the proposed change

### With `php-reviewer` Agent

The `php-reviewer` agent can use iterative retrieval to expand review scope:

1. Start with the PR diff files
2. **Cycle 1:** Find all callers of changed methods
3. **Cycle 2:** Check if tests exist for changed behavior
4. **Cycle 3:** Look for similar patterns elsewhere that should be updated consistently

### General Subagent Pattern

When delegating to any subagent that explores code:

```
Prompt the subagent with:
1. Start file: {known entry point}
2. Goal: {what context you need}
3. Max cycles: 3
4. Report back: list of files read, key findings, remaining uncertainties
```

This prevents subagents from doing unbounded exploration while ensuring they find enough context.
