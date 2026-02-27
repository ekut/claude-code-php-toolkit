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

## Core Principles

### One Class = One Action
Each class handles exactly one use case. The class name describes what it does: `CreateOrderAction`, `ListProductsAction`, `CancelSubscriptionAction`. No multi-method controllers.

### `__invoke()` as Entry Point
Every action class is invokable. This integrates cleanly with PHP framework routing (both Laravel and Symfony support invokable controllers) and keeps the API surface minimal.

### Flat or Use-Case Directory Structure
Actions are organized by domain concept, not by technical layer. All code for "create order" lives near each other, making navigation intuitive.

### No God Services
Instead of a massive `OrderService` with 20 methods, each use case is its own class. This eliminates the "where does this method go?" problem and makes code ownership clear.

## Directory Structure

### Standard Layout

```
src/
├── Action/
│   ├── Order/
│   │   ├── CreateOrderAction.php      # POST /orders
│   │   ├── GetOrderAction.php         # GET /orders/{id}
│   │   ├── ListOrdersAction.php       # GET /orders
│   │   ├── CancelOrderAction.php      # POST /orders/{id}/cancel
│   │   └── ExportOrdersCsvAction.php  # GET /orders/export
│   ├── Product/
│   │   ├── CreateProductAction.php
│   │   ├── UpdateProductAction.php
│   │   └── ListProductsAction.php
│   └── Auth/
│       ├── LoginAction.php
│       └── LogoutAction.php
│
├── Command/                            # Write operations
│   ├── CreateOrderCommand.php          # Immutable command DTO
│   ├── CreateOrderHandler.php          # Business logic
│   ├── CancelOrderCommand.php
│   └── CancelOrderHandler.php
│
├── Query/                              # Read operations
│   ├── GetOrderQuery.php
│   ├── GetOrderHandler.php
│   ├── ListOrdersQuery.php
│   └── ListOrdersHandler.php
│
├── DTO/
│   ├── OrderResponseDTO.php
│   └── PaginatedResponseDTO.php
│
├── Entity/
│   ├── Order.php
│   └── Product.php
│
├── Repository/
│   ├── OrderRepository.php
│   └── ProductRepository.php
│
└── Responder/                          # ADR: response formatting (optional)
    ├── JsonResponder.php
    └── CsvResponder.php
```

### ADR Variant

```
src/
├── UI/
│   ├── Order/
│   │   ├── CreateOrder/
│   │   │   ├── CreateOrderAction.php    # Action: HTTP → Domain
│   │   │   ├── CreateOrderResponder.php # Responder: Domain → HTTP
│   │   │   └── CreateOrderInput.php     # Input validation
│   │   └── ListOrders/
│   │       ├── ListOrdersAction.php
│   │       └── ListOrdersResponder.php
│   └── Product/
│       └── ...
├── Domain/
│   ├── Order.php
│   └── OrderRepository.php
└── Infrastructure/
    └── ...
```

## Building Blocks

### Single Action Controller

An invokable controller that handles one HTTP endpoint:

```php
final readonly class CreateOrderAction
{
    public function __construct(
        private CreateOrderHandler $handler,
    ) {}

    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        $command = new CreateOrderCommand(
            customerId: $request->integer('customer_id'),
            lines: array_map(
                fn (array $line) => new OrderLineInput(
                    productId: $line['product_id'],
                    quantity: $line['quantity'],
                ),
                $request->validated('lines'),
            ),
        );

        $orderId = ($this->handler)($command);

        return new JsonResponse(
            ['id' => $orderId, 'status' => 'created'],
            Response::HTTP_CREATED,
        );
    }
}

final readonly class CancelOrderAction
{
    public function __construct(
        private CancelOrderHandler $handler,
    ) {}

    public function __invoke(int $orderId, CancelOrderRequest $request): JsonResponse
    {
        ($this->handler)(new CancelOrderCommand(
            orderId: $orderId,
            reason: $request->validated('reason'),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

final readonly class ListOrdersAction
{
    public function __construct(
        private ListOrdersHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $result = ($this->handler)(new ListOrdersQuery(
            customerId: $request->query->getInt('customer_id'),
            page: $request->query->getInt('page', 1),
            perPage: $request->query->getInt('per_page', 20),
        ));

        return new JsonResponse($result);
    }
}
```

### Command Handler

Handles a write operation. Accepts an immutable command, performs the action, returns minimal result:

```php
final readonly class CreateOrderCommand
{
    public function __construct(
        public int $customerId,
        /** @var list<OrderLineInput> */
        public array $lines,
    ) {}
}

final readonly class OrderLineInput
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}

final readonly class CreateOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
        private EventDispatcherInterface $events,
    ) {}

    public function __invoke(CreateOrderCommand $command): int
    {
        $order = new Order();
        $order->setCustomerId($command->customerId);
        $order->setStatus(OrderStatus::Pending);

        foreach ($command->lines as $line) {
            $product = $this->products->findOrFail($line->productId);

            if ($product->getStock() < $line->quantity) {
                throw new InsufficientStockException($product->getId());
            }

            $order->addLine($product, $line->quantity, $product->getPrice());
            $product->decrementStock($line->quantity);
        }

        $this->orders->save($order);
        $this->events->dispatch(new OrderCreatedEvent($order->getId()));

        return $order->getId();
    }
}
```

### Query Handler

Handles a read operation. Returns DTOs, never modifies state:

```php
final readonly class GetOrderQuery
{
    public function __construct(
        public int $orderId,
    ) {}
}

final readonly class GetOrderHandler
{
    public function __construct(
        private OrderReadRepository $repository,
    ) {}

    public function __invoke(GetOrderQuery $query): OrderResponseDTO
    {
        return $this->repository->findSummary($query->orderId)
            ?? throw new OrderNotFoundException($query->orderId);
    }
}

final readonly class ListOrdersQuery
{
    public function __construct(
        public int $customerId,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}

final readonly class ListOrdersHandler
{
    public function __construct(
        private OrderReadRepository $repository,
    ) {}

    public function __invoke(ListOrdersQuery $query): PaginatedResponseDTO
    {
        return $this->repository->findByCustomerPaginated(
            $query->customerId,
            $query->page,
            $query->perPage,
        );
    }
}
```

### ADR Responder

Separates domain result from HTTP response formatting:

```php
final readonly class JsonResponder
{
    public function success(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['data' => $data], $status);
    }

    public function created(mixed $data): JsonResponse
    {
        return $this->success($data, Response::HTTP_CREATED);
    }

    public function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    public function paginated(PaginatedResponseDTO $result): JsonResponse
    {
        return new JsonResponse([
            'data' => $result->items,
            'meta' => [
                'total' => $result->total,
                'page' => $result->page,
                'per_page' => $result->perPage,
                'has_next' => $result->hasNextPage(),
            ],
        ]);
    }
}

final readonly class CsvResponder
{
    public function respond(string $filename, iterable $rows, array $headers): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($rows, $headers): void {
                $output = fopen('php://output', 'wb');
                fputcsv($output, $headers);

                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }
}

// Usage in ADR-style action:
final readonly class CreateOrderAction
{
    public function __construct(
        private CreateOrderHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        $command = CreateOrderCommand::fromRequest($request);
        $orderId = ($this->handler)($command);

        return $this->responder->created(['id' => $orderId]);
    }
}
```

### Route Registration

Invokable controllers integrate with standard framework routing:

```php
// Laravel (routes/api.php)
Route::prefix('orders')->group(function () {
    Route::get('/', ListOrdersAction::class);
    Route::post('/', CreateOrderAction::class);
    Route::get('/{order}', GetOrderAction::class);
    Route::post('/{order}/cancel', CancelOrderAction::class);
    Route::get('/export', ExportOrdersCsvAction::class);
});

// Symfony (config/routes.php or attributes)
#[Route('/api/orders', name: 'order_create', methods: ['POST'])]
final readonly class CreateOrderAction { /* ... */ }

#[Route('/api/orders/{id}', name: 'order_get', methods: ['GET'])]
final readonly class GetOrderAction { /* ... */ }

// Symfony routing config alternative
return static function (RoutingConfigurator $routes): void {
    $routes->add('order_create', '/api/orders')
        ->controller(CreateOrderAction::class)
        ->methods(['POST']);
    $routes->add('order_get', '/api/orders/{id}')
        ->controller(GetOrderAction::class)
        ->methods(['GET']);
};
```

## Anti-Patterns

- **Multi-responsibility actions** — an action that handles both creation and update, or reads and writes in one class. Each action does one thing
- **Shared mutable state** — actions storing data in instance properties between requests. Actions are stateless; use `readonly` classes to enforce this
- **Bypassing DI** — creating dependencies inside action methods with `new` instead of injecting them via constructor. This breaks testability
- **Over-granular one-line actions** — creating an action class for trivial operations that have no logic (e.g., a health check endpoint with just `return new JsonResponse(['ok' => true])`). Some pragmatism is needed; group truly trivial endpoints
- **Command/Query mixing** — a command handler that returns complex data, or a query handler that has side effects. Keep the separation clean
- **Fat input validation** — putting business rules in form request classes. Form requests validate format (required, string, max:255); business rules belong in handlers

## Cross-Cutting Concerns

- **Logging** — handlers log significant business events. Actions log HTTP-level concerns (if needed). Use middleware for request/response logging
- **Authentication/Authorization** — middleware handles auth before the action is called. Actions or handlers may check specific permissions via a policy/voter
- **Validation** — input validation in form requests or dedicated input classes. Business validation in handlers. This two-layer approach keeps actions thin
- **Error handling** — handlers throw domain exceptions. Actions or global exception handlers translate to HTTP responses. Responders (in ADR) can handle error formatting

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
